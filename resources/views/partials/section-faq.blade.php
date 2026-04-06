@php
    $sectionFaqTitle = trim((string) ($faq_title ?? ''));
    $sectionFaqDescription = trim((string) ($faq_description ?? ''));
    $sectionFaqItems = collect($faq_items ?? [])
        ->map(fn ($item) => is_array($item) ? $item : [])
        ->filter(fn ($item) => filled($item['question'] ?? null) && filled($item['answer'] ?? null))
        ->values();
    $faqAccordionId = 'accordion-faq-' . substr(md5((string) request()->path()), 0, 8);
@endphp

@if($sectionFaqItems->isNotEmpty())
    <div class="section-faqs style-2">
        <div class="tf-container-2">
            <div class="heading-section d-flex gap_12 justify-content-between flex-wrap-md mb_59">
                <div class="left">
                    <h2 class="title text_mono-dark-9 fw-5">
                        {{ $sectionFaqTitle !== '' ? $sectionFaqTitle : 'Частые вопросы' }}
                    </h2>
                </div>
                @if($sectionFaqDescription !== '')
                    <div class="right">
                        <p class="text-body-1 text_mono-gray-7 wow animate__fadeInUp animate__animated" data-wow-delay="0s">{{ $sectionFaqDescription }}</p>
                    </div>
                @endif
            </div>
            <ul class="accordion-wrap style-faqs d-grid gap_23" id="{{ $faqAccordionId }}">
                @foreach($sectionFaqItems as $index => $faq)
                    @php
                        $itemId = $faqAccordionId . '-' . ($index + 1);
                        $isFirst = $index === 0;
                    @endphp
                    <li class="accordion-item action_click {{ $isFirst ? 'active' : '' }} style-default v4 scrolling-effect effectRight">
                        <a href="#{{ $itemId }}" class="action accordion-title {{ $isFirst ? 'current' : 'collapsed' }}" data-bs-toggle="collapse" aria-expanded="{{ $isFirst ? 'true' : 'false' }}" aria-controls="{{ $itemId }}">
                            <div class="heading">
                                <div class="text_mono-dark-9 text-body-1 title fw-5">{{ $faq['question'] }}</div>
                            </div>
                            <div class="icon"></div>
                        </a>
                        <div id="{{ $itemId }}" class="collapse {{ $isFirst ? 'show' : '' }}" data-bs-parent="#{{ $faqAccordionId }}">
                            <div class="accordion-faqs-content">
                                <p class="text_mono-dark-9 text-body-2">{{ $faq['answer'] }}</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
