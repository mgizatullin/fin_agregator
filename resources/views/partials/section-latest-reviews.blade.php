@php
    $sectionReviews = collect($latestSectionReviews ?? [])->filter()->values();
    $reviewsTitle = trim((string) ($reviews_block_title ?? ''));
@endphp

@if($sectionReviews->isNotEmpty())
    <div class="section-opportunities tf-spacing-27 pt-0">
        <div class="tf-container">
            <div class="heading-section mb_40">
                <h2 class="title text_mono-dark-9 fw-5">{{ $reviewsTitle !== '' ? $reviewsTitle : 'Последние отзывы' }}</h2>
            </div>
            <div class="tf-grid-layout lg-col-4 md-col-2 gap_23">
                @foreach($sectionReviews as $review)
                    @php
                        $reviewable = $review->reviewable;
                        $reviewableType = $review->reviewable_type;
                        $reviewableSlug = $reviewable?->slug ? (string) $reviewable->slug : null;

                        $bankName = $review->bank?->name
                            ?? $reviewable?->bank?->name
                            ?? $reviewable?->company_name
                            ?? (($reviewableType === \App\Models\Bank::class) ? ($reviewable?->name ?? null) : null);

                        $bankSlug = $review->bank?->slug
                            ?? $reviewable?->bank?->slug
                            ?? (($reviewableType === \App\Models\Bank::class) ? ($reviewableSlug) : null);

                        $bankUrl = $bankSlug ? url_section('banki/' . $bankSlug) : null;

                        $reviewTargetUrl = match ($reviewableType) {
                            \App\Models\Credit::class => $reviewableSlug ? url_section('kredity/' . $reviewableSlug) . '#product-reviews' : null,
                            \App\Models\Deposit::class => $reviewableSlug ? url_section('vklady/' . $reviewableSlug) . '#product-reviews' : null,
                            \App\Models\Card::class => $reviewableSlug ? url_section('karty/' . $reviewableSlug) . '#product-reviews' : null,
                            \App\Models\Loan::class => $reviewableSlug ? url_section('zaimy/' . $reviewableSlug) . '#product-reviews' : null,
                            \App\Models\Bank::class => $reviewableSlug ? url_section('banki/' . $reviewableSlug) . '#product-reviews' : null,
                            default => null,
                        };

                        $rating = (int) ($review->rating ?? 0);
                        $rating = max(1, min(5, $rating));
                    @endphp
                    <article class="review-card">
                        <header class="review-card__header">
                            <h4 class="review-card__title">
                                @if($bankUrl && $bankName)
                                    <a href="{{ $bankUrl }}" class="link">{{ $bankName }}</a>
                                @else
                                    {{ $bankName ?? 'Банк' }}
                                @endif
                            </h4>
                            <span class="review-card__header-meta">
                                @if($reviewTargetUrl)
                                    <a href="{{ $reviewTargetUrl }}" class="link">{{ $review->title }}</a>
                                @elseif(filled($review->title))
                                    <span>{{ $review->title }}</span>
                                @endif
                            </span>
                            <div class="review-stars review-stars--readonly" aria-label="Оценка: {{ $rating }} из 5">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="review-stars__star {{ $i <= $rating ? 'is-filled' : '' }}">★</span>
                                @endfor
                            </div>
                        </header>
                        <div class="review-card__body">
                            <p>{{ $review->body }}</p>
                        </div>
                        <footer class="review-card__footer">
                            <span class="review-card__author">{{ $review->name }}</span>
                        </footer>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
@endif
