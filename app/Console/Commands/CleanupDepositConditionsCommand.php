<?php

namespace App\Console\Commands;

use App\Models\DepositCondition;
use Illuminate\Console\Command;

class CleanupDepositConditionsCommand extends Command
{
    protected $signature = 'deposits:cleanup-conditions
                            {--dry-run : Only show what would be deleted}';

    protected $description = 'Remove invalid deposit conditions (amount_min=amount_max, or placeholder rows from failed normalization)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $query = DepositCondition::query()
            ->where(function ($q) {
                $q->whereRaw('amount_min IS NOT NULL AND amount_max IS NOT NULL AND amount_min = amount_max')
                    ->orWhere(function ($q2) {
                        $q2->where('is_active', false)
                            ->where('rate', 0)
                            ->where('term_days_min', 1)
                            ->whereNull('term_days_max');
                    });
            });

        $count = $query->count();
        if ($count === 0) {
            $this->info('No invalid conditions found.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("Would delete {$count} condition(s). Run without --dry-run to delete.");
            return self::SUCCESS;
        }

        $query->delete();
        $this->info("Deleted {$count} invalid condition(s).");
        return self::SUCCESS;
    }
}
