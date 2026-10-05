<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Modules\Core\Dashboard\Card;

/**
 * @internal
 */
final class CardTest extends CIUnitTestCase
{
    public function testCardsSortByUrgencyThenOrder(): void
    {
        $sorted = Card::sort([
            new Card('later info', urgency: Card::URGENCY_INFO, sortOrder: 50),
            new Card('ok', urgency: Card::URGENCY_OK, sortOrder: 1),
            new Card('danger', urgency: Card::URGENCY_DANGER, sortOrder: 99),
            new Card('early info', urgency: Card::URGENCY_INFO, sortOrder: 10),
        ]);

        $this->assertSame(['danger', 'early info', 'later info', 'ok'], array_map(static fn (Card $c) => $c->title, $sorted));
    }
}
