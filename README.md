# GuruVandan

GuruVandan is a Laravel 13 Digital Guru Dakshina platform for Guru Purnima. Students create moderated messages and private media tributes, teachers receive a reveal-day dashboard and verified certificate, and administrators manage accounts, profiles, schedules, certificates, settings, and a searchable audit trail.

## Features

- Public home, teacher gallery, reveal-aware tribute pages, memory wall, event schedule, certificate verification, and downloadable teacher QR codes
- Real 22-teacher GuruVandan roster with individual usernames, dashboards, public tribute pages, certificates, and QR codes
- Four protected roles: Super Admin, Admin, Student, and Teacher
- Separate login portals for Students, Teachers, and Admin/Super Admin
- Super Admin account CRUD with self-protection, last-active-Super-Admin safeguards, password resets, status controls, and activity history
- Searchable teacher and student CRUD with username-based accounts, pagination, status controls, password resets, CSV export, tribute totals, and safe archival when historical records exist
- Professional moderation queue with advanced filters, full media preview, moderator text edits, mandatory rejection reasons, featured status, bulk actions, and deletion of unsafe content
- Private tribute media delivered only through authorization-controlled routes; direct public storage links cannot expose pending or rejected uploads
- Image, audio, and video previews with file removal, upload state, lightbox, native media controls, MIME verification, empty/corrupt-file rejection, unsafe double-extension checks, randomized filenames, and configurable limits
- Student dashboard with counts, pending edits, rejection feedback, tracked resubmission, countdown, reveal state, and AI writing assistant
- Teacher dashboard with tribute-type analytics, featured and recent tributes, reply management, event information, certificate download, QR download/print, and sharing
- Certificates with secure numbers/tokens, PDF preview/download/print, regeneration, revocation, QR verification, and public valid/revoked status
- Ordered event-schedule CRUD with speaker, location, visibility, and before/during/after countdown states
- School, celebration, certificate, AI-status, reveal, and safe upload settings
- Provider-based AI generation with Gemini, local fallback, authentication, timeout handling, server-side rate limiting, bounded input/output, and no key exposure. Teacher subjects have been removed from AI prompts and forms.
- Searchable activity logs for authentication, accounts, tributes, moderation, certificates, events, settings, and reveal changes
- Responsive role-aware navigation, keyboard focus styles, accessible labels, dialogs, empty states, status messages, and mobile layouts

## Requirements

- PHP 8.3 or newer
- Composer 2
- MySQL 8+ for production or SQLite for local testing
- Required PHP extensions: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `PDO`, `pdo_mysql` or `pdo_sqlite`, `tokenizer`, `xml`, and `zip`
- Optional: GD or Imagick for future server-side thumbnail generation and image re-encoding
- Node.js is not required for the current static CSS/JavaScript assets

Current local PHP check on this machine:

- Loaded PHP config: `C:\php\php.ini`
- `gd` loaded: no
- `imagick` loaded: no

To enable GD on Windows when the extension DLL exists for the active PHP version, open `C:\php\php.ini`, enable `extension=gd`, restart `php artisan serve`, and verify with `php -m`. If the DLL is missing, install a PHP build that includes `php_gd.dll` for the same PHP version and architecture. Imagick requires matching ImageMagick binaries plus the compatible `php_imagick.dll`; GD is the simpler production choice for thumbnails.

## Installation

```powershell
cd C:\Users\hp\Desktop\GuruVandan
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate:fresh --seed
php artisan serve
```

Open `http://127.0.0.1:8000`.

Login portals:

- Student: `http://127.0.0.1:8000/student/login`
- Teacher: `http://127.0.0.1:8000/teacher/login`
- Admin and Super Admin: `http://127.0.0.1:8000/admin/login`

For an existing installation, preserve the database and use:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan db:seed
```

The phase-two migration moves existing tribute media from the public disk to private storage and removes publicly addressable originals.

This phase also adds username login identifiers, first-login password-change flags, richer teacher profile fields, public visibility controls, and removes the teacher `subject` column.

## Environment

Configure MySQL in `.env`:

```env
APP_NAME=GuruVandan
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Kolkata

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=guruvandan
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.5-flash
```

The Gemini API key is read only from `.env`; it is never stored in platform settings, rendered in HTML, returned by an endpoint, placed in JavaScript, or written to activity logs. Gemini requests send the key in the `x-goog-api-key` header instead of the URL.

For SQLite development:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Then create `database/database.sqlite` and run `php artisan migrate:fresh --seed`.

## Free Vercel + GitHub Deployment Storage Plan

Vercel is a good free frontend/serverless host from GitHub, but it should not be treated as permanent file storage. Anything written to Laravel's local `storage/` directory on Vercel can disappear between deployments or serverless runs.

Recommended free setup:

| Data type | Free service | Laravel setting |
| --- | --- | --- |
| Users, teachers, students, tributes, certificates, event data, settings | Supabase Postgres Free | `DB_CONNECTION=pgsql` |
| Photos, drawings, audio, video, logos, signatures | Supabase Storage Free using S3-compatible API | `FILESYSTEM_DISK=s3` |
| Source code and deployment | GitHub + Vercel Hobby | connected GitHub repository |
| AI key | Vercel environment variable | `GEMINI_API_KEY` |

Live database example:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-vercel-domain.vercel.app

DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-south-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.your-project-ref
DB_PASSWORD=your-supabase-database-password
DB_SSLMODE=require

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
VIEW_COMPILED_PATH=/tmp/guruvandan/views
```

Live upload storage example:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-supabase-storage-access-key
AWS_SECRET_ACCESS_KEY=your-supabase-storage-secret-key
AWS_DEFAULT_REGION=ap-south-1
AWS_BUCKET=guruvandan
AWS_ENDPOINT=https://your-project-ref.supabase.co/storage/v1/s3
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Before using S3-compatible storage, install the Laravel S3 filesystem adapter:

```powershell
composer require league/flysystem-aws-s3-v3
```

Important: the current application protects tribute media through Laravel's `media.show` route. Do not make the tribute media bucket public unless the media authorization flow is changed carefully.

Certificate PDFs currently render local public images with `storage_path(...)`. On Vercel/Supabase storage, PDF generation may need a small follow-up update to read logo, signature, and teacher photos from cloud storage URLs or streams.

## Uploads

- Tribute media is stored under `storage/app/private/tributes` and served through `media.show` authorization checks.
- Teacher photos, cover images, logos, and signatures are public presentation assets under `storage/app/public`.
- Safe configurable extensions are JPG, JPEG, PNG, WebP, MP3, WAV, M4A, MP4, and WebM.
- Default application limits are 5 MB images, 12 MB audio, and 50 MB video.
- SVG, PHP, HTML, scripts, executables, double-extension MIME mismatches, path traversal, and unsafe filenames are rejected or neutralized.
- Empty uploads and unreadable image files are rejected with friendly validation errors.
- Ensure PHP/web-server limits are at least as large as the selected application values:

```ini
upload_max_filesize = 50M
post_max_size = 55M
max_file_uploads = 10
```

This environment has no GD/Imagick. The application accepts valid original images and browser previews continue to work. Server-side thumbnail generation, physical resizing, orientation normalization, and safe re-encoding are skipped until GD or Imagick is installed.

## Browser QA

The release QA pass attempted to attach the bundled browser automation connector. The connector initialized, but no browser backend was available in this Codex session, so viewport screenshots could not be captured here.

Live HTTP checks were completed against `http://127.0.0.1:8095` after a clean seed:

- Public pages checked: home, teacher gallery, teacher tribute page, memory wall, event page, login, register, invalid teacher 404
- QR pages checked: teacher QR SVG download and teacher QR print page
- Static assets checked: `public/assets/css/styles.css` and `public/assets/js/app.js`
- Automated workflow coverage checks the student submit, admin approve, reveal-public, teacher certificate download, certificate verification, private-media authorization, disabled-account, and Super Admin restriction paths

Recommended supported browsers for production QA: current Chrome, Edge, Firefox, and Safari on iOS/macOS. Test the main pages at `375x667`, `390x844`, `768x1024`, `1024x768`, `1366x768`, and `1920x1080` when a browser runner is available.

Screenshots: none created in this session because `agent.browsers.list()` returned an empty browser list.

## Teacher Roster

| Teacher | Username | Detail |
| --- | --- | --- |
| Seema Ma'am | `seema` | Teacher |
| Poonam Ma'am | `poonam` | Teacher |
| Pallavi Ma'am | `pallavi` | Teacher |
| Anjana Ma'am | `anjana` | Teacher |
| Rahul Sir | `rahul` | Teacher |
| Rajmani Ma'am | `rajmani` | Teacher |
| Jaya Ma'am | `jaya` | Teacher |
| D. L. Sir | `dl` | Teacher |
| Parimal Ma'am | `parimal` | Teacher |
| Nivedita Ma'am | `nivedita` | Teacher |
| Saziya Ma'am | `saziya` | 1st Floor |
| Aparna Ma'am | `aparna` | Teacher |
| Priya Ma'am | `priya` | Teacher |
| Ankita Ma'am | `ankita` | Teacher |
| Manoj Sir | `manoj` | Teacher |
| Jyotsana Ma'am | `jyotsana` | Teacher |
| Aditi Ma'am | `aditi` | Teacher |
| Sweksha Ma'am | `sweksha` | Teacher |
| Sita Ma'am | `sita` | Teacher |
| Rashmi Ma'am | `rashmi` | Teacher |
| Sujay Sir | `sujay` | Director |
| Ajeet Sir | `ajeet` | Principal |

Saziya Ma'am's `1st Floor` detail is stored as profile location, not as part of the login username. Teacher subjects have been removed from the database, forms, cards, AI assistant, and public pages.

## Demo Accounts

| Role | Login | Password |
| --- | --- | --- |
| Super Admin | `superadmin@guruvandan.test` at `/admin/login` | `password123` |
| Admin | `admin@guruvandan.test` at `/admin/login` | `password123` |
| Teachers | teacher usernames above at `/teacher/login` | `teacher123` temporary |
| Seeded students | `student1` through `student8` at `/student/login` | `password` temporary |

Teacher accounts and the first sample student accounts are flagged to change temporary passwords on first login. Change or remove all demo credentials before deployment.

## Testing

```powershell
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan test
php artisan route:list
php artisan view:cache
php artisan config:cache
php artisan route:cache
vendor\bin\pint --test
node --check public\assets\js\app.js
php artisan optimize:clear
```

The suite currently covers 36 feature tests and 166 assertions, including the original 11 tests. Gemini is mocked or replaced by the local provider in tests; the test suite never makes an external AI request.

Manual Gemini testing is only recommended when `GEMINI_API_KEY` is already configured in `.env`. Test English, Hindi, Hinglish, poem, letter, and Guru Purnima wish prompts from the student dashboard. Invalid keys and provider failures should show the friendly retry/fallback message without breaking tribute submission.

## Packages

- `barryvdh/laravel-dompdf` for PDF certificates
- `bacon/bacon-qr-code` for SVG QR generation

## Production Deployment

```powershell
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Point the web-server document root to `public/`, use HTTPS, set `APP_ENV=production`, set `APP_DEBUG=false`, configure a strong `APP_KEY`, secure database credentials, configure backups, and set appropriate storage permissions.

Production security checklist:

- Replace seeded passwords and disable unused demo accounts.
- Remove or change every demo account before public deployment; never deploy the seeded passwords above.
- Set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, and a strong `APP_KEY`.
- Keep `.env`, `storage/app/private`, logs, database backups, and source files outside public web access.
- Restrict accepted proxy hosts and configure secure cookies, HTTPS, HSTS, CSP, and rate limits at the reverse proxy.
- Set realistic PHP/web-server request-size and timeout limits.
- Run scheduled database and private-media backups with restoration drills.
- Review activity logs, failed logins, revoked certificates, inactive accounts, and moderation queues.
- Configure log rotation and avoid logging uploaded contents, API keys, passwords, tokens, or session payloads.
- Run tests and dependency/security audits before every deployment.

## Project Notes

- The live Laravel application is the project root.
- `public/assets` contains the active GuruVandan CSS and JavaScript.
- The original prototype and confirmed conversion leftovers are preserved in `prototype-backup/legacy-files/`.
- The duplicate `config/config` conversion leftover has also been moved to `prototype-backup/legacy-files/config/config/`.
- Teacher and student deletion uses safe archival whenever related tributes or certificates exist, preserving authorship, audit history, and certificate verification.

## Known Limitations

- Browser screenshots and real viewport automation were blocked in this local Codex session because no browser backend was available.
- GD/Imagick are not loaded locally, so physical thumbnail generation and server-side re-encoding are documented production enhancements rather than active local behavior.
- QR downloads are SVG. PNG output can be added later if a raster QR export is required by the school printer workflow.
