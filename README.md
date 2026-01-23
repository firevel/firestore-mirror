# Laravel Firestore Mirror

[![Latest Version on Packagist](https://img.shields.io/packagist/v/firevel/firestore-mirror.svg?style=flat-square)](https://packagist.org/packages/firevel/firestore-mirror)
[![Total Downloads](https://img.shields.io/packagist/dt/firevel/firestore-mirror.svg?style=flat-square)](https://packagist.org/packages/firevel/firestore-mirror)
[![CI](https://github.com/firevel/firestore-mirror/actions/workflows/ci.yml/badge.svg)](https://github.com/firevel/firestore-mirror/actions/workflows/ci.yml)

Automatically sync your Laravel Eloquent models to Google Firestore collections in real-time. This package provides a seamless way to mirror your database records to Firestore, enabling powerful real-time features and offline capabilities for your applications.

## Features

- 🔄 **Automatic Synchronization**: Changes to your Eloquent models are automatically reflected in Firestore
- 🚀 **Batch Operations**: Efficiently sync entire collections using Firestore's batch API
- 📂 **Multiple Collections**: Mirror a single model to multiple Firestore collections for denormalization
- 🎯 **Flexible Configuration**: Customize collection names, document IDs, and document structure
- 🧩 **Simple Integration**: Just add a trait to your existing Eloquent models
- ⚡ **Performance Optimized**: Uses Firestore batch operations for bulk updates
- 🛠️ **Artisan Command**: Built-in command for bulk mirroring with progress tracking and chunked processing

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

### Mirroring to Multiple Collections

You can mirror a single model to multiple Firestore collections. This is useful for denormalization patterns common in Firestore, where you might want the same data available in different collections for efficient querying.

#### Using Property
```php
class User extends Model
{
    use HasFirestoreMirror;

    // Mirror to multiple collections
    public $firestoreCollection = ['users', 'public_profiles'];
}
```

#### Using getFirestoreCollectionName() Method
```php
class User extends Model
{
    use HasFirestoreMirror;

    public function getFirestoreCollectionName()
    {
        return ['users', 'public_profiles', 'search_index'];
    }
}
```

#### Using getFirestoreCollectionNames() Method (Recommended)
```php
class User extends Model
{
    use HasFirestoreMirror;

    public function getFirestoreCollectionNames(): array
    {
        return ['users', 'public_profiles'];
    }
}
```

When mirroring to multiple collections:
- All collections receive the same document ID and document structure
- Write and delete operations use Firestore's batch API for atomicity
- If you need different document structures per collection, override `toFirestoreDocument()` with conditional logic

**Priority Order**: If multiple methods are defined, `getFirestoreCollectionNames()` takes precedence over `getFirestoreCollectionName()`, which takes precedence over the `$firestoreCollection` property.

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

### Artisan Command for Bulk Mirroring

For large-scale operations or initial data migration, use the included Artisan command to mirror all records of a model to Firestore:

```bash
# Mirror all users to Firestore
php artisan firestore:mirror "App\Models\User"

# Mirror with custom chunk size (default: 100)
php artisan firestore:mirror "App\Models\User" --chunk=500

# Mirror any model with the HasFirestoreMirror trait
php artisan firestore:mirror "App\Models\Post" --chunk=200
```

**Features:**
- 📊 **Progress Bar**: Real-time progress feedback during the mirror operation
- 🔄 **Chunked Processing**: Processes records in configurable batches to prevent memory issues
- ⚡ **Batch Operations**: Uses Firestore batch API for optimal performance
- ✅ **Validation**: Ensures the model exists and uses the `HasFirestoreMirror` trait

**Example Output:**
```
Starting mirror process for App\Models\User...
Total records to mirror: 1500
1500/1500 [████████████████████████████] 100%
Successfully mirrored 1500 records to Firestore.
```

**When to Use:**
- Initial data migration when setting up Firestore mirroring
- Recovering from sync failures or data inconsistencies
- Re-syncing data after changing `toFirestoreDocument()` structure
- Batch operations on very large collections (thousands+ records)

**Note:** The command respects the `shouldMirrorToFirestore()` method and will skip records that shouldn't be mirrored. For programmatic batch operations within your application code, use the collection macro `->mirrorToFirestore()` instead.

### Deleting from Firestore

To manually delete a model from Firestore without deleting it from your database:

```php
$user = User::find(1);
$user->deleteFromFirestore();
```

## Advanced Usage

### Disabling Syncing Temporarily

You can temporarily disable Firestore syncing for specific operations using the `withoutSyncingToFirestore()` method:

```php
// Disable syncing for a single operation
User::withoutSyncingToFirestore(function () {
    User::create([
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]);
});

// Disable syncing for multiple operations
User::withoutSyncingToFirestore(function () {
    // None of these will sync to Firestore
    $user = User::create(['name' => 'Jane']);
    $user->update(['email' => 'jane@example.com']);

    User::find(1)->update(['status' => 'inactive']);
});

// The callback return value is passed through
$user = User::withoutSyncingToFirestore(function () {
    return User::create(['name' => 'Test User']);
});
```

This is particularly useful for:
- Importing large datasets without Firestore overhead
- Running database seeders and factories during testing
- Performing bulk operations that you'll sync manually later
- Temporary data that doesn't need to be mirrored

### Conditional Syncing

The package provides built-in support for conditional syncing. Override the `shouldMirrorToFirestore()` method to control when models should be synced to Firestore:

```php
class User extends Model
{
    use HasFirestoreMirror;

    /**
     * Determine if the model should be mirrored to Firestore.
     *
     * @return bool
     */
    public function shouldMirrorToFirestore()
    {
        // Only mirror active users with verified emails
        return $this->is_active && $this->email_verified_at !== null;
    }
}
```

When `shouldMirrorToFirestore()` returns `false`:
- The model will not be synced to Firestore on create/update
- The model will not be deleted from Firestore on deletion
- Both `mirrorToFirestore()` and `deleteFromFirestore()` will return early without making Firestore calls

This is useful for:
- Skipping sync for draft or incomplete records
- Conditional syncing based on user roles or permissions
- Implementing soft-delete patterns where you want to keep Firestore data
- Performance optimization by reducing unnecessary Firestore writes

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
