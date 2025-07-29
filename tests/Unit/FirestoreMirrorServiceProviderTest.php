<?php

namespace Firevel\FirestoreMirror\Tests\Unit;

use Firevel\FirestoreMirror\Tests\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Mockery;

class FirestoreMirrorServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }


    public function testCollectionMacro()
    {
        // Check if the macro exists
        $this->assertTrue(Collection::hasMacro('mirrorToFirestore'));
    }

    public function testCollectionMacroWithModels()
    {
        // Create test models
        $model1 = new class extends Model {
            public function getFirestoreCollectionName()
            {
                return 'test_collection';
            }

            public function getFirestoreDocumentId()
            {
                return 'doc1';
            }

            public function toFirestoreDocument()
            {
                return ['id' => 'doc1', 'name' => 'Test 1'];
            }
        };

        $model2 = new class extends Model {
            public function getFirestoreCollectionName()
            {
                return 'test_collection';
            }

            public function getFirestoreDocumentId()
            {
                return 'doc2';
            }

            public function toFirestoreDocument()
            {
                return ['id' => 'doc2', 'name' => 'Test 2'];
            }
        };

        // Create a collection with the models
        $collection = new Collection([$model1, $model2]);

        // Mock Firestore batch
        $batchMock = Mockery::mock('WriteBatch');
        $batchMock->shouldReceive('set')
            ->twice()
            ->andReturn($batchMock);
        $batchMock->shouldReceive('commit')
            ->once()
            ->andReturn(true);

        // Mock the Firestore document
        $documentMock1 = Mockery::mock('DocumentReference');
        $documentMock2 = Mockery::mock('DocumentReference');

        // Mock the Firestore collection
        $collectionMock = Mockery::mock('CollectionReference');
        $collectionMock->shouldReceive('document')
            ->once()
            ->with('doc1')
            ->andReturn($documentMock1);
        $collectionMock->shouldReceive('document')
            ->once()
            ->with('doc2')
            ->andReturn($documentMock2);

        // Mock the Firestore facade
        $firestoreMock = Mockery::mock('alias:Firestore');
        $firestoreMock->shouldReceive('batch')
            ->once()
            ->andReturn($batchMock);
        
        $firestoreMock->shouldReceive('collection')
            ->twice()
            ->with('test_collection')
            ->andReturn($collectionMock);

        // Call the macro
        $result = $collection->mirrorToFirestore();

        // Assert that the collection is returned for chaining
        $this->assertSame($collection, $result);
    }

    public function testCollectionMacroWithInvalidModel()
    {
        // Create a model that doesn't implement all required methods
        $invalidModel = new class extends Model {
            // Missing required methods
        };

        $validModel = new class extends Model {
            public function getFirestoreCollectionName()
            {
                return 'test_collection';
            }

            public function getFirestoreDocumentId()
            {
                return 'doc1';
            }

            public function toFirestoreDocument()
            {
                return ['id' => 'doc1', 'name' => 'Test 1'];
            }
        };

        // Create a collection with both valid and invalid models
        $collection = new Collection([$invalidModel, $validModel]);

        // Mock Firestore batch
        $batchMock = Mockery::mock('WriteBatch');
        $batchMock->shouldReceive('set')
            ->once() // Only valid model should be processed
            ->andReturn($batchMock);
        $batchMock->shouldReceive('commit')
            ->once()
            ->andReturn(true);

        // Mock the Firestore document
        $documentMock = Mockery::mock('DocumentReference');

        // Mock the Firestore collection
        $collectionMock = Mockery::mock('CollectionReference');
        $collectionMock->shouldReceive('document')
            ->once()
            ->with('doc1')
            ->andReturn($documentMock);

        // Mock the Firestore facade
        $firestoreMock = Mockery::mock('alias:Firestore');
        $firestoreMock->shouldReceive('batch')
            ->once()
            ->andReturn($batchMock);
        
        $firestoreMock->shouldReceive('collection')
            ->once()
            ->with('test_collection')
            ->andReturn($collectionMock);

        // Call the macro - should not throw an exception
        $result = $collection->mirrorToFirestore();

        // Assert that the collection is returned for chaining
        $this->assertSame($collection, $result);
    }
}