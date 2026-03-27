<?php

namespace App\Console\Commands;

use App\Models\City;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportCities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-cities {file : Path to CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import cities from CSV file';

    private const HTTP_HEADERS = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
        'Accept' => 'application/json, text/html',
        'Accept-Language' => 'ru-RU,ru;q=0.9',
        'Connection' => 'keep-alive',
    ];

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders(self::HTTP_HEADERS)
            ->withOptions([
                'connect_timeout' => 20,
                'force_ip_resolve' => 'v4',
                'version' => 1.1,
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                ],
            ])
            ->timeout(30)
            ->retry(3, 1000);
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return 1;
        }

        $this->info('Starting cities import...');

        $result = $this->loadCsvRows($filePath);
        if ($result[0] === false) {
            $this->error($result[1] ?? 'Failed to read CSV file.');

            return 1;
        }

        [$headers, $rows] = $result;

        $this->info("Found {$rows->count()} rows in CSV.");

        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($rows as $row) {
            $check = $this->validateCityRow($row);

            if (! $check['valid']) {
                $this->warn('Skipped row: '.implode(', ', $check['issues']));
                $skipped++;
                $bar->advance();

                continue;
            }

            $cityData = $check['data'];

            try {
                City::create([
                    'name' => $cityData['name'],
                    'name_genitive' => $cityData['name_genitive'],
                    'name_prepositional' => $cityData['name_prepositional'],
                    'region' => $cityData['region'],
                    'population' => $cityData['population'],
                    'is_active' => true,
                ]);
                $imported++;
            } catch (\Exception $e) {
                $this->error("Error importing {$cityData['name']}: ".$e->getMessage());
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info('Import completed:');
        $this->info("- Imported: {$imported}");
        $this->info("- Skipped: {$skipped}");
        $this->info("- Errors: {$errors}");

        return 0;
    }

    protected function loadCsvRows(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return [false, 'Failed to open file.'];
        }

        $firstLine = fgetcsv($handle, 0, ';');
        if ($firstLine === false) {
            fclose($handle);

            return [false, 'Failed to read file.'];
        }

        $firstLower = array_map(fn ($item) => mb_strtolower(trim((string) $item)), $firstLine);
        $hasHeader = isset($firstLower[0], $firstLower[1])
            && $firstLower[0] === 'регион'
            && $firstLower[1] === 'город';

        if ($hasHeader) {
            $headers = ['регион', 'город'];
            $rows = collect();
        } else {
            // Файл без строки заголовка: две колонки «Регион;Город»
            $headers = ['регион', 'город'];
            $rows = collect();
            $rows->push([
                'регион' => isset($firstLine[0]) ? trim((string) $firstLine[0]) : '',
                'город' => isset($firstLine[1]) ? trim((string) $firstLine[1]) : '',
            ]);
        }

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $items = [];
            foreach ($headers as $columnIndex => $columnName) {
                $items[$columnName] = isset($row[$columnIndex]) ? trim((string) $row[$columnIndex]) : '';
            }

            $rows->push($items);
        }

        fclose($handle);

        return [$headers, $rows];
    }

    protected function validateCityRow(array $row): array
    {
        $issues = [];

        $region = trim((string) ($row['регион'] ?? ''));
        $name = trim((string) ($row['город'] ?? ''));

        if ($region === '') {
            $issues[] = 'Missing region';
        }

        if ($name === '') {
            $issues[] = 'Missing city name';
        }

        if ($region !== '' && $name !== '') {
            $exists = City::where('name', $name)->where('region', $region)->exists();
            if ($exists) {
                $issues[] = 'City already exists';
            }
        }

        $nameGenitive = '';
        $namePrepositional = '';
        $population = null;

        if ($name !== '') {
            // Для стабильного массового импорта используем локальную эвристику (внешний Morpher часто недоступен).
            [$nameGenitive, $namePrepositional] = $this->guessCityCases($name);
            if ($nameGenitive === '' || $namePrepositional === '') {
                $issues[] = 'Failed to get declensions';
            }

            // Население можно дозаполнить отдельным прогоном, чтобы не блокировать импорт на внешних API.
            $population = null;
        }

        $valid = count($issues) === 0;

        return [
            'valid' => $valid,
            'issues' => $issues,
            'data' => [
                'name' => $name,
                'name_genitive' => $nameGenitive,
                'name_prepositional' => $namePrepositional,
                'region' => $region,
                'population' => $population,
            ],
        ];
    }

    // Morpher intentionally not used: unstable from this environment.

    protected function getPopulationFromWikipedia(string $name, string $region): ?int
    {
        sleep(1);
        try {
            $title = $name;
            if (str_contains($region, 'область') || str_contains($region, 'край') || str_contains($region, 'Республика')) {
                $title .= ' ('.$region.')';
            }

            // 1) Быстрый вариант: summary (иногда extract содержит население)
            $response = $this->http()->get('https://ru.wikipedia.org/api/rest_v1/page/summary/'.urlencode($title));

            if ($response->successful()) {
                $data = $response->json();
                $extract = $data['extract'] ?? '';

                if (preg_match('/население.*?(\d{1,3}(?:[ \.,]\d{3})*)/ui', $extract, $matches)) {
                    $popStr = str_replace([' ', '.', ','], '', $matches[1]);

                    return (int) $popStr;
                }
            }

            // 1.5) Парсим wikitext инфобокса через API parse — чаще всего там есть |население=
            $popFromWikitext = $this->getPopulationFromWikipediaWikitext($title)
                ?? $this->getPopulationFromWikipediaWikitext($name);
            if ($popFromWikitext !== null) {
                return $popFromWikitext;
            }

            // 2) Надежнее: Wikipedia API → Wikidata item → population (P1082)
            $wikidataId = $this->getWikidataItemId($title) ?? $this->getWikidataItemId($name);
            if ($wikidataId !== null) {
                $population = $this->getPopulationFromWikidata($wikidataId);
                if ($population !== null) {
                    return $population;
                }
            }
        } catch (\Exception $e) {
            //
        }

        return null;
    }

    private function getPopulationFromWikipediaWikitext(string $title): ?int
    {
        $title = trim($title);
        if ($title === '') {
            return null;
        }

        $response = $this->http()->get('https://ru.wikipedia.org/w/api.php', [
            'action' => 'parse',
            'format' => 'json',
            'prop' => 'wikitext',
            'redirects' => 1,
            'page' => $title,
            'section' => 0,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();
        $wikitext = $json['parse']['wikitext']['*'] ?? null;
        if (! is_string($wikitext) || trim($wikitext) === '') {
            return null;
        }

        // Ищем "население" в карточке: |население = 12 345
        if (preg_match('/\|\s*население\s*=\s*([0-9][0-9 \.\,]*)/ui', $wikitext, $m) === 1) {
            $popStr = str_replace([' ', '.', ','], '', $m[1]);
            if (is_numeric($popStr)) {
                return (int) $popStr;
            }
        }

        // Иногда "население_перепись" или "население_2021" и т.п.
        if (preg_match('/\|\s*население[^\=]{0,40}\=\s*([0-9][0-9 \.\,]*)/ui', $wikitext, $m) === 1) {
            $popStr = str_replace([' ', '.', ','], '', $m[1]);
            if (is_numeric($popStr)) {
                return (int) $popStr;
            }
        }

        return null;
    }

    private function getWikidataItemId(string $title): ?string
    {
        $title = trim($title);
        if ($title === '') {
            return null;
        }

        $response = $this->http()->get('https://ru.wikipedia.org/w/api.php', [
            'action' => 'query',
            'format' => 'json',
            'prop' => 'pageprops',
            'ppprop' => 'wikibase_item',
            'redirects' => 1,
            'titles' => $title,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();
        $pages = $json['query']['pages'] ?? null;
        if (! is_array($pages)) {
            return null;
        }

        foreach ($pages as $page) {
            if (! is_array($page)) {
                continue;
            }
            $item = $page['pageprops']['wikibase_item'] ?? null;
            if (is_string($item) && preg_match('/^Q\d+$/', $item)) {
                return $item;
            }
        }

        return null;
    }

    private function getPopulationFromWikidata(string $qid): ?int
    {
        $qid = trim($qid);
        if ($qid === '' || ! preg_match('/^Q\d+$/', $qid)) {
            return null;
        }

        $response = $this->http()->get('https://www.wikidata.org/wiki/Special:EntityData/'.$qid.'.json');

        if (! $response->successful()) {
            return null;
        }

        $json = $response->json();
        $entity = $json['entities'][$qid] ?? null;
        if (! is_array($entity)) {
            return null;
        }

        $claims = $entity['claims']['P1082'] ?? null;
        if (! is_array($claims) || $claims === []) {
            return null;
        }

        // Берём максимум из числовых значений (часто есть разные годы, берем самый большой как "текущее"/макс).
        $values = [];
        foreach ($claims as $claim) {
            $amount = $claim['mainsnak']['datavalue']['value']['amount'] ?? null;
            if (! is_string($amount)) {
                continue;
            }
            $amount = ltrim($amount, '+');
            if (! is_numeric($amount)) {
                continue;
            }
            $values[] = (int) round((float) $amount);
        }

        if ($values === []) {
            return null;
        }

        return max($values);
    }

    /**
     * Локальная эвристика для заполнения родительного и предложного падежей города.
     * Не идеальна, но позволяет корректно заполнить большинство русских топонимов.
     *
     * @return array{0: string, 1: string}
     */
    private function guessCityCases(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['', ''];
        }

        // Обрабатываем конструкции вида "Ростов-на-Дону"
        if (preg_match('/^(.+?)-(на|во|в)-(.+)$/ui', $name, $m) === 1) {
            $left = trim((string) $m[1]);
            $mid = (string) $m[2];
            $right = trim((string) $m[3]);
            [$gLeft, $pLeft] = $this->guessCityCases($left);

            // правую часть часто не склоняют (Дону), оставляем как есть
            return [$gLeft.'-'.$mid.'-'.$right, $pLeft.'-'.$mid.'-'.$right];
        }

        // Разбиваем по пробелам и склоняем каждое слово, кроме предлогов.
        $parts = preg_split('/\s+/u', $name) ?: [$name];
        $genParts = [];
        $prepParts = [];

        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }
            $lower = mb_strtolower($part);
            if (in_array($lower, ['в', 'во', 'на', 'и', 'по', 'де', 'у'], true)) {
                $genParts[] = $part;
                $prepParts[] = $part;

                continue;
            }

            // Городские прилагательные в составе ("Александровск-Сахалинский")
            $hyphenParts = explode('-', $part);
            $genHyphen = [];
            $prepHyphen = [];
            foreach ($hyphenParts as $hp) {
                $hp = trim($hp);
                if ($hp === '') {
                    continue;
                }
                [$g, $p] = $this->guessWordCases($hp);
                $genHyphen[] = $g;
                $prepHyphen[] = $p;
            }
            $genParts[] = implode('-', $genHyphen);
            $prepParts[] = implode('-', $prepHyphen);
        }

        return [implode(' ', $genParts), implode(' ', $prepParts)];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function guessWordCases(string $word): array
    {
        $w = trim($word);
        if ($w === '') {
            return ['', ''];
        }

        $lw = mb_strtolower($w);

        // прилагательные
        foreach (['ский', 'цкий'] as $suffix) {
            if (mb_substr($lw, -mb_strlen($suffix)) === $suffix) {
                $base = mb_substr($w, 0, mb_strlen($w) - mb_strlen($suffix));

                return [$base.$suffix[0].'ого', $base.$suffix[0].'ом']; // "ского/ском"
            }
        }
        foreach (['ый', 'ой'] as $suffix) {
            if (mb_substr($lw, -2) === $suffix) {
                $base = mb_substr($w, 0, mb_strlen($w) - 2);

                return [$base.'ого', $base.'ом'];
            }
        }
        if (mb_substr($lw, -2) === 'ий') {
            $base = mb_substr($w, 0, mb_strlen($w) - 2);

            return [$base.'его', $base.'ем'];
        }

        $last = mb_substr($w, -1);
        $prev = mb_substr($w, -2, 1);
        $prevLower = mb_strtolower($prev);

        $hushers = ['г', 'к', 'х', 'ж', 'ч', 'ш', 'щ', 'ц'];

        if ($last === 'а' || $last === 'А') {
            $base = mb_substr($w, 0, mb_strlen($w) - 1);
            $genEnd = in_array($prevLower, $hushers, true) ? 'и' : 'ы';

            return [$base.$genEnd, $base.'е'];
        }

        if ($last === 'я' || $last === 'Я') {
            $base = mb_substr($w, 0, mb_strlen($w) - 1);

            return [$base.'и', $base.'е'];
        }

        if ($last === 'ь' || $last === 'Ь') {
            $base = mb_substr($w, 0, mb_strlen($w) - 1);

            // для городов чаще "Твери/Твери"
            return [$base.'и', $base.'и'];
        }

        if ($last === 'й' || $last === 'Й') {
            $base = mb_substr($w, 0, mb_strlen($w) - 1);

            return [$base.'я', $base.'е'];
        }

        if ($last === 'о' || $last === 'О') {
            $base = mb_substr($w, 0, mb_strlen($w) - 1);

            return [$base.'а', $base.'е'];
        }

        if ($last === 'е' || $last === 'Е') {
            // часто не склоняется (например, "Море"), но для городов обычно склоняют: "Ровно" не сюда.
            return [$w, $w];
        }

        // согласная: Минск -> Минска, Омск -> Омска
        return [$w.'а', $w.'е'];
    }
}
