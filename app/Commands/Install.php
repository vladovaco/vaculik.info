<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Shield\Entities\User;
use App\Models\UserModel;
use Modules\Household\Entities\Person;
use Modules\Household\Models\HouseholdModel;
use Modules\Household\Models\PersonModel;

/**
 * First-run setup: creates the household, the first adult person and the admin login.
 * Public registration is disabled, so this is the only way to get the first user.
 *
 *   php spark app:install --household "Vaculíkovci" --name Vlado --email vlado@example.com --password "tajne-heslo"
 */
class Install extends BaseCommand
{
    protected $group       = 'Family';
    protected $name        = 'app:install';
    protected $description = 'Vytvorí domácnosť, prvú osobu a admin účet.';
    protected $usage       = 'app:install [--household NAME] [--name FIRSTNAME] [--email EMAIL] [--password PASSWORD]';
    protected $options     = [
        '--household' => 'Názov domácnosti',
        '--name'      => 'Krstné meno správcu',
        '--email'     => 'E-mail správcu (prihlasovacie meno)',
        '--password'  => 'Heslo správcu (min. 8 znakov)',
    ];

    public function run(array $params): int
    {
        $households = model(HouseholdModel::class);
        $persons    = model(PersonModel::class);
        $users      = model(UserModel::class);

        if ($households->countAllResults() > 0) {
            CLI::error('Domácnosť už existuje. Ďalších používateľov pridajte cez „php spark shield:user create“.');

            return EXIT_ERROR;
        }

        $householdName = $params['household'] ?? CLI::prompt('Názov domácnosti', 'Naša rodina');
        $firstName     = $params['name'] ?? CLI::prompt('Krstné meno správcu', null, 'required');
        $email         = $params['email'] ?? CLI::prompt('E-mail správcu', null, 'required|valid_email');
        $password      = $params['password'] ?? CLI::prompt('Heslo', null, 'required|min_length[8]');

        $db = db_connect();
        $db->transStart();

        $householdId = (int) $households->insert(['name' => $householdName]);
        $personId    = (int) $persons->insert([
            'household_id' => $householdId,
            'first_name'   => $firstName,
            'role'         => Person::ROLE_ADULT,
            'color'        => '#2563eb',
            'sort_order'   => 1,
        ]);

        $user = new User(['username' => null, 'email' => $email, 'password' => $password]);
        $users->save($user);
        $user = $users->findById($users->getInsertID());
        $user->fill(['person_id' => $personId]);
        $users->save($user);
        $user->addGroup('admin');
        $user->activate();

        $db->transComplete();

        if (! $db->transStatus()) {
            CLI::error('Inštalácia zlyhala.');

            return EXIT_ERROR;
        }

        CLI::write("Hotovo. Domácnosť „{$householdName}“, osoba {$firstName}, admin {$email}.", 'green');

        return EXIT_SUCCESS;
    }
}
