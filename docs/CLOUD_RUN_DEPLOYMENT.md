# Deploy Technomart to Google Cloud Run

Technomart runs on Cloud Run as a Docker container. Firebase Hosting is optional; Cloud Run already provides a public HTTPS URL.

## Important production requirements

- Enable billing on the Google Cloud project.
- Use Cloud SQL MySQL or PostgreSQL. Do not use SQLite on Cloud Run because container files are ephemeral.
- Store secrets in Secret Manager, not in the image, repository, or command history.
- Uploaded profile photos, maintenance images, and payment proofs currently use Laravel's local disks. Cloud Run can lose these files whenever an instance restarts. Configure persistent object storage before accepting production uploads.
- Run migrations as a Cloud Run Job or from Cloud Shell before serving traffic.
- Queued mail requires a worker. Approval mail is sent immediately, but other queued notifications need a separate worker/job strategy.
- Scheduled Laravel commands should be invoked by Cloud Scheduler through a Cloud Run Job.

## 1. Google Cloud setup

Open Cloud Shell in the Google Cloud Console, then choose values:

```sh
export PROJECT_ID="your-google-cloud-project-id"
export REGION="asia-southeast1"
export SERVICE="technomart"

gcloud config set project "$PROJECT_ID"
gcloud services enable run.googleapis.com cloudbuild.googleapis.com artifactregistry.googleapis.com sqladmin.googleapis.com secretmanager.googleapis.com
```

The Singapore region (`asia-southeast1`) is a reasonable choice for users in the Philippines.

## 2. Clone and build

```sh
git clone https://github.com/FinnTheUi/Technomart.git
cd Technomart

gcloud artifacts repositories create technomart \
  --repository-format=docker \
  --location="$REGION" \
  --description="Technomart Cloud Run images"

gcloud builds submit \
  --tag "$REGION-docker.pkg.dev/$PROJECT_ID/technomart/web:latest"
```

## 3. Configure Cloud SQL

Create a Cloud SQL MySQL database and application user in the same region. Record its instance connection name (`PROJECT:REGION:INSTANCE`). Grant the Cloud Run service account the **Cloud SQL Client** role.

Laravel's Cloud SQL socket settings are:

```text
DB_CONNECTION=mysql
DB_SOCKET=/cloudsql/PROJECT:REGION:INSTANCE
DB_DATABASE=technomart
DB_USERNAME=technomart
DB_PASSWORD=(Secret Manager)
```

## 4. Create secrets

Generate an application key locally with `php artisan key:generate --show`. Create secrets without placing their values in Git:

```sh
printf '%s' 'base64:YOUR_APP_KEY' | gcloud secrets create technomart-app-key --data-file=-
printf '%s' 'YOUR_DATABASE_PASSWORD' | gcloud secrets create technomart-db-password --data-file=-
printf '%s' 'YOUR_GMAIL_APP_PASSWORD' | gcloud secrets create technomart-mail-password --data-file=-
```

Grant the Cloud Run service identity Secret Manager Secret Accessor access to these secrets.

## 5. Deploy the web service

Replace the Cloud SQL connection name and sender address:

```sh
gcloud run deploy "$SERVICE" \
  --image "$REGION-docker.pkg.dev/$PROJECT_ID/technomart/web:latest" \
  --region "$REGION" \
  --platform managed \
  --allow-unauthenticated \
  --port 8080 \
  --memory 1Gi \
  --cpu 1 \
  --min-instances 0 \
  --max-instances 3 \
  --add-cloudsql-instances "PROJECT:REGION:INSTANCE" \
  --set-env-vars "APP_NAME=Technomart,APP_ENV=production,APP_DEBUG=false,LOG_CHANNEL=stderr,DB_CONNECTION=mysql,DB_SOCKET=/cloudsql/PROJECT:REGION:INSTANCE,DB_DATABASE=technomart,DB_USERNAME=technomart,SESSION_DRIVER=database,CACHE_STORE=database,QUEUE_CONNECTION=database,MAIL_MAILER=smtp,MAIL_HOST=smtp.gmail.com,MAIL_PORT=587,MAIL_FROM_ADDRESS=YOUR_GMAIL_ADDRESS,MAIL_FROM_NAME=Technomart" \
  --set-secrets "APP_KEY=technomart-app-key:latest,DB_PASSWORD=technomart-db-password:latest,MAIL_PASSWORD=technomart-mail-password:latest"
```

After deployment, set `APP_URL` to the displayed HTTPS service URL and deploy a new revision. Also add `{APP_URL}/auth/google/callback` to the Google OAuth client's authorized redirect URIs if Google sign-in is enabled.

## 6. Database migration

Create a Cloud Run Job from the same image with the same environment, secrets, and Cloud SQL attachment, overriding the command to:

```text
php artisan migrate --force
```

Execute it once for initial deployment and after releases containing migrations. Seed the initial administrator only when required, with `ADMIN_EMAIL` and `ADMIN_PASSWORD` supplied as secrets.

## 7. Verify

```sh
curl --fail "https://YOUR_SERVICE_URL/up"
```

Then verify login, stall browsing, rental submission, manager approval email, uploads, queue processing, and scheduled tasks before treating the deployment as production-ready.
