<?php

namespace Firevel\FirestoreMirror\Jobs;

use Illuminate\Database\Eloquent\Collection;
use Firevel\FirestoreMirror\HasFirestoreMirror;

class DeletableFirestoreMirrorCollection extends Collection
{
    /**
     * Get the Firestore document identifiers for all of the entities.
     *
     * @return array
     */
    public function getQueueableIds()
    {
        if ($this->isEmpty()) {
            return [];
        }

        return in_array(HasFirestoreMirror::class, class_uses_recursive($this->first()))
                    ? $this->map->getFirestoreDocumentId()->all()
                    : parent::getQueueableIds();
    }
}
