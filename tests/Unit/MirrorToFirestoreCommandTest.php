<?php

namespace Firevel\FirestoreMirror\Tests\Unit;

use Firevel\FirestoreMirror\HasFirestoreMirror;
use Firevel\FirestoreMirror\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;

class MirrorToFirestoreCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create test table
        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function testCommandWithInvalidModelClass()
    {
        $this->artisan('firestore:mirror', ['model' => 'NonExistentModel'])
            ->expectsOutput('Model class NonExistentModel does not exist.')
            ->assertExitCode(1);
    }

    public function testCommandWithNonEloquentClass()
    {
        $this->artisan('firestore:mirror', ['model' => \stdClass::class])
            ->expectsOutput(\stdClass::class . ' is not an Eloquent model.')
            ->assertExitCode(1);
    }

    public function testCommandWithModelWithoutTrait()
    {
        $modelClass = TestModelWithoutTrait::class;

        $this->artisan('firestore:mirror', ['model' => $modelClass])
            ->expectsOutput("{$modelClass} does not use the HasFirestoreMirror trait.")
            ->assertExitCode(1);
    }

    public function testCommandWithEmptyTable()
    {
        $modelClass = TestModel::class;

        $this->artisan('firestore:mirror', ['model' => $modelClass])
            ->expectsOutput("Starting mirror process for {$modelClass}...")
            ->expectsOutput("Total records to mirror: 0")
            ->expectsOutput("No records to mirror.")
            ->assertExitCode(0);
    }

    public function testCommandWithRecords()
    {
        $modelClass = TestModel::class;

        // Mock Firestore batch
        $batchMock = Mockery::mock('WriteBatch');
        $batchMock->shouldReceive('set')
            ->andReturn($batchMock);
        $batchMock->shouldReceive('commit')
            ->andReturn(true);

        // Mock the Firestore document
        $documentMock = Mockery::mock('DocumentReference');

        // Mock the Firestore collection
        $collectionMock = Mockery::mock('CollectionReference');
        $collectionMock->shouldReceive('document')
            ->andReturn($documentMock);

        // Mock the Firestore facade
        $firestoreMock = Mockery::mock('alias:Firestore');
        $firestoreMock->shouldReceive('batch')
            ->andReturn($batchMock);
        $firestoreMock->shouldReceive('collection')
            ->andReturn($collectionMock);

        // Create test records
        TestModel::withoutSyncingToFirestore(function () {
            TestModel::create(['name' => 'Test 1']);
            TestModel::create(['name' => 'Test 2']);
            TestModel::create(['name' => 'Test 3']);
        });

        $this->artisan('firestore:mirror', ['model' => $modelClass, '--chunk' => 2])
            ->expectsOutput("Starting mirror process for {$modelClass}...")
            ->expectsOutput("Total records to mirror: 3")
            ->assertExitCode(0);
    }

    public function testCommandRegistered()
    {
        $this->assertTrue(
            array_key_exists('firestore:mirror', $this->app['Illuminate\Contracts\Console\Kernel']->all())
        );
    }
}

class TestModel extends Model
{
    use HasFirestoreMirror;

    protected $table = 'test_models';
    protected $fillable = ['name'];
    public $timestamps = false;
}

class TestModelWithoutTrait extends Model
{
    protected $table = 'test_models';
    protected $fillable = ['name'];
    public $timestamps = false;
}
