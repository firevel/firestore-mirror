<?php

namespace Firevel\FirestoreMirror\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;

class MirrorToFirestore implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The models to be mirrored to Firestore.
     *
     * @var \Illuminate\Database\Eloquent\Collection
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
        $this->models = $models;
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

        $this->models->each(function ($model) {
            $model->mirrorToFirestoreNow();
        });
    }
}
