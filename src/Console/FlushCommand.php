<?php

namespace Firevel\FirestoreMirror\Console;

use Firevel\FirestoreMirror\Events\ModelsFlushed;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'firestore-mirror:flush')]
class FlushCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firestore-mirror:flush
            {model : Class name of the model to flush}
            {--c|chunk= : The number of records to flush at a time (Defaults to configuration value: `firestore-mirror.chunk.delete`)}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Flush all of the model's records from Firestore";

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

        $events->listen(ModelsFlushed::class, function ($event) use ($class) {
            $id = $event->models->last()->getFirestoreDocumentId();

            $this->line('<comment>Flushed ['.$class.'] models up to ID:</comment> '.$id);
        });

        $model::deleteAllFromFirestore($this->option('chunk'));

        $events->forget(ModelsFlushed::class);

        $this->info('All ['.$class.'] records have been flushed.');
    }
}
