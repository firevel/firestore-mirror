<?php

namespace Firevel\FirestoreMirror\Tests\Unit;

use Firevel\FirestoreMirror\HasFirestoreMirror;
use Mockery;
use PHPUnit\Framework\TestCase;

class HasFirestoreMirrorTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testMirrorToFirestore()
    {
        // Create a mock model that uses the HasFirestoreMirror trait
        $model = new class {
            use HasFirestoreMirror;

            protected $attributes = ['id' => 1, 'name' => 'Test'];
            protected $table = 'tests';

            public function getKey()
            {
                return $this->attributes['id'];
            }

            public function getTable()
            {
                return $this->table;
            }

            public function attributesToArray()
            {
                return $this->attributes;
            }
        };

        // Mock the Firestore document
        $documentMock = Mockery::mock('DocumentReference');
        $documentMock->shouldReceive('set')
            ->once()
            ->with(['id' => 1, 'name' => 'Test'])
            ->andReturn(true);

        // Mock the Firestore collection
        $collectionMock = Mockery::mock('CollectionReference');
        $collectionMock->shouldReceive('document')
            ->once()
            ->with(1)
            ->andReturn($documentMock);

        // Mock the Firestore facade
        $firestoreMock = Mockery::mock('alias:Firestore');
        $firestoreMock->shouldReceive('collection')
            ->once()
            ->with('tests')
            ->andReturn($collectionMock);

        // Call the method
        $result = $model->mirrorToFirestore();

        // Assert that the model is returned for chaining
        $this->assertSame($model, $result);
    }

    public function testDeleteFromFirestore()
    {
        // Create a mock model that uses the HasFirestoreMirror trait
        $model = new class {
            use HasFirestoreMirror;

            protected $attributes = ['id' => 1, 'name' => 'Test'];
            protected $table = 'tests';

            public function getKey()
            {
                return $this->attributes['id'];
            }

            public function getTable()
            {
                return $this->table;
            }
        };

        // Mock the Firestore document
        $documentMock = Mockery::mock('DocumentReference');
        $documentMock->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        // Mock the Firestore collection
        $collectionMock = Mockery::mock('CollectionReference');
        $collectionMock->shouldReceive('document')
            ->once()
            ->with(1)
            ->andReturn($documentMock);

        // Mock the Firestore facade
        $firestoreMock = Mockery::mock('alias:Firestore');
        $firestoreMock->shouldReceive('collection')
            ->once()
            ->with('tests')
            ->andReturn($collectionMock);

        // Call the method
        $result = $model->deleteFromFirestore();

        // Assert that the model is returned for chaining
        $this->assertSame($model, $result);
    }

    public function testCustomFirestoreCollection()
    {
        // Create a mock model that uses the HasFirestoreMirror trait with a custom collection
        $model = new class {
            use HasFirestoreMirror;

            protected $attributes = ['id' => 1, 'name' => 'Test'];
            protected $table = 'tests';
            public $firestoreCollection = 'custom_collection';

            public function getKey()
            {
                return $this->attributes['id'];
            }

            public function getTable()
            {
                return $this->table;
            }
        };

        $this->assertEquals('custom_collection', $model->getFirestoreCollectionName());
    }

    public function testShouldMirrorToFirestoreReturnsTrue()
    {
        // Create a mock model that uses the HasFirestoreMirror trait
        $model = new class {
            use HasFirestoreMirror;

            protected $attributes = ['id' => 1, 'name' => 'Test'];
            protected $table = 'tests';

            public function getKey()
            {
                return $this->attributes['id'];
            }

            public function getTable()
            {
                return $this->table;
            }
        };

        // Assert that shouldMirrorToFirestore returns true by default
        $this->assertTrue($model->shouldMirrorToFirestore());
    }

    public function testMirrorToFirestoreSkipsWhenShouldMirrorReturnsFalse()
    {
        // Create a mock model that returns false from shouldMirrorToFirestore
        $model = new class {
            use HasFirestoreMirror;

            protected $attributes = ['id' => 1, 'name' => 'Test'];
            protected $table = 'tests';

            public function getKey()
            {
                return $this->attributes['id'];
            }

            public function getTable()
            {
                return $this->table;
            }

            public function attributesToArray()
            {
                return $this->attributes;
            }

            public function shouldMirrorToFirestore()
            {
                return false;
            }
        };

        // Mock the Firestore facade - it should NOT receive any calls
        $firestoreMock = Mockery::mock('alias:Firestore');
        $firestoreMock->shouldNotReceive('collection');

        // Call the method
        $result = $model->mirrorToFirestore();

        // Assert that the model is returned for chaining
        $this->assertSame($model, $result);
    }

    public function testDeleteFromFirestoreSkipsWhenShouldMirrorReturnsFalse()
    {
        // Create a mock model that returns false from shouldMirrorToFirestore
        $model = new class {
            use HasFirestoreMirror;

            protected $attributes = ['id' => 1, 'name' => 'Test'];
            protected $table = 'tests';

            public function getKey()
            {
                return $this->attributes['id'];
            }

            public function getTable()
            {
                return $this->table;
            }

            public function shouldMirrorToFirestore()
            {
                return false;
            }
        };

        // Mock the Firestore facade - it should NOT receive any calls
        $firestoreMock = Mockery::mock('alias:Firestore');
        $firestoreMock->shouldNotReceive('collection');

        // Call the method
        $result = $model->deleteFromFirestore();

        // Assert that the model is returned for chaining
        $this->assertSame($model, $result);
    }

    public function testWithoutSyncingToFirestoreDisablesSyncing()
    {
        // Create a test model class
        $modelClass = new class {
            use HasFirestoreMirror;

            protected $attributes = ['id' => 1, 'name' => 'Test'];
            protected $table = 'tests';

            public function getKey()
            {
                return $this->attributes['id'];
            }

            public function getTable()
            {
                return $this->table;
            }

            public function attributesToArray()
            {
                return $this->attributes;
            }
        };

        // Mock the Firestore facade - it should NOT receive any calls
        $firestoreMock = Mockery::mock('alias:Firestore');
        $firestoreMock->shouldNotReceive('collection');

        // Assert that syncing is enabled by default
        $this->assertFalse($modelClass::isSyncingToFirestoreDisabled());

        // Execute within withoutSyncingToFirestore
        $result = $modelClass::withoutSyncingToFirestore(function () use ($modelClass) {
            // Assert that syncing is disabled inside the callback
            $this->assertTrue($modelClass::isSyncingToFirestoreDisabled());

            // Try to mirror - should be skipped
            $modelClass->mirrorToFirestore();

            return 'test-value';
        });

        // Assert that the callback return value is passed through
        $this->assertEquals('test-value', $result);

        // Assert that syncing is re-enabled after the callback
        $this->assertFalse($modelClass::isSyncingToFirestoreDisabled());
    }

    public function testWithoutSyncingToFirestoreRestoresStateOnException()
    {
        // Create a test model class
        $modelClass = new class {
            use HasFirestoreMirror;
        };

        // Assert that syncing is enabled by default
        $this->assertFalse($modelClass::isSyncingToFirestoreDisabled());

        try {
            $modelClass::withoutSyncingToFirestore(function () {
                throw new \Exception('Test exception');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        // Assert that syncing is re-enabled even after an exception
        $this->assertFalse($modelClass::isSyncingToFirestoreDisabled());
    }

    public function testWithoutSyncingToFirestoreCanBeNested()
    {
        // Create a test model class
        $modelClass = new class {
            use HasFirestoreMirror;
        };

        $this->assertFalse($modelClass::isSyncingToFirestoreDisabled());

        $modelClass::withoutSyncingToFirestore(function () use ($modelClass) {
            $this->assertTrue($modelClass::isSyncingToFirestoreDisabled());

            $modelClass::withoutSyncingToFirestore(function () use ($modelClass) {
                $this->assertTrue($modelClass::isSyncingToFirestoreDisabled());
            });

            $this->assertTrue($modelClass::isSyncingToFirestoreDisabled());
        });

        $this->assertFalse($modelClass::isSyncingToFirestoreDisabled());
    }
}