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
        if (!$this->shouldMirrorToFirestore()) {
            return $this;
        }

        Firestore::collection($this->getFirestoreCollectionName())
            ->document($this->getFirestoreDocumentId())
            ->delete();

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