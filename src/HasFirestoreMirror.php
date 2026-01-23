<?php

namespace Firevel\FirestoreMirror;

use Firestore;

trait HasFirestoreMirror
{
    /**
     * Indicates if Firestore syncing is disabled for all models.
     *
     * @var bool
     */
    protected static $firestoreSyncingDisabled = false;

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
     * Execute a callback without syncing to Firestore.
     *
     * @param  callable  $callback
     * @return mixed
     */
    public static function withoutSyncingToFirestore(callable $callback)
    {
        $original = static::$firestoreSyncingDisabled;
        static::$firestoreSyncingDisabled = true;

        try {
            return $callback();
        } finally {
            static::$firestoreSyncingDisabled = $original;
        }
    }

    /**
     * Determine if Firestore syncing is currently disabled.
     *
     * @return bool
     */
    public static function isSyncingToFirestoreDisabled()
    {
        return static::$firestoreSyncingDisabled;
    }

    /**
     * Mirror model to firestore.
     *
     * @return self
     */
    public function mirrorToFirestore()
    {
        if (!$this->shouldMirrorToFirestore()) {
            return $this;
        }

        $collections = $this->resolveFirestoreCollections();
        $documentId = $this->getFirestoreDocumentId();
        $document = $this->toFirestoreDocument();

        if (count($collections) === 1) {
            Firestore::collection($collections[0])
                ->document($documentId)
                ->set($document);
        } else {
            $batch = Firestore::batch();
            foreach ($collections as $collection) {
                $ref = Firestore::collection($collection)->document($documentId);
                $batch->set($ref, $document);
            }
            $batch->commit();
        }

        return $this;
    }

    /**
     * Delete model from firestore.
     *
     * @return self
     */
    public function deleteFromFirestore()
    {
        if (!$this->shouldMirrorToFirestore()) {
            return $this;
        }

        $collections = $this->resolveFirestoreCollections();
        $documentId = $this->getFirestoreDocumentId();

        if (count($collections) === 1) {
            Firestore::collection($collections[0])
                ->document($documentId)
                ->delete();
        } else {
            $batch = Firestore::batch();
            foreach ($collections as $collection) {
                $ref = Firestore::collection($collection)->document($documentId);
                $batch->delete($ref);
            }
            $batch->commit();
        }

        return $this;
    }

    /**
     * Determine if the model should be mirrored to Firestore.
     *
     * @return bool
     */
    public function shouldMirrorToFirestore()
    {
        if (static::isSyncingToFirestoreDisabled()) {
            return false;
        }

        return true;
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
     * @return string|array
     */
    public function getFirestoreCollectionName()
    {
        if (empty($this->firestoreCollection)) {
            return $this->getTable();
        }

        return $this->firestoreCollection;
    }

    /**
     * Get firestore collections used for mirroring.
     *
     * @return array|null
     */
    public function getFirestoreCollectionNames(): ?array
    {
        return null;
    }

    /**
     * Resolve the firestore collections to mirror to.
     *
     * @return array
     */
    public function resolveFirestoreCollections(): array
    {
        $collections = $this->getFirestoreCollectionNames();

        if ($collections !== null) {
            return $collections;
        }

        $collection = $this->getFirestoreCollectionName();

        if (is_array($collection)) {
            return $collection;
        }

        return [$collection];
    }
}