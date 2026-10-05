<?php

declare(strict_types=1);

namespace Modules\Finance\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePayments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'household_id'       => ['type' => 'INT', 'unsigned' => true],
            'person_id'          => ['type' => 'INT', 'unsigned' => true, 'null' => true], // for whom (child)
            'title'              => ['type' => 'VARCHAR', 'constraint' => 160],
            'payee'              => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'iban'               => ['type' => 'VARCHAR', 'constraint' => 34, 'null' => true],
            'variable_symbol'    => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'specific_symbol'    => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'constant_symbol'    => ['type' => 'VARCHAR', 'constraint' => 4, 'null' => true],
            'amount'             => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'currency'           => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'EUR'],
            'category'           => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'iny'],
            'recurrence'         => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'none'], // none|weekly|monthly|quarterly|yearly
            'due_at'             => ['type' => 'DATE'],
            'paid_at'            => ['type' => 'DATE', 'null' => true],
            'paid_by_person_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'document_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true], // receipt / invoice
            'note'               => ['type' => 'TEXT', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['household_id', 'paid_at', 'due_at']);
        $this->forge->addForeignKey('household_id', 'households', 'id', '', 'CASCADE');
        $this->forge->createTable('payments');
    }

    public function down(): void
    {
        $this->forge->dropTable('payments');
    }
}
