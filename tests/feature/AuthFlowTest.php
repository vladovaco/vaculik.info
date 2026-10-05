<?php

declare(strict_types=1);

namespace Tests\Feature;

use Modules\Household\Models\PersonModel;
use Tests\Support\FamilyTestCase;

/**
 * @internal
 */
final class AuthFlowTest extends FamilyTestCase
{
    public function testHealthEndpointIsPublic(): void
    {
        $result = $this->get('up');
        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'ok']);
    }

    public function testDashboardRedirectsAnonymousToLogin(): void
    {
        $this->get('/')->assertRedirectTo(site_url('login'));
    }

    public function testLoginPageRendersInSlovak(): void
    {
        $result = $this->get('login');
        $result->assertStatus(200);
        $result->assertSee('Prihlásenie');
    }

    public function testUserIsLinkedToPerson(): void
    {
        $user = $this->makeUser('admin');
        $this->assertNotNull($user->person_id);
        $this->assertSame('Admin', model(PersonModel::class)->find($user->person_id)->first_name);
    }

    public function testAdminSeesDashboardAndCanManagePersons(): void
    {
        $this->loginAs('admin');

        $result = $this->get('/');
        $result->assertStatus(200);
        $result->assertSee('Dnes');

        $this->withSession()
            ->post('osoby', $this->csrf() + ['first_name' => 'Ema', 'role' => 'child', 'color' => '#ff0000', 'birth_date' => '2016-05-01'])
            ->assertRedirectTo(site_url('osoby'));

        $this->assertSame(1, model(PersonModel::class)->where('first_name', 'Ema')->countAllResults());
        $result = $this->get('osoby');
        $result->assertStatus(200);
        $result->assertSee('Ema');
    }

    public function testChildCannotManagePersons(): void
    {
        $this->loginAs('child');

        // Shield's permission filter redirects browsers back with an error instead of a bare 403.
        $result = $this->get('osoby/nova');
        $result->assertRedirect();
        $result->assertSessionHas('error');
    }
}
