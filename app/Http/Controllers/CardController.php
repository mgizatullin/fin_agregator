<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesLoadMorePagination;
use App\Http\Helpers\SectionRouteResolver;
use App\Models\Card;
use App\Models\CardCategory;
use App\Models\Review;
use App\Models\SectionSetting;
use App\Models\SiteSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CardController extends Controller
{
    use HandlesLoadMorePagination;

    public function index(Request $request, ?string $citySlug = null): View|Response
    {
        $city = SectionRouteResolver::resolveCity($citySlug);

        $baseQuery = Card::query()
            ->with('bank')
            ->where('is_active', true)
            ->orderBy('name');

        if ($city) {
            $baseQuery->whereHas('bank', function (Builder $bankQuery) use ($city): void {
                $bankQuery->where('is_active', true)
                    ->where(function (Builder $q) use ($city): void {
                        $q->where('is_online_bank', true)
                            ->orWhereHas('branches', function (Builder $branchesQuery) use ($city): void {
                                $branchesQuery
                                    ->where('is_active', true)
                                    ->where('city_id', $city->id);
                            });
                    });
            });
        }

        $filterMeta = $this->buildFilterMeta(clone $baseQuery);

        $query = clone $baseQuery;
        $this->applyFilters($query, $request);

        $cards = $query->paginate(20)->withQueryString();

        if ($response = $this->loadMoreResponse($request, $cards, 'cards.partials.list-items', [
            'items' => $cards,
        ])) {
            return $response;
        }
        $categories = CardCategory::orderBy('title')->get();
        $setting = SectionSetting::forType('cards');
        $section = (object) [
            'title' => $setting?->title ?: 'Кредитные карты',
            'subtitle' => $setting?->subtitle ?? '',
            'description' => $setting?->description ?? '',
        ];

        if ($city) {
            $seo_title = filled($setting?->seo_title_template)
                ? SectionRouteResolver::parseTemplate($setting->seo_title_template, $section, $city)
                : ($setting?->seo_title ? $setting->seo_title.' в '.($city->name_prepositional ?? $city->name) : null);
            $seo_description = filled($setting?->seo_description_template)
                ? SectionRouteResolver::parseTemplate($setting->seo_description_template, $section, $city)
                : SectionRouteResolver::sectionDescription($setting?->seo_description, $city);
            $page_h1 = filled($setting?->h1_template)
                ? SectionRouteResolver::parseTemplate($setting->h1_template, $section, $city)
                : SectionRouteResolver::sectionTitle($section, $city);
            $page_content = filled($setting?->content_template)
                ? SectionRouteResolver::parseTemplate($setting->content_template, $section, $city)
                : ($section->description ?? '');
        } else {
            $seo_title = $setting?->seo_title;
            $seo_description = $setting?->seo_description;
            $page_h1 = $section->title;
            $page_content = $section->description ?? '';
        }

        $title = $page_h1;
        $latestSectionReviews = Review::query()
            ->with(['bank', 'reviewable'])
            ->where('reviewable_type', Card::class)
            ->where('is_published', true)
            ->latest()
            ->limit(4)
            ->get();

        return view('cards.index', array_merge([
            'cards' => $cards,
            'categories' => $categories,
            'section' => $section,
            'city' => $city,
            'seo_title' => $seo_title,
            'seo_description' => $seo_description,
            'title' => $title,
            'page_h1' => $page_h1,
            'page_content' => $page_content,
            'faq_title' => $setting?->faq_title,
            'faq_description' => $setting?->faq_description,
            'faq_items' => $setting?->faq_items ?? [],
            'reviews_block_title' => $setting?->reviews_block_title,
            'latestSectionReviews' => $latestSectionReviews,
            'filterMeta' => $filterMeta,
        ], $city ? [] : ['redirectToCityIfStored' => true, 'sectionBaseForRedirect' => 'karty']));
    }

    /**
     * Show a single card by slug. No city in URL.
     */
    public function show(Request $request, string $slug): View
    {
        $card = Card::with(['bank', 'reviews.bank'])->where('slug', $slug)->where('is_active', true)->firstOrFail();

        $pageHeadline = $card->pageHeadline();
        $siteDisplayName = SiteSettings::getInstance()->displayNameForTitle();

        $section = (object) [
            'title' => $pageHeadline,
            'subtitle' => null,
        ];

        return view('cards.show', array_merge(compact('card', 'section'), [
            'seo_title' => $pageHeadline.' — '.$siteDisplayName,
            'seo_description' => null,
            'title' => $pageHeadline,
        ]));
    }

    protected function applyFilters(Builder $query, Request $request): void
    {
        $gracePeriod = (int) $request->integer('grace_period', 0);
        $creditLimit = (float) $request->input('credit_limit', 0);
        $annualFee = (float) $request->input('annual_fee', 0);
        $psk = (float) $request->input('psk', 0);
        $cashback = (float) $request->input('cashback', 0);

        if ($gracePeriod > 0) {
            $query->whereNotNull('grace_period')
                ->where('grace_period', '>=', $gracePeriod);
        }

        if ($creditLimit > 0) {
            $query->whereNotNull('credit_limit')
                ->where('credit_limit', '>=', $creditLimit);
        }

        if ($annualFee > 0) {
            $query->where(function (Builder $annualFeeQuery) use ($annualFee): void {
                $annualFeeQuery->where(function (Builder $q) use ($annualFee): void {
                    $q->whereNotNull('annual_fee')
                        ->where('annual_fee', '<=', $annualFee);
                })->orWhere(function (Builder $q) use ($annualFee): void {
                    $q->whereNotNull('annual_fee_text')
                        ->whereRaw("CAST(REPLACE(REPLACE(annual_fee_text, ',', '.'), ' ', '') AS DECIMAL(10,2)) <= ?", [$annualFee]);
                });
            });
        }

        if ($psk > 0) {
            $query->where(function (Builder $pskQuery) use ($psk): void {
                $pskQuery->where(function (Builder $q) use ($psk): void {
                    $q->whereNotNull('psk')
                        ->where('psk', '<=', $psk);
                })->orWhere(function (Builder $q) use ($psk): void {
                    $q->whereNotNull('psk_text')
                        ->whereRaw("CAST(REPLACE(REPLACE(psk_text, ',', '.'), '%', '') AS DECIMAL(10,2)) <= ?", [$psk]);
                });
            });
        }

        if ($cashback > 0) {
            $query->where(function (Builder $cashbackQuery) use ($cashback): void {
                $cashbackQuery->where(function (Builder $q) use ($cashback): void {
                    $q->whereNotNull('cashback')
                        ->whereRaw("CAST(REPLACE(REPLACE(cashback, ',', '.'), '%', '') AS DECIMAL(10,2)) >= ?", [$cashback]);
                })->orWhere(function (Builder $q) use ($cashback): void {
                    $q->whereNotNull('cashback_text')
                        ->whereRaw("CAST(REPLACE(REPLACE(cashback_text, ',', '.'), '%', '') AS DECIMAL(10,2)) >= ?", [$cashback]);
                });
            });
        }
    }

    protected function buildFilterMeta(Builder $query): array
    {
        $cards = (clone $query)->get([
            'grace_period',
            'credit_limit',
            'annual_fee',
            'annual_fee_text',
            'psk',
            'psk_text',
            'cashback',
            'cashback_text',
        ]);

        $maxGracePeriod = (int) $cards->max(fn (Card $card): int => (int) ($card->grace_period ?? 0));
        $maxCreditLimit = (float) $cards->max(fn (Card $card): float => (float) ($card->credit_limit ?? 0));

        $maxAnnualFee = (float) $cards->max(function (Card $card): float {
            $parsed = $this->parseNumericValue($card->annual_fee_text);
            if ($parsed !== null) {
                return $parsed;
            }

            return (float) ($card->annual_fee ?? 0);
        });

        $maxPsk = (float) $cards->max(function (Card $card): float {
            $parsed = $this->parseNumericValue($card->psk_text);
            if ($parsed !== null) {
                return $parsed;
            }

            return (float) ($card->psk ?? 0);
        });

        $maxCashback = (float) $cards->max(function (Card $card): float {
            $parsed = $this->parseNumericValue($card->cashback);
            if ($parsed !== null) {
                return $parsed;
            }

            return (float) ($this->parseNumericValue($card->cashback_text) ?? 0);
        });

        return [
            'max_grace_period' => max(0, $maxGracePeriod),
            'max_credit_limit' => (int) ceil($maxCreditLimit),
            'max_annual_fee' => (int) ceil($maxAnnualFee),
            'max_psk' => round($maxPsk, 2),
            'max_cashback' => round($maxCashback, 2),
        ];
    }

    private function parseNumericValue(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (! preg_match('/(\d+(?:[.,]\d+)?)/u', $raw, $matches)) {
            return null;
        }

        return (float) str_replace(',', '.', $matches[1]);
    }
}
