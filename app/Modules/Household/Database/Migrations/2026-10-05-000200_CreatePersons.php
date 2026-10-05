<?php

declare(strict_types=1);

namespace Modules\Household\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePersons extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'household_id' => ['type' => 'INT', 'unsigned' => true],
            'first_name'   => ['type' => 'VARCHAR', 'constraint' => 80],
            'last_name'    => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'nickname'     => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'role'         => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'child'], // adult | child | guest
            'birth_date'   => ['type' => 'DATE', 'null' => true],
            'color'        => ['type' => 'VARCHAR', 'constraint' => 7, 'default' => '#2563eb'],
            'sort_order'   => ['type' => 'INT', 'default' => 100],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('household_id');
        $this->forge->addForeignKey('household_id', 'households', 'id', '', 'CASCADE');
        $this->forge->createTable('persons');
    }

    public function down(): void
    {
        $this->forge->dropTable('persons');
    }
}
