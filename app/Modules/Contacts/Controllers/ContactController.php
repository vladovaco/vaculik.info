<?php

declare(strict_types=1);

namespace Modules\Contacts\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Contacts\Entities\Contact;
use Modules\Contacts\Models\ContactModel;
use Modules\Household\Models\PersonModel;

class ContactController extends BaseController
{
    private ContactModel $contacts;

    public function __construct()
    {
        $this->contacts = model(ContactModel::class);
    }

    public function index(): string
    {
        $query = trim((string) $this->request->getGet('q'));
        $kind  = (string) $this->request->getGet('druh');

        return view('Modules\Contacts\Views\index', [
            'title'    => 'Kontakty',
            'contacts' => $this->contacts->search($this->householdId(), $query, $kind),
            'query'    => $query,
            'kind'     => $kind,
        ]);
    }

    public function show(int $id): string
    {
        $contact = $this->findOrFail($id);

        return view('Modules\Contacts\Views\show', [
            'title'   => $contact->name,
            'contact' => $contact,
            'person'  => $contact->person_id ? model(PersonModel::class)->find($contact->person_id) : null,
        ]);
    }

    public function new(): string
    {
        return $this->form(new Contact(['kind' => 'iny']), url_to('contacts.create'), 'Nový kontakt');
    }

    public function create(): RedirectResponse
    {
        $data                 = $this->formData();
        $data['household_id'] = $this->householdId();

        if (! $this->contacts->insert($data)) {
            return redirect()->back()->withInput()->with('errors', $this->contacts->errors());
        }

        return redirect()->route('contacts')->with('message', 'Kontakt bol pridaný.');
    }

    public function edit(int $id): string
    {
        $contact = $this->findOrFail($id);

        return $this->form($contact, url_to('contacts.update', $id), 'Upraviť kontakt');
    }

    public function update(int $id): RedirectResponse
    {
        $contact = $this->findOrFail($id);

        if (! $this->contacts->update($contact->id, $this->formData())) {
            return redirect()->back()->withInput()->with('errors', $this->contacts->errors());
        }

        return redirect()->route('contacts.show', [$contact->id])->with('message', 'Zmeny boli uložené.');
    }

    public function delete(int $id): RedirectResponse
    {
        $this->contacts->delete($this->findOrFail($id)->id);

        return redirect()->route('contacts')->with('message', 'Kontakt bol odstránený.');
    }

    private function form(Contact $contact, string $action, string $title): string
    {
        return view('Modules\Contacts\Views\form', [
            'title'   => $title,
            'contact' => $contact,
            'action'  => $action,
            'persons' => model(PersonModel::class)->forHousehold($this->householdId()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $post  = $this->request->getPost();
        $clean = static fn (string $key): ?string => trim((string) ($post[$key] ?? '')) ?: null;

        return [
            'name'         => trim((string) ($post['name'] ?? '')),
            'organization' => $clean('organization'),
            'kind'         => (string) ($post['kind'] ?? 'iny'),
            'phone'        => $clean('phone'),
            'phone2'       => $clean('phone2'),
            'email'        => $clean('email'),
            'address'      => $clean('address'),
            'web'          => $clean('web'),
            'note'         => $clean('note'),
            'person_id'    => ($post['person_id'] ?? '') !== '' ? (int) $post['person_id'] : null,
        ];
    }

    private function householdId(): int
    {
        return (int) service('householdContext')->householdId();
    }

    private function findOrFail(int $id): Contact
    {
        $contact = $this->contacts->find($id);
        if ($contact === null || $contact->household_id !== $this->householdId()) {
            throw PageNotFoundException::forPageNotFound('Kontakt neexistuje.');
        }

        return $contact;
    }
}
