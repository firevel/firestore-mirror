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
            $model->mirrorToFirestore();
        });
    }

    /**
     * Mirror model to firestore.
     *
     * @return self
     */
    public function mirrorToFirestore()
    {
        Firestore::collection($this->getFirestoreCollectionName())
            ->document($this->getKey())
            ->set($this->toFirestoreDocument());

        return $this;
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