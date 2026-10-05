<?php

declare(strict_types=1);

namespace Modules\Documents\Services;

use CodeIgniter\HTTP\Files\UploadedFile;
use Modules\Documents\Entities\DocumentFile;
use Modules\Documents\Models\DocumentFileModel;

/**
 * Stores uploaded files under writable/uploads/documents/<household>/<year>/ (outside the web root),
 * shrinks big photos and creates a thumbnail for lists. Files are served through the controller only.
 */
final class DocumentStorage
{
    public const MAX_SIZE_BYTES = 12 * 1024 * 1024;
    public const ALLOWED_MIMES  = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif', 'application/pdf'];
    private const MAX_EDGE      = 1800;
    private const THUMB_EDGE    = 480;

    public function validate(UploadedFile $file): ?string
    {
        if (! $file->isValid()) {
            return $file->getErrorString();
        }
        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            return "Súbor {$file->getClientName()} je väčší než 12 MB.";
        }
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            return "Súbor {$file->getClientName()} má nepodporovaný typ ({$file->getMimeType()}). Povolené sú fotky a PDF.";
        }

        return null;
    }

    public function store(int $householdId, int $documentId, UploadedFile $file, int $sortOrder = 0): DocumentFile
    {
        $relativeDir = 'documents/' . $householdId . '/' . date('Y');
        $absoluteDir = WRITEPATH . 'uploads/' . $relativeDir;
        if (! is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0775, true);
        }

        $mime         = $file->getMimeType();
        $originalName = $file->getClientName();
        $size         = $file->getSize();
        $name         = $file->getRandomName();
        $file->move($absoluteDir, $name);
        $absolute  = $absoluteDir . '/' . $name;
        $thumbPath = null;

        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $this->shrink($absolute, self::MAX_EDGE);
            $size      = (int) filesize($absolute);
            $thumbName = pathinfo($name, PATHINFO_FILENAME) . '_thumb.jpg';
            if ($this->thumbnail($absolute, $absoluteDir . '/' . $thumbName)) {
                $thumbPath = $relativeDir . '/' . $thumbName;
            }
        }

        $files = model(DocumentFileModel::class);
        $id    = $files->insert([
            'document_id'   => $documentId,
            'path'          => $relativeDir . '/' . $name,
            'thumb_path'    => $thumbPath,
            'original_name' => $originalName,
            'mime'          => $mime,
            'size'          => $size,
            'sort_order'    => $sortOrder,
        ]);

        return $files->find($id);
    }

    public function delete(DocumentFile $file): void
    {
        foreach ([$file->absolutePath(), $file->absoluteThumbPath()] as $path) {
            if ($path !== null && is_file($path)) {
                unlink($path);
            }
        }
        model(DocumentFileModel::class)->delete($file->id);
    }

    private function shrink(string $path, int $maxEdge): void
    {
        try {
            [$width, $height] = getimagesize($path) ?: [0, 0];
            if ($width <= $maxEdge && $height <= $maxEdge) {
                return;
            }
            service('image')->withFile($path)->resize($maxEdge, $maxEdge, true)->save($path, 85);
        } catch (\Throwable $e) {
            log_message('warning', 'Image shrink failed for {path}: {error}', ['path' => $path, 'error' => $e->getMessage()]);
        }
    }

    private function thumbnail(string $source, string $target): bool
    {
        try {
            service('image')->withFile($source)->fit(self::THUMB_EDGE, self::THUMB_EDGE, 'center')->convert(IMAGETYPE_JPEG)->save($target, 75);

            return true;
        } catch (\Throwable $e) {
            log_message('warning', 'Thumbnail failed for {path}: {error}', ['path' => $source, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
