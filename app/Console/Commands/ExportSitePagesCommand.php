<?php

namespace App\Console\Commands;

use App\Http\Helpers\SectionRouteResolver;
use App\Models\Article;
use App\Models\Bank;
use App\Models\BankCategory;
use App\Models\Card;
use App\Models\CardCategory;
use App\Models\Category;
use App\Models\City;
use App\Models\Credit;
use App\Models\CreditCategory;
use App\Models\Deposit;
use App\Models\DepositCategory;
use App\Models\HomePageSetting;
use App\Models\Loan;
use App\Models\LoanCategory;
use App\Models\Page;
use App\Models\SectionSetting;
use App\Models\Service;
use App\Models\SiteSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportSitePagesCommand extends Command
{
    protected $signature = 'app:export-site-pages
        {--format=csv : csv|jsonl (csv recommended)}
        {--out=storage/app/exports : Output directory (absolute or relative to project)}
        {--city-scope=active : active|dialog (dialog = population>=150000)}
        {--virtual-limit=0 : Limit virtual pages (0 = no limit)}';

    protected $description = 'Export physical and virtual site pages (url, title, description).';

    public function handle(): int
    {
        $format = strtolower(trim((string) $this->option('format')));
        if (! in_array($format, ['csv', 'jsonl'], true)) {
            $this->error('Invalid --format. Allowed: csv, jsonl');

            return 1;
        }

        $outDir = (string) $this->option('out');
        $outDir = $this->resolveOutDir($outDir);
        if (! is_dir($outDir) && ! @mkdir($outDir, 0775, true) && ! is_dir($outDir)) {
            $this->error("Failed to create output directory: {$outDir}");

            return 1;
        }

        $cityScope = strtolower(trim((string) $this->option('city-scope')));
        if (! in_array($cityScope, ['active', 'dialog'], true)) {
            $this->error('Invalid --city-scope. Allowed: active, dialog');

            return 1;
        }

        $virtualLimit = (int) $this->option('virtual-limit');
        if ($virtualLimit < 0) {
            $virtualLimit = 0;
        }

        $physicalPath = $outDir.'/pages-physical.'.$format;
        $virtualPath = $outDir.'/pages-virtual.'.$format;

        $this->info("Export format: {$format}");
        $this->info("Physical output: {$physicalPath}");
        $this->info("Virtual output: {$virtualPath}");

        $physicalWriter = $this->openWriter($physicalPath, $format);
        $virtualWriter = $this->openWriter($virtualPath, $format);

        $physicalCount = 0;
        $virtualCount = 0;

        $this->writeHeader($physicalWriter, $format);
        $this->writeHeader($virtualWriter, $format);

        foreach ($this->iteratePhysicalPages() as $row) {
            $this->writeRow($physicalWriter, $format, $row);
            $physicalCount++;
        }

        $cities = $this->loadCitiesForVirtual($cityScope);
        foreach ($this->iterateVirtualPages($cities, $virtualLimit) as $row) {
            $this->writeRow($virtualWriter, $format, $row);
            $virtualCount++;
        }

        $this->closeWriter($physicalWriter);
        $this->closeWriter($virtualWriter);

        $this->info("Done. Physical pages: {$physicalCount}. Virtual pages: {$virtualCount}.");

        return 0;
    }

    /**
     * @return iterable<array{url:string,title:?string,description:?string,kind:string}>
     */
    private function iteratePhysicalPages(): iterable
    {
        yield $this->row($this->urlCanonical('/'), $this->homeSeoTitle(), $this->homeSeoDescription(), 'physical');

        yield $this->row($this->urlCanonical('/about'), $this->aboutSeoTitle(), $this->aboutSeoDescription(), 'physical');
        yield $this->row($this->urlCanonical('/about.html'), $this->aboutSeoTitle(), $this->aboutSeoDescription(), 'physical');

        yield from $this->sectionPhysical('karty', 'cards');
        yield from $this->sectionPhysical('kredity', 'credits');
        yield from $this->sectionPhysical('vklady', 'deposits');
        yield from $this->sectionPhysical('zaimy', 'loans');
        yield from $this->sectionPhysical('banki', 'banks');

        yield $this->row($this->urlCanonical('/blog'), $this->sectionSeoTitle('blog'), $this->sectionSeoDescription('blog'), 'physical');
        foreach (Category::query()->where('is_active', true)->get(['slug', 'name', 'description']) as $cat) {
            yield $this->row(
                $this->urlSection('blog/category/'.$cat->slug),
                $cat->name,
                $cat->description ?: null,
                'physical'
            );
        }
        foreach (Article::query()->where('is_published', true)->get(['slug', 'title', 'seo_title', 'seo_description']) as $article) {
            yield $this->row(
                $this->urlSection('blog/'.$article->slug),
                $article->seo_title ?: $article->title,
                $article->seo_description,
                'physical'
            );
        }

        foreach (Service::query()->where('is_active', true)->get(['slug', 'title', 'seo_title', 'seo_description']) as $service) {
            yield $this->row(
                $this->urlSection('services/'.$service->slug),
                $service->seo_title ?: $service->title,
                $service->seo_description,
                'physical'
            );
        }

        yield $this->row($this->urlCanonical('/team'), $this->teamSeoTitle(), $this->teamSeoDescription(), 'physical');
        yield $this->row($this->urlCanonical('/contact-us'), $this->contactSeoTitle(), $this->contactSeoDescription(), 'physical');

        foreach (Page::query()->where('is_active', true)->get(['slug', 'title', 'seo_title', 'seo_description']) as $page) {
            yield $this->row(
                $this->urlSection($page->slug),
                $page->seo_title,
                $page->seo_description,
                'physical'
            );
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int,City>  $cities
     * @return iterable<array{url:string,title:?string,description:?string,kind:string}>
     */
    private function iterateVirtualPages($cities, int $limit): iterable
    {
        $emitted = 0;
        $emit = function (array $row) use (&$emitted, $limit): ?array {
            if ($limit > 0 && $emitted >= $limit) {
                return null;
            }
            $emitted++;

            return $row;
        };

        $cardsCitySlugsBlocked = $this->blockedCitySlugsForSection('cards');
        $creditsCitySlugsBlocked = $this->blockedCitySlugsForSection('credits');
        $depositsCitySlugsBlocked = $this->blockedCitySlugsForSection('deposits');
        $loansCitySlugsBlocked = $this->blockedCitySlugsForSection('loans');
        $banksCitySlugsBlocked = $this->blockedCitySlugsForSection('banks');

        foreach ($cities as $city) {
            if (! isset($cardsCitySlugsBlocked[$city->slug])) {
                if ($row = $emit($this->sectionCityRow('karty', 'cards', $city))) {
                    yield $row;
                }
            }
            if (! isset($creditsCitySlugsBlocked[$city->slug])) {
                if ($row = $emit($this->sectionCityRow('kredity', 'credits', $city))) {
                    yield $row;
                }
            }
            if (! isset($depositsCitySlugsBlocked[$city->slug])) {
                if ($row = $emit($this->sectionCityRow('vklady', 'deposits', $city))) {
                    yield $row;
                }
            }
            if (! isset($loansCitySlugsBlocked[$city->slug])) {
                if ($row = $emit($this->sectionCityRow('zaimy', 'loans', $city))) {
                    yield $row;
                }
            }
            if (! isset($banksCitySlugsBlocked[$city->slug])) {
                if ($row = $emit($this->sectionCityRow('banki', 'banks', $city))) {
                    yield $row;
                }
            }
        }

        foreach (CardCategory::query()->get(['slug', 'title', 'description', 'h1_template', 'seo_title_template', 'seo_description_template']) as $category) {
            foreach ($cities as $city) {
                $row = $emit($this->cardCategoryCityRow($category, $city));
                if ($row) {
                    yield $row;
                }
            }
        }

        foreach (CreditCategory::query()->get(['slug', 'title', 'description', 'h1_template', 'seo_title_template', 'seo_description_template']) as $category) {
            foreach ($cities as $city) {
                $row = $emit($this->creditCategoryCityRow($category, $city));
                if ($row) {
                    yield $row;
                }
            }
        }

        foreach (DepositCategory::query()->get(['slug', 'title', 'description', 'h1_template', 'seo_title_template', 'seo_description_template']) as $category) {
            foreach ($cities as $city) {
                $row = $emit($this->depositCategoryCityRow($category, $city));
                if ($row) {
                    yield $row;
                }
            }
        }

        foreach (LoanCategory::query()->get(['slug', 'title', 'description', 'h1_template', 'seo_title_template', 'seo_description_template']) as $category) {
            foreach ($cities as $city) {
                $row = $emit($this->loanCategoryCityRow($category, $city));
                if ($row) {
                    yield $row;
                }
            }
        }

        foreach (BankCategory::query()->get(['slug', 'title', 'description', 'h1_template', 'seo_title_template', 'seo_description_template']) as $category) {
            foreach ($cities as $city) {
                $row = $emit($this->bankCategoryCityRow($category, $city));
                if ($row) {
                    yield $row;
                }
            }
        }

        // Bank pages that exist only for cities where the bank has branches.
        $bankCityPairs = $this->bankAvailableCitySlugs();
        foreach ($bankCityPairs as $pair) {
            $bankSlug = (string) $pair->bank_slug;
            $citySlug = (string) $pair->city_slug;
            $city = $cities->firstWhere('slug', $citySlug) ?? City::query()->where('slug', $citySlug)->where('is_active', true)->first();
            if (! $city) {
                continue;
            }

            $bank = Bank::query()->where('slug', $bankSlug)->where('is_active', true)->first();
            if (! $bank) {
                continue;
            }

            $titleCity = $city->name_prepositional ?? $city->name;
            $title = $bank->name.' в '.$titleCity;

            if ($row = $emit($this->row($this->urlSection('banki/'.$bank->slug.'/'.$city->slug), $bank->seo_title, $bank->seo_description, 'virtual'))) {
                yield $row;
            }
            if ($row = $emit($this->row($this->urlSection('banki/'.$bank->slug.'/otdeleniya/'.$city->slug), 'Отделения '.$bank->name.' в '.$titleCity, null, 'virtual'))) {
                yield $row;
            }
        }
    }

    /**
     * @return iterable<array{url:string,title:?string,description:?string,kind:string}>
     */
    private function sectionPhysical(string $sectionPath, string $sectionType): iterable
    {
        $setting = SectionSetting::forType($sectionType);
        $title = $setting?->seo_title;
        $desc = $setting?->seo_description;
        yield $this->row($this->urlSection($sectionPath), $title, $desc, 'physical');

        if ($sectionType === 'cards') {
            foreach (CardCategory::query()->get(['slug', 'title', 'description', 'seo_title_template', 'seo_description_template', 'h1_template']) as $category) {
                $seo = $this->categorySeoNoCity($sectionType, $category);
                yield $this->row($this->urlSection('karty/category/'.$category->slug), $seo['title'], $seo['description'], 'physical');
            }
            foreach (Card::query()->where('is_active', true)->get(['slug', 'name']) as $item) {
                $headline = method_exists($item, 'pageHeadline') ? $item->pageHeadline() : $item->name;
                yield $this->row($this->urlSection('karty/'.$item->slug), $headline.' — '.$this->siteDisplayName(), null, 'physical');
            }

            return;
        }

        if ($sectionType === 'credits') {
            foreach (CreditCategory::query()->get(['slug', 'title', 'description', 'seo_title_template', 'seo_description_template', 'h1_template']) as $category) {
                $seo = $this->categorySeoNoCity($sectionType, $category);
                yield $this->row($this->urlSection('kredity/'.$category->slug), $seo['title'], $seo['description'], 'physical');
            }
            foreach (Credit::query()->where('is_active', true)->get(['slug', 'name']) as $item) {
                $headline = method_exists($item, 'pageHeadline') ? $item->pageHeadline() : $item->name;
                yield $this->row($this->urlSection('kredity/'.$item->slug), $headline.' — '.$this->siteDisplayName(), null, 'physical');
            }

            return;
        }

        if ($sectionType === 'deposits') {
            foreach (DepositCategory::query()->get(['slug', 'title', 'description', 'seo_title_template', 'seo_description_template', 'h1_template']) as $category) {
                $seo = $this->categorySeoNoCity($sectionType, $category);
                yield $this->row($this->urlSection('vklady/'.$category->slug), $seo['title'], $seo['description'], 'physical');
            }
            foreach (Deposit::query()->where('is_active', true)->get(['slug', 'name']) as $item) {
                $headline = method_exists($item, 'pageHeadline') ? $item->pageHeadline() : $item->name;
                yield $this->row($this->urlSection('vklady/'.$item->slug), $headline.' — '.$this->siteDisplayName(), null, 'physical');
            }

            return;
        }

        if ($sectionType === 'loans') {
            foreach (LoanCategory::query()->get(['slug', 'title', 'description', 'seo_title_template', 'seo_description_template', 'h1_template']) as $category) {
                $seo = $this->categorySeoNoCity($sectionType, $category);
                yield $this->row($this->urlSection('zaimy/'.$category->slug), $seo['title'], $seo['description'], 'physical');
            }
            foreach (Loan::query()->where('is_active', true)->get(['slug', 'name']) as $item) {
                $headline = method_exists($item, 'pageHeadline') ? $item->pageHeadline() : $item->name;
                yield $this->row($this->urlSection('zaimy/'.$item->slug), $headline.' — '.$this->siteDisplayName(), null, 'physical');
            }

            return;
        }

        if ($sectionType === 'banks') {
            foreach (Bank::query()->where('is_active', true)->get(['slug', 'name', 'seo_title', 'seo_description']) as $bank) {
                yield $this->row($this->urlSection('banki/'.$bank->slug), $bank->seo_title, $bank->seo_description, 'physical');
                yield $this->row($this->urlSection('banki/'.$bank->slug.'/otzyvy'), 'Отзывы о '.$bank->name, null, 'physical');
                yield $this->row($this->urlSection('banki/'.$bank->slug.'/otdeleniya'), 'Отделения '.$bank->name, null, 'physical');
                yield $this->row($this->urlSection('banki/'.$bank->slug.'/vklady'), 'Вклады '.$bank->name, null, 'physical');
                yield $this->row($this->urlSection('banki/'.$bank->slug.'/karty'), 'Карты '.$bank->name, null, 'physical');
                yield $this->row($this->urlSection('banki/'.$bank->slug.'/kredity'), 'Кредиты '.$bank->name, null, 'physical');
            }
            foreach (BankCategory::query()->get(['slug', 'title', 'description', 'seo_title_template', 'seo_description_template', 'h1_template']) as $category) {
                $seo = $this->categorySeoNoCity($sectionType, $category);
                yield $this->row($this->urlSection('banki/'.$category->slug), $seo['title'], $seo['description'], 'physical');
            }

            return;
        }
    }

    private function sectionCityRow(string $sectionPath, string $sectionType, City $city): array
    {
        $setting = SectionSetting::forType($sectionType);
        $section = (object) [
            'title' => $setting?->title ?: $sectionPath,
        ];

        $seoTitle = filled($setting?->seo_title_template)
            ? SectionRouteResolver::parseTemplate($setting->seo_title_template, $section, $city)
            : ($setting?->seo_title ? $setting->seo_title.' в '.($city->name_prepositional ?? $city->name) : null);

        $seoDescription = filled($setting?->seo_description_template)
            ? SectionRouteResolver::parseTemplate($setting->seo_description_template, $section, $city)
            : SectionRouteResolver::sectionDescription($setting?->seo_description, $city);

        return $this->row(
            $this->urlSection($sectionPath.'/'.$city->slug),
            $seoTitle,
            $seoDescription,
            'virtual'
        );
    }

    private function cardCategoryCityRow($category, City $city): array
    {
        return $this->categoryCityRow('cards', 'karty/category/'.$category->slug.'/'.$city->slug, $category, $city);
    }

    private function creditCategoryCityRow($category, City $city): array
    {
        return $this->categoryCityRow('credits', 'kredity/'.$category->slug.'/'.$city->slug, $category, $city);
    }

    private function depositCategoryCityRow($category, City $city): array
    {
        return $this->categoryCityRow('deposits', 'vklady/'.$category->slug.'/'.$city->slug, $category, $city);
    }

    private function loanCategoryCityRow($category, City $city): array
    {
        return $this->categoryCityRow('loans', 'zaimy/'.$category->slug.'/'.$city->slug, $category, $city);
    }

    private function bankCategoryCityRow($category, City $city): array
    {
        return $this->categoryCityRow('banks', 'banki/'.$category->slug.'/'.$city->slug, $category, $city);
    }

    private function categoryCityRow(string $sectionType, string $path, $category, City $city): array
    {
        $sectionSetting = SectionSetting::getOrCreateForType($sectionType);
        $serviceName = $sectionSetting->title ?? '';
        $variables = [
            'service_name' => $serviceName,
            'category_name' => $category->title ?? '',
            'city' => $city->name,
            'city.g' => $city->name_genitive ?? $city->name,
            'city.p' => $city->name_prepositional ?? $city->name,
        ];

        $useTemplates = filled($category->h1_template) || filled($category->seo_title_template) || filled($category->seo_description_template);
        if ($useTemplates) {
            $title = filled($category->h1_template)
                ? SectionRouteResolver::parseSeoTemplate($category->h1_template, $variables)
                : (($category->title ?? '').' в '.($city->name_prepositional ?? $city->name));
            $seoTitle = filled($category->seo_title_template)
                ? SectionRouteResolver::parseSeoTemplate($category->seo_title_template, $variables)
                : null;
            $seoDescription = filled($category->seo_description_template)
                ? SectionRouteResolver::parseSeoTemplate($category->seo_description_template, $variables)
                : SectionRouteResolver::sectionDescription($category->description ?? null, $city);
        } else {
            $title = ($category->title ?? '').' в '.$city->name;
            $seoTitle = null;
            $seoDescription = SectionRouteResolver::sectionDescription($category->description ?? null, $city);
        }

        return $this->row($this->urlSection($path), $seoTitle ?: $title, $seoDescription, 'virtual');
    }

    /**
     * @return array{title:?string,description:?string}
     */
    private function categorySeoNoCity(string $sectionType, $category): array
    {
        $sectionSetting = SectionSetting::getOrCreateForType($sectionType);
        $serviceName = $sectionSetting->title ?? '';
        $variables = [
            'service_name' => $serviceName,
            'category_name' => $category->title ?? '',
            'city' => null,
            'city.g' => null,
            'city.p' => null,
        ];

        $useTemplates = filled($category->h1_template) || filled($category->seo_title_template) || filled($category->seo_description_template);
        if ($useTemplates) {
            $title = filled($category->seo_title_template)
                ? SectionRouteResolver::parseSeoTemplate($category->seo_title_template, $variables)
                : ($category->title ?? null);
            $description = filled($category->seo_description_template)
                ? SectionRouteResolver::parseSeoTemplate($category->seo_description_template, $variables)
                : ($category->description ?? null);

            return [
                'title' => $title,
                'description' => $description,
            ];
        }

        return [
            'title' => $category->title ?? null,
            'description' => $category->description ?? null,
        ];
    }

    private function homeSeoTitle(): ?string
    {
        $settings = HomePageSetting::instance();

        return $settings->seo_title;
    }

    private function homeSeoDescription(): ?string
    {
        $settings = HomePageSetting::instance();

        return $settings->seo_description;
    }

    private function aboutSeoTitle(): ?string
    {
        $siteSettings = SiteSettings::getInstance();

        return $siteSettings->about_project_seo_title
            ?: ($siteSettings->about_project_team_title ?: 'О проекте');
    }

    private function aboutSeoDescription(): ?string
    {
        $siteSettings = SiteSettings::getInstance();

        return $siteSettings->about_project_seo_description
            ?: ($siteSettings->about_project_reviews_description ?: ($siteSettings->about_project_approach_description ?: ''));
    }

    private function sectionSeoTitle(string $type): ?string
    {
        return SectionSetting::getOrCreateForType($type)->seo_title;
    }

    private function sectionSeoDescription(string $type): ?string
    {
        return SectionSetting::getOrCreateForType($type)->seo_description;
    }

    private function teamSeoTitle(): ?string
    {
        $section = SectionSetting::forType('specialists');
        $title = $section?->title ?: 'Специалисты';

        return $section?->seo_title ?: $title;
    }

    private function teamSeoDescription(): ?string
    {
        $section = SectionSetting::forType('specialists');

        return $section?->seo_description ?: strip_tags((string) ($section?->description ?? ''));
    }

    private function contactSeoTitle(): ?string
    {
        $settings = \App\Models\ContactPageSetting::getInstance();

        return $settings->title ?: 'Контакты';
    }

    private function contactSeoDescription(): ?string
    {
        return 'Свяжитесь с нами через форму обратной связи.';
    }

    private function siteDisplayName(): string
    {
        return SiteSettings::getInstance()->displayNameForTitle();
    }

    private function urlSection(string $path): string
    {
        return url_section($path);
    }

    private function urlCanonical(string $path): string
    {
        $path = trim($path);
        if ($path === '' || $path === '/') {
            return url('/');
        }

        return url_section(ltrim($path, '/'));
    }

    private function row(string $url, ?string $title, ?string $description, string $kind): array
    {
        return [
            'url' => $url,
            'title' => $this->nullIfBlank($title),
            'description' => $this->nullIfBlank($description),
            'kind' => $kind,
        ];
    }

    private function nullIfBlank(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($this->normalizeText($value));

        return $value === '' ? null : $value;
    }

    /**
     * Best-effort normalization of common mojibake cases (UTF-8 decoded as Latin-1, then re-encoded).
     */
    private function normalizeText(string $value): string
    {
        $value = (string) $value;

        if ($value === '') {
            return $value;
        }

        // Heuristic: "Ð¡Ñ‚Ñ€..." pattern, but no Cyrillic characters.
        if (preg_match('/[ÐÑ]/u', $value) === 1 && preg_match('/[А-Яа-яЁё]/u', $value) !== 1) {
            // Recover when UTF-8 bytes were decoded as Windows-1252, then stored as UTF-8 text.
            $fixed = @iconv('UTF-8', 'Windows-1252//IGNORE', $value);
            if (is_string($fixed) && $fixed !== '' && preg_match('/[А-Яа-яЁё]/u', $fixed) === 1) {
                return $fixed;
            }
        }

        return $value;
    }

    private function resolveOutDir(string $outDir): string
    {
        $outDir = trim($outDir);
        if ($outDir === '') {
            $outDir = 'storage/app/exports';
        }
        if (str_starts_with($outDir, '/')) {
            return rtrim($outDir, '/');
        }

        return rtrim(base_path($outDir), '/');
    }

    private function openWriter(string $path, string $format)
    {
        $handle = fopen($path, 'w');
        if (! $handle) {
            throw new \RuntimeException("Failed to open file: {$path}");
        }

        return $handle;
    }

    private function closeWriter($handle): void
    {
        if (is_resource($handle)) {
            fclose($handle);
        }
    }

    private function writeHeader($handle, string $format): void
    {
        if ($format === 'csv') {
            fputcsv($handle, ['url', 'title', 'description', 'kind']);
            return;
        }
    }

    /**
     * @param  array{url:string,title:?string,description:?string,kind:string}  $row
     */
    private function writeRow($handle, string $format, array $row): void
    {
        if ($format === 'csv') {
            fputcsv($handle, [$row['url'], $row['title'], $row['description'], $row['kind']]);
            return;
        }

        fwrite($handle, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
    }

    /**
     * @return \Illuminate\Support\Collection<int,City>
     */
    private function loadCitiesForVirtual(string $scope)
    {
        $query = City::query()->where('is_active', true)->orderBy('name');
        if ($scope === 'dialog') {
            $query->where('population', '>=', 150000);
        }

        return $query->get(['id', 'name', 'name_genitive', 'name_prepositional', 'slug', 'population']);
    }

    /**
     * Some city slugs are unreachable as "city pages" because the router resolves
     * the same segment as a category/product/bank first (see routes/web.php).
     *
     * @return array<string,true> keyed by blocked city slug
     */
    private function blockedCitySlugsForSection(string $sectionType): array
    {
        $blocked = [];

        if ($sectionType === 'cards') {
            foreach (Card::query()->where('is_active', true)->pluck('slug') as $slug) {
                $blocked[(string) $slug] = true;
            }

            return $blocked;
        }

        if ($sectionType === 'credits') {
            foreach (CreditCategory::query()->pluck('slug') as $slug) {
                $blocked[(string) $slug] = true;
            }
            foreach (Credit::query()->where('is_active', true)->pluck('slug') as $slug) {
                $blocked[(string) $slug] = true;
            }

            return $blocked;
        }

        if ($sectionType === 'deposits') {
            foreach (Deposit::query()->where('is_active', true)->pluck('slug') as $slug) {
                $blocked[(string) $slug] = true;
            }
            foreach (DepositCategory::query()->pluck('slug') as $slug) {
                $blocked[(string) $slug] = true;
            }

            return $blocked;
        }

        if ($sectionType === 'loans') {
            foreach (Loan::query()->where('is_active', true)->pluck('slug') as $slug) {
                $blocked[(string) $slug] = true;
            }
            foreach (LoanCategory::query()->pluck('slug') as $slug) {
                $blocked[(string) $slug] = true;
            }

            return $blocked;
        }

        if ($sectionType === 'banks') {
            foreach (Bank::query()->where('is_active', true)->pluck('slug') as $slug) {
                $blocked[(string) $slug] = true;
            }
            foreach (BankCategory::query()->pluck('slug') as $slug) {
                $blocked[(string) $slug] = true;
            }

            return $blocked;
        }

        return $blocked;
    }

    /**
     * @return \Illuminate\Support\Collection<int,object{bank_slug:string,city_slug:string}>
     */
    private function bankAvailableCitySlugs()
    {
        return DB::table('branches')
            ->join('banks', 'banks.id', '=', 'branches.bank_id')
            ->join('cities', 'cities.id', '=', 'branches.city_id')
            ->where('branches.is_active', true)
            ->whereNotNull('branches.city_id')
            ->where('banks.is_active', true)
            ->where('cities.is_active', true)
            ->select([
                'banks.slug as bank_slug',
                'cities.slug as city_slug',
            ])
            ->distinct()
            ->orderBy('banks.slug')
            ->orderBy('cities.slug')
            ->get();
    }
}

