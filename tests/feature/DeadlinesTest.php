<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\I18n\Time;
use Modules\Documents\Models\DocumentModel;
use Modules\Finance\Models\PaymentModel;
use Tests\Support\FamilyTestCase;

/**
 * @internal
 */
final class DeadlinesTest extends FamilyTestCase
{
    public function testPageAggregatesPaymentsAndDocumentsByPermission(): void
    {
        model(PaymentModel::class)->insert(['household_id' => $this->householdId, 'title' => 'Školné', 'amount' => '10.00', 'due_at' => Time::now()->addDays(2)->format('Y-m-d'), 'category' => 'iny', 'recurrence' => 'none']);
        model(DocumentModel::class)->insert(['household_id' => $this->householdId, 'title' => 'Pas', 'kind' => 'doklad', 'expires_at' => Time::now()->addDays(20)->format('Y-m-d')]);

        $this->loginAs('adult');
        $result = $this->get('terminy');
        $result->assertStatus(200);
        $result->assertSee('Zaplatiť: Školné');
        $result->assertSee('Končí platnosť: Pas');
    }

    public function testGuestDoesNotSeePayments(): void
    {
        model(PaymentModel::class)->insert(['household_id' => $this->householdId, 'title' => 'Školné', 'amount' => '10.00', 'due_at' => Time::now()->addDays(2)->format('Y-m-d'), 'category' => 'iny', 'recurrence' => 'none']);

        $this->loginAs('guest');
        $result = $this->get('terminy');
        $result->assertStatus(200);
        $result->assertDontSee('Školné');
    }
}
