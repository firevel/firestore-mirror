# Laravel Firestore Mirror

This package can be used to store copy of Laravel model inside Firestore collection.

## Installation

Install package:
```
composer require firevel/firestore-mirror
```

Add trait `\Firevel\FirestoreMirror\HasFirestoreMirror;` to the model you would like to mirror.


### Configuration

## Collection

By default model will be stored inside collection matching model table name. Use `$firestoreCollection` to customize collection name, for example:
```
    /**
     * Firestore collection name.
     *
     * @var string
     */
    public $firestoreCollection = 'users';
```

Create `public function getFirestoreCollectionName()` method to customize collection name (by default table name).

## Document

Create `toFirestoreDocument` method to customize document schema. By default:
```
    /**
     * Convert model to firestore document.
     *
     * @return array
     */
    public function toFirestoreDocument()
    {
        return $this->attributesToArray();
    }
```

Create `public function getFirestoreDocumentId()` method to customize document id (model key by default).
