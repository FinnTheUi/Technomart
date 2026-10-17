# Rentelligence

Predictive Analytics and Supply-Integrated Stall Rent Management System for **Technomart**.

Rentelligence manages a two-floor commercial mall. Prospective tenants apply for stalls online. Managers run leases, billing and maintenance. Admins run the supply catalog, restocking and the whole operation. A machine-learning model flags tenants who are likely to pay late, and explains why.

---

## Roles & modules

| Role | Can do |
|---|---|
| **Guest** | Browse the interactive floor plan; apply for an available stall; pay the first month online (PayMongo), by GCash reference, or in person; join a waitlist for an occupied stall |
| **Tenant** | Dashboard; rent and electric bills (pay online or submit a GCash reference); official receipts; sign lease contracts electronically and request renewals; maintenance requests with photos; order supplies and track orders; messaging; notifications |
| **Manager** | Stalls and floor-plan editor; review and approve rental applications; issue rent and electric bills; verify or reject payments; record cash; leases (renew, terminate, print); maintenance inbox; late-payment risk scores with reasons; revenue charts |
| **Admin** | System dashboard; users (create managers, change roles, deactivate); product catalog; stock ledger and count adjustments; suppliers and purchase orders; supply-order fulfilment and payment verification; locations; full audit log |

### Stall rental lifecycle

```
Guest applies ─► checkout (expiring signed link)
                   ├─ pay online (PayMongo, confirmed by webhook)
                   ├─ send GCash + submit 13-digit reference
                   └─ pay in person
Manager approves (confirms the payment) ─► tenant account (must verify email)
                                        + lease contract + monthly rent bills
                                        + official receipt for month 1
                                        (one row-locked transaction)
Lease: tenant requests renewal ─► manager renews (extra months billed) or terminates
Nightly: ended leases expire ─► stall available ─► waitlist emailed (guests included)
```

### Stalls & live availability

- The mall layout lives in `config/stalls.php`: **Floor 1** has Stalls 1–24 (U-shape) and **Floor 2** has Carts 1–10. Every slot is created automatically, by `php artisan stalls:sync`, when a manager first opens their dashboard, or nightly. Names follow the numbering ("Stall 12", "Cart 3").
- Managers set each stall's price, size and location, toggle maintenance, and can assign a registered tenant directly. Assigning a tenant creates the lease and rent bills.
- A stall is *occupied* only while it has an active lease. Maintenance is set by the manager.
- Every floor-plan map, and the public stall list, polls `GET /stalls/availability` (public fields only) and updates in place. Each stall shows a status bubble whose color matches its class: green ✓ available, blue occupied, amber maintenance. The legend and floor tabs show live counts.

### Payment workflow

Rent bills, electric bills and supply orders share a single payment workflow (`App\Services\Billing\PaymentService`):

```
unpaid ──tenant submits GCash reference──► for verification ──staff verifies──► paid + receipt
   ▲                                              │
   └────────────── staff rejects (reason) ────────┘
unpaid ──staff records cash / PayMongo webhook──► paid + receipt
```

- Tenants can never mark their own bills as paid.
- Receipts are numbered sequentially per year (`OR-2026-000123`) using a locked sequence, so there are no gaps and no duplicates.
- Overdue bills get a one-time late fee (`LATE_FEE_PERCENT` + `LATE_FEE_FIXED`, after `LATE_FEE_GRACE_DAYS`).
- PayMongo is optional. The "Pay online" buttons appear only when its keys are configured.

### Supply chain

- Every stock change is written to the **stock ledger** (`stock_movements`). This covers the opening balance, purchases received, tenant orders, cancellations and manual counts. Changes happen under a row lock, so stock can never oversell or go negative.
- **Tenant orders** reserve stock as soon as they are placed. They then move from *pending* → *paid* (verified like bills) → *delivered*. Unpaid orders are cancelled automatically after `ORDER_AUTO_CANCEL_DAYS`, and their stock is released.
- **Purchase orders** (`PO-2026-0001`) go from *draft* → *ordered* → *received*. Receiving adds stock.
- Products have a **reorder level**. Admins get a daily low-stock digest, and the purchase-order page pre-fills items that need restocking.
- The daily inventory and sales report is built from the ledger.

### Predictive analytics

- `ml/train_model.py` trains a logistic-regression model on real payment history. Laravel exports that history with `php artisan ml:train`, which retrains weekly. Training keeps the existing model if there is too little data, or if the data has only one class.
- Each bill's features use only history from before that bill. A bill counts as **late** if it is overdue *or* was paid after its due date.
- `php artisan ml:score` refreshes the cached scores daily in `tenant_risk_scores`, so dashboards never wait on Python. If Python or the model is unavailable, the tenant's historical late ratio is used instead.
- Each score shows its reasons, for example "4 of the last 6 bills were late", "2 bill(s) currently overdue", or "Pays about 5 day(s) late on average".

---

## Tech stack

- Laravel 13 / PHP 8.3+, Blade + Alpine.js, Vite
- MySQL (production) / SQLite (tests)
- Laravel Socialite (Google sign-in), PayMongo Checkout API (online payments)
- Python 3 with scikit-learn, pandas and joblib (risk model)

## Project layout

```
app/
  Console/Commands/     scheduled jobs and tooling (payments, leases, ML, stock, secret encryption)
  Contracts/            Payable: shared interface for rent bills, electric bills, supply orders
  Enums/                status enums (single source of truth for every status value)
  Http/
    Controllers/        Admin/, Manager/, Tenant/, Billing/, Auth/ + public rental flow
    Middleware/         RoleMiddleware, EnsureUserIsActive
    Requests/           Form Requests (validation) grouped by area
  Models/               Eloquent models (+ Concerns/HasPaymentWorkflow)
  Notifications/        queued mail + in-app notifications
  Policies/             authorization rules (who may pay, verify, renew, …)
  Services/
    Admin/              UserAccountService (deactivate instead of delete)
    Analytics/          TenantRiskAnalyzer (ML integration, cached scores, reasons)
    Auth/               Google OAuth credentials + account linking
    Billing/            PaymentService, PayMongo gateway, receipt numbering
    Inventory/          InventoryService (stock ledger, POs), OrderService, InventoryReport
    Rental/             application approval, LeaseService, WaitlistService
  Support/              EncryptedSecret (encrypted third-party keys)
ml/                     Python risk model (train / predict)
routes/web.php          routes grouped by role
routes/console.php      schedule
```

---

## Setup

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
# configure DB_*, MAIL_* and GCASH_ACCOUNT_* in .env, then:
php artisan migrate
php artisan db:seed                        # creates the admin account (ADMIN_EMAIL / ADMIN_PASSWORD)
pip install scikit-learn pandas joblib     # for the risk model (optional; there is a fallback)
composer run dev                           # server, queue worker, logs and Vite together
```

### Background processes

All emails and notifications are **queued**, and are dispatched only after the database transaction commits. Production needs:

```bash
php artisan queue:work --tries=3            # keep running (e.g. Supervisor / systemd)
* * * * * php /path/to/artisan schedule:run # cron entry for the scheduler
```

| Scheduled job | When | What it does |
|---|---|---|
| `stalls:sync` | daily 00:20 | Creates any missing layout stalls, applies standard names, makes availability match active leases |
| `leases:expire` | daily 00:10 | Expires ended leases, frees stalls, emails waitlists, cancels stale unpaid orders |
| `app:analyze-payments` | daily 06:00 | Marks overdue bills, applies late fees, sends reminders and lease-expiry warnings |
| `ml:score` | daily 06:30 | Refreshes cached late-payment risk scores |
| `ml:train` | Sundays 02:00 | Retrains the risk model on real payment history |
| `inventory:check-low-stock` | daily 07:00 | Low-stock digest to admins |

### Email verification

New accounts must verify their email before using the portal. This applies to self-registration, managers created by an admin, and tenants created by an approval. Accounts that already existed are grandfathered in by a migration. Google sign-ups are verified automatically.

### Encrypted third-party secrets

Secrets are never stored in plain text. `php artisan secret:encrypt {target}` asks for the secret (hidden input), encrypts it with `APP_KEY`, and writes it to `.env`:

| Target | Env var | Used for |
|---|---|---|
| `google` | `GOOGLE_CLIENT_SECRET_ENCRYPTED` | Sign up / sign in with Google |
| `paymongo-secret` | `PAYMONGO_SECRET_KEY_ENCRYPTED` | Creating PayMongo checkouts |
| `paymongo-webhook` | `PAYMONGO_WEBHOOK_SECRET_ENCRYPTED` | Verifying webhook signatures |

If you rotate `APP_KEY`, either keep the old key in `APP_PREVIOUS_KEYS` or re-run the command.

**Google:** create an OAuth client ID (type *Web application*) with the redirect URI `{APP_URL}/auth/google/callback`, and set `GOOGLE_CLIENT_ID`. New Google users become verified tenants. Existing accounts with the same Gmail address are linked, not duplicated.

**PayMongo:** in the PayMongo dashboard, create a webhook for `{APP_URL}/webhooks/paymongo` with the event `checkout_session.payment.paid`. Payment is confirmed only by the signed webhook, never by the browser redirect. The webhook also checks the session ID and the amount paid, and is safe to redeliver.

---

## Security notes

- Authorization is enforced through role route groups plus model **policies** (pay / review / manage) on every action.
- Guest rental pages are reachable only through **expiring signed URLs**.
- Approvals, payments and stock changes run in row-locked transactions: no double approval, double payment, duplicate receipt number or overselling.
- Deleting a user with billing, lease or order history **deactivates** the account instead. Financial records are kept, active leases are terminated, and the user is signed out on their next request.
- Payment screenshots are stored on the private disk and shown only to the payer and the reviewing staff.
- Login, registration, rental submission, payment submission, Google and webhook routes are rate-limited.
- Contracts store a SHA-256 document hash, the signer's IP address and user agent. The audit log records every administrative, billing and stock action.

## Testing

```bash
php artisan test
```

### Capstone demonstration

Create idempotent local demonstration data with `php artisan db:seed --class=DemoDataSeeder`. All demo accounts use `Password123!`; never run this seeder in production. See `docs/DEFENSE_CHECKLIST.md`, `docs/CAPSTONE_ALIGNMENT.md`, and `docs/BACKUP_AND_RECOVERY.md`.

The suite runs against in-memory SQLite and needs neither MySQL, Python, Google nor PayMongo credentials. It covers:

- payments and receipts
- late fees
- PayMongo webhooks (with signature checks)
- leases and waitlists
- the stock ledger and purchase orders
- risk scoring
- email verification
- authorization
- a render check of every page for every role
