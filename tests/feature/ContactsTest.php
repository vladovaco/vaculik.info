<?php

declare(strict_types=1);

namespace Tests\Feature;

use Modules\Contacts\Models\ContactModel;
use Tests\Support\FamilyTestCase;

/**
 * @internal
 */
final class ContactsTest extends FamilyTestCase
{
    public function testAdultCreatesAndSearchesContacts(): void
    {
        $this->loginAs('adult');

        $this->withSession()
            ->post('kontakty', $this->csrf() + ['name' => 'MUDr. Nováková', 'kind' => 'lekar', 'phone' => '+421 900 123 456', 'email' => 'novakova@example.com'])
            ->assertRedirectTo(site_url('kontakty'));

        $contact = model(ContactModel::class)->where('name', 'MUDr. Nováková')->first();
        $this->assertSame($this->householdId, $contact->household_id);
        $this->assertSame('tel:+421900123456', $contact->telHref());

        $result = $this->get('kontakty?q=Nov');
        $result->assertStatus(200);
        $result->assertSee('MUDr. Nováková');

        $result = $this->get('kontakty?q=xyz');
        $result->assertStatus(200);
        $result->assertDontSee('MUDr. Nováková');
    }

    public function testInvalidEmailIsRejected(): void
    {
        $this->loginAs('adult');

        $result = $this->withSession()->post('kontakty', $this->csrf() + ['name' => 'X', 'kind' => 'iny', 'email' => 'nie-email']);
        $result->assertRedirect();
        $result->assertSessionHas('errors');
        $this->assertSame(0, model(ContactModel::class)->countAllResults());
    }

    public function testGuestCanViewButNotCreate(): void
    {
        $this->loginAs('guest');
        $this->get('kontakty')->assertStatus(200);
        $this->get('kontakty/novy')->assertRedirect();
    }
}
