# MooPanel

MooPanel is an administrative portal for managing many Moodle instances from one place. It keeps instance data in sync (core version, plugins, courses, update logs), runs core and plugin updates, and manages course backups across instances.

The application has two Filament panels:

- **Admin panel** (`/admin`): work across all instances. Manage instances, run multi-instance update and backup wizards, view logs and delta reports.
- **Instance panel** (`/instance`): work inside a single Moodle instance. Dashboard, core and plugin updates, courses and backups.

## Tech stack

- PHP 8.4, Laravel 13, Filament 5, Livewire
- Any database supported by Laravel (PostgreSQL, MySQL/MariaDB, ...). The DDEV setup uses PostgreSQL 14.
- Redis (optional, see [Queue and scheduler](#queue-and-scheduler))
- Vite, Tailwind CSS 4 (Node.js 24 inside DDEV)

## Requirements

- [DDEV](https://ddev.readthedocs.io/) (v1.25 or newer)
- A Docker provider supported by DDEV (OrbStack, Docker Desktop, Colima, ...)

## Local setup

```bash
git clone https://github.com/AGILEDROP/moopanel.git
cd moopanel
```

Start the project and install everything. On the first `ddev start`, DDEV creates `.env` from `.env.example` and fills in the database connection and `APP_URL` for you.

```bash
ddev start
ddev composer install
ddev artisan migrate --seed       # seeds default backup storage and backup settings
ddev artisan storage:link         # add --relative if the symlink does not work
ddev npm install
ddev npm run build                # or `ddev npm run dev` while working on assets
```

If `APP_KEY` in `.env` is still empty after the first start, generate it once with `ddev artisan key:generate`. Do not regenerate it later, because MooPanel encrypts instance API keys with it.

Open the panel at https://moodle-admin-panel.ddev.site/admin (or run `ddev launch`).

Useful DDEV commands:

| Command | Purpose |
| --- | --- |
| `ddev describe` | Show URLs, ports and service status |
| `ddev ssh` | Open a shell in the web container |
| `ddev mailpit` | Open Mailpit to inspect outgoing mail |
| `ddev redis-cli` | Open a Redis CLI |
| `ddev stop` | Stop the project |

## Creating a user

You log in with a **username and password**. Azure SSO is also available. New users automatically get access to all existing instances (see `app/Observers/UserObserver.php`).

### Option 1: create a user manually

```bash
ddev artisan tinker
```

```php
App\Models\User::create([
    'name' => 'Local Admin',
    'email' => 'admin@example.com',
    'username' => 'admin',
    'password' => 'admin',
    'azure_id' => (string) Str::uuid(),
    'employee_id' => '12345',
    'app_role_id' => collect([
        ['value' => json_encode(['value' => App\Enums\Role::MasterAdmin->value])],
    ]),
]);
```

To change the password of an existing user later:

```bash
ddev artisan user:change-password
```

### Option 2: create a user through the SCIM API

Send a `POST` request to the SCIM users endpoint at `https://moodle-admin-panel.ddev.site/scim/v2/Users`. The request needs an `Authorization: Bearer <TOKEN>` header, where `<TOKEN>` is the value of `SCIM_SECRET_TOKEN_USERS` in your `.env`.

```bash
curl -X POST https://moodle-admin-panel.ddev.site/scim/v2/Users \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/scim+json" \
  -d @user.json
```

Example `user.json`:

```json
{
    "schemas": [
        "urn:ietf:params:scim:schemas:core:2.0:User",
        "urn:ietf:params:scim:schemas:extension:enterprise:2.0:User"
    ],
    "externalId": "0e53b12e-6e28-4ed8-8239-7171ce44566e",
    "userName": "admin",
    "name": {
        "formatted": "Local Admin"
    },
    "displayName": "Local Admin",
    "title": "12345",
    "password": "admin",
    "emails": [
        {
            "type": "work",
            "primary": true,
            "value": "admin@example.com"
        }
    ],
    "roles": [
        {
            "type": "WindowsAzureActiveDirectoryRole",
            "primary": true,
            "value": "1"
        }
    ]
}
```

How the fields map to the user (see `app/SCIM/AzureSCIMConfig.php`):

| SCIM field | User field |
| --- | --- |
| `externalId` | `azure_id` (Azure object ID, must be a unique UUID) |
| `userName` | `username` (used to log in) |
| `name.formatted` | `name` |
| `title` | `employee_id` |
| `password` | `password` (optional; a random password is set when omitted) |
| `emails` | `email` |
| `roles[].value` | `app_role_id` (`1` = Master Admin, `2` = User) |

### Option 3: Azure SSO

The "Login with Azure" button on the login page needs the `AZURE_*` variables to be set (see [Environment variables](#environment-variables)). Azure login only works for a user that already exists in MooPanel with a matching `azure_id`. In shared environments, Azure provisions these users through the SCIM endpoint (Option 2). Locally, you can create the user with Option 1 or Option 2 and use your real Azure object ID as `azure_id` / `externalId`.

## Queue and scheduler

Most communication with Moodle runs in queued jobs. Queue, cache and sessions use the `database` driver by default. Redis is optional: DDEV runs a Redis service, and you can switch to it by setting `QUEUE_CONNECTION=redis` and `CACHE_STORE=redis` (with `REDIS_HOST=redis`) in `.env`. Syncs, updates and backups will not run until a queue worker is running. Moodle also processes updates and backups asynchronously, and the scheduler checks their status.

Run both in separate terminals while developing:

```bash
ddev artisan queue:work
ddev artisan schedule:work
```

Main scheduled tasks (full list in `routes/console.php`):

| Command | Schedule | What it does |
| --- | --- | --- |
| `module-api:sync-data` | Every 2 hours | Syncs core, plugins, courses and info from all instances |
| `module-api:get-active-moodle-users-count` | Hourly | Stores active Moodle user counts |
| `module-api:check-pending-update-requests` | Every 3 minutes | Checks the status of pending update requests |
| `backup:check-pending-backup-results` | Every 3 minutes | Checks the status of pending backups |
| `backup:automatic-backup-command` | Every 30 minutes | Runs automatic course backups |
| `backup:auto-prune` | Daily | Removes old automatic backups |
| `azure-api:app-data-sync` | Daily at 01:00 | Syncs Azure app data |
| `sis:sync-accounts-data` | Daily at 03:00 | Syncs student and employee accounts from SIS |

You can also run any of these commands manually, for example `ddev artisan module-api:sync-data`.

## Connecting a Moodle instance

Every Moodle instance managed by MooPanel needs the **`local_moopanel`** plugin: https://github.com/AGILEDROP/moodle-local_moopanel.git

1. Install the `local_moopanel` plugin on the Moodle site.
2. In MooPanel, open **Admin > Add Instance**, enter the Moodle site URL and generate an API key.
3. Configure the same API key in the Moodle plugin settings.
4. Save the instance. MooPanel fetches the instance data and starts the initial sync (a queue worker must be running).

MooPanel calls Moodle at `{instance url}/local/moopanel/server.php/...`. Moodle calls back to MooPanel's `/api/...` endpoints. Both directions authenticate with the `X-API-KEY` header. MooPanel stores the key encrypted, so changing `APP_KEY` makes existing instance keys unreadable.

## Environment variables

Every variable is listed in `.env.example`. Apart from the standard Laravel settings, the integrations below are only needed if you work on that feature.

| Group | Variables | Used for |
| --- | --- | --- |
| Azure login and API | `AZURE_CLIENT_ID`, `AZURE_CLIENT_SECRET`, `AZURE_REDIRECT_URI`, `AZURE_TENANT_ID`, `AZURE_APP_RESOURCE_ID`, `AZURE_OAUTH2_SCOPE`, `AZURE_CLIENT_ASSERTION_TYPE`, `AZURE_SIS_CLIENT_ID` | Azure SSO login, Azure app provisioning and data sync |
| SCIM | `SCIM_SECRET_TOKEN_USERS`, `SCIM_SECRET_TOKEN_ACCOUNTS`, `SCIM_BASE_PATH` (default `/scim`) | User and account provisioning from Azure through SCIM |
| SIS | `SIS_API_KEY_NAME`, `SIS_API_KEY_VALUE`, `SIS_API_PRIVATE_KEY_PATH`, `SIS_JWT_ALG`, `SIS_JWT_X5T`, `SIS_JWT_ISS`, `SIS_JWT_SUB`, `SIS_JWT_AUD`, `SIS_JWT_EXPIRATION` | Student and employee account sync from the SIS API (JWT signed requests) |

When `APP_ENV` is anything other than `local`, all generated URLs are forced to HTTPS.

## Troubleshooting

| Problem | Cause and fix |
| --- | --- |
| "No application encryption key has been specified" | `APP_KEY` in `.env` is empty. Run `ddev artisan key:generate` (only on a fresh install). |
| Nothing syncs after adding an instance, or updates and backups stay pending | The queue worker or scheduler is not running. Start `ddev artisan queue:work` and `ddev artisan schedule:work`. |
| The SCIM request returns `403 You are not authorized` | The bearer token does not match `SCIM_SECRET_TOKEN_USERS`, or the variable is empty. Set it in `.env` and run `ddev artisan config:clear`. |
| Azure login shows "User not found" | No user exists with a matching `azure_id`. Create the user first with Option 1 or Option 2 under [Creating a user](#creating-a-user). |

## Tests

Tests run on an in-memory SQLite database.

```bash
ddev composer test                          # full suite
ddev artisan test --filter=SomeTest         # single test class or method
ddev artisan test tests/Feature/SomeTest.php
```

## Code style

Run Pint before every commit to follow the required code style:

```bash
ddev exec ./vendor/bin/pint
```
