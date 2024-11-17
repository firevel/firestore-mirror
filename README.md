# Laravel Firestore Mirror

This package can be used to store copy of Laravel model inside Firestore collection.

## Installation

Install package:
```sh
composer require firevel/firestore-mirror
```

Publish the `firestore-mirror.php` configuration file to your application's `config` directory:
```sh
php artisan vendor:publish --provider="Firevel\FirestoreMirror\FirestoreMirrorServiceProvider"
```

Add trait `\Firevel\FirestoreMirror\HasFirestoreMirror;` to the model you would like to mirror.

### Queueing

While not strictly required to use Laravel Firestore Mirror, you should strongly consider configuring a [queue driver](https://laravel.com/docs/master/queues) before using the library.
Running a queue worker will allow Laravel Firestore Mirror to queue all operations that mirror your model information to Firestore, providing much better response times for your application's web interface.

Once you have configured a queue driver, set the value of the `queue` option in your `config/firestore-mirror.php` configuration file to `true`:
```php
'queue' => true,
```

To specify the connection and queue that your Laravel Firestore Mirror jobs utilize, you may define the `queue` configuration option as an array:
```php
'queue' => [
    'connection' => 'redis',
    'queue' => 'firestore-mirror',
],
```

Of course, if you customize the connection and queue that Laravel Firestore Mirror jobs utilize, you should run a queue worker to process jobs on that connection and queue:
```sh
php artisan queue:work redis --queue=firestore-mirror
```

## Configuration

### Collection

By default model will be stored inside collection matching model table name. Use `$firestoreCollection` to customize collection name, for example:
```php
/**
 * Firestore collection name.
 *
 * @var string
 */
public $firestoreCollection = 'users';
```

Create `public function getFirestoreCollectionName()` method to customize collection name (by default table name).

### Document

Create `toFirestoreDocument` method to customize document schema. By default:
```php
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

Create `public function getFirestoreDocumentId()` method and `public function getFirestoreDocumentIdName()` method to customize document id. By default:
```php
/**
 * Get document id value used for mirroring.
 *
 * @return mixed
 */
public function getFirestoreDocumentId()
{
    return $this->getKey();
}

/**
 * Get document id name used for mirroring.
 *
 * @return mixed
 */
public function getFirestoreDocumentIdName()
{
    return $this->getKeyName();
}
```

## Mirroring

### Batch Import

If you are installing Laravel Firestore Mirror into an existing project, you may already have database records you need to import into Firestore.
Laravel Firestore Mirror provides a `firestore-mirror:import` Artisan command that you may use to import all of your existing records into Firestore:
```sh
php artisan firestore-mirror:import "App\Models\Post"
```

The flush command may be used to delete all of a model's records from Firestore:
```sh
php artisan firestore-mirror:flush "App\Models\Post"
```

### Adding Records

Once you have added the `Firevel\FirestoreMirror\HasFirestoreMirror` trait to a model, all you need to do is `save` or `create` a model instance and it will automatically be added to Firestore.
If you have configured Laravel Firestore Mirror to use queues this operation will be performed in the background by your queue worker:
```php
use App\Models\Order;

$order = new Order;

// ...

$order->save();
```

#### Adding Records via Query

If you would like to add a collection of models to Firestore via an Eloquent query, you may chain the `mirrorToFirestore` method onto the Eloquent query.
The `mirrorToFirestore` method will [chunk the results](https://laravel.com/docs/master/eloquent#chunking-results) of the query and add the records to Firestore.
Again, if you have configured Laravel Firestore Mirror to use queues, all of the chunks will be imported in the background by your queue workers:
```php
use App\Models\Order;

Order::where('price', '>', 100)->mirrorToFirestore();
```

You may also call the `mirrorToFirestore` method on an Eloquent relationship instance:
```php
$user->orders()->mirrorToFirestore();
```

Or, if you already have a collection of Eloquent models in memory, you may call the `mirrorToFirestore` method on the collection instance to add the model instances to Firestore:
```php
$orders->mirrorToFirestore();
```

### Updating Records

To update a model that has mirror, you only need to update the model instance's properties and `save` the model to your database.
Laravel Firestore Mirror will automatically mirror the changes to Firestore:
```php
use App\Models\Order;

$order = Order::find(1);

// Update the order...

$order->save();
```

You may also invoke the `mirrorToFirestore` method on an Eloquent query instance to update a collection of models.
If the models do not exist in Firestore, they will be created:
```php
Order::where('price', '>', 100)->mirrorToFirestore();
```

If you would like to update the Firestore records for all of the models in a relationship, you may invoke the `mirrorToFirestore` on the relationship instance:
```php
$user->orders()->mirrorToFirestore();
```

Or, if you already have a collection of Eloquent models in memory, you may call the `mirrorToFirestore` method on the collection instance to update the model instances in Firestore:
```php
$orders->mirrorToFirestore();
```

### Deleting Records

To delete a record from Firestore you may simply `delete` the model from the database:
```php
use App\Models\Order;

$order = Order::find(1);

$order->delete();
```

If you do not want to retrieve the model before deleting the record, you may use the `deleteFromFirestore` method on an Eloquent query instance:
```php
Order::where('price', '>', 100)->deleteFromFirestore();
```

If you would like to delete the Firestore records for all of the models in a relationship, you may invoke the `deleteFromFirestore` on the relationship instance:
```php
$user->orders()->deleteFromFirestore();
```

Or, if you already have a collection of Eloquent models in memory, you may call the `deleteFromFirestore` method on the collection instance to delete the model instances from Firestore:
```php
$orders->deleteFromFirestore();
```

### Pause Mirroring

Sometimes you may need to perform a batch of Eloquent operations on a model without mirroring the model data to Firestore.
You may do this using the `withoutMirroringToFirestore` method.
This method accepts a single closure which will be immediately executed.
Any model operations that occur within the closure will not be mirrored to Firestore:
```php
use App\Models\Order;

Order::withoutMirroringToFirestore(function () {
    // Perform model actions...
});
```
