<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Bank;
use App\Models\BankCategory;
use App\Models\Card;
use App\Models\CardCategory;
use App\Models\Credit;
use App\Models\CreditCategory;
use App\Models\Deposit;
use App\Models\DepositCategory;
use App\Models\Loan;
use App\Models\LoanCategory;
use App\Models\Page;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    private const CHANGEFREQ_HOME = 'daily';

    private const CHANGEFREQ_CATEGORY = 'weekly';

    private const CHANGEFREQ_CARD = 'monthly';

    private const PRIORITY_HOME = '1.0';

    private const PRIORITY_CATEGORY = '0.5';

    private const PRIORITY_CARD = '0.25';

    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml.v2', 3600, function (): string {
            return $this->buildSitemapXml();
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    private function buildSitemapXml(): string
    {
        $urls = [];

        $dailyLastmod = Carbon::now(config('app.timezone'))->startOfDay()->toAtomString();

        $add = function (
            string $path,
            string $changefreq,
            string $priority,
            ?Carbon $lastmod = null,
            bool $useDailyLastmod = false
        ) use (&$urls, $dailyLastmod): void {
            $lm = $useDailyLastmod ? $dailyLastmod : ($lastmod?->toAtomString());
            $urls[] = [
                'loc' => $this->normalizeUrl(url($path)),
                'lastmod' => $lm,
                'changefreq' => $changefreq,
                'priority' => $priority,
            ];
        };

        // Главная
        $add('/', self::CHANGEFREQ_HOME, self::PRIORITY_HOME, null, true);

        // Лендинги уровня раздела (как категории по приоритету)
        foreach (['/about', '/team', '/contact-us', '/blog', '/kredity', '/vklady', '/karty', '/zaimy', '/banki'] as $path) {
            $add($path, self::CHANGEFREQ_CATEGORY, self::PRIORITY_CATEGORY, null, true);
        }

        // Статические страницы из Pages
        Page::query()
            ->where('is_active', true)
            ->select(['slug', 'updated_at'])
            ->orderBy('id')
            ->chunk(500, function ($pages) use ($add): void {
                foreach ($pages as $page) {
                    $add('/'.$page->slug, self::CHANGEFREQ_CARD, self::PRIORITY_CARD, $page->updated_at);
                }
            });

        // Блог: категории
        \App\Models\Category::query()
            ->select(['slug', 'updated_at'])
            ->orderBy('id')
            ->chunk(500, function ($cats) use ($add): void {
                foreach ($cats as $cat) {
                    $add('/blog/category/'.$cat->slug, self::CHANGEFREQ_CATEGORY, self::PRIORITY_CATEGORY, null, true);
                }
            });

        // Блог: статьи
        Article::query()
            ->where('is_published', true)
            ->select(['slug', 'updated_at', 'published_at'])
            ->orderBy('id')
            ->chunk(500, function ($articles) use ($add): void {
                foreach ($articles as $article) {
                    $lm = $article->published_at ?? $article->updated_at;
                    $add('/blog/'.$article->slug, self::CHANGEFREQ_CARD, self::PRIORITY_CARD, $lm);
                }
            });

        // Категории разделов
        CreditCategory::query()->select(['slug'])->orderBy('id')->chunk(500, function ($items) use ($add): void {
            foreach ($items as $item) {
                $add('/kredity/'.$item->slug, self::CHANGEFREQ_CATEGORY, self::PRIORITY_CATEGORY, null, true);
            }
        });
        DepositCategory::query()->select(['slug'])->orderBy('id')->chunk(500, function ($items) use ($add): void {
            foreach ($items as $item) {
                $add('/vklady/'.$item->slug, self::CHANGEFREQ_CATEGORY, self::PRIORITY_CATEGORY, null, true);
            }
        });
        CardCategory::query()->select(['slug'])->orderBy('id')->chunk(500, function ($items) use ($add): void {
            foreach ($items as $item) {
                $add('/karty/category/'.$item->slug, self::CHANGEFREQ_CATEGORY, self::PRIORITY_CATEGORY, null, true);
            }
        });
        LoanCategory::query()->select(['slug'])->orderBy('id')->chunk(500, function ($items) use ($add): void {
            foreach ($items as $item) {
                $add('/zaimy/'.$item->slug, self::CHANGEFREQ_CATEGORY, self::PRIORITY_CATEGORY, null, true);
            }
        });
        BankCategory::query()->select(['slug'])->orderBy('id')->chunk(500, function ($items) use ($add): void {
            foreach ($items as $item) {
                $add('/banki/'.$item->slug, self::CHANGEFREQ_CATEGORY, self::PRIORITY_CATEGORY, null, true);
            }
        });

        // Карточки продуктов / банков
        Credit::query()->where('is_active', true)->select(['slug', 'updated_at'])->orderBy('id')->chunk(500, function ($items) use ($add): void {
            foreach ($items as $item) {
                $add('/kredity/'.$item->slug, self::CHANGEFREQ_CARD, self::PRIORITY_CARD, $item->updated_at);
            }
        });
        Deposit::query()->where('is_active', true)->select(['slug', 'updated_at'])->orderBy('id')->chunk(500, function ($items) use ($add): void {
            foreach ($items as $item) {
                $add('/vklady/'.$item->slug, self::CHANGEFREQ_CARD, self::PRIORITY_CARD, $item->updated_at);
            }
        });
        Card::query()->where('is_active', true)->select(['slug', 'updated_at'])->orderBy('id')->chunk(500, function ($items) use ($add): void {
            foreach ($items as $item) {
                $add('/karty/'.$item->slug, self::CHANGEFREQ_CARD, self::PRIORITY_CARD, $item->updated_at);
            }
        });
        Loan::query()->where('is_active', true)->select(['slug', 'updated_at'])->orderBy('id')->chunk(500, function ($items) use ($add): void {
            foreach ($items as $item) {
                $add('/zaimy/'.$item->slug, self::CHANGEFREQ_CARD, self::PRIORITY_CARD, $item->updated_at);
            }
        });
        Bank::query()->where('is_active', true)->select(['slug', 'updated_at'])->orderBy('id')->chunk(500, function ($items) use ($add): void {
            foreach ($items as $item) {
                $add('/banki/'.$item->slug, self::CHANGEFREQ_CARD, self::PRIORITY_CARD, $item->updated_at);
            }
        });

        $out = [];
        $out[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $out[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $u) {
            $loc = htmlspecialchars($u['loc'], ENT_XML1);
            $lastmod = htmlspecialchars((string) $u['lastmod'], ENT_XML1);
            $changefreq = htmlspecialchars($u['changefreq'], ENT_XML1);
            $priority = htmlspecialchars($u['priority'], ENT_XML1);
            $out[] = '  <url>';
            $out[] = "    <loc>{$loc}</loc>";
            $out[] = "    <lastmod>{$lastmod}</lastmod>";
            $out[] = "    <changefreq>{$changefreq}</changefreq>";
            $out[] = "    <priority>{$priority}</priority>";
            $out[] = '  </url>';
        }

        $out[] = '</urlset>';

        return implode("\n", $out)."\n";
    }

    private function normalizeUrl(string $url): string
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        if ($path !== '' && $path !== '/' && ! str_ends_with($url, '/')) {
            return $url.'/';
        }

        return $url;
    }
}
