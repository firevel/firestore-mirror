<?php

namespace Firevel\FirestoreMirror;

use Firestore;

trait HasFirestoreMirror
{
    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    public static function bootHasFirestoreMirror(): void
    {
        static::saved(function ($model) {
            Firestore::collection($model->getFirestoreCollectionName())
                ->document($model->getKey())
                ->set($model->toFirestoreDocument());
        });
    }

    /**
     * Convert model to firestore document.
     *
     * @return array
     */
    public function toFirestoreDocument()
    {
        return $this->attributesToArray();
    }

    /**
     * Get firestore collection used for mirroring.
     *
     * @return string
     */
    public function getFirestoreCollectionName()
    {
        if (empty($this->firestoreCollection)) {
            return $this->getTable();
        }

        return $this->firestoreCollection;
    }
}