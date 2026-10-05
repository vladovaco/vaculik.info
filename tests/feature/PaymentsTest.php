<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\I18n\Time;
use Modules\Finance\Dashboard\FinanceCards;
use Modules\Finance\Entities\Payment;
use Modules\Finance\Models\PaymentModel;
use Modules\Finance\Services\PayBySquare;
use Tests\Support\FamilyTestCase;

/**
 * @internal
 */
final class PaymentsTest extends FamilyTestCase
{
    private const IBAN = 'SK9509000000000123456789';

    public function testCreatePaymentNormalisesAmountAndIban(): void
    {
        $this->loginAs('adult');

        $result = $this->withSession()->post('peniaze', $this->csrf() + [
            'title' => 'Školné', 'amount' => '45,50', 'due_at' => '2030-01-15', 'category' => 'skolka',
            'recurrence' => 'monthly', 'iban' => 'sk95 0900 0000 0001 2345 6789', 'variable_symbol' => '2030',
        ]);
        $result->assertRedirect();

        $payment = model(PaymentModel::class)->first();
        $this->assertSame(45.5, (float) $payment->amount);
        $this->assertSame(self::IBAN, $payment->iban);
        $this->assertSame('45,50 €', $payment->amountFormatted());
    }

    public function testMarkingRecurringPaymentPaidCreatesNextOccurrence(): void
    {
        $this->loginAs('adult');
        $payments = model(PaymentModel::class);
        $id       = $payments->insert([
            'household_id' => $this->householdId, 'title' => 'Obedy', 'amount' => '30.00', 'due_at' => '2030-01-31',
            'category' => 'strava', 'recurrence' => 'monthly',
        ]);

        $this->withSession()->post("peniaze/{$id}/zaplatit", $this->csrf())->assertRedirectTo(site_url('peniaze'));

        $this->assertNotNull($payments->find($id)->paid_at);
        $next = $payments->unpaid($this->householdId);
        $this->assertCount(1, $next);
        $this->assertSame('2030-02-28', $next[0]->due_at->format('Y-m-d'));
        $this->assertSame('Obedy', $next[0]->title);
    }

    public function testRecurrenceClampsToMonthEnd(): void
    {
        $jan31 = new Payment(['due_at' => '2030-01-31', 'recurrence' => 'monthly']);
        $this->assertSame('2030-02-28', $jan31->nextDueAt()->format('Y-m-d'));
        $this->assertSame('2030-04-30', Payment::addMonthsClamped(Time::parse('2030-01-31'), 3)->format('Y-m-d'));
        $this->assertSame('2031-12-15', Payment::addMonthsClamped(Time::parse('2030-12-15'), 12)->format('Y-m-d'));
        $this->assertSame('2031-01-15', Payment::addMonthsClamped(Time::parse('2030-12-15'), 1)->format('Y-m-d'));
    }

    public function testOneOffPaymentDoesNotRepeat(): void
    {
        $this->loginAs('adult');
        $payments = model(PaymentModel::class);
        $id       = $payments->insert(['household_id' => $this->householdId, 'title' => 'Výlet', 'amount' => '12.00', 'due_at' => '2030-03-01', 'category' => 'skola', 'recurrence' => 'none']);

        $this->withSession()->post("peniaze/{$id}/zaplatit", $this->csrf());
        $this->assertCount(0, $payments->unpaid($this->householdId));
    }

    public function testPayBySquareProducesQrSvg(): void
    {
        if (trim((string) shell_exec('command -v xz')) === '') {
            $this->markTestSkipped('xz binary not available');
        }
        $this->loginAs('adult');
        $id = model(PaymentModel::class)->insert([
            'household_id' => $this->householdId, 'title' => 'Školné', 'amount' => '45.50', 'due_at' => '2030-01-15',
            'category' => 'skolka', 'recurrence' => 'none', 'iban' => self::IBAN, 'variable_symbol' => '2030', 'payee' => 'MŠ Lienka',
        ]);

        $payload = (new PayBySquare())->payload(model(PaymentModel::class)->find($id));
        $this->assertNotNull($payload);
        $this->assertStringStartsWith('000', $payload);

        $result = $this->get("peniaze/{$id}/qr.svg");
        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8');
        $this->assertStringContainsString('<svg', $result->response()->getBody());
    }

    public function testPaymentWithoutIbanHasNoQr(): void
    {
        $payment = new Payment(['title' => 'Hotovosť', 'amount' => '5.00', 'due_at' => '2030-01-01', 'currency' => 'EUR']);
        $this->assertNull((new PayBySquare())->payload($payment));
    }

    public function testDashboardShowsOverdueAndUpcoming(): void
    {
        $user  = $this->loginAs('adult');
        $today = Time::parse('2030-06-10');
        model(PaymentModel::class)->insert(['household_id' => $this->householdId, 'title' => 'Stará', 'amount' => '10.00', 'due_at' => '2030-06-01', 'category' => 'iny', 'recurrence' => 'none']);
        model(PaymentModel::class)->insert(['household_id' => $this->householdId, 'title' => 'Blízka', 'amount' => '20.00', 'due_at' => '2030-06-12', 'category' => 'iny', 'recurrence' => 'none']);
        model(PaymentModel::class)->insert(['household_id' => $this->householdId, 'title' => 'Ďaleká', 'amount' => '30.00', 'due_at' => '2030-09-01', 'category' => 'iny', 'recurrence' => 'none']);

        $cards = (new FinanceCards())->dashboardCards($user, $today);
        $this->assertCount(2, $cards);
        $this->assertSame('danger', $cards[0]->urgency);
        $this->assertStringContainsString('Stará', $cards[0]->title);
        $this->assertSame('warning', $cards[1]->urgency);
        $this->assertStringContainsString('Blízka', $cards[1]->title);
    }

    public function testChildCannotSeeFinance(): void
    {
        $this->loginAs('child');
        $this->get('peniaze')->assertRedirect();
    }
}
