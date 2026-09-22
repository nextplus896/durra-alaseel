// dropzone-init.js — single global Dropzone initializer replacing FileHolder
// ponytail: one shared config + hidden CSV sync, keeps FileController contract (fileholder_files param)
(function () {
  if (typeof Dropzone !== 'undefined') Dropzone.autoDiscover = false;

  function csrf() { return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''; }
  function uploadUrl() { return window.dropzoneUploadUrl || document.body.getAttribute('data-dropzone-upload-url') || '/fileholder-upload'; }
  function removeUrl() { return window.dropzoneRemoveUrl || document.body.getAttribute('data-dropzone-remove-url') || '/fileholder-remove'; }

  function getHiddenInput(dropzoneEl) {
    var wrapper = dropzoneEl.closest('.dropzone-wrapper');
    if (wrapper) return wrapper.querySelector('.dropzone-hidden-input');
    return dropzoneEl.parentElement?.querySelector('.dropzone-hidden-input');
  }
  function getSyncFileInput(dropzoneEl) {
    var wrapper = dropzoneEl.closest('.dropzone-wrapper');
    if (wrapper) return wrapper.querySelector('.dropzone-sync-file');
    return null;
  }
  function syncFileInput(dropzoneEl, files) {
    var fi = getSyncFileInput(dropzoneEl);
    if (!fi) return;
    try {
      var dt = new DataTransfer();
      files.forEach(function(f){ if (!f.isMock) dt.items.add(f); });
      fi.files = dt.files;
    } catch(e) {}
  }
  function setHiddenValue(input, values) { if (input) input.value = values.join(','); }
  function getHiddenValues(input) { if (!input || !input.value) return []; return input.value.split(',').filter(Boolean); }

  function addFileName(input, fileName) {
    var vals = getHiddenValues(input);
    if (vals.indexOf(fileName) === -1) vals.push(fileName);
    setHiddenValue(input, vals);
  }
  function removeFileName(input, fileName) {
    var vals = getHiddenValues(input).filter(function(v){ return v !== fileName; });
    setHiddenValue(input, vals);
  }

  window.initDropzone = function (el, opts) {
    if (!el || el.dropzone) return el?.dropzone || null;
    opts = opts || {};
    var wrapper = el.closest ? el.closest('.dropzone-wrapper') : null;
    var isDirectMode = opts.isDirect || (wrapper && wrapper.getAttribute('data-direct') === 'true') || el.getAttribute('data-direct') === 'true';
    var hiddenInput = opts.hiddenInput || getHiddenInput(el);
    var directFileInput = wrapper ? wrapper.querySelector('input[type="file"][data-direct-input]') || wrapper.querySelector('input[type="file"]:not(.dropzone-hidden-input)') : null;
    var isMultiple = opts.isMultiple || (hiddenInput && hiddenInput.getAttribute('data-multiple') === 'true') || el.getAttribute('data-multiple') === 'true' || (directFileInput && directFileInput.hasAttribute('multiple'));
    var maxFiles = opts.maxFiles != null ? opts.maxFiles : (isMultiple ? (parseInt(el.getAttribute('data-file_limit')|| hiddenInput?.getAttribute('data-file_limit')|| wrapper?.getAttribute('data-file_limit')|| '15',10)) : 1);
    var maxFilesize = opts.maxFilesize != null ? opts.maxFilesize : parseInt(el.getAttribute('data-max_size')|| hiddenInput?.getAttribute('data-max_size') || wrapper?.getAttribute('data-max_size') || '20',10);
    var acceptedFiles = opts.acceptedFiles || el.getAttribute('data-accepted') || el.getAttribute('accept') || hiddenInput?.getAttribute('accept') || wrapper?.getAttribute('data-accepted') || (directFileInput && directFileInput.getAttribute('accept')) || 'image/*';

    if (isDirectMode) {
      var dzDirect = new Dropzone(el, {
        url: '#',
        autoProcessQueue: false,
        maxFiles: maxFiles,
        maxFilesize: maxFilesize,
        acceptedFiles: acceptedFiles,
        addRemoveLinks: true,
        dictDefaultMessage: opts.dictDefaultMessage || '<div class="dz-message"><i class="las la-cloud-upload-alt" style="font-size:32px"></i><br>Drop files here or click to upload</div>',
      });
      function syncDirectInput() {
        if (!directFileInput) return;
        var dt = new DataTransfer();
        dzDirect.files.forEach(function(f){ if (!f.isMock) dt.items.add(f); });
        directFileInput.files = dt.files;
      }
      dzDirect.on('addedfile', function(){ syncDirectInput(); });
      dzDirect.on('removedfile', function(){ syncDirectInput(); });
      return dzDirect;
    }

    function acceptedToMimes(accept) {
      if (!accept || accept === 'image/*') return 'jpg,jpeg,png,webp,svg,gif';
      return accept.split(',').map(function(s){
        s = s.trim().replace(/^\./,'').replace(/^image\//,'');
        if (s === '*') return 'jpg,jpeg,png,webp,svg,gif';
        return s;
      }).filter(Boolean).join(',') || 'jpg,jpeg,png,webp,svg,gif';
    }
    var mimesVal = opts.mimes || el.getAttribute('data-mimes') || acceptedToMimes(acceptedFiles);
    var dz = new Dropzone(el, {
      url: opts.url || uploadUrl(),
      paramName: opts.paramName || 'fileholder_files',
      maxFiles: maxFiles,
      maxFilesize: maxFilesize,
      acceptedFiles: acceptedFiles,
      addRemoveLinks: true,
      dictDefaultMessage: opts.dictDefaultMessage || '<div class="dz-message"><i class="las la-cloud-upload-alt" style="font-size:32px"></i><br>Drop files here or click to upload</div>',
      headers: { 'X-CSRF-TOKEN': csrf() },
      params: { mimes: mimesVal },
      maxFilesize: maxFilesize,
    });

    // track fileName on file object for removal
    dz.on('success', function(file, response){
      var fileName = response.file_name || response.fileName || (response.data && response.data.file_name);
      if (!fileName && response.file_name === undefined && response.fileName === undefined) {
        // fallback: response may be { file_name, status }
        fileName = response.file_name;
      }
      if (fileName) {
        file._dropzoneFileName = fileName;
        addFileName(hiddenInput, fileName);
        // for single-file, remove previous entry if any beyond last
        if (!isMultiple) {
          var vals = getHiddenValues(hiddenInput);
          if (vals.length > 1) setHiddenValue(hiddenInput, [vals[vals.length-1]]);
          // remove extra previews visually (Dropzone maxFiles handles, but hidden sync needs)
          if (dz.files.length > 1) {
            dz.files.slice(0, -1).forEach(function(f){ try{ dz.removeFile(f); }catch(e){} });
          }
        }
      } else if (response.error) {
        dz.emit('error', file, response.error.join ? response.error.join(', ') : response.error);
        dz.removeFile(file);
      }
    });

    dz.on('addedfile', function(file){
      if (!file.isMock) syncFileInput(el, dz.files);
    });

    dz.on('error', function(file, message){
      if (typeof message === 'string' && message.indexOf('Upload canceled') === -1) {
        // keep error visible
      }
    });

    dz.on('removedfile', function(file){
      var fileName = file._dropzoneFileName;
      // for mock files (existing preview), _dropzoneFileName is set, but we may want to NOT call removeUrl if it's an old_file (already persisted)
      // old images: file.isMock === true — don't delete temp, just clear hidden if user replaced
      if (file.isMock) {
        // if mock removed and no new upload, clear hidden so backend knows to keep old via old_* field
        // but hidden holds temp filenames only; old file is via separate old_image hidden. So just remove mock name if it was in hidden (rare)
        if (fileName) removeFileName(hiddenInput, fileName);
        return;
      }
      if (fileName) {
        removeFileName(hiddenInput, fileName);
        // call remove endpoint (best-effort)
        try {
          var fd = new FormData();
          fd.append('file_info', JSON.stringify({ file_name: fileName }));
          fd.append('_token', csrf());
          fetch(removeUrl(), { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrf() } });
        } catch(e) {}
      }
      syncFileInput(el, dz.files);
    });

    dz.on('maxfilesexceeded', function(file){
      if (!isMultiple) {
        dz.removeAllFiles(true);
        dz.addFile(file);
      }
    });

    // render existing preview if wrapper has data-preview-name
    var wrapper = el.closest('.dropzone-wrapper');
    if (wrapper) {
      var previewName = wrapper.getAttribute('data-preview-name') || hiddenInput?.getAttribute('data-preview-name') || el.getAttribute('data-preview-name');
      var previewPath = wrapper.getAttribute('data-preview-path') || hiddenInput?.getAttribute('data-preview-path') || el.getAttribute('data-preview-path');
      if (previewName && previewPath) {
        // only for single-file wrappers; for multiple skip
        var mockName = previewName;
        var mockUrl = previewPath.replace(/\/$/, '') + '/' + mockName;
        var mockFile = { name: mockName, size: 12345, isMock: true, _dropzoneFileName: mockName, accepted: true, status: Dropzone.ADDED };
        // don't push mock into hidden (hidden is for temp only); just show preview
        setTimeout(function(){
          dz.emit('addedfile', mockFile);
          // try to create thumbnail if image
          if (/\.(jpg|jpeg|png|webp|gif|svg)$/i.test(mockName)) {
            dz.emit('thumbnail', mockFile, mockUrl);
          }
          dz.emit('complete', mockFile);
          // mark as success visually
          if (mockFile.previewElement) mockFile.previewElement.classList.add('dz-success','dz-complete');
          dz.files.push(mockFile);
        }, 100);
      }
    }

    return dz;
  };

  // auto-init all .dropzone elements on page and on modal open
  function autoInit(root) {
    root = root || document;
    root.querySelectorAll('.dropzone-wrapper .dropzone:not(.dz-clickable)').forEach(function(el){
      // skip if already has dropzone
      if (el.dropzone) return;
      window.initDropzone(el);
    });
    // also support standalone .dropzone.dropzone-standalone (support-ticket)
    root.querySelectorAll('.dropzone.dropzone-standalone:not(.dz-clickable)').forEach(function(el){
      if (el.dropzone) return;
      window.initDropzone(el);
    });
  }

  document.addEventListener('DOMContentLoaded', function(){ autoInit(document); });

  // expose for modals / dynamic content
  window.dropzonePreviewReInit = function(selector) {
    var el = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (!el) return;
    // selector may point to hidden input or wrapper; find dropzone
    var dzEl = el.closest ? (el.closest('.dropzone-wrapper')?.querySelector('.dropzone') || el) : el;
    if (!dzEl) return;
    // if wrapper has new data-preview-name set via JS, re-render mock
    var wrapper = dzEl.closest('.dropzone-wrapper');
    if (!wrapper) wrapper = dzEl.parentElement;
    var previewName = el.getAttribute ? el.getAttribute('data-preview-name') : null;
    var previewPath = el.getAttribute ? el.getAttribute('data-preview-path') : null;
    if (previewName && wrapper) {
      wrapper.setAttribute('data-preview-name', previewName);
      if (previewPath) wrapper.setAttribute('data-preview-path', previewPath);
    }
    // if already init, add mock; else init
    var dz = dzEl.dropzone;
    if (!dz) {
      dz = window.initDropzone(dzEl);
    } else if (previewName) {
      // clear existing mocks
      dz.files.filter(function(f){ return f.isMock; }).forEach(function(f){ dz.removeFile(f); });
      var mockUrl = (previewPath || wrapper.getAttribute('data-preview-path') || '').replace(/\/$/, '') + '/' + previewName;
      var mockFile = { name: previewName, size: 12345, isMock: true, _dropzoneFileName: previewName, accepted: true, status: Dropzone.ADDED };
      dz.emit('addedfile', mockFile);
      if (/\.(jpg|jpeg|png|webp|gif|svg)$/i.test(previewName)) dz.emit('thumbnail', mockFile, mockUrl);
      dz.emit('complete', mockFile);
      if (mockFile.previewElement) mockFile.previewElement.classList.add('dz-success','dz-complete');
      dz.files.push(mockFile);
    }
  };
  // backward compat: keep old global name as alias
  window.fileHolderPreviewReInit = window.dropzonePreviewReInit;
  window.dropzoneAutoInit = autoInit;

  // re-init on bootstrap modal shown (magnific popup open)
  document.addEventListener('click', function(e){
    // delay to catch dynamically inserted gateway currency blocks
    setTimeout(function(){ autoInit(document); }, 300);
  });
})();
