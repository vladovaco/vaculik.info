<?php

declare(strict_types=1);

namespace Modules\Core\Dashboard;

/**
 * One card on the "Dnes" dashboard. Cards are sorted by urgency, then sortOrder.
 */
final class Card
{
    public const URGENCY_DANGER  = 'danger';   // overdue payment, missed medication
    public const URGENCY_WARNING = 'warning';  // due today, action required
    public const URGENCY_INFO    = 'info';     // today's schedule, messages
    public const URGENCY_OK      = 'ok';       // all good, nice-to-know

    private const ORDER = [
        self::URGENCY_DANGER  => 0,
        self::URGENCY_WARNING => 1,
        self::URGENCY_INFO    => 2,
        self::URGENCY_OK      => 3,
    ];

    public function __construct(
        public readonly string $title,
        public readonly string $body = '',
        public readonly string $icon = 'info',
        public readonly ?string $url = null,
        public readonly string $urgency = self::URGENCY_INFO,
        public readonly int $sortOrder = 100,
        public readonly ?string $actionLabel = null,
    ) {
    }

    public function urgencyRank(): int
    {
        return self::ORDER[$this->urgency] ?? 9;
    }

    /**
     * @param list<Card> $cards
     *
     * @return list<Card>
     */
    public static function sort(array $cards): array
    {
        usort($cards, static fn (Card $a, Card $b): int => [$a->urgencyRank(), $a->sortOrder] <=> [$b->urgencyRank(), $b->sortOrder]);

        return $cards;
    }
}
