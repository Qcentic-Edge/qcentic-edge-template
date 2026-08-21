# AGENTS.md — Filament 5.x Project

## First rule: communication style

**Caveman mode is always on, by default.** Every session starts in caveman mode (level `full`) and it stays on for the whole session. It is the first skill picked, before any other skill or task work. The only way it turns off is the user saying "stop caveman" or "normal mode". When writing files (code, docs, notes, this file), write normal prose — caveman applies to chat output only.

## Task completion notification

When a task is finished (or needs the user's attention, e.g. a blocking question or an error), call the `notify` MCP tool with a short `title` (max 50 chars, e.g. "Task done") and a 1–2 sentence `message` describing the result. If the `notify` tool is not available in the session, skip silently — never announce the skip. Only notify once per task, at the end, not after every step.

## Source of truth

**The Filament documentation on the website is the single source of truth.**
That means: `https://filamentphp.com/docs/5.x/` (the full page index is mirrored in `knowledgebase/docs-index.md`, fetched from `https://filamentphp.com/docs/llms.txt`).

Consequences:

1. When anything is unclear, ambiguous, or in conflict — code, conversation, or a local note — the website documentation wins. Do not guess from memory.
2. The local `knowledgebase/` is a cache, not the truth. It exists for speed and continuity. If a local note and the website ever disagree, re-fetch the page, overwrite the note, bump the fetch date, and mention the correction.
3. Project target is Filament **5.x only**. Ignore 3.x and 4.x content unless explicitly comparing upgrade behavior (use the upgrade-guide doc).
4. Plugins: **free only**. A paid plugin is recorded as `paid — skip` in `knowledgebase/research/plugins/` and never recommended.

## The knowledgebase

Layout and the full workflow live in the `filament-research` skill (`.agents/skills/filament-research/SKILL.md`). In short:

- `knowledgebase/INDEX.md` — one line per note, newest first. Read it before fetching anything.
- `knowledgebase/CONTEXT.md` — glossary and project decisions. Keep it current as new terms appear.
- `knowledgebase/docs-index.md` — mirror of the docs index. Re-mirror from `llms.txt` when a page is missing or a section appears.
- `knowledgebase/research/docs/` — saved doc pages, verbatim content + source URL + fetch date.
- `knowledgebase/research/plugins/` — structured plugin notes (price verified, versions, repo, install).
- `knowledgebase/decisions/` — ADRs for architecture choices.

### Sync discipline

- Local notes are snapshots with a fetch date. If the user reports behavior that differs from a note, or a page 404s, re-fetch from the website and overwrite the note.
- If the website docs change in a way that contradicts saved notes the user depends on, proactively re-fetch and update those notes.
- Never write a knowledgebase note without a source URL and fetch date.
- Every new note gets one line in `INDEX.md`.

## Research workflow

For any "how does X work" or "does a free plugin exist for X" question, run the `filament-research` skill flow: check `INDEX.md` → fetch from the website only if not covered locally → save cited note → update `INDEX.md` and `CONTEXT.md` → answer from the note, citing the note path.

For open-ended research questions that go beyond the docs (ecosystem behavior, platform quirks, "investigate X against primary sources"), use Matt Pocock's `research` skill pattern: delegate to a background agent that reads primary sources and writes one cited Markdown note into the knowledgebase, following the filament-research conventions (source URL + fetch date + INDEX.md line).

## Plugin workflows

### Finding a plugin ("does a free plugin exist for X?")

Follow `knowledgebase/research/plugins/find-plugins-guide.md` (the canonical recipe). Short version:

1. Fetch `https://filamentphp.com/plugins?price=free&q=<keyword>&sort=popular` (verified URL params: `price=free`, `q=`, `sort=popular`, `score=N`; version/tag filters are browser-only — a URL 5.x filter does not exist).
2. Open each candidate's `https://filamentphp.com/plugins/<slug>` page.
3. Accept only if: price Free, "Supported versions:" includes `5.x`, not flagged "Legacy", health score ≥ 60 (prefer 80+) with no failed security/maintenance checks.
4. Save a note per plugin in `knowledgebase/research/plugins/<slug>.md` (format in the filament-research skill) + INDEX.md line. Paid → note marked `paid — skip` + look for a free alternative.
5. Fallbacks: tavily `site:filamentphp.com/plugins <name>`, Packagist JSON `https://packagist.org/search.json?q=filament-<keyword>`, GitHub topic `filament-plugin`.

### Making a plugin ("how do I build a plugin?")

Read the local notes first — they cover the full official docs, no fetching needed:

- `knowledgebase/research/docs/plugins-development-synthesis.md` — the direct answer: panel vs standalone, scaffolding, the two building blocks, asset rules, shipping.
- Verbatim doc pages: `plugins-getting-started.md`, `plugins-panel-plugins.md`, `plugins-building-a-panel-plugin.md` (tutorial), `plugins-building-a-standalone-plugin.md` (tutorial), `plugins-configurable-resources-and-pages.md` in `knowledgebase/research/docs/`.
- Reference implementations from the tutorials: awcodes/clock-widget (panel), awcodes/headings (standalone).

Key rules from those notes: scaffold from filamentphp/plugin-skeleton + `php ./configure.php`; service provider extends spatie's `PackageServiceProvider` with `static string $name`; Plugin object (`getId`/`register`/`boot`) only for panel plugins; assets registered in `packageBooted()` (or `$panel->assets()` in `register()` when needed on every page of a panel); prefer lazy loading (`x-load`, `loadedOnRequest()`).

If a gap is not covered by the notes (e.g. advanced/assets, styling/icons details), fetch that specific 5.x doc page per the filament-research flow and save it.

If the plugin is meant to be a standalone product of this repo (not throwaway), treat it as real work: ADR in `knowledgebase/decisions/` for the architecture choice, tests, and consider submitting to the registry via https://filamentphp.com/author (GitHub OAuth).
