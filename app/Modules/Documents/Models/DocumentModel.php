<?php

declare(strict_types=1);

namespace Modules\Documents\Models;

use CodeIgniter\I18n\Time;
use CodeIgniter\Model;
use Modules\Documents\Entities\Document;

class DocumentModel extends Model
{
    protected $table          = 'documents';
    protected $primaryKey     = 'id';
    protected $returnType     = Document::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'household_id', 'person_id', 'kind', 'title', 'note', 'meta', 'ocr_text', 'expires_at', 'created_by',
    ];

    protected $validationRules = [
        'household_id' => 'required|is_natural_no_zero',
        'title'        => 'required|max_length[160]',
        'kind'         => 'required|in_list[zmluva,blocek,karticka,zarucny_list,potvrdenie,lekarska_sprava,doklad,iny]',
        'person_id'    => 'permit_empty|is_natural_no_zero',
        'expires_at'   => 'permit_empty|valid_date[Y-m-d]',
    ];

    protected $validationMessages = [
        'title' => ['required' => 'Názov je povinný.'],
    ];

    /**
     * @return list<Document>
     */
    public function search(int $householdId, string $query = '', string $kind = '', ?int $personId = null, bool $expiringOnly = false): array
    {
        $builder = $this->where('household_id', $householdId);
        if ($kind !== '') {
            $builder->where('kind', $kind);
        }
        if ($personId !== null) {
            $builder->where('person_id', $personId);
        }
        if ($expiringOnly) {
            $builder->where('expires_at IS NOT NULL')->where('expires_at <=', Time::now()->addDays(60)->format('Y-m-d'));
        }
        if ($query !== '') {
            $builder->groupStart()->like('title', $query)->orLike('note', $query)->orLike('ocr_text', $query)->groupEnd();
        }

        $documents = $builder->orderBy('created_at', 'DESC')->findAll();

        return $this->attachFiles($documents);
    }

    /**
     * @return list<Document>
     */
    public function cards(int $householdId): array
    {
        return $this->attachFiles(
            $this->where('household_id', $householdId)->where('kind', Document::KIND_CARD)->orderBy('person_id')->findAll()
        );
    }

    /**
     * @return list<Document>
     */
    public function expiringBetween(int $householdId, Time $from, Time $to): array
    {
        return $this->where('household_id', $householdId)
            ->where('expires_at >=', $from->format('Y-m-d'))
            ->where('expires_at <=', $to->format('Y-m-d'))
            ->orderBy('expires_at')
            ->findAll();
    }

    public function findWithFiles(int $id): ?Document
    {
        $document = $this->find($id);
        if ($document === null) {
            return null;
        }

        return $this->attachFiles([$document])[0];
    }

    /**
     * @param list<Document> $documents
     *
     * @return list<Document>
     */
    public function attachFiles(array $documents): array
    {
        if ($documents === []) {
            return [];
        }
        $byId = [];
        foreach ($documents as $document) {
            $document->files   = [];
            $byId[$document->id] = $document;
        }
        $files = model(DocumentFileModel::class)->whereIn('document_id', array_keys($byId))->orderBy('sort_order')->orderBy('id')->findAll();
        foreach ($files as $file) {
            $byId[$file->document_id]->files[] = $file;
        }

        return array_values($byId);
    }
}
