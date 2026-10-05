<?php

declare(strict_types=1);

namespace Modules\Finance\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;
use Modules\Finance\Entities\Payment;
use Modules\Finance\Models\PaymentModel;
use Modules\Finance\Services\PayBySquare;
use Modules\Household\Models\PersonModel;

class PaymentController extends BaseController
{
    private PaymentModel $payments;

    public function __construct()
    {
        $this->payments = model(PaymentModel::class);
    }

    public function index(): string
    {
        $tab    = $this->request->getGet('zalozka') === 'zaplatene' ? 'zaplatene' : 'cakajuce';
        $today  = Time::now();
        $unpaid = $this->payments->unpaid($this->householdId());

        $groups = ['overdue' => [], 'week' => [], 'later' => []];
        foreach ($unpaid as $p) {
            $days = $p->daysLeft($today);
            $groups[$days < 0 ? 'overdue' : ($days <= 7 ? 'week' : 'later')][] = $p;
        }

        return view('Modules\Finance\Views\index', [
            'title'   => 'Peniaze',
            'tab'     => $tab,
            'groups'  => $groups,
            'paid'    => $tab === 'zaplatene' ? $this->payments->paidSince($this->householdId(), $today->subDays(90)) : [],
            'sums'    => [
                'overdue' => array_sum(array_map(static fn ($p) => (float) $p->amount, $groups['overdue'])),
                'week'    => array_sum(array_map(static fn ($p) => (float) $p->amount, $groups['week'])),
                'later'   => array_sum(array_map(static fn ($p) => (float) $p->amount, $groups['later'])),
            ],
            'persons' => $this->personsById(),
            'today'   => $today,
        ]);
    }

    public function new(): string
    {
        return $this->form(new Payment(['category' => 'iny', 'recurrence' => 'none', 'currency' => 'EUR', 'due_at' => Time::now()->addDays(7)]), url_to('payments.create'), 'Nová platba');
    }

    public function create(): RedirectResponse
    {
        $data                 = $this->formData();
        $data['household_id'] = $this->householdId();

        $id = $this->payments->insert($data);
        if (! $id) {
            return redirect()->back()->withInput()->with('errors', $this->payments->errors());
        }

        return redirect()->route('payments.show', [$id])->with('message', 'Platba bola pridaná.');
    }

    public function show(int $id): string
    {
        $payment = $this->findOrFail($id);

        return view('Modules\Finance\Views\show', [
            'title'   => $payment->title,
            'payment' => $payment,
            'hasQr'   => (new PayBySquare())->payload($payment) !== null,
            'persons' => $this->personsById(),
        ]);
    }

    public function qr(int $id): ResponseInterface
    {
        $svg = (new PayBySquare())->svg($this->findOrFail($id));
        if ($svg === null) {
            throw PageNotFoundException::forPageNotFound('QR kód nie je k dispozícii.');
        }

        return $this->response->setContentType('image/svg+xml')->setHeader('Cache-Control', 'private, max-age=3600')->setBody($svg);
    }

    public function edit(int $id): string
    {
        $payment = $this->findOrFail($id);

        return $this->form($payment, url_to('payments.update', $id), 'Upraviť platbu');
    }

    public function update(int $id): RedirectResponse
    {
        $payment = $this->findOrFail($id);

        if (! $this->payments->update($payment->id, $this->formData())) {
            return redirect()->back()->withInput()->with('errors', $this->payments->errors());
        }

        return redirect()->route('payments.show', [$payment->id])->with('message', 'Zmeny boli uložené.');
    }

    public function pay(int $id): RedirectResponse
    {
        $payment = $this->findOrFail($id);
        if ($payment->isPaid()) {
            return redirect()->route('payments.show', [$payment->id]);
        }

        $next = $this->payments->markPaid($payment, service('householdContext')->person()?->id);
        $msg  = 'Platba je označená ako zaplatená.';
        if ($next !== null) {
            $msg .= ' Ďalšia je splatná ' . $next->due_at->format('j.n.Y') . '.';
        }

        return redirect()->route('finance')->with('message', $msg);
    }

    public function unpay(int $id): RedirectResponse
    {
        $payment = $this->findOrFail($id);
        $this->payments->update($payment->id, ['paid_at' => null, 'paid_by_person_id' => null]);

        return redirect()->route('payments.show', [$payment->id])->with('message', 'Platba je znova medzi čakajúcimi.');
    }

    public function delete(int $id): RedirectResponse
    {
        $this->payments->delete($this->findOrFail($id)->id);

        return redirect()->route('finance')->with('message', 'Platba bola odstránená.');
    }

    private function form(Payment $payment, string $action, string $title): string
    {
        return view('Modules\Finance\Views\form', [
            'title'   => $title,
            'payment' => $payment,
            'action'  => $action,
            'persons' => model(PersonModel::class)->forHousehold($this->householdId()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $post   = $this->request->getPost();
        $clean  = static fn (string $key): ?string => trim((string) ($post[$key] ?? '')) ?: null;
        $amount = str_replace([' ', ','], ['', '.'], (string) ($post['amount'] ?? '0'));

        return [
            'title'           => trim((string) ($post['title'] ?? '')),
            'payee'           => $clean('payee'),
            'iban'            => strtoupper((string) preg_replace('/\s+/', '', (string) ($post['iban'] ?? ''))) ?: null,
            'variable_symbol' => $clean('variable_symbol'),
            'specific_symbol' => $clean('specific_symbol'),
            'constant_symbol' => $clean('constant_symbol'),
            'amount'          => is_numeric($amount) ? number_format((float) $amount, 2, '.', '') : $amount,
            'currency'        => 'EUR',
            'category'        => (string) ($post['category'] ?? 'iny'),
            'recurrence'      => (string) ($post['recurrence'] ?? 'none'),
            'due_at'          => (string) ($post['due_at'] ?? ''),
            'person_id'       => ($post['person_id'] ?? '') !== '' ? (int) $post['person_id'] : null,
            'note'            => $clean('note'),
        ];
    }

    /**
     * @return array<int, \Modules\Household\Entities\Person>
     */
    private function personsById(): array
    {
        $out = [];
        foreach (model(PersonModel::class)->forHousehold($this->householdId()) as $person) {
            $out[$person->id] = $person;
        }

        return $out;
    }

    private function householdId(): int
    {
        return (int) service('householdContext')->householdId();
    }

    private function findOrFail(int $id): Payment
    {
        $payment = $this->payments->find($id);
        if ($payment === null || $payment->household_id !== $this->householdId()) {
            throw PageNotFoundException::forPageNotFound('Platba neexistuje.');
        }

        return $payment;
    }
}
