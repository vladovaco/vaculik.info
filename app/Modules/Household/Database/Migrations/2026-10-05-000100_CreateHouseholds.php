<?php

declare(strict_types=1);

namespace Modules\Household\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHouseholds extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 120],
            'timezone'   => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'Europe/Bratislava'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('households');
    }

    public function down(): void
    {
        $this->forge->dropTable('households');
    }
}
