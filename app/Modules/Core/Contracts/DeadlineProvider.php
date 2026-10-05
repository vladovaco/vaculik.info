<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use CodeIgniter\I18n\Time;
use Modules\Core\Deadlines\Deadline;

/**
 * A module that produces deadlines (due payments, expiring documents, ...)
 * exposes them through this contract so the Deadlines module can list them,
 * the dashboard can show them and the reminder dispatcher can notify about them.
 */
interface DeadlineProvider
{
    /**
     * Deadlines of the given household whose due date falls in [$from, $to].
     *
     * @return list<Deadline>
     */
    public function deadlines(int $householdId, Time $from, Time $to): array;
}
