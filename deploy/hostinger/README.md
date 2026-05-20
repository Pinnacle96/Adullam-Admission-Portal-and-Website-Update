# Hostinger Shared Hosting CI/CD

This setup deploys the project to Hostinger shared hosting through GitHub Actions using SSH.

It is designed to:
- build PHP dependencies in CI
- upload a release package over SSH
- back up the current live code before every deployment
- deploy only application code while preserving live config and writable folders
- allow manual rollback to a previous backup if a deployment goes wrong

## What Gets Preserved On The Server

These paths are not overwritten during deployment:
- `db.php`
- `dashboard/db.php`
- `includes/dbconnection.php`
- `june2025/dashboard/db.php`
- `june2025/includes/dbconnection.php`
- `token.json`
- `uploads/`
- `dashboard/uploads/`
- `dashboard/logs/`
- `dashboard/letters/admission_letters/`
- `june2025/dashboard/uploads/`
- `june2025/dashboard/logs/`
- `june2025/dashboard/letters/admission_letters/`
- `user/uploads/`
- `user/userimages/`
- `error_log`
- `dashboard/error_log`
- `dashboard/error_log.txt`
- `june2025/dashboard/error_log`

This is important for this project because live database credentials, student uploads, and generated PDF letters must stay on the server.

## GitHub Secrets To Add

Add these secrets in the GitHub environment named `adullam secret`:

- `HOSTINGER_HOST`: SSH host or IP
- `HOSTINGER_USER`: SSH username
- `HOSTINGER_PORT`: SSH port from Hostinger
- `HOSTINGER_SSH_PRIVATE_KEY`: private key used by GitHub Actions
- `HOSTINGER_PUBLIC_PATH`: absolute path to the live site, for example `/home/u123456789/domains/example.com/public_html`
- `HOSTINGER_DEPLOY_PATH`: absolute path for deployment working files and backups, for example `/home/u123456789/deployments/adullam`

Optional secrets:

- `HOSTINGER_SSH_FINGERPRINT`: SSH host fingerprint for stronger verification
- `HOSTINGER_HEALTHCHECK_URL`: URL to test after deployment, for example `https://example.com/`
- `HOSTINGER_KEEP_BACKUPS`: number of backups to keep, default is `5`

## One-Time SSH Setup

1. Enable SSH access in Hostinger hPanel.
2. Generate a deployment key on your machine:

```bash
ssh-keygen -t ed25519 -C "github-actions@adullam" -f hostinger_adullam_deploy
```

3. Add the public key content from `hostinger_adullam_deploy.pub` to Hostinger SSH authorized keys.
4. Add the private key content from `hostinger_adullam_deploy` to the GitHub secret `HOSTINGER_SSH_PRIVATE_KEY`.
5. Add the other Hostinger secrets listed above.

## First Deployment Notes

- This setup assumes the website is already live in `HOSTINGER_PUBLIC_PATH`.
- The workflow backs up the current code before replacing it.
- Because deployment preserves the live config files, your existing production database settings stay intact.
- If `HOSTINGER_PUBLIC_PATH` is empty on the first deployment, create the live config files there first:
  - `db.php`
  - `dashboard/db.php`
  - `includes/dbconnection.php`

## Deploy Workflow

The deploy workflow:
- runs on push to `master`
- can also be started manually
- installs Composer dependencies
- uploads the packaged release
- creates a timestamped backup of the current live code
- syncs the new release into the live folder
- optionally checks `HOSTINGER_HEALTHCHECK_URL`

If the health check fails, the workflow restores the backup automatically and marks the deployment as failed.

## Rollback Workflow

The rollback workflow is manual.

You can:
- roll back to the latest backup by leaving the input empty
- roll back to a specific backup by entering the backup file name

Rollback restores code only. It does not roll back database changes.

## Important Limitation

- Database schema or data changes are not automatically reversible by this rollback.
- If a deployment includes database changes, use a matching database backup strategy as well.
