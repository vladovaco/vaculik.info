<?php

declare(strict_types=1);

namespace Modules\Core\Deadlines;

use CodeIgniter\I18n\Time;

/**
 * One upcoming (or overdue) thing with a date. Produced by modules, consumed by
 * the Deadlines page, dashboard and reminders.
 */
final class Deadline
{
    public function __construct(
        public readonly string $ref,        // stable id, e.g. "payment:12" – used for reminder de-duplication
        public readonly string $kind,       // payment | document | event | ...
        public readonly string $title,
        public readonly Time $dueAt,
        public readonly ?string $url = null,
        public readonly ?int $personId = null,
        public readonly string $detail = '',
        public readonly string $icon = 'alert',
    ) {
    }

    public function daysLeft(?Time $today = null): int
    {
        $today = ($today ?? Time::now())->setTime(0, 0);

        return (int) $today->difference($this->dueAt->setTime(0, 0))->getDays();
    }

    /**
     * @param list<Deadline> $deadlines
     *
     * @return list<Deadline>
     */
    public static function sort(array $deadlines): array
    {
        usort($deadlines, static fn (Deadline $a, Deadline $b): int => $a->dueAt <=> $b->dueAt);

        return $deadlines;
    }
}
