# Laravel Firestore Mirror

[![Latest Version on Packagist](https://img.shields.io/packagist/v/firevel/firestore-mirror.svg?style=flat-square)](https://packagist.org/packages/firevel/firestore-mirror)
[![Total Downloads](https://img.shields.io/packagist/dt/firevel/firestore-mirror.svg?style=flat-square)](https://packagist.org/packages/firevel/firestore-mirror)
[![CI](https://github.com/firevel/firestore-mirror/actions/workflows/ci.yml/badge.svg)](https://github.com/firevel/firestore-mirror/actions/workflows/ci.yml)

Automatically sync your Laravel Eloquent models to Google Firestore collections in real-time. This package provides a seamless way to mirror your database records to Firestore, enabling powerful real-time features and offline capabilities for your applications.

## Features

- 🔄 **Automatic Synchronization**: Changes to your Eloquent models are automatically reflected in Firestore
- 🚀 **Batch Operations**: Efficiently sync entire collections using Firestore's batch API
- 🎯 **Flexible Configuration**: Customize collection names, document IDs, and document structure
- 🧩 **Simple Integration**: Just add a trait to your existing Eloquent models
- ⚡ **Performance Optimized**: Uses Firestore batch operations for bulk updates

## Requirements

- PHP 8.0 or higher
- Laravel 8.0 or higher  
- [firevel/firestore](https://github.com/firevel/firestore) package

## Installation

Install the package using Composer:

```bash
composer require firevel/firestore-mirror
```

## Quick Start

Add the `HasFirestoreMirror` trait to any Eloquent model you want to sync with Firestore:

```php
use Firevel\FirestoreMirror\HasFirestoreMirror;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFirestoreMirror;
    
    // Optional: Customize the Firestore collection name
    public $firestoreCollection = 'users';
    
    // Optional: Customize the document structure
    public function toFirestoreDocument()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

That's it! Your User model will now automatically sync to Firestore whenever you create, update, or delete records.

## Configuration

### Customizing Collection Names

By default, the Firestore collection name matches your model's database table name. You can customize this in two ways:

#### Static Collection Name
```php
class User extends Model
{
    use HasFirestoreMirror;
    
    public $firestoreCollection = 'app_users';
}
```

#### Dynamic Collection Name
```php
class User extends Model
{
    use HasFirestoreMirror;
    
    public function getFirestoreCollectionName()
    {
        return 'users_' . $this->tenant_id;
    }
}
```

### Customizing Document Structure

Control exactly what data gets synced to Firestore by overriding the `toFirestoreDocument()` method:

```php
public function toFirestoreDocument()
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'email' => $this->email,
        'role' => $this->role,
        'metadata' => [
            'last_login' => $this->last_login?->toIso8601String(),
            'verified' => $this->email_verified_at !== null,
        ],
        'updated_at' => $this->updated_at->toIso8601String(),
    ];
}
```

### Customizing Document IDs

By default, the Firestore document ID matches your model's primary key. Override this behavior:

```php
public function getFirestoreDocumentId()
{
    return $this->uuid; // Use a UUID field instead
}
```

## Usage

### Automatic Syncing

Once you've added the `HasFirestoreMirror` trait, your models will automatically sync to Firestore on these events:

- **Create**: New model instances are added to Firestore
- **Update**: Changes to existing models are synced to Firestore
- **Delete**: Deleted models are removed from Firestore

```php
// This will automatically create a document in Firestore
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// This will automatically update the document in Firestore
$user->update(['name' => 'Jane Doe']);

// This will automatically delete the document from Firestore
$user->delete();
```

### Manual Syncing

You can manually sync individual models:

```php
$user = User::find(1);
$user->mirrorToFirestore(); // Manually sync to Firestore
```

### Batch Syncing Collections

The package provides a powerful `mirrorToFirestore()` collection macro for efficiently syncing multiple models at once using Firestore's batch operations:

```php
// Sync all users to Firestore
User::all()->mirrorToFirestore();

// Sync filtered collections
User::where('active', true)->get()->mirrorToFirestore();

// Sync users with relationships loaded
User::with('posts')->get()->mirrorToFirestore();

// Sync paginated results
User::paginate(100)->mirrorToFirestore();
```

The batch operation is atomic - either all documents are synced successfully, or none are.

### Deleting from Firestore

To manually delete a model from Firestore without deleting it from your database:

```php
$user = User::find(1);
$user->deleteFromFirestore();
```

## Advanced Usage

### Conditional Syncing

You might want to sync only certain models based on conditions:

```php
class User extends Model
{
    use HasFirestoreMirror;
    
    public function shouldMirrorToFirestore()
    {
        return $this->is_active && $this->email_verified_at !== null;
    }
    
    public function mirrorToFirestore()
    {
        if ($this->shouldMirrorToFirestore()) {
            return parent::mirrorToFirestore();
        }
        
        return $this;
    }
}
```

### Multi-Tenant Applications

For multi-tenant applications, you can dynamically set collection names:

```php
class Order extends Model
{
    use HasFirestoreMirror;
    
    public function getFirestoreCollectionName()
    {
        return "tenants/{$this->tenant_id}/orders";
    }
}
```

## Testing

Run the test suite:

```bash
composer test
```

Run a specific test:

```bash
vendor/bin/phpunit tests/Unit/HasFirestoreMirrorTest.php
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
