<?php

namespace Firevel\FirestoreMirror;

use Firestore;
use Firevel\FirestoreMirror\Console\Commands\MirrorToFirestore;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\ServiceProvider;

class FirestoreMirrorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Collection::macro('mirrorToFirestore', function () {
            $batch = Firestore::batch();

            $this->each(function ($model) use ($batch) {
                if (!method_exists($model, 'getFirestoreCollectionName') ||
                    !method_exists($model, 'getFirestoreDocumentId') ||
                    !method_exists($model, 'toFirestoreDocument')) {
                    return;
                }

                // Use resolveFirestoreCollections if available, otherwise fall back to getFirestoreCollectionName
                if (method_exists($model, 'resolveFirestoreCollections')) {
                    $collections = $model->resolveFirestoreCollections();
                } else {
                    $collection = $model->getFirestoreCollectionName();
                    $collections = is_array($collection) ? $collection : [$collection];
                }

                $documentId = $model->getFirestoreDocumentId();
                $document = $model->toFirestoreDocument();

                foreach ($collections as $collection) {
                    $batch->set(
                        Firestore::collection($collection)->document($documentId),
                        $document
                    );
                }
            });

            $batch->commit();

            return $this;
        });

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                MirrorToFirestore::class,
            ]);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}