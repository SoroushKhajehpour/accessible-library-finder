# Accessible Library Finder — Full Learning Context for GPT

Use this document as **project context** when asking GPT to teach you Drupal, DDEV, Composer, Views, custom modules, themes, testing, or accessibility using this repo.

**How to use:** paste this file (or relevant sections) into a chat, then ask focused questions such as:

- “Explain `StatusClient` like I’m coming from Rails.”
- “Walk me through what happens when I open `/resources`.”
- “Quiz me on config sync vs the database.”
- “Help me read `views.view.library_resources.yml` line by line.”

---

## 1. Project identity

| Item | Value |
|------|--------|
| Project name | Accessible Library Finder |
| Purpose | Learning / portfolio Drupal 11 demo |
| CMS | Drupal **11.4.4** |
| Local URL | `https://accessible-library-finder.ddev.site` |
| Local stack | **DDEV** on **Docker** (nginx-fpm, PHP 8.4, MariaDB 11.8) |
| Package manager | Composer |
| CLI | Drush 13 |
| Default theme | `library_accessible` (custom) |
| Admin theme | `claro` (core) |
| Config sync path | `config/sync` (via `$settings['config_sync_directory'] = '../config/sync'`) |
| Install profile | `standard` |

**What this is not:** a production library system, a University of Waterloo product, or a WCAG-certified site. Sample “service status” data comes from **JSONPlaceholder**, not real library APIs.

---

## 2. Learner background this project was designed around

You already know:

- **React** (components, props, client rendering, SPA mental model)
- **Rails** (MVC, `Gemfile`, ActiveRecord, routes, views, Docker-ish local stacks)

Drupal will feel familiar in places and misleading in others. Prefer asking GPT to **map and then break** each analogy.

---

## 3. Mental model: Drupal vs Rails vs React

| Concept | Rails-ish | React-ish | Drupal in this project |
|---------|-----------|-----------|-------------------------|
| App root | Rails root | project root | repo root (`composer.json`, `.ddev/`) |
| Public web root | `public/` | often `public/` or Vite out | `web/` |
| Dependencies | `Gemfile` / `Gemfile.lock` | `package.json` / lockfile | `composer.json` / `composer.lock` |
| Installed libs | gems / `vendor/bundle` | `node_modules` | `vendor/` **plus** Drupal packages installed into `web/core`, `web/modules/contrib`, etc. |
| Framework code | `rails` gem | React package | `web/core/` |
| App features | engines / gems | feature folders | **modules** (`web/modules/custom/…`) |
| Presentation | ERB / layouts | components / CSS | **themes** + Twig |
| Models | ActiveRecord | — | **content entities** (nodes) + fields |
| Categories | associations | — | **taxonomy** vocabularies/terms |
| Index + filters | controller + scopes | client filter state | **Views** (config-driven query UI) |
| Routes | `config/routes.rb` | React Router | `*.routing.yml` + core/module routes |
| Env config | `database.yml`, credentials | `.env` | `settings.php` + `settings.ddev.php` |
| Site config | mostly code + DB migrations | — | **active config in DB** + YAML in `config/sync` |
| Rendering | server templates | often CSR | **server-rendered HTML** every request (with caches) |

### Imperfect analogies (important)

1. **Drupal is CMS-first**, Rails is app-first. Much “business structure” is configuration and content, not only PHP classes.
2. **Modules can be enabled/disabled** in the UI; gems are usually always in the bundle once required.
3. **Views are not React views** and not exactly Rails views. A Drupal View is a saved query + display definition (closer to an admin-built index page).
4. **Config lives in two places**: active (DB) and export (`config/sync`). Rails config is mostly files.
5. **Nodes ≠ ActiveRecord models one-to-one**. A content type is more like a model + form + display definitions composed from Field API.
6. **Twig render arrays** sit between controller and HTML. Controllers often return structured arrays (`#theme`, `#cache`), not strings.

---

## 4. Repository layout (what each top-level thing is for)

```text
accessible-library-finder/
├── .ddev/                     # DDEV project config → Docker Compose orchestration
├── .gitignore                 # Ignores vendor, core, files, local overrides, etc.
├── composer.json              # PHP dependency manifest + Drupal installer paths
├── composer.lock              # Locked dependency tree (commit this)
├── phpunit.xml                # PHPUnit config for custom module unit tests
├── README.md                  # Human project docs
├── docs/
│   ├── TESTING.md             # Manual test checklist
│   └── LEARNING_CONTEXT.md    # This file
├── config/sync/               # Exported Drupal configuration (YAML)
├── recipes/                   # Drupal recipes placeholder (template)
├── vendor/                    # Composer PHP libraries (DO NOT EDIT; gitignored)
└── web/                       # Document root (nginx points here)
    ├── index.php              # Front controller
    ├── autoload.php           # Thin wrapper → ../vendor/autoload.php
    ├── core/                  # Drupal core (Composer-installed; gitignored)
    ├── modules/
    │   ├── README.txt
    │   └── custom/
    │       └── library_status/   # Custom module (THIS project’s PHP)
    ├── themes/
    │   ├── README.txt
    │   └── custom/
    │       └── library_accessible/  # Custom theme
    └── sites/default/
        ├── settings.php       # Site bootstrap settings (tracked)
        ├── settings.ddev.php  # DDEV-generated DB/hosts (gitignored)
        └── files/             # Uploads, aggregates (gitignored)
```

### Do not manually edit (regenerated / managed)

- `vendor/`
- `web/core/`
- `web/modules/contrib/`, `web/themes/contrib/`
- `web/sites/default/files/`
- `.ddev/.ddev-docker-compose-*.yaml` (generated)
- `web/sites/default/settings.ddev.php` (DDEV-generated)

### Safe to study and edit (project-owned)

- `web/modules/custom/library_status/`
- `web/themes/custom/library_accessible/`
- `config/sync/` (usually via UI/`drush cex`, not random hand-edits)
- `composer.json` (then `ddev composer update` / `require`)
- `.ddev/config.yaml`
- `README.md`, `docs/*`
- Parts of `web/sites/default/settings.php` (carefully)

---

## 5. DDEV + Docker

### What `.ddev` is

DDEV is a **developer tool that generates and runs Docker containers** for local PHP apps.

Key file: `.ddev/config.yaml`

Relevant settings in this project:

- `name: accessible-library-finder` → hostname `*.ddev.site`
- `type: drupal11`
- `docroot: web`
- `php_version: "8.4"`
- `webserver_type: nginx-fpm`
- `database: mariadb:11.8`

### What `ddev start` roughly does

1. Reads `.ddev/config.yaml`
2. Builds/uses Docker images for web + db
3. Starts containers; mounts the project
4. Registers the project with DDEV’s router (Traefik)
5. Writes helpers such as `settings.ddev.php`
6. Exposes `https://accessible-library-finder.ddev.site`

### Containers (simplified)

| Service | Role |
|---------|------|
| `web` | nginx + PHP-FPM + Composer + Drush + Node |
| `db` | MariaDB; hostname inside Docker network is `db` |
| router | Routes `*.ddev.site` to the correct project |

### Rule for this project

Always run PHP tooling **inside DDEV**:

```bash
ddev composer …
ddev drush …
ddev exec vendor/bin/phpunit …
ddev exec vendor/bin/phpcs …
```

Do **not** use host-level PHP/Composer/Drush for this repo.

---

## 6. Composer in a Drupal project

### `composer.json` responsibilities here

1. Require packages (`drupal/core-recommended`, `drush/drush`, dev tools).
2. Tell Composer where Drupal packages go (`extra.installer-paths`):
   - core → `web/core`
   - contrib modules → `web/modules/contrib/{$name}`
   - custom module type → `web/modules/custom/{$name}` (when installed as packages)
3. Set scaffold web root to `web/` (`drupal-scaffold`).

### Extra autoload added for tests

This project also maps:

- `Drupal\library_status\` → `web/modules/custom/library_status/src/`
- `Drupal\Tests\library_status\` → `web/modules/custom/library_status/tests/src/`

so PHPUnit can load the custom classes without a full Drupal bootstrap for unit tests.

### Commands

```bash
ddev composer install
ddev composer require drupal/some_module
ddev composer require --dev drupal/core-dev drupal/coder …
```

---

## 7. Request lifecycle: homepage (general Drupal)

```text
Browser
  → https://accessible-library-finder.ddev.site/
    → DDEV router
      → nginx (docroot = web/)
        → PHP-FPM
          → web/index.php
            → DrupalKernel
              → load settings (sites/default)
              → connect DB
              → boot modules/services
              → match route
              → build render array
              → theme + Twig
              → HTML response
```

`web/index.php` creates a `DrupalKernel` and relies on Composer autoload via `web/autoload.php` → `vendor/autoload.php`.

---

## 8. Content model (the “Library Resource” domain)

### Content type

- Machine name: `library_resource`
- Label: Library Resource
- Config: `config/sync/node.type.library_resource.yml`

### Fields (expected)

| Field machine name | Label | Type | Notes |
|--------------------|-------|------|-------|
| `title` | Title | core string | Built-in node title |
| `field_description` | Description | string_long | |
| `field_subject` | Subject | entity_reference → taxonomy | vocabulary `subjects` |
| `field_resource_type` | Resource type | list_string | database, journal_platform, search_tool, research_guide |
| `field_resource_url` | Resource URL | link | |
| `field_access_level` | Access level | list_string | public, waterloo_login_required, on_campus_access_required |
| `field_last_reviewed` | Last reviewed | datetime | |

Field storage + field instance configs live under `config/sync/field.storage.node.*` and `config/sync/field.field.node.library_resource.*`.

### Taxonomy

- Vocabulary: `subjects` (`taxonomy.vocabulary.subjects.yml`)
- Example terms used in sample content: Engineering, Computer Science, Health, Business, Humanities, Multidisciplinary

### Sample nodes

About six Library Resource nodes may exist in the **database** (IEEE Xplore, JSTOR, PubMed, Google Scholar, Scopus, Engineering Research Guide).  

**Critical learning point:** `drush cex` exports **configuration**, not node content. Reinstalling without a DB dump can lose sample nodes even if `config/sync` is perfect.

### Rails mapping

- Content type ≈ model class + form definitions  
- Field storage ≈ column definition  
- Field instance on a bundle ≈ “this model has this attribute”  
- Node ≈ one row / one record  
- Taxonomy term ≈ belonging to a Category model  

---

## 9. Views: `/resources`

### What exists

- View ID: `library_resources`
- Label: Library Resources
- Path: `/resources`
- Config file: `config/sync/views.view.library_resources.yml`

### Behaviour

- Base: content (`node_field_data`)
- Bundle filter: `library_resource` only
- Published only
- Fields shown: title (link to node), description, subject, resource type, access level, last reviewed, resource URL (link text **“Open resource”**)
- Sort: title ASC
- Pager: 10 items
- Exposed filters:
  - Title contains (`identifier: title`)
  - Subject (`identifier: subject`)
  - Resource type (`identifier: resource_type`)
- Empty text explains no matches

### Why Views matter for learning

Views let you build list/search UIs with **configuration** instead of writing SQL and a custom controller. The YAML is verbose; learning to read it is a Drupal skill.

### Suggested GPT prompts

- “Explain the `display.default.display_options.filters` section of this View.”
- “How would I add an Access level exposed filter?”
- “Compare this View to a Rails index action with `params` filters.”

---

## 10. Custom module: `library_status`

Path: `web/modules/custom/library_status/`

### Files and roles

| File | Role |
|------|------|
| `library_status.info.yml` | Module metadata; `core_version_requirement: ^11` |
| `library_status.routing.yml` | Route `/library-status` → controller; permission `access content` |
| `library_status.services.yml` | DI definitions: logger channel + `StatusClient` |
| `library_status.module` | `hook_theme()` registers Twig theme hook `library_status` |
| `src/Controller/LibraryStatusController.php` | Builds render array; cache max-age 300 |
| `src/Service/StatusClient.php` | HTTP + JSON + validation + mapping + error handling |
| `templates/library-status.html.twig` | Semantic HTML; Available/Unavailable text |
| `tests/src/Unit/StatusClientTest.php` | Mocked HTTP/logger unit tests |

### Request lifecycle for `/library-status`

```text
URL /library-status
→ Drupal route (library_status.routing.yml)
→ LibraryStatusController::page()
→ StatusClient::getStatuses()
→ GET https://jsonplaceholder.typicode.com/todos (timeout 5s, up to 5 items)
→ decode JSON with JSON_THROW_ON_ERROR
→ validate each item
→ map to [ ['name' => string, 'available' => bool], ... ]
→ render array (#theme library_status, #items, #error, #cache max-age 300)
→ Twig library-status.html.twig
→ HTML
```

### Design rules implemented (good interview talking points)

- `declare(strict_types=1);`
- Constructor dependency injection (no `\Drupal::service()` in production classes)
- HTTP logic isolated in `StatusClient`
- Controller only prepares the page response
- On failure: log internally, return `[]`, show safe fallback message
- Do not display raw exception messages
- Status meaning uses **text**, not colour alone
- Result cached for **five minutes** (`#cache['max-age'] = 300`)

### Mapping choice (demo-specific)

JSONPlaceholder todo fields:

- `title` → `name`
- `completed` → `available` (bool)

This is a teaching mapping, not a real library outage API.

### Services YAML (conceptual)

```yaml
logger.channel.library_status:
  parent: logger.channel_base
  arguments: ['library_status']

library_status.status_client:
  class: Drupal\library_status\Service\StatusClient
  arguments: ['@http_client', '@logger.channel.library_status']
```

Controller uses `ContainerInjectionInterface`-style `create()` to receive `library_status.status_client`.

---

## 11. Custom theme: `library_accessible`

Path: `web/themes/custom/library_accessible/`

### Files

| File | Role |
|------|------|
| `library_accessible.info.yml` | Theme metadata; `base theme: stable9`; regions |
| `library_accessible.libraries.yml` | Attaches `css/style.css` |
| `css/style.css` | Minimal accessible CSS |
| `templates/html.html.twig` | Skip link + HTML shell |
| `templates/page.html.twig` | Header/main/footer regions; `#main-content` |
| `templates/views-view-table.html.twig` | Adds `data-label` for narrow-screen table layout |

### Accessibility decisions encoded in CSS/markup

- No CSS framework, no external fonts, no gradients
- Readable line length / max width
- Strong `:focus-visible` styles; do not remove outlines without replacement
- Visible labels; form controls with adequate size
- Skip link to `#main-content`
- Resource table stacks on narrow screens
- `prefers-reduced-motion` respected
- Status colours may reinforce meaning but **text remains primary**

### Theme enablement (already done in this project)

```bash
ddev drush theme:enable library_accessible
ddev drush config:set system.theme default library_accessible -y
```

Block placements are exported under `config/sync/block.block.library_accessible_*.yml`.

---

## 12. Configuration management

### Active vs sync

| Location | Meaning |
|----------|---------|
| Database (active config) | What the running site uses |
| `config/sync/*.yml` | Exported snapshot for Git / deploy / rebuild |

### Commands

```bash
ddev drush cex -y   # export active → config/sync
ddev drush cim -y   # import config/sync → active
ddev drush cr       # rebuild caches
```

### settings.php snippet

```php
$settings['config_sync_directory'] = '../config/sync';
```

`settings.ddev.php` only sets a fallback sync dir if empty; the explicit setting above wins.

### What config includes in this project (examples)

- Content type + fields + form/view displays
- Taxonomy vocabulary
- View `library_resources`
- Theme default + blocks
- Enabled modules (`core.extension.yml`) including `library_status`

### What config does **not** include

- Node content (Library Resource samples)
- Users’ passwords
- Uploaded files under `sites/default/files`

---

## 13. Testing and code quality

### Unit tests

```bash
ddev exec vendor/bin/phpunit -c phpunit.xml
```

`StatusClientTest` covers:

- successful JSON → correct `name` / `available` mapping
- request exception → `[]` + error log
- malformed JSON → `[]` + error log
- non-array JSON → `[]` + error log
- invalid items skipped with warnings

**No real network** in tests (mocked `ClientInterface` + `LoggerInterface`).

### PHPCS

```bash
ddev exec vendor/bin/phpcs \
  --standard=Drupal,DrupalPractice \
  web/modules/custom/library_status
```

Dev packages: `drupal/core-dev`, `drupal/coder`, `dealerdirect/phpcodesniffer-composer-installer`.

---

## 14. Important URLs

| Path | What you should see |
|------|---------------------|
| `/` | Homepage with custom theme, skip link, site name |
| `/resources` | Searchable Library Resources table + exposed filters |
| `/library-status` | Five mapped status items or fallback message |
| `/user/login` | Login form with visible labels |
| `/admin` | Needs authenticated admin (use `ddev drush uli`) |

---

## 15. Useful commands cheat sheet

```bash
# Environment
ddev start
ddev stop
ddev describe
ddev launch
ddev launch /resources

# Drupal
ddev drush status
ddev drush cr
ddev drush cex -y
ddev drush cim -y
ddev drush uli
ddev drush watchdog:show --count=10 --type=library_status

# Code quality
ddev exec vendor/bin/phpunit -c phpunit.xml
ddev exec vendor/bin/phpcs --standard=Drupal,DrupalPractice web/modules/custom/library_status
ddev exec php -l web/modules/custom/library_status/src/Service/StatusClient.php
```

---

## 16. Git history (learning milestones)

Commits created for the feature work (oldest → newest after init):

1. `save site config` — sync dir + full config export  
2. `add resource search` — Library Resources View  
3. `add status page` — `library_status` module  
4. `add library theme` — `library_accessible` + default theme  
5. `test status client` — PHPUnit + Coder deps  
6. `write project docs` — README + TESTING.md  
7. `clean up project` — remove duplicate theme blocks  

---

## 17. Suggested learning path (ask GPT to teach in this order)

1. **Repo topology** — why `web/` exists; what is gitignored  
2. **DDEV** — containers; why `db` hostname works inside PHP  
3. **Content model** — create/read a Library Resource in admin UI  
4. **Config sync** — change a label in UI, `cex`, read the YAML diff  
5. **Views** — change pager to 5, export, explain YAML  
6. **Routing + controller** — read `library_status` route → controller  
7. **Services + DI** — redraw the service graph on paper  
8. **HTTP client** — failure modes; compare to Faraday/fetch  
9. **Twig + render arrays** — why `#theme` / `#cache`  
10. **Theme layer** — skip link, focus, responsive table  
11. **Tests** — add one new unit case for empty title rejection  
12. **Interview mode** — explain the status lifecycle in 60 seconds  

---

## 18. Good questions to ask GPT with this context

- “I know Rails strong parameters; how do Drupal forms/fields compare?”
- “Show me the exact code path from `/library-status` to Twig variables.”
- “If JSONPlaceholder is down, what does a visitor see vs what’s in watchdog?”
- “Why is `web/core` gitignored but `config/sync` committed?”
- “Help me add an Access level exposed filter to the View without custom PHP.”
- “Refactor talk: where would a real timeout/retry policy live?”
- “Quiz me: active config vs sync directory vs `settings.php`.”
- “Review my theme CSS for focus and colour-only risks.”

---

## 19. Anti-claims / honesty constraints for tutoring

When teaching from this project, GPT should **not** encourage you to claim:

- full WCAG compliance
- production readiness
- official University of Waterloo affiliation
- integration with real Waterloo Library systems
- that JSONPlaceholder data is real service status
- expert-level Drupal experience after one demo

AI helped scaffold and review parts of this project; you should still be able to explain every custom file and the request lifecycle yourself.

---

## 20. Key source files to open while learning

**Must-read custom code**

- `web/modules/custom/library_status/src/Service/StatusClient.php`
- `web/modules/custom/library_status/src/Controller/LibraryStatusController.php`
- `web/modules/custom/library_status/library_status.routing.yml`
- `web/modules/custom/library_status/library_status.services.yml`
- `web/modules/custom/library_status/templates/library-status.html.twig`
- `web/modules/custom/library_status/tests/src/Unit/StatusClientTest.php`

**Must-read theme**

- `web/themes/custom/library_accessible/templates/html.html.twig`
- `web/themes/custom/library_accessible/templates/page.html.twig`
- `web/themes/custom/library_accessible/css/style.css`

**Must-read config**

- `config/sync/node.type.library_resource.yml`
- `config/sync/views.view.library_resources.yml`
- `config/sync/core.extension.yml`
- `config/sync/system.theme.yml`

**Bootstrap / tooling**

- `composer.json`
- `.ddev/config.yaml`
- `web/sites/default/settings.php` (config sync setting)
- `web/index.php`
- `phpunit.xml`

---

## 21. One-paragraph “elevator” summary (memorize this)

Accessible Library Finder is a Drupal 11 DDEV project that stores library resources as nodes with taxonomy Subjects, exposes a configuration-based searchable View at `/resources`, provides a small custom module at `/library-status` that fetches sample todos from JSONPlaceholder through an injected HTTP client with logging, validation, caching, and safe failure UI, and presents everything in a minimal custom theme focused on skip links, visible focus, labels, and text-based status—not as a production or officially affiliated library system.

---

*Generated as learning context for the `accessible-library-finder` repository. Prefer verifying live behaviour with `ddev drush status` and the URLs above when something in this file disagrees with your local site.*
