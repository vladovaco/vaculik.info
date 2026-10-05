<?php

declare(strict_types=1);

namespace Modules\Documents\Models;

use CodeIgniter\Model;
use Modules\Documents\Entities\DocumentFile;

class DocumentFileModel extends Model
{
    protected $table         = 'document_files';
    protected $primaryKey    = 'id';
    protected $returnType    = DocumentFile::class;
    protected $useTimestamps = true;
    protected $updatedField  = '';
    protected $allowedFields = ['document_id', 'path', 'thumb_path', 'original_name', 'mime', 'size', 'sort_order'];
}
