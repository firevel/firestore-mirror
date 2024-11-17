<?php

namespace Firevel\FirestoreMirror\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class DeleteFromFirestore implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The models to be removed from Firestore.
     *
     * @var \Firevel\FirestoreMirror\Jobs\DeletableFirestoreMirrorCollection
     */
    public $models;

    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Database\Eloquent\Collection  $models
     * @return void
     */
    public function __construct($models)
    {
        $this->models = DeletableFirestoreMirrorCollection::make($models);
    }

    /**
     * Handle the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->models->isEmpty()) {
            return;
        }

        return $this->models->each(function ($model) {
            $model->deleteFromFirestoreNow();
        });
    }

    /**
     * Restore a queueable collection instance.
     *
     * @param  \Illuminate\Contracts\Database\ModelIdentifier  $value
     * @return \Laravel\FirestoreMirror\Jobs\DeletableFirestoreMirrorCollection
     */
    protected function restoreCollection($value)
    {
        if (! $value->class || count($value->id) === 0) {
            return new DeletableFirestoreMirrorCollection;
        }

        return new DeletableFirestoreMirrorCollection(
            collect($value->id)->map(function ($id) use ($value) {
                return tap(new $value->class, function ($model) use ($id) {
                    $model->setKeyType(
                        is_string($id) ? 'string' : 'int'
                    )->forceFill([
                        $model->getFirestoreDocumentIdName() => $id,
                    ]);
                });
            })
        );
    }
}
