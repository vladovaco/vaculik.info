<?php

declare(strict_types=1);

namespace Modules\Documents\Deadlines;

use CodeIgniter\I18n\Time;
use Modules\Core\Contracts\DeadlineProvider;
use Modules\Core\Deadlines\Deadline;
use Modules\Documents\Models\DocumentModel;

final class DocumentDeadlines implements DeadlineProvider
{
    public function deadlines(int $householdId, Time $from, Time $to): array
    {
        $out = [];
        foreach (model(DocumentModel::class)->expiringBetween($householdId, $from, $to) as $document) {
            $out[] = new Deadline(
                ref: 'document:' . $document->id,
                kind: 'document',
                title: 'Končí platnosť: ' . $document->title,
                dueAt: $document->expires_at,
                url: url_to('documents.show', $document->id),
                personId: $document->person_id,
                detail: $document->kindLabel(),
                icon: 'document',
            );
        }

        return $out;
    }
}
