<?php

declare(strict_types=1);

namespace Modules\Documents\Entities;

use CodeIgniter\Entity\Entity;

/**
 * @property int         $id
 * @property int         $document_id
 * @property string      $path
 * @property string|null $thumb_path
 * @property string      $original_name
 * @property string      $mime
 * @property int         $size
 * @property int         $sort_order
 */
class DocumentFile extends Entity
{
    protected $casts = [
        'id'          => 'integer',
        'document_id' => 'integer',
        'size'        => 'integer',
        'sort_order'  => 'integer',
    ];

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime === 'application/pdf';
    }

    public function absolutePath(): string
    {
        return WRITEPATH . 'uploads/' . $this->path;
    }

    public function absoluteThumbPath(): ?string
    {
        return $this->thumb_path === null ? null : WRITEPATH . 'uploads/' . $this->thumb_path;
    }

    public function humanSize(): string
    {
        $size = $this->size;
        if ($size >= 1024 * 1024) {
            return number_format($size / 1024 / 1024, 1, ',', ' ') . ' MB';
        }

        return number_format($size / 1024, 0, ',', ' ') . ' kB';
    }
}
