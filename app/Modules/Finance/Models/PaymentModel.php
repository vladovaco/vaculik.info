<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;
use Modules\Finance\Entities\Payment;

class PaymentModel extends Model
{
    protected $table          = 'payments';
    protected $primaryKey     = 'id';
    protected $returnType     = Payment::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'household_id', 'person_id', 'title', 'payee', 'iban', 'variable_symbol', 'specific_symbol', 'constant_symbol',
        'amount', 'currency', 'category', 'recurrence', 'due_at', 'paid_at', 'paid_by_person_id', 'document_id', 'note',
    ];

    protected $validationRules = [
        'household_id'    => 'required|is_natural_no_zero',
        'title'           => 'required|max_length[160]',
        'amount'          => 'required|decimal|greater_than_equal_to[0]',
        'due_at'          => 'required|valid_date[Y-m-d]',
        'iban'            => 'permit_empty|regex_match[/^[A-Z]{2}[0-9]{2}[A-Z0-9]{11,30}$/]',
        'variable_symbol' => 'permit_empty|numeric|max_length[10]',
        'specific_symbol' => 'permit_empty|numeric|max_length[10]',
        'constant_symbol' => 'permit_empty|numeric|max_length[4]',
        'category'        => 'required|in_list[skola,skolka,strava,kruzok,zdravie,byvanie,auto,poistenie,iny]',
        'recurrence'      => 'required|in_list[none,weekly,monthly,quarterly,yearly]',
    ];

    protected $validationMessages = [
        'title'  => ['required' => 'Názov je povinný.'],
        'amount' => ['required' => 'Suma je povinná.', 'decimal' => 'Suma musí byť číslo.'],
        'due_at' => ['required' => 'Dátum splatnosti je povinný.'],
        'iban'   => ['regex_match' => 'IBAN nemá správny tvar (bez medzier, napr. SK3112000000198742637541).'],
        'variable_symbol' => ['numeric' => 'Variabilný symbol musí byť číslo.'],
    ];

    /**
     * @return list<Payment>
     */
    public function unpaid(int $householdId): array
    {
        return $this->where('household_id', $householdId)->where('paid_at', null)->orderBy('due_at')->findAll();
    }

    /**
     * @return list<Payment>
     */
    public function paidSince(int $householdId, Time $since): array
    {
        return $this->where('household_id', $householdId)
            ->where('paid_at >=', $since->format('Y-m-d'))
            ->orderBy('paid_at', 'DESC')
            ->findAll();
    }

    /**
     * @return list<Payment>
     */
    public function unpaidDueBetween(int $householdId, Time $from, Time $to): array
    {
        return $this->where('household_id', $householdId)
            ->where('paid_at', null)
            ->where('due_at >=', $from->format('Y-m-d'))
            ->where('due_at <=', $to->format('Y-m-d'))
            ->orderBy('due_at')
            ->findAll();
    }

    /**
     * Marks a payment paid; a recurring payment spawns its next occurrence.
     */
    public function markPaid(Payment $payment, ?int $paidByPersonId, ?Time $when = null): ?Payment
    {
        $this->update($payment->id, ['paid_at' => ($when ?? Time::now())->format('Y-m-d'), 'paid_by_person_id' => $paidByPersonId]);

        $next = $payment->nextDueAt();
        if ($next === null) {
            return null;
        }

        $id = $this->insert([
            'household_id'    => $payment->household_id,
            'person_id'       => $payment->person_id,
            'title'           => $payment->title,
            'payee'           => $payment->payee,
            'iban'            => $payment->iban,
            'variable_symbol' => $payment->variable_symbol,
            'specific_symbol' => $payment->specific_symbol,
            'constant_symbol' => $payment->constant_symbol,
            'amount'          => $payment->amount,
            'currency'        => $payment->currency,
            'category'        => $payment->category,
            'recurrence'      => $payment->recurrence,
            'due_at'          => $next->format('Y-m-d'),
            'note'            => $payment->note,
        ]);

        return $this->find($id);
    }
}
