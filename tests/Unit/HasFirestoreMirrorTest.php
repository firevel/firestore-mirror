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
}