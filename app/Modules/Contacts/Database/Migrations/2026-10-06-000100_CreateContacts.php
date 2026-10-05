<?php

declare(strict_types=1);

namespace Modules\Contacts\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContacts extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'household_id' => ['type' => 'INT', 'unsigned' => true],
            'person_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true], // e.g. a child's pediatrician
            'name'         => ['type' => 'VARCHAR', 'constraint' => 120],
            'organization' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'kind'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'iny'],
            'phone'        => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'phone2'       => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'email'        => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'address'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'web'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'note'         => ['type' => 'TEXT', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['household_id', 'kind']);
        $this->forge->addForeignKey('household_id', 'households', 'id', '', 'CASCADE');
        $this->forge->createTable('contacts');
    }

    public function down(): void
    {
        $this->forge->dropTable('contacts');
    }
}
