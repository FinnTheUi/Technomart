# Capstone defense checklist

## Before the presentation

- [ ] Run `php artisan migrate --force`
- [ ] Run `php artisan db:seed --class=DemoDataSeeder` on a local/demo database only
- [ ] Run `php artisan ml:train` then `php artisan ml:score`
- [ ] Run `php artisan app:backup`
- [ ] Start `composer run dev`
- [ ] Start `php artisan schedule:work` in a second terminal
- [ ] Open Admin → System Health and resolve all required red checks
- [ ] Run `php artisan test`
- [ ] Run `npm run build`

## Demonstration sequence

- [ ] Guest views the live floor plan and submits an application
- [ ] Manager approves the application and the tenant/lease/bills are created atomically
- [ ] Tenant verifies email, signs the lease, views bills, and submits payment
- [ ] Manager verifies payment and opens the numbered receipt
- [ ] Tenant orders supplies; show stock reservation and cancellation restoration
- [ ] Tenant files maintenance only for their active leased stall
- [ ] Manager processes maintenance and shows its timeline
- [ ] Manager explains the risk score, factors, evaluation metrics, and human-review limitation
- [ ] Admin demonstrates users, inventory, reports, audit export, session invalidation, and health checks
- [ ] Attempt cross-role/cross-tenant access and show the 403 response
- [ ] Show a generated backup and explain recovery procedures

## Evidence to retain

- [ ] Passing test output
- [ ] ML metrics JSON and training log
- [ ] Screenshots at desktop and mobile widths
- [ ] Browser/black-box test sheet
- [ ] User evaluation and ISO/IEC 25010 questionnaire results
- [ ] Backup file and restore rehearsal notes

