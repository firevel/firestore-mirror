<?php

namespace Firevel\FirestoreMirror;

use Firevel\FirestoreMirror\Events\ModelsFlushed;
use Firevel\FirestoreMirror\Events\ModelsImported;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Scope;

class HasFirestoreMirrorScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(EloquentBuilder $builder, Model $model)
    {
        //
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(EloquentBuilder $builder)
    {
        $builder->macro('mirrorToFirestore', function (EloquentBuilder $builder, $chunk = null) {
            $firestoreDocumentIdName = $builder->getModel()->getFirestoreDocumentIdName();

            $builder->chunkById($chunk ?: config('firestore-mirror.chunk.mirror', 500), function ($models) {
                $models->mirrorToFirestore();

                event(new ModelsImported($models));
            }, $builder->qualifyColumn($firestoreDocumentIdName), $firestoreDocumentIdName);
        });

        $builder->macro('deleteFromFirestore', function (EloquentBuilder $builder, $chunk = null) {
            $firestoreDocumentIdName = $builder->getModel()->getFirestoreDocumentIdName();

            $builder->chunkById($chunk ?: config('firestore-mirror.chunk.delete', 500), function ($models) {
                $models->deleteFromFirestore();

                event(new ModelsFlushed($models));
            }, $builder->qualifyColumn($firestoreDocumentIdName), $firestoreDocumentIdName);
        });

        HasManyThrough::macro('mirrorToFirestore' ?: config('firestore-mirror.chunk.mirror', 500), function ($chunk = null) {
            /** @var HasManyThrough $this */
            $this->chunkById($chunk, function ($models) {
                $models->mirrorToFirestore();

                event(new ModelsImported($models));
            });
        });

        HasManyThrough::macro('deleteFromFirestore' ?: config('firestore-mirror.chunk.delete', 500), function ($chunk = null) {
            /** @var HasManyThrough $this */
            $this->chunkById($chunk, function ($models) {
                $models->deleteFromFirestore();

                event(new ModelsFlushed($models));
            });
        });
    }
}
