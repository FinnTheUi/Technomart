# Backup and recovery

Create a local SQLite backup with:

```powershell
php artisan app:backup
```

The timestamped ZIP in `storage/app/backups` contains the SQLite database, public uploads, private payment evidence, and the trained risk model. Copy the ZIP to a separate protected device or storage account.

Recovery is intentionally manual to prevent an accidental overwrite: stop the web server, queue, and scheduler; make a backup of the current installation; extract the selected archive into a clean staging directory; inspect it; then replace only the database and required storage/model files. Run `php artisan optimize:clear` and verify System Health before reopening the app.

After moving to MySQL, use an authenticated `mysqldump`/restore procedure instead of the SQLite command and test restoration regularly.

