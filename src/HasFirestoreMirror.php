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

        static::deleting(function ($model) {
            $model->deleteFromFirestore();
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
            ->document($this->getFirestoreDocumentId())
            ->set($this->toFirestoreDocument());

        return $this;
    }

    /**
     * Delete model from firestore.
     *
     * @return self
     */
    public function deleteFromFirestore()
    {
        Firestore::collection($this->getFirestoreCollectionName())
            ->document($this->getFirestoreDocumentId())
            ->delete();

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
     * Get document id used for mirroring.
     *
     * @return mixed
     */
    public function getFirestoreDocumentId()
    {
        return $this->getKey();
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