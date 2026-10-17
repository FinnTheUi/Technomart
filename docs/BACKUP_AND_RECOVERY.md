# Backup and recovery

Create a local SQLite backup with:

```powershell
php artisan app:backup --keep=7
```

The timestamped ZIP in `storage/app/backups` contains the SQLite database, public uploads, private payment evidence, and the trained risk model. Every new archive is reopened and checked before success is reported. `--keep=7` removes older Technomart archives after retaining the seven newest. Copy at least one ZIP to a separate protected device or storage account.

When `BACKUP_PASSWORD` is configured, every file in the archive is encrypted with AES-256. The adjacent `.sha256` file detects corruption or tampering. Keep the password separate from the archive and copy both the ZIP and checksum off-device.

Recovery is intentionally manual to prevent an accidental overwrite: stop the web server, queue, and scheduler; make a backup of the current installation; extract the selected archive into a clean staging directory; inspect it; then replace only the database and required storage/model files. Run `php artisan optimize:clear` and verify System Health before reopening the app.

Run `php artisan app:doctor` before and after restoration. For a restoration drill, extract the backup into a temporary directory, point `DB_DATABASE` at the extracted database, then run `php artisan app:doctor`. Never test restoration over the active database.

After moving to MySQL, use an authenticated `mysqldump`/restore procedure instead of the SQLite command and test restoration regularly.

