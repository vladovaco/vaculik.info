<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Household\Models\HouseholdModel;
use Modules\Household\Models\PersonModel;

/**
 * @internal
 */
final class AuthFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use AuthenticationTesting;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null; // run migrations from every namespace (Shield, Settings, modules)

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

    public function testAdminSeesDashboardAndCanManagePersons(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin);

        $result = $this->get('/');
        $result->assertStatus(200);
        $result->assertSee('Dnes');

        $this->withSession()
            ->post('osoby', [
                csrf_token()  => csrf_hash(),
                'first_name'  => 'Ema',
                'role'        => 'child',
                'color'       => '#ff0000',
                'birth_date'  => '2016-05-01',
            ])
            ->assertRedirectTo(site_url('osoby'));

        $this->assertSame(1, model(PersonModel::class)->where('first_name', 'Ema')->countAllResults());
        $result = $this->get('osoby');
        $result->assertStatus(200);
        $result->assertSee('Ema');
    }

    public function testChildCannotManagePersons(): void
    {
        $child = $this->makeUser('child');

        // Shield's permission filter redirects browsers back with an error instead of a bare 403.
        $result = $this->actingAs($child)->get('osoby/nova');
        $result->assertRedirect();
        $result->assertSessionHas('error');
    }

    private function makeUser(string $group): User
    {
        $householdId = (int) model(HouseholdModel::class)->insert(['name' => 'Testovacia rodina']);
        $personId    = (int) model(PersonModel::class)->insert([
            'household_id' => $householdId,
            'first_name'   => 'Test',
            'role'         => $group === 'child' ? 'child' : 'adult',
            'color'        => '#2563eb',
        ]);

        $users = model(UserModel::class);
        $user  = new User(['username' => $group, 'email' => "{$group}@example.com", 'password' => 'secret-password']);
        $users->save($user);
        $user = $users->findById($users->getInsertID());
        $user->fill(['person_id' => $personId]);
        $users->save($user);
        $user->addGroup($group);
        $user->activate();

        return $user;
    }
}
