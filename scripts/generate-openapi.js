/* eslint-disable no-console */

const fs = require('fs');
const path = require('path');
const postmanToOpenApi = require('postman-to-openapi');
const YAML = require('yaml');

const projectRoot = path.resolve(__dirname, '..');
const openapiDir = path.join(projectRoot, 'openapi');

const dorraCollection = path.join(projectRoot, 'Dorra_Alaseel_Complete_API.postman_collection.json');
const twilioCollection = path.join(projectRoot, 'Twilio_OTP_Postman_Collection.json');

const dorraOut = path.join(openapiDir, 'dorra-alaseel-api-v1.openapi.yaml');
const twilioOut = path.join(openapiDir, 'twilio-otp.openapi.yaml');
const mergedOut = path.join(openapiDir, 'openapi.yaml');

function ensureDir(dirPath) {
  fs.mkdirSync(dirPath, { recursive: true });
}

function existsOrThrow(filePath) {
  if (!fs.existsSync(filePath)) {
    throw new Error(`Missing required file: ${filePath}`);
  }
}

function loadYaml(filePath) {
  const content = fs.readFileSync(filePath, 'utf8');
  return YAML.parse(content);
}

function writeYaml(filePath, obj) {
  const doc = new YAML.Document(obj);
  doc.options.lineWidth = 0;
  fs.writeFileSync(filePath, String(doc), 'utf8');
}

function mergeOpenApiSpecs(primary, secondary) {
  const merged = { ...primary };

  merged.paths = { ...(primary.paths || {}) };
  for (const [p, pathItem] of Object.entries(secondary.paths || {})) {
    if (!merged.paths[p]) {
      merged.paths[p] = pathItem;
      continue;
    }

    const existing = merged.paths[p];
    for (const [method, operation] of Object.entries(pathItem || {})) {
      if (!existing[method]) {
        existing[method] = operation;
        continue;
      }

      // Conflict: same path + method present in both specs.
      // Keep primary's operation, but record the secondary under a namespaced key.
      const conflictKey = `${method}__from_secondary`;
      if (!existing[conflictKey]) {
        existing[conflictKey] = operation;
      }
    }
  }

  merged.components = {
    ...(primary.components || {}),
    ...(secondary.components || {}),
    schemas: {
      ...((primary.components && primary.components.schemas) || {}),
      ...((secondary.components && secondary.components.schemas) || {}),
    },
    securitySchemes: {
      ...((primary.components && primary.components.securitySchemes) || {}),
      ...((secondary.components && secondary.components.securitySchemes) || {}),
    },
  };

  // Merge tags (dedupe by name)
  const tagsByName = new Map();
  for (const tag of [...(primary.tags || []), ...(secondary.tags || [])]) {
    if (tag && tag.name && !tagsByName.has(tag.name)) tagsByName.set(tag.name, tag);
  }
  merged.tags = Array.from(tagsByName.values());

  // Prefer a single server that mirrors the Postman variable.
  merged.servers = [{ url: '{{base_url}}' }];

  // Set basic metadata.
  merged.info = {
    ...(primary.info || {}),
    title: (primary.info && primary.info.title) || 'Dorra Alaseel API',
    version: (primary.info && primary.info.version) || 'v1',
    description:
      (primary.info && primary.info.description) ||
      'Generated from Postman collections for Apidog import.',
  };

  return merged;
}

async function main() {
  ensureDir(openapiDir);

  existsOrThrow(dorraCollection);
  existsOrThrow(twilioCollection);

  console.log('Generating OpenAPI from Postman collections...');

  // Generate individual specs.
  await postmanToOpenApi(dorraCollection, dorraOut, {
    defaultTag: 'Dorra Alaseel API v1',
    servers: [{ url: '{{base_url}}' }],
  });

  await postmanToOpenApi(twilioCollection, twilioOut, {
    defaultTag: 'Twilio OTP',
    servers: [{ url: '{{base_url}}' }],
  });

  // Merge into a single spec for one-click import.
  const dorraSpec = loadYaml(dorraOut);
  const twilioSpec = loadYaml(twilioOut);

  const merged = mergeOpenApiSpecs(dorraSpec, twilioSpec);
  writeYaml(mergedOut, merged);

  console.log('Done. Generated:');
  console.log(`- ${path.relative(projectRoot, dorraOut)}`);
  console.log(`- ${path.relative(projectRoot, twilioOut)}`);
  console.log(`- ${path.relative(projectRoot, mergedOut)}`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
