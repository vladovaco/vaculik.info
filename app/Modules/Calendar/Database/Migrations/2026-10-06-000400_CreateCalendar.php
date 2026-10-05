<?php

declare(strict_types=1);

namespace Modules\Calendar\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCalendar extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'household_id'   => ['type' => 'INT', 'unsigned' => true],
            'person_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 80],
            'ics_url'        => ['type' => 'VARCHAR', 'constraint' => 1000],
            'color'          => ['type' => 'VARCHAR', 'constraint' => 7, 'default' => '#0ea5e9'],
            'enabled'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'last_synced_at' => ['type' => 'DATETIME', 'null' => true],
            'last_error'     => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('household_id');
        $this->forge->addForeignKey('household_id', 'households', 'id', '', 'CASCADE');
        $this->forge->createTable('calendar_sources');

        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'household_id'     => ['type' => 'INT', 'unsigned' => true],
            'source_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true], // null = created in the app
            'person_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'driver_person_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true], // who drives / accompanies
            'uid'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // external uid + instance
            'title'            => ['type' => 'VARCHAR', 'constraint' => 200],
            'description'      => ['type' => 'TEXT', 'null' => true],
            'location'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'starts_at'        => ['type' => 'DATETIME'],
            'ends_at'          => ['type' => 'DATETIME'],
            'all_day'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['household_id', 'starts_at']);
        $this->forge->addKey(['source_id', 'starts_at']);
        $this->forge->addForeignKey('household_id', 'households', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('source_id', 'calendar_sources', 'id', '', 'CASCADE');
        $this->forge->createTable('calendar_events');
    }

    public function down(): void
    {
        $this->forge->dropTable('calendar_events');
        $this->forge->dropTable('calendar_sources');
    }
}
