# Laravel DMS Disk Server

[![Latest Version on Packagist](https://img.shields.io/packagist/v/arshad1114/laravel-dms-disk-server.svg?style=flat-square)](https://packagist.org/packages/arshad1114/laravel-dms-disk-server)
[![Total Downloads](https://img.shields.io/packagist/dt/arshad1114/laravel-dms-disk-server.svg?style=flat-square)](https://packagist.org/packages/arshad1114/laravel-dms-disk-server)
[![License](https://img.shields.io/packagist/l/arshad1114/laravel-dms-disk-server.svg?style=flat-square)](https://packagist.org/packages/arshad1114/laravel-dms-disk-server)

Laravel server-side receiver package for [arshad1114/laravel-dms-disk](https://github.com/arshad1114/laravel-dms-disk).

Install this package in your **DMS Laravel service**. It exposes a standardized HTTP API so any consumer service using `arshad1114/laravel-dms-disk` can store, retrieve and manage files on this service using the native `Storage` facade — no custom HTTP code needed on either side.

## How it works
```
Consumer service                         DMS service (this package)
────────────────                         ──────────────────────────
Storage::disk('dms')->put(...)           POST /dms-disk/upload
Storage::disk('dms')->get(...)    HTTP   GET  /dms-disk/file
Storage::disk('dms')->delete()   ────►  DELETE /dms-disk/file
...                                      ...
                                         Calls Storage::put(...)
                                         using your filesystems.php
```

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12

## Installation
```bash
composer require arshad1114/laravel-dms-disk-server
```

The `DmsServerServiceProvider` is auto-discovered. All routes, middleware and controller are registered automatically.

Publish the config:
```bash
php artisan vendor:publish --tag=dms-disk-server-config
```

## Configuration

Add to your DMS service `.env`:
```env
DMS_SERVER_TOKEN=your-strong-secret-token
```

Set your storage disk as you normally would in any Laravel app:
```env
FILESYSTEM_DISK=local
```

The package uses whatever disk you configure here — local, S3, GCS, or any other Laravel-supported driver. It has zero opinion about where files are stored.

### Full config reference

All options in `config/dms-disk-server.php`:

| Key | Env variable | Default | Description |
|---|---|---|---|
| `token` | `DMS_SERVER_TOKEN` | `''` | Bearer token consumers must send |
| `max_file_size_kb` | `DMS_SERVER_MAX_FILE_SIZE_KB` | `102400` | Max upload size in KB (default 100 MB) |
| `route_prefix` | `DMS_SERVER_ROUTE_PREFIX` | `'dms-disk'` | URL prefix for all endpoints |

## API endpoints

All endpoints are protected by bearer token authentication.

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/dms-disk/upload` | Upload a file |
| `GET` | `/dms-disk/file` | Download a file |
| `DELETE` | `/dms-disk/file` | Delete a file |
| `GET` | `/dms-disk/exists` | Check if a file exists |
| `GET` | `/dms-disk/url` | Get public URL |
| `GET` | `/dms-disk/temp-url` | Get temporary signed URL |
| `POST` | `/dms-disk/move` | Move or rename a file |
| `GET` | `/dms-disk/list` | List files in a directory |
| `GET` | `/dms-disk/metadata` | Get file metadata |
| `POST` | `/dms-disk/visibility` | Set file visibility |

## Authentication

Every request must include the token as a bearer token:
```
Authorization: Bearer your-strong-secret-token
```

Or as a custom header:
```
X-DMS-Token: your-strong-secret-token
```

## Verify installation

After installing, verify all routes are registered:
```bash
php artisan route:list --path=dms-disk
```

You should see all 10 endpoints listed.

## Troubleshooting

### 401 Unauthorized
`DMS_SERVER_TOKEN` in the DMS service does not match `DMS_TOKEN` in the consumer service. Make sure both values are identical.

### 500 DMS_SERVER_TOKEN is not set
`DMS_SERVER_TOKEN` is missing from `.env`. Add it:
```env
DMS_SERVER_TOKEN=your-strong-secret-token
```

### Routes not showing up
```bash
php artisan route:clear
php artisan route:list --path=dms-disk
```

If still missing, check the provider is registered:
```bash
php artisan package:discover
```

## Consumer package

Install this on every service that needs to store files on this DMS:

[arshad1114/laravel-dms-disk](https://github.com/arshad1114/laravel-dms-disk)

## Contributing

Contributions are welcome. Please:

1. Fork the repo
2. Create a feature branch: `git checkout -b feat/your-feature`
3. Write tests for your change
4. Make sure all tests pass: `./vendor/bin/phpunit`
5. Open a pull request

## License

MIT — see [LICENSE](LICENSE) file.