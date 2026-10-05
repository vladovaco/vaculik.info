<?php

declare(strict_types=1);

namespace Modules\Notifications\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotifications extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'household_id' => ['type' => 'INT', 'unsigned' => true],
            'user_id'      => ['type' => 'INT', 'unsigned' => true],
            'dedupe_key'   => ['type' => 'VARCHAR', 'constraint' => 120], // e.g. "payment:12:offset:1" – one notification per key per user
            'title'        => ['type' => 'VARCHAR', 'constraint' => 160],
            'body'         => ['type' => 'TEXT', 'null' => true],
            'url'          => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'level'        => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'info'], // info|warning|danger
            'read_at'      => ['type' => 'DATETIME', 'null' => true],
            'pushed_at'    => ['type' => 'DATETIME', 'null' => true],
            'emailed_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'dedupe_key']);
        $this->forge->addKey(['user_id', 'read_at']);
        $this->forge->createTable('notifications');

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'unsigned' => true],
            'endpoint'     => ['type' => 'VARCHAR', 'constraint' => 1000],
            'endpoint_hash' => ['type' => 'CHAR', 'constraint' => 40],
            'p256dh'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'auth'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_agent'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'last_used_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('endpoint_hash');
        $this->forge->addKey('user_id');
        $this->forge->createTable('push_subscriptions');
    }

    public function down(): void
    {
        $this->forge->dropTable('push_subscriptions');
        $this->forge->dropTable('notifications');
    }
}
