<?php

namespace Firevel\FirestoreMirror;

use Firestore;
use Firevel\FirestoreMirror\Jobs\DeleteFromFirestore;
use Firevel\FirestoreMirror\Jobs\MirrorToFirestore;
use Illuminate\Support\Collection as BaseCollection;

trait HasFirestoreMirror
{
    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    public static function bootHasFirestoreMirror(): void
    {
        static::addGlobalScope(new HasFirestoreMirrorScope);

        static::observe(new ModelObserver);

        (new static)->registerHasFirestoreMirrorMacros();
    }

    /**
     * Register the HasFirestoreMirror macros.
     *
     * @return void
     */
    public function registerHasFirestoreMirrorMacros()
    {
        $self = $this;

        BaseCollection::macro('mirrorToFirestore', function () use ($self) {
            $self->queueMirrorToFirestore($this);
        });

        BaseCollection::macro('deleteFromFirestore', function () use ($self) {
            $self->queueDeleteFromFirestore($this);
        });
    }

    /**
     * Dispatch the job to mirror the given models to Firestore.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function queueMirrorToFirestore($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        if (! config('firestore-mirror.queue')) {
            return $models->each(function ($model) {
                $model->mirrorToFirestoreNow();
            });
        }

        dispatch(new MirrorToFirestore($models))
            ->onQueue($models->first()->mirrorToFirestoreUsingQueue())
            ->onConnection($models->first()->mirrorToFirestoreUsing());
    }

    /**
     * Dispatch the job to delete the given models from Firestore.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function queueDeleteFromFirestore($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        if (! config('firestore-mirror.queue')) {
            $models->each(function ($model) {
                $model->deleteFromFirestoreNow();
            });
        }

        dispatch(new DeleteFromFirestore($models))
            ->onQueue($models->first()->mirrorToFirestoreUsingQueue())
            ->onConnection($models->first()->mirrorToFirestoreUsing());
    }

    /**
     * Mirror all instances of the model to Firestore.
     *
     * @param  int  $chunk
     * @return void
     */
    public static function mirrorAllToFirestore($chunk = null)
    {
        $self = new static;

        $self->newQuery()
            ->orderBy($self->qualifyColumn($self->getFirestoreDocumentIdName()))
            ->mirrorToFirestore($chunk);
    }

    /**
     * Mirror the given model instance to Firestore.
     *
     * @return void
     */
    public function mirrorToFirestore()
    {
        $this->newCollection([$this])->mirrorToFirestore();
    }

    /**
     * Delete all instances of the model from Firestore.
     *
     * @param  int  $chunk
     * @return void
     */
    public static function deleteAllFromFirestore($chunk = null)
    {
        $self = new static;

        $self->newQuery()
            ->orderBy($self->qualifyColumn($self->getFirestoreDocumentIdName()))
            ->deleteFromFirestore($chunk);
    }

    /**
     * Delete the given model instance from Firestore.
     *
     * @return void
     */
    public function deleteFromFirestore()
    {
        $this->newCollection([$this])->deleteFromFirestore();
    }

    /**
     * Enable Firestore mirroring for this model.
     *
     * @return void
     */
    public static function enableFirestoreMirroring()
    {
        ModelObserver::enableMirroringFor(get_called_class());
    }

    /**
     * Disable Firestore mirroring for this model.
     *
     * @return void
     */
    public static function disableFirestoreMirroring()
    {
        ModelObserver::disableMirroringFor(get_called_class());
    }

    /**
     * Temporarily disable Firestore mirroring for the given callback.
     *
     * @param  callable  $callback
     * @return mixed
     */
    public static function withoutMirroringToFirestore($callback)
    {
        static::disableFirestoreMirroring();

        try {
            return $callback();
        } finally {
            static::enableFirestoreMirroring();
        }
    }

    /**
     * Get the queue that should be used with mirroring.
     *
     * @return string
     */
    public function mirrorToFirestoreUsing()
    {
        return config('firestore-mirror.queue.connection') ?: config('queue.default');
    }

    /**
     * Get the queue that should be used with mirroring.
     *
     * @return string
     */
    public function mirrorToFirestoreUsingQueue()
    {
        return config('firestore-mirror.queue.queue');
    }

    /**
     * Mirror model to firestore iimmediately.
     *
     * @return self
     */
    public function mirrorToFirestoreNow()
    {
        Firestore::collection($this->getFirestoreCollectionName())
            ->document($this->getFirestoreDocumentId())
            ->set($this->toFirestoreDocument());

        return $this;
    }

    /**
     * Delete model from firestore immediately.
     *
     * @return self
     */
    public function deleteFromFirestoreNow()
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
     * Get document id value used for mirroring.
     *
     * @return mixed
     */
    public function getFirestoreDocumentId()
    {
        return $this->getKey();
    }

    /**
     * Get document id name used for mirroring.
     *
     * @return mixed
     */
    public function getFirestoreDocumentIdName()
    {
        return $this->getKeyName();
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
