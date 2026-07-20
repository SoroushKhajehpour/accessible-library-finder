# Accessible Library Finder

A small Drupal 11 learning project that presents library resources in a searchable list, shows a sample external “service status” page, and uses a minimal custom theme with accessibility-minded markup and CSS.

This is a **portfolio / learning demo**, not a production library system.

## Why this project exists

The goal was to practice a realistic Drupal workflow end to end:

- local development with DDEV and Docker
- Composer-managed Drupal 11
- content modeling (content type, fields, taxonomy)
- configuration export/import
- Views-based search/filter UI
- a custom module that calls an external HTTP API
- a custom theme focused on readable layout and keyboard/focus support
- automated tests and Drupal coding standards

## Main features

- **Library Resource** content type with description, subject, resource type, URL, access level, and last reviewed date
- **Subjects** taxonomy used by resources
- Searchable **Library Resources** View at `/resources`
- Custom **Library Status** module at `/library-status` (sample data from JSONPlaceholder)
- Custom **Library Accessible** theme as the default front-end theme
- Exported configuration in `config/sync`
- Unit tests for the status HTTP client (mocked network)

## Technology stack

| Layer | Choice |
|-------|--------|
| CMS / framework | Drupal 11 |
| PHP | 8.4 (via DDEV) |
| Local environment | DDEV on Docker |
| Package management | Composer |
| CLI | Drush |
| Templating | Twig |
| Sample status API | JSONPlaceholder `/todos` |
| Tests | PHPUnit |
| Coding standards | Drupal Coder / PHPCS |

## Drupal content model

```text
Taxonomy vocabulary: Subjects
        ^
        | entity reference
        |
Node type: Library Resource
  - title (core)
  - field_description
  - field_subject          → Subjects term
  - field_resource_type    → list (database, journal platform, …)
  - field_resource_url     → link
  - field_access_level     → list (public, login required, …)
  - field_last_reviewed    → datetime
        ^
        | filtered / displayed by
        |
View: Library Resources  →  page at /resources
```

### How the pieces connect

- A **content type** is like a Rails model + form definition for one kind of content.
- **Fields** are attached field storage + instance config (closer to DB columns + form widgets than to ActiveRecord attributes alone).
- A **node** is one content item (one Library Resource).
- **Taxonomy** is a controlled vocabulary; Subjects terms categorize resources.
- A **View** is a configurable query + display (similar in spirit to an index action with filters, but stored mostly as configuration, not custom SQL).

## Architecture (plain text)

```text
Browser
  → DDEV router (Traefik)
    → nginx + PHP-FPM (web container)
      → web/index.php
        → DrupalKernel
          → routing / modules / theme
            → HTML response

Data:
  MariaDB (db container)  ← nodes, taxonomy, config (active)
  config/sync/            ← exported YAML configuration
  sites/default/files/    ← public files (ignored by Git)
```

### Custom module request lifecycle

```text
URL /library-status
→ Drupal route (library_status.routing.yml)
→ LibraryStatusController
→ StatusClient service
→ external API (JSONPlaceholder /todos)
→ mapped data [{ name, available }, ...]
→ render array (#theme library_status, cache max-age 300)
→ Twig template library-status.html.twig
→ HTML
```

## DDEV and Docker

DDEV reads `.ddev/config.yaml` and starts Docker containers for this project:

- **web**: nginx, PHP-FPM, Composer, Drush, Node
- **db**: MariaDB
- a shared **router** publishes `https://accessible-library-finder.ddev.site`

You should run Composer, Drush, PHP, and tests **through DDEV** (`ddev composer`, `ddev drush`, `ddev exec`), not with host PHP.

## Setup instructions

### Prerequisites

- Docker Desktop (or another Docker provider supported by DDEV)
- DDEV
- Git

### Clone and start

```bash
git clone <your-fork-or-clone-url> accessible-library-finder
cd accessible-library-finder
ddev start
```

### Composer installation

Dependencies install inside the web container:

```bash
ddev composer install
```

### Database and configuration

If the site is not installed yet:

```bash
ddev drush site:install standard -y
```

Import configuration from `config/sync`:

```bash
ddev drush cim -y
ddev drush cr
```

The sync directory is configured in `web/sites/default/settings.php` as:

```php
$settings['config_sync_directory'] = '../config/sync';
```

Sample Library Resource nodes may already exist in a local database dump or prior install. Configuration export does **not** include node content; preserve any existing sample content when reinstalling if you need those demos.

Create an admin login link when needed:

```bash
ddev drush uli
```

### Useful DDEV and Drush commands

```bash
ddev start
ddev stop
ddev describe
ddev drush status
ddev drush cr
ddev drush cex -y
ddev drush cim -y
ddev drush en library_status -y
ddev drush theme:enable library_accessible
ddev drush config:set system.theme default library_accessible -y
ddev launch
ddev launch /resources
ddev launch /library-status
```

## Accessibility decisions

The custom theme intentionally stays small:

- no CSS framework, no external fonts, no decorative gradients
- readable measure (`max-width` / line length) and responsive layout
- visible labels on form controls
- strong `:focus-visible` styles (outlines are not removed without replacement)
- skip link to `#main-content`
- status text shows **Available** / **Unavailable** in words (not colour alone)
- resource table stacks on narrow screens with `data-label` context
- `prefers-reduced-motion` disables non-essential motion
- no fixed heights meant to clip enlarged text

This project does **not** claim full WCAG conformance or production accessibility sign-off.

### Suggested manual checks

- Keyboard: Tab through header, filters, results, and status list
- Focus: confirm a clear focus ring on links, inputs, and buttons
- Zoom: browser zoom to about 200%
- Narrow viewport: around 320 CSS pixels
- Screen reader: VoiceOver (or similar) on `/resources` filters and `/library-status`
- Automated assistants: axe DevTools and Lighthouse accessibility category as **helpers**, not proof of compliance

See [docs/TESTING.md](docs/TESTING.md) for a compact checklist.

## API error handling, logging, and caching

`StatusClient`:

- uses Drupal’s HTTP client with a **5-second** timeout
- requests up to **five** todos
- decodes JSON with `JSON_THROW_ON_ERROR`
- validates each item before mapping
- catches request errors, malformed JSON, and unexpected shapes
- logs useful detail to the `library_status` logger channel
- returns an empty list on failure (no raw exception text to users)

The controller:

- builds a Twig render array
- sets `#cache.max-age` to **300** seconds (five minutes)
- shows a clear fallback message when no items are available

## Testing commands

Unit tests (no real network):

```bash
ddev exec vendor/bin/phpunit -c phpunit.xml
```

Coding standards for the custom module:

```bash
ddev exec vendor/bin/phpcs \
  --standard=Drupal,DrupalPractice \
  web/modules/custom/library_status
```

## Project layout (important paths)

```text
config/sync/                 Exported Drupal configuration
web/modules/custom/library_status/
web/themes/custom/library_accessible/
web/sites/default/settings.php
composer.json / composer.lock
phpunit.xml
docs/TESTING.md
```

Do not hand-edit generated trees such as `vendor/`, `web/core/`, `web/modules/contrib/`, or `web/sites/default/files/`.

## Limitations

- Demo status data comes from JSONPlaceholder, **not** real library systems
- Not production-hardened (security review, performance tuning, CDN, etc.)
- Not a full accessibility audit
- Not affiliated with the University of Waterloo
- Sample content may differ between environments because nodes live in the database
- Caching means status changes from the remote API may take up to five minutes to appear

## What was learned

- Drupal’s “app code” is often configuration (content types, fields, Views, blocks) plus small custom modules
- Composer install paths put core under `web/core` while PHP libraries stay in `vendor`
- DDEV is an orchestration layer over Docker, not a replacement for understanding nginx/PHP/DB roles
- External HTTP calls belong in an injectable service with logging and safe fallbacks
- Accessibility work starts with structure, labels, focus, and text—not colour alone

## AI usage disclosure

AI tools accelerated scaffolding (module/theme file layout, first-pass CSS, test stubs, and documentation structure) and helped with review checklists.

Every project file was still inspected in context, the `/library-status` request lifecycle was traced through route → controller → service → Twig, and normal plus failure cases were manually exercised (successful API response, unavailable endpoint, anonymous access, logs, `/resources` filters, and theme pages). Automated tests cover the status client with mocked HTTP and logging; they do not replace manual UI checks.

## License

See `LICENSE.txt` for Drupal’s GPL-2.0-or-later project licensing notes.
