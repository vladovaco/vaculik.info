<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Modules\Calendar\Models\CalendarSourceModel;
use Modules\Calendar\Services\IcsSync;
use Modules\Notifications\Services\ReminderDispatcher;

/**
 * Periodic housekeeping, run from cron every 5–15 minutes:
 *   (every 10 minutes) cd /path/app && php spark app:tick >> writable/logs/tick.log 2>&1
 *
 * - synchronises external calendars older than 15 minutes
 * - sends deadline reminders and the morning digest
 */
class Tick extends BaseCommand
{
    protected $group       = 'Family';
    protected $name        = 'app:tick';
    protected $description = 'Synchronizuje kalendáre a posiela pripomienky. Spúšťať z cronu.';
    protected $usage       = 'app:tick [--sync-all]';
    protected $options     = ['--sync-all' => 'Synchronizuje všetky kalendáre bez ohľadu na čas poslednej synchronizácie.'];

    public function run(array $params): int
    {
        $lock = fopen(WRITEPATH . 'tick.lock', 'c');
        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
            CLI::write('app:tick už beží.', 'yellow');

            return EXIT_SUCCESS;
        }

        try {
            $this->syncCalendars(array_key_exists('sync-all', $params) || CLI::getOption('sync-all') !== null);
            $created = (new ReminderDispatcher())->run();
            CLI::write("[" . date('Y-m-d H:i:s') . "] Upozornenia: {$created} nových.");
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        return EXIT_SUCCESS;
    }

    private function syncCalendars(bool $all): void
    {
        $sources = model(CalendarSourceModel::class);
        $sync    = new IcsSync();
        $list    = $all ? $sources->where('enabled', 1)->findAll() : $sources->dueForSync(15);

        foreach ($list as $source) {
            try {
                $count = $sync->sync($source);
                CLI::write("[" . date('Y-m-d H:i:s') . "] Kalendár {$source->name}: {$count} udalostí.");
            } catch (\Throwable $e) {
                CLI::error("[" . date('Y-m-d H:i:s') . "] Kalendár {$source->name}: " . $e->getMessage());
            }
        }
    }
}
