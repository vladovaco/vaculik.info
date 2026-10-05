<?php

declare(strict_types=1);

namespace Modules\Documents\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Documents\Entities\Document;
use Modules\Documents\Entities\DocumentFile;
use Modules\Documents\Models\DocumentFileModel;
use Modules\Documents\Models\DocumentModel;
use Modules\Documents\Services\DocumentStorage;
use Modules\Household\Models\PersonModel;

class DocumentController extends BaseController
{
    private DocumentModel $documents;
    private DocumentStorage $storage;

    public function __construct()
    {
        $this->documents = model(DocumentModel::class);
        $this->storage   = new DocumentStorage();
    }

    public function index(): string
    {
        $query    = trim((string) $this->request->getGet('q'));
        $kind     = (string) $this->request->getGet('druh');
        $personId = $this->request->getGet('osoba') !== null && $this->request->getGet('osoba') !== '' ? (int) $this->request->getGet('osoba') : null;
        $expiring = $this->request->getGet('expiruje') === '1';

        return view('Modules\Documents\Views\index', [
            'title'     => 'Dokumenty',
            'documents' => $this->documents->search($this->householdId(), $query, $kind, $personId, $expiring),
            'persons'   => $this->personsById(),
            'query'     => $query,
            'kind'      => $kind,
            'personId'  => $personId,
            'expiring'  => $expiring,
        ]);
    }

    /**
     * Insurance cards per person – the page the service worker keeps available offline.
     */
    public function cards(): string
    {
        $persons = $this->personsById();
        $grouped = [];
        foreach ($this->documents->cards($this->householdId()) as $card) {
            $grouped[$card->person_id ?? 0][] = $card;
        }

        return view('Modules\Documents\Views\cards', [
            'title'   => 'Kartičky poistencov',
            'grouped' => $grouped,
            'persons' => $persons,
        ]);
    }

    public function new(): string
    {
        $kind = (string) ($this->request->getGet('druh') ?? 'iny');

        return $this->form(new Document(['kind' => array_key_exists($kind, Document::KINDS) ? $kind : 'iny', 'meta' => []]), url_to('documents.create'), 'Nový dokument');
    }

    public function create(): RedirectResponse
    {
        $uploads = $this->uploads();
        if (is_string($uploads)) {
            return redirect()->back()->withInput()->with('error', $uploads);
        }

        $data                 = $this->formData();
        $data['household_id'] = $this->householdId();
        $data['created_by']   = auth()->id();

        $id = $this->documents->insert($data);
        if (! $id) {
            return redirect()->back()->withInput()->with('errors', $this->documents->errors());
        }

        foreach ($uploads as $i => $file) {
            $this->storage->store($this->householdId(), (int) $id, $file, $i);
        }

        return redirect()->route('documents.show', [$id])->with('message', 'Dokument bol uložený.');
    }

    public function show(int $id): string
    {
        $document = $this->findOrFail($id);

        return view('Modules\Documents\Views\show', [
            'title'    => $document->title,
            'document' => $document,
            'person'   => $document->person_id ? model(PersonModel::class)->find($document->person_id) : null,
        ]);
    }

    public function edit(int $id): string
    {
        $document = $this->findOrFail($id);

        return $this->form($document, url_to('documents.update', $id), 'Upraviť dokument');
    }

    public function update(int $id): RedirectResponse
    {
        $document = $this->findOrFail($id);
        $uploads  = $this->uploads();
        if (is_string($uploads)) {
            return redirect()->back()->withInput()->with('error', $uploads);
        }

        if (! $this->documents->update($document->id, $this->formData())) {
            return redirect()->back()->withInput()->with('errors', $this->documents->errors());
        }

        $sort = count($document->files);
        foreach ($uploads as $file) {
            $this->storage->store($this->householdId(), $document->id, $file, $sort++);
        }

        return redirect()->route('documents.show', [$document->id])->with('message', 'Zmeny boli uložené.');
    }

    public function delete(int $id): RedirectResponse
    {
        $document = $this->findOrFail($id);
        foreach ($document->files as $file) {
            $this->storage->delete($file);
        }
        $this->documents->delete($document->id);

        return redirect()->route('documents')->with('message', 'Dokument bol odstránený.');
    }

    public function file(int $id, int $fileId): ResponseInterface
    {
        $file = $this->fileOrFail($this->findOrFail($id), $fileId);

        return $this->response->download($file->absolutePath(), null, true)
            ->setFileName($file->original_name)
            ->inline();
    }

    public function thumb(int $id, int $fileId): ResponseInterface
    {
        $file = $this->fileOrFail($this->findOrFail($id), $fileId);
        $path = $file->absoluteThumbPath() ?? $file->absolutePath();

        return $this->response->download($path, null, true)->setFileName('nahlad.jpg')->inline();
    }

    public function deleteFile(int $id, int $fileId): RedirectResponse
    {
        $document = $this->findOrFail($id);
        $this->storage->delete($this->fileOrFail($document, $fileId));

        return redirect()->route('documents.edit', [$document->id])->with('message', 'Súbor bol odstránený.');
    }

    private function form(Document $document, string $action, string $title): string
    {
        return view('Modules\Documents\Views\form', [
            'title'    => $title,
            'document' => $document,
            'action'   => $action,
            'persons'  => model(PersonModel::class)->forHousehold($this->householdId()),
        ]);
    }

    /**
     * @return list<\CodeIgniter\HTTP\Files\UploadedFile>|string Error message when a file is invalid.
     */
    private function uploads(): array|string
    {
        $files = $this->request->getFileMultiple('files') ?? [];
        $valid = [];
        foreach ($files as $file) {
            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $error = $this->storage->validate($file);
            if ($error !== null) {
                return $error;
            }
            $valid[] = $file;
        }

        return $valid;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $post = $this->request->getPost();
        $kind = (string) ($post['kind'] ?? 'iny');
        $meta = [];
        if ($kind === Document::KIND_CARD) {
            $meta = [
                'insurer' => trim((string) ($post['meta_insurer'] ?? '')),
                'number'  => trim((string) ($post['meta_number'] ?? '')),
            ];
        } elseif ($kind === 'zmluva') {
            $meta = ['counterparty' => trim((string) ($post['meta_counterparty'] ?? '')), 'notice_period' => trim((string) ($post['meta_notice_period'] ?? ''))];
        } elseif ($kind === 'blocek' || $kind === 'zarucny_list') {
            $meta = ['vendor' => trim((string) ($post['meta_vendor'] ?? '')), 'amount' => trim((string) ($post['meta_amount'] ?? ''))];
        }

        return [
            'title'      => trim((string) ($post['title'] ?? '')),
            'kind'       => $kind,
            'person_id'  => ($post['person_id'] ?? '') !== '' ? (int) $post['person_id'] : null,
            'note'       => trim((string) ($post['note'] ?? '')) ?: null,
            'expires_at' => ($post['expires_at'] ?? '') !== '' ? (string) $post['expires_at'] : null,
            'meta'       => json_encode(array_filter($meta, static fn ($v) => $v !== ''), JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * @return array<int, \Modules\Household\Entities\Person>
     */
    private function personsById(): array
    {
        $out = [];
        foreach (model(PersonModel::class)->forHousehold($this->householdId()) as $person) {
            $out[$person->id] = $person;
        }

        return $out;
    }

    private function householdId(): int
    {
        return (int) service('householdContext')->householdId();
    }

    private function findOrFail(int $id): Document
    {
        $document = $this->documents->findWithFiles($id);
        if ($document === null || $document->household_id !== $this->householdId()) {
            throw PageNotFoundException::forPageNotFound('Dokument neexistuje.');
        }

        return $document;
    }

    private function fileOrFail(Document $document, int $fileId): DocumentFile
    {
        foreach ($document->files as $file) {
            if ($file->id === $fileId && is_file($file->absolutePath())) {
                return $file;
            }
        }

        throw PageNotFoundException::forPageNotFound('Súbor neexistuje.');
    }
}
