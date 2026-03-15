# laravel-dms-disk-server

Laravel server-side receiver package for [arshad1114/laravel-dms-disk](https://github.com/arshad1114/laravel-dms-disk).

Install this package in your **DMS Laravel service**. It exposes a standardized HTTP API so any consumer service using `arshad1114/laravel-dms-disk` can store, retrieve, and manage files on this service using the native `Storage` facade — no custom HTTP code needed on either side.

## How it works
```
Consumer service                      DMS service
────────────────                      ───────────────────────────
Storage::disk('dms')->put(...)  HTTP  This package receives the
                               ────► request and calls
                                     Storage::put(...) using
                                     your own filesystems.php
```

## Installation
```bash
composer require arshad1114/laravel-dms-disk-server
```

The `DmsServerServiceProvider` is auto-discovered. All routes, middleware and controller are registered automatically.

## Configuration

Publish the config:
```bash
php artisan vendor:publish --tag=dms-disk-server-config
```

Add to your DMS `.env`:
```
DMS_SERVER_TOKEN=your-strong-secret-token
```

Set your storage disk in `.env` as you normally would in any Laravel app:
```
FILESYSTEM_DISK=local   # or s3, or any disk you have configured
```

## Consumer package

Install this on the consumer side:

[arshad1114/laravel-dms-disk](https://github.com/arshad1114/laravel-dms-disk)

## License

MIT
