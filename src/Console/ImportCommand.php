<?php

namespace Firevel\FirestoreMirror\Console;

use Firevel\FirestoreMirror\Events\ModelsImported;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'firestore-mirror:import')]
class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firestore-mirror:import
            {model : Class name of model to bulk import}
            {--c|chunk= : The number of records to import at a time (Defaults to configuration value: `firestore-mirror.chunk.mirror`)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the given model into Firestore';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function handle(Dispatcher $events)
    {
        $class = $this->argument('model');

        $model = new $class;

        $events->listen(ModelsImported::class, function ($event) use ($class) {
            $id = $event->models->last()->getFirestoreDocumentId();

            $this->line('<comment>Imported ['.$class.'] models up to ID:</comment> '.$id);
        });

        $model::mirrorAllToFirestore($this->option('chunk'));

        $events->forget(ModelsImported::class);

        $this->info('All ['.$class.'] records have been imported.');
    }
}
