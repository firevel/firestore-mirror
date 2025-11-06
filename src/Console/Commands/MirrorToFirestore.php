<?php

namespace Firevel\FirestoreMirror\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class MirrorToFirestore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firestore:mirror
                            {model : The fully qualified class name of the model to mirror}
                            {--chunk=100 : Number of records to process in each chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mirror all records of a model to Firestore';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $modelClass = $this->argument('model');
        $chunkSize = (int) $this->option('chunk');

        // Validate model class exists
        if (!class_exists($modelClass)) {
            $this->error("Model class {$modelClass} does not exist.");
            return 1;
        }

        // Validate model is an Eloquent model
        $model = new $modelClass;
        if (!$model instanceof Model) {
            $this->error("{$modelClass} is not an Eloquent model.");
            return 1;
        }

        // Validate model uses HasFirestoreMirror trait
        if (!method_exists($model, 'mirrorToFirestore')) {
            $this->error("{$modelClass} does not use the HasFirestoreMirror trait.");
            return 1;
        }

        $this->info("Starting mirror process for {$modelClass}...");

        // Get total count
        $total = $modelClass::count();
        $this->info("Total records to mirror: {$total}");

        if ($total === 0) {
            $this->info("No records to mirror.");
            return 0;
        }

        // Create progress bar
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $mirrored = 0;

        // Process in chunks to handle large collections efficiently
        $modelClass::chunk($chunkSize, function ($models) use (&$mirrored, $progressBar) {
            // Use the collection macro to batch mirror
            $models->mirrorToFirestore();

            $mirrored += $models->count();
            $progressBar->advance($models->count());
        });

        $progressBar->finish();
        $this->newLine();

        $this->info("Successfully mirrored {$mirrored} records to Firestore.");

        return 0;
    }
}
