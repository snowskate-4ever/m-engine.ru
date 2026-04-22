# Laravel 13 Upgrade Notes

## Scope
- Upgraded application from Laravel 12 to Laravel 13.
- Updated first-party and core ecosystem packages to Laravel 13 compatible versions.
- Applied required compatibility changes from the Laravel 13 upgrade guide.

## Dependency Changes
- `php` -> `^8.3`
- `laravel/framework` -> `^13.0` (resolved to `13.6.0`)
- `laravel/tinker` -> `^3.0`
- `phpunit/phpunit` -> `^12.0`
- `laravel/fortify` -> `^1.36.2`
- `laravel/sanctum` -> `^4.3.1`
- `laravel/pail` -> `^1.2.6`
- `laravel/sail` -> `^1.57.0`
- `nunomaduro/collision` -> `^8.9.4`
- `moonshine/moonshine` -> `^4.10.0`
- `spatie/laravel-medialibrary` -> `^11.21.0`
- `livewire/flux` -> `^2.13.2`
- `livewire/volt` -> `^1.10.5`
- Added explicit pin: `livewire/livewire` -> `^3.7.15` to avoid an implicit major jump to v4 during framework migration.

## Code Compatibility Adjustments
- Replaced CSRF middleware class references with Laravel 13 class:
  - `config/moonshine.php`: `VerifyCsrfToken` -> `PreventRequestForgery`
  - `config/sanctum.php`: `ValidateCsrfToken` -> `PreventRequestForgery`
- Added hardened cache unserialization setting:
  - `config/cache.php`: `serializable_classes` key (empty allow-list by default).
- Fixed Livewire profile switch sync after upgrade:
  - `app/Livewire/Music/MusicProfilesPage.php`: added `updatedQuickSwitchTab()` to keep `tab` and quick switch state synchronized.

## Verification Results
- `php artisan --version` -> `Laravel Framework 13.6.0`
- `php -d memory_limit=512M ./vendor/bin/phpunit` -> `OK (269 tests, 765 assertions)`
- `php artisan route:list` completed successfully (route registration smoke check).

## Known Issues / Follow-up
- `composer audit` reports one low severity advisory:
  - `firebase/php-jwt` (`CVE-2025-45769`, affected `<7.0.0`).
  - Current constraint is `^6.10`; plan upgrade path to v7 after compatibility validation.
- Running tests through `artisan test` with default memory limit may hit memory exhaustion in this environment. For CI/local reliability, prefer:
  - `php -d memory_limit=512M ./vendor/bin/phpunit`

## Rollback Procedure
1. Revert `composer.json` and `composer.lock` to the previous Laravel 12 state.
2. Reinstall dependencies:
   - `composer install`
3. Revert framework-specific config changes:
   - `config/moonshine.php`
   - `config/sanctum.php`
   - `config/cache.php`
   - `app/Livewire/Music/MusicProfilesPage.php`
4. Run baseline verification:
   - `php -d memory_limit=512M ./vendor/bin/phpunit`
   - `php artisan --version`

## Post-deploy Checks
- Login/logout and session persistence.
- Sanctum SPA authentication flow.
- MoonShine admin panel page access.
- Livewire profile tab switching UI behavior.
- Queue job processing and failed-job metrics.
- Public profile pages and music search endpoints.
