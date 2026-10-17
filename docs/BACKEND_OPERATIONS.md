# Backend operations

## Runtime and deployment

PHP 8.3 or newer is mandatory. Run `php artisan app:doctor` before opening a release. XAMPP PHP 8.2 cannot run this application and must be upgraded separately.

Production must use real SMTP credentials, a continuously supervised queue worker, HTTPS, and `APP_DEBUG=false`. Credentials belong only in `.env`; never commit them.

## Safety controls

- Critical payment, inventory, purchasing, rental approval, and lease workflows use transactions and row locks.
- State changes reject repeated or illegal operations.
- Mutating role routes, payment submission, messaging, authentication, public applications, availability polling, and webhooks are rate limited.
- Every web response includes `X-Request-ID`; audit records include the same correlation ID, actor, target, IP address, and user agent.
- Failed queue jobs are stored in `failed_jobs` and displayed on System Health. Inspect with `php artisan queue:failed`, retry with `php artisan queue:retry all`, and remove resolved entries with `php artisan queue:flush`.

## Backups

Set `BACKUP_PASSWORD` to a long random secret stored outside this machine, then run:

```powershell
php artisan app:backup --keep=7
```

The command creates an AES-256 encrypted ZIP when a password is configured, verifies that the database can be decrypted, writes a SHA-256 sidecar file, and rotates expired archives. Copy both files off-device. Losing the password makes encrypted backups unrecoverable.

## Privacy retention

Review the periods in `.env.example`, preview eligible records with `php artisan data:prune`, and execute with `php artisan data:prune --execute`. Automatic monthly pruning starts only when `DATA_RETENTION_ENABLED=true`.

## Continuous integration

`.github/workflows/quality.yml` runs formatting, tests, the production build, and system checks on PHP 8.3 and 8.4. A separate MySQL 8.4 job proves migrations remain portable before production migration.
