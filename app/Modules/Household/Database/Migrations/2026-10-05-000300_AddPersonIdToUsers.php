<?php

declare(strict_types=1);

namespace Modules\Household\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Links a Shield user (login) to a person (family member). A person may exist
 * without a login (small child), a login always belongs to exactly one person.
 */
class AddPersonIdToUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'person_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'username'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', 'person_id');
    }
}
