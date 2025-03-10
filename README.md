# Laravel Firestore Mirror

This package allows you to store a copy of a Laravel model inside a Firestore collection, keeping it in sync with your application's database.

## Installation

Install the package using Composer:

```sh
composer require firevel/firestore-mirror
```

Then, add the `\Firevel\FirestoreMirror\HasFirestoreMirror` trait to any model you want to mirror in Firestore:
```php
use Firevel\FirestoreMirror\HasFirestoreMirror;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFirestoreMirror;
}
```

## Configuration

### Firestore Collection

By default, each model will be stored in a Firestore collection that matches its database table name. To customize the collection name, define the $firestoreCollection property in your model:
```php
/**
 * Firestore collection name.
 *
 * @var string
 */
public $firestoreCollection = 'users';
```

Alternatively, you can define a getFirestoreCollectionName() method to dynamically determine the collection name:
```php
/**
 * Get firestore collection used for mirroring.
 *
 * @return string
 */
public function getFirestoreCollectionName()
{
    if (empty($this->firestoreCollection)) {
        return $this->getTable();
    }

    return $this->firestoreCollection;
}
```
### Firestore Document

To customize the document schema stored in Firestore, define a toFirestoreDocument() method in your model. By default, all model attributes are converted to an array:
```php
/**
 * Convert the model to a Firestore document.
 *
 * @return array
 */
public function toFirestoreDocument()
{
    return $this->attributesToArray();
}
```

### Firestore Document ID

By default, the document ID in Firestore matches the model’s primary key. You can customize this by defining a getFirestoreDocumentId() method:
```php
/**
 * Get the Firestore document ID used for mirroring.
 *
 * @return mixed
 */
public function getFirestoreDocumentId()
{
    return $this->getKey(); // Default: model's primary key
}
```

## Usage

Once the trait is added, the package will automatically mirror changes in your model to Firestore. The following actions trigger updates:
- Creating a new model instance
- Updating an existing model
- Deleting a model instance (removes the document from Firestore)
