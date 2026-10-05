<?php

declare(strict_types=1);

namespace Modules\Finance\Deadlines;

use CodeIgniter\I18n\Time;
use Modules\Core\Contracts\DeadlineProvider;
use Modules\Core\Deadlines\Deadline;
use Modules\Finance\Models\PaymentModel;

final class PaymentDeadlines implements DeadlineProvider
{
    public function deadlines(int $householdId, Time $from, Time $to): array
    {
        $out = [];
        foreach (model(PaymentModel::class)->unpaidDueBetween($householdId, $from, $to) as $payment) {
            $out[] = new Deadline(
                ref: 'payment:' . $payment->id,
                kind: 'payment',
                title: 'Zaplatiť: ' . $payment->title,
                dueAt: $payment->due_at,
                url: url_to('payments.show', $payment->id),
                personId: $payment->person_id,
                detail: $payment->amountFormatted() . ($payment->payee ? ' · ' . $payment->payee : ''),
                icon: 'wallet',
            );
        }

        return $out;
    }
}
