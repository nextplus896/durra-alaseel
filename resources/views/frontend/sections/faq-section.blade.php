@php
    $faq_slug = Str::slug(site_section_const()::FAQ_SECTION);
    $faq = App\Models\Admin\SiteSections::getData($faq_slug)->first();
    $items = json_decode(json_encode($faq->value->items), true);
    $totalItems = count($items);
    $half = ceil($totalItems / 2);
@endphp
<section class="faq-section ptb-80">
    <div class="container">
        <div class="section-header-title">
            <div class="section-title pb-20">
                <h4 class="ssub-title text--base">
                    {{ $faq->value->language->$default->section_title ?? '' }}
                </h4>
            </div>
            <div class="faq-title pb-20">
                <h2 class="title">
                    {{ $faq->value->language->$default->heading ?? '' }}
                </h2>
            </div>
        </div>
        <div class="row justify-content-center mb-20-none">
            <div class="col-xl-6 col-lg-6 mb-20">
                <div class="faq-wrapper">
                    @foreach ($faq->value->items ?? [] as $key => $item)
                        @if ($loop->iteration <= $half)
                            <div class="faq-item">
                                <h3 class="faq-title">
                                    <span class="title">
                                        {{ $item->language->$default->question ?? ''  }}
                                    </span><span class="right-icon"></span>
                                </h3>
                                <div class="faq-content">
                                    <p>
                                        {{ $item->language->$default->answer ?? ''  }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="col-xl-6 col-lg-6 mb-20">
                <div class="faq-wrapper">
                    @foreach ($faq->value->items ?? [] as $key => $item)
                        @if ($loop->iteration > $half)
                        <div class="faq-item">
                            <h3 class="faq-title">
                                <span class="title">
                                    {{ $item->language->$default->question ?? ''  }}
                                </span><span class="right-icon"></span>
                            </h3>
                            <div class="faq-content">
                                <p>
                                    {{ $item->language->$default->answer ?? ''  }}
                                </p>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
</section>
