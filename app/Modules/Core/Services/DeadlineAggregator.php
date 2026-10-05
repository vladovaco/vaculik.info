<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use CodeIgniter\I18n\Time;
use Modules\Core\Contracts\DeadlineProvider;
use Modules\Core\Deadlines\Deadline;

/**
 * Collects deadlines from every provider registered in Config\Family.
 */
final class DeadlineAggregator
{
    /**
     * @return list<Deadline>
     */
    public function between(int $householdId, Time $from, Time $to): array
    {
        $all = [];
        foreach (config('Family')->deadlineProviders as $class) {
            $provider = new $class();
            if ($provider instanceof DeadlineProvider) {
                array_push($all, ...$provider->deadlines($householdId, $from, $to));
            }
        }

        return Deadline::sort($all);
    }
}
