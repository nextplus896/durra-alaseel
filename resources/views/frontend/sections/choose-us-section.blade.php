@php
    $choose_slug = Str::slug(site_section_const()::CHOOSE_US_SECTION);
    $choose = App\Models\Admin\SiteSections::getData($choose_slug)->first();
    $default = get_default_language_code() ?? 'en';
    $default_lng = 'en';
@endphp
<section class="why-choose-us pt-80">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="section-title pb-20">
                    <h4 class="sub-title text--base">
                        {{ $choose->value->language->$default->section_title ?? '' }}
                    </h4>
                </div>
                <div class="choose-us-title pb-20">
                    <h2 class="title">
                        {{ $choose->value->language->$default->heading ?? '' }}
                    </h2>
                </div>
                <div class="choose-us-content">
                    <p>{{ $choose->value->language->$default->sub_heading ?? '' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="choose-us-area pt-60">
            <div class="row">
                @forelse ($choose->value->items ?? [] as $value)
                    <div class="col-lg-6 mb-20">
                        <div class="choose-us-area" data-aos="fade-left" data-aos-duration="1200">
                            <div class="number">
                                <h3 class="title">{{ $loop->iteration }}.</h3>
                            </div>
                            <div class="work-content tri-right left-top">
                                <p>{{ $value->language->$default->description ?? '' }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                @endforelse
            </div>
        </div>
    </div>
</section>
