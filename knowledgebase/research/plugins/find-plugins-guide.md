# Finding Filament plugins — discovery guide

Sources — all fetched 2026-08-21:

- https://filamentphp.com/plugins (also with `?price=free`, `?version=5.x`, `?price=free&version=5.x`, `?q=kanban&price=free`, `?sort=popular`, `?tag=theme`, `?price=free&score=100`, `?price=free&versions[]=5.x`)
- https://filamentphp.com/plugins/xplodman-count-up (example plugin listing)
- https://filamentphp.com/author (submission dashboard — redirects to GitHub OAuth login)
- https://github.com/filamentphp/filamentphp.com → redirects to https://github.com/filamentphp/legacy-site (archived plugin-directory repo, README read)
- https://github.com/topics/filament-plugin
- https://packagist.org/search.json?q=filament-plugin (JSON API; HTML page https://packagist.org/search/?query=filament-plugin is JS-rendered)
- https://github.com/spekulatius/awesome-filament
- https://github.com/filamentphp/awesome-filament (404 — no official awesome list at this URL)

## 1) Official registry — https://filamentphp.com/plugins

~982 plugins, 453 authors, 35.1K stars (2026-08-21). Built-in search box matches **plugins, authors, descriptions, and repos**.

### URL query params — verified by fetching

| Param | Effect | Evidence |
|---|---|---|
| `?price=free` | WORKS — filters to free plugins | items found 982 → 896 |
| `?q=<term>` | WORKS — text search, combines with other params | `?q=kanban&price=free` → 5 items |
| `?sort=popular` | WORKS — orders list by GitHub stars (Shield 1,684 first) | items found 982, star-ordered |
| `?score=100` | WORKS — health score floor | `?price=free&score=100` → 118 items |
| `?version=5.x` | IGNORED server-side | still 982 items |
| `?versions[]=5.x` | IGNORED server-side | still 896 (same as free alone) |
| `?tag=theme` | IGNORED server-side | still 982 items |

Other `price`/`sort`/`score` values (e.g. `paid`, `oldest`, `60`) not tested — UNVERIFIED. The **Version (3.x/4.x/5.x)**, **tag**, and **features** filters exist in the browser UI (Livewire) but are NOT addressable via plain URL — important for automated fetching: you cannot 5.x-filter by URL, you must open each plugin page.

### Plugin listing page — https://filamentphp.com/plugins/<slug>

Slug = author name + plugin name (e.g. `bezhansalleh-shield`, `filament-spatie-media-library`) — not the composer vendor/package. Verified contents (example: `xplodman-count-up`):

- **Name, description, author** (avatar + bio + plugin count)
- Badges: "Dark mode ready", "Multilingual support", **"Supports v5.x"**
- **"Supported versions:"** line — exact list, e.g. `5.x`
- Tags (e.g. Tables, Table Column, Widget); "Featured"/"Official" markers; registry cards can show a **"Legacy"** flag (seen on `mokhosh-kanban`)
- Price: shown on registry card as "Free / Get it now" or "$79.00 / Buy now"
- "Visit on GitHub" repo link + star count
- Full README (docs section) — includes the **install command** (`composer require xplodman/filament-count-up`)
- **Package health** block: score N/100 (e.g. 92/100), sub-scores Security/Maintenance/Ecosystem, ~15 automated checks, "Powered by Plumb" with link `https://plumbphp.dev/<vendor>/<package>`, "Last scanned" date
- Security disclaimer: third-party plugins are not reviewed/vetted; report link https://filamentphp.com/plugins/report-security-issue

### How to read 5.x support + health score

- 5.x: on the plugin page, look for the **"Supports v5.x"** badge and the **"Supported versions:"** list containing `5.x`. A "Legacy" card flag or missing 5.x in the list = reject for this project.
- Health score: N/100 from Plumb automated Composer checks. Registry UI offers score floors 60+/80+/100 (URL `?score=100` verified). Failed checks worth reading: open security advisories, abandoned/archived, commit/release recency, current Laravel/PHP support.

## 2) Submitting a plugin to the registry

- Registry page links "Submit plugins" → **https://filamentphp.com/author**. Verified: requires signing in with GitHub (GitHub OAuth redirect); an author dashboard manages submissions.
- Old flow (archived): the GitHub repo **filamentphp/legacy-site** (formerly `filamentphp/filamentphp.com`, "Filament Website & Plugin Directory", archived 2026-02-25) accepted PRs + Markdown files. Its README states: all plugins were transferred to the new site; to manage plugins now, request access to your author profile at filamentphp.com/author; once approved it links to your account and you can "submit new plugins, push updates to existing ones, and manage everything from there". No GitHub PRs anymore.
- The archived repo keeps a `PLUGIN_REVIEW_GUIDELINES.md` (historical; current review process runs through the /author dashboard — details UNVERIFIED).

## 3) Other discovery channels (verified URLs)

- **GitHub topic** — https://github.com/topics/filament-plugin — 322 public repos (mostly PHP), sortable by stars/forks/updated; e.g. mokhosh/filament-kanban (463), awcodes/filament-curator (441). Related topic `filamentphp-plugin` also exists.
- **Packagist** — https://packagist.org/search/?query=filament-plugin (HTML, JS-rendered) and the JSON API **https://packagist.org/search.json?q=filament-plugin** (verified: 602 results with name, description, repo URL, downloads, favers). Every composer-installable Filament plugin is here; no price/health/Filament-version metadata — use it to find packages, the registry to vet them.
- **awesome-filament list** — https://github.com/spekulatius/awesome-filament (203 stars; categories: Complete Sections, Integrations, Data Imports/Exports, Filtering, Logging, UI, Fields, Charts, Analytics, MISC). Caveat: entries look 3.x-era and carry no Filament-version data — always re-verify 5.x support per plugin. There is NO official list at filamentphp/awesome-filament (404, verified). A "Laravel & Filament" section exists in filastudio/awesome-laravel (UNVERIFIED directly; seen in its own dev.to announcement).

## 4) Recommended search recipe (this project: FREE + Filament 5.x)

1. **Fetch** `https://filamentphp.com/plugins?price=free&q=<keyword>&sort=popular` (verified param combo; drop `q=` to browse all free, or add `&score=100`/`&score=80` for quality).
2. **Extract candidate slugs** from result card links (`/plugins/<slug>`); card already shows price, health score, stars, tags, "Legacy" flag.
3. **Fetch each** `https://filamentphp.com/plugins/<slug>`.
4. **Reject if**: "Supported versions:" lacks `5.x` (no "Supports v5.x" badge); price is not Free ("Buy now" + amount); card flagged "Legacy"; health score < 60 or failed critical checks (security advisories, abandoned, stale commits).
5. **Extract**: install command (`composer require vendor/package`) from the README section, repo URL, tags, health sub-scores, Plumb link.
6. **Save** a note at `knowledgebase/research/plugins/<slug>.md` per the filament-research skill format, and add a line to `knowledgebase/INDEX.md`.
7. **Fallbacks** if the registry search misses: tavily `site:filamentphp.com/plugins <name>`; Packagist JSON `https://packagist.org/search.json?q=filament-<keyword>` then map the package name to its registry slug; GitHub topic for repo-first discovery (verify 5.x on the registry page before trusting the README).

Never trust a URL 5.x filter — it does not exist (verified ignored).
