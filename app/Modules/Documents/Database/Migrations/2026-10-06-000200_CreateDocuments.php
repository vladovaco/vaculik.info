<?php

declare(strict_types=1);

namespace Modules\Documents\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocuments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'household_id' => ['type' => 'INT', 'unsigned' => true],
            'person_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'kind'         => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'iny'],
            'title'        => ['type' => 'VARCHAR', 'constraint' => 160],
            'note'         => ['type' => 'TEXT', 'null' => true],
            'meta'         => ['type' => 'TEXT', 'null' => true], // JSON, kind-specific fields (insurer, card number, ...)
            'ocr_text'     => ['type' => 'TEXT', 'null' => true], // filled by the assistant pipeline in phase 3
            'expires_at'   => ['type' => 'DATE', 'null' => true],
            'created_by'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['household_id', 'kind']);
        $this->forge->addKey(['household_id', 'expires_at']);
        $this->forge->addForeignKey('household_id', 'households', 'id', '', 'CASCADE');
        $this->forge->createTable('documents');

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'document_id'   => ['type' => 'INT', 'unsigned' => true],
            'path'          => ['type' => 'VARCHAR', 'constraint' => 255], // relative to writable/uploads
            'thumb_path'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'mime'          => ['type' => 'VARCHAR', 'constraint' => 100],
            'size'          => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'sort_order'    => ['type' => 'INT', 'default' => 0],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('document_id');
        $this->forge->addForeignKey('document_id', 'documents', 'id', '', 'CASCADE');
        $this->forge->createTable('document_files');
    }

    public function down(): void
    {
        $this->forge->dropTable('document_files');
        $this->forge->dropTable('documents');
    }
}
