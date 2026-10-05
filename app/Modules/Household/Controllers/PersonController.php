<?php

declare(strict_types=1);

namespace Modules\Household\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Household\Entities\Person;
use Modules\Household\Models\PersonModel;

class PersonController extends BaseController
{
    private PersonModel $persons;

    public function __construct()
    {
        $this->persons = model(PersonModel::class);
    }

    public function index(): string
    {
        $householdId = service('householdContext')->householdId();

        return view('Modules\Household\Views\persons\index', [
            'title'   => 'Osoby',
            'persons' => $householdId !== null ? $this->persons->forHousehold($householdId) : [],
        ]);
    }

    public function new(): string
    {
        return view('Modules\Household\Views\persons\form', [
            'title'  => 'Nová osoba',
            'person' => new Person(['role' => Person::ROLE_CHILD, 'color' => '#2563eb', 'sort_order' => 100]),
            'action' => url_to('persons.create'),
        ]);
    }

    public function create(): RedirectResponse
    {
        $data                 = $this->formData();
        $data['household_id'] = service('householdContext')->householdId();

        if (! $this->persons->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $this->persons->errors());
        }

        return redirect()->route('persons')->with('message', 'Osoba bola pridaná.');
    }

    public function edit(int $id): string
    {
        return view('Modules\Household\Views\persons\form', [
            'title'  => 'Upraviť osobu',
            'person' => $this->findOrFail($id),
            'action' => url_to('persons.update', $id),
        ]);
    }

    public function update(int $id): RedirectResponse
    {
        $person = $this->findOrFail($id);

        if (! $this->persons->update($person->id, $this->formData())) {
            return redirect()->back()->withInput()->with('errors', $this->persons->errors());
        }

        return redirect()->route('persons')->with('message', 'Zmeny boli uložené.');
    }

    public function delete(int $id): RedirectResponse
    {
        $person = $this->findOrFail($id);
        $this->persons->delete($person->id);

        return redirect()->route('persons')->with('message', 'Osoba bola odstránená.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $post = $this->request->getPost(['first_name', 'last_name', 'nickname', 'role', 'birth_date', 'color', 'sort_order']);

        return [
            'first_name' => trim((string) ($post['first_name'] ?? '')),
            'last_name'  => trim((string) ($post['last_name'] ?? '')) ?: null,
            'nickname'   => trim((string) ($post['nickname'] ?? '')) ?: null,
            'role'       => (string) ($post['role'] ?? Person::ROLE_CHILD),
            'birth_date' => ($post['birth_date'] ?? '') !== '' ? (string) $post['birth_date'] : null,
            'color'      => (string) ($post['color'] ?? '#2563eb'),
            'sort_order' => (int) ($post['sort_order'] ?? 100),
        ];
    }

    private function findOrFail(int $id): Person
    {
        $person = $this->persons->find($id);
        if ($person === null || $person->household_id !== service('householdContext')->householdId()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Osoba neexistuje.');
        }

        return $person;
    }
}
