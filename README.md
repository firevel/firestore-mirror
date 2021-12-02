# Laravel Firestore Mirror

This package can be used to store copy of Laravel model inside Firestore collection.

## Installation

Install package:
```
composer require firevel/firestore-mirror
```

Add trait `\Firevel\FirestoreMirror\HasFirestoreMirror;` to the model you would like to mirror.


### Configuration

By default model will be stored inside collection matching model table name. Use `$firestoreCollection` to customize collection name, for example:
```
    /**
     * Firestore collection name.
     *
     * @var string
     */
    public $firestoreCollection = 'users';
```

Extend `toFirestoreDocument` method to customize document schema. By default:
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

Extend `getFirestoreCollectionName` method to generate dynamic collection name.

