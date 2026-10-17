# Release checklist

1. Use PHP 8.3 or newer everywhere. The current terminal uses PHP 8.4.5, while the detected XAMPP PHP 8.2.12 is incompatible. Upgrade XAMPP PHP before serving this app through Apache; do not mix those runtimes.
2. Keep development in `database/database.sqlite`. Copy `.env.demo.example` to `.env.demo`, generate a key, create `database/demo.sqlite`, and run commands with `php artisan --env=demo ...` for demonstrations.
3. Run `php artisan app:doctor` and resolve every FAIL.
4. Run `composer test:report` and require zero failures.
5. Run `npm run build` and confirm there are no external CDN/font dependencies.
6. Run `php artisan app:backup --keep=7`.
7. Extract the newest ZIP into a temporary directory and run SQLite `PRAGMA quick_check` against the extracted database.
8. Commit the verified state and tag it as the release candidate.
