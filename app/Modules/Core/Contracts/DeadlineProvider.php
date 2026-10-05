<?php

declare(strict_types=1);

namespace Modules\Core\Contracts;

use CodeIgniter\I18n\Time;

/**
 * A module that produces deadlines (STK, due payments, expiring documents, ...)
 * exposes them through this contract so the Deadlines module can list and remind.
 */
interface DeadlineProvider
{
    /**
     * @return list<array{title: string, due_at: Time, url: string, person_id: ?int, kind: string}>
     */
    public function upcomingDeadlines(Time $from, Time $to): array;
}
