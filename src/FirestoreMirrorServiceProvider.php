<?php

namespace Firevel\FirestoreMirror;

use Firestore;
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
                
                $batch->set(
                    Firestore::collection($model->getFirestoreCollectionName())
                        ->document($model->getFirestoreDocumentId()),
                    $model->toFirestoreDocument()
                );
            });
            
            $batch->commit();
            
            return $this;
        });
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