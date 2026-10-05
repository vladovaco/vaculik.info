<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\UserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Modules\Household\Models\HouseholdModel;
use Modules\Household\Models\PersonModel;

/**
 * Base for feature tests: fresh in-memory database with every migration,
 * helpers to create a household, persons and logged-in users.
 */
abstract class FamilyTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use AuthenticationTesting;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;

    protected int $householdId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->householdId = (int) model(HouseholdModel::class)->insert(['name' => 'Testovacia rodina']);
    }

    protected function makePerson(string $firstName, string $role = 'adult', array $extra = []): int
    {
        return (int) model(PersonModel::class)->insert($extra + [
            'household_id' => $this->householdId,
            'first_name'   => $firstName,
            'role'         => $role,
            'color'        => '#2563eb',
        ]);
    }

    protected function makeUser(string $group, ?int $personId = null): User
    {
        $personId ??= $this->makePerson(ucfirst($group), $group === 'child' ? 'child' : 'adult');

        $users = model(UserModel::class);
        $user  = new User(['username' => $group . $personId, 'email' => "{$group}{$personId}@example.com", 'password' => 'secret-password']);
        $users->save($user);
        $user = $users->findById($users->getInsertID());
        $user->fill(['person_id' => $personId]);
        $users->save($user);
        $user->addGroup($group);
        $user->activate();

        return $users->findById($user->id);
    }

    protected function loginAs(string $group = 'admin'): User
    {
        $user = $this->makeUser($group);
        $this->actingAs($user);

        return $user;
    }

    /**
     * @return array<string, string>
     */
    protected function csrf(): array
    {
        return [csrf_token() => csrf_hash()];
    }
}
