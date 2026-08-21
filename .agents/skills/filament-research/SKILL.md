---
name: filament-research
description: Research system for the Filament 5.x project. Use whenever the user asks how a Filament feature works, needs a specific doc page (fields, columns, actions, forms, tables, infolists, panels, widgets, testing, deployment), or asks whether a plugin exists and is free. Fetches filamentphp.com docs (clean .md) or the free plugin registry, saves a cited note in knowledgebase/, updates INDEX.md and CONTEXT.md, then answers from the note.
---

# Filament Research

Builds and uses a local knowledgebase for a Filament 5.x app that only uses free plugins.
Knowledgebase root: `knowledgebase/` (repo root).

## Hard rules

1. Local note first. Always read `knowledgebase/INDEX.md` before fetching anything. If the topic is covered, read the note and answer from it. No re-fetching.
2. Filament 5.x only. Ignore 3.x and 4.x content unless the user explicitly asks about upgrade differences (then use the upgrade-guide doc).
3. Free plugins only. If a plugin is paid, write a note marked `paid — skip`, never recommend it, and look for a free alternative (registry search or tavily with `site:filamentphp.com/plugins`).
4. Every saved note must carry source URL + fetch date. Never write a note without a source.

## Knowledgebase layout

```
knowledgebase/
  INDEX.md            # one line per note, newest first
  CONTEXT.md          # glossary + project decisions
  docs-index.md       # mirror of https://filamentphp.com/docs/llms.txt
  research/docs/      # saved doc pages (raw .md + source header)
  research/plugins/   # structured plugin notes
  decisions/          # ADRs for architecture choices
```

## Workflow

### 1. Check local KB
Read `knowledgebase/INDEX.md`. Search for the topic. If a note exists, read it, answer, done.

### 2. Fetch the right source

**Core docs.** Pick the page(s) in `knowledgebase/docs-index.md`. The URLs end in `.md` and return markdown — fetch them directly (webfetch, format text). The response contains boilerplate: drop the `> ## Documentation Index` preamble, all `export const ...` JS blocks (AutoScreenshot, EditOnGitHub, Footer), and keep only the real doc content. Convert `<Info>`, `<Warning>` etc. into plain `> Note:` / `> Warning:` lines; keep code blocks verbatim. If the topic is not in the index, tavily search `site:filamentphp.com/docs 5.x <topic>`, then re-mirror the index if a whole section is new.

**Plugins.**
- Search the registry: fetch `https://filamentphp.com/plugins?price=free` (verified working; adds the free filter). For a specific name, fetch the plugin page `https://filamentphp.com/plugins/<slug>` directly.
- Fallback search: tavily `site:filamentphp.com/plugins <name>`.
- From the plugin page extract: name, author, price (Free vs amount), Filament versions supported (must include 5.x), package health score, repo URL, install command, tags, description.
- If price is not Free: note it, mark `paid — skip`, find free alternative.
- Full canonical recipe with all verified URL params and acceptance rules: `knowledgebase/research/plugins/find-plugins-guide.md`.

**Plugin development ("how do I build a plugin?").** Read the local notes first — the full official docs are already saved, no fetching needed:
- `knowledgebase/research/docs/plugins-development-synthesis.md` — direct answer (panel vs standalone, scaffolding, PackageServiceProvider + Plugin object, asset rules, shipping).
- Verbatim pages: `plugins-getting-started.md`, `plugins-panel-plugins.md`, `plugins-building-a-panel-plugin.md`, `plugins-building-a-standalone-plugin.md`, `plugins-configurable-resources-and-pages.md`.
Only fetch a 5.x doc page when the notes have a gap (e.g. advanced/assets, styling/icons) — then save it like any doc page.

**Anything else** (package behavior, GitHub issues, community examples): tavily search, prefer the plugin's GitHub repo README. For open-ended questions beyond the docs, delegate to a background agent per Matt Pocock's `research` skill pattern and save its cited note here.

### 3. Save the note

**Doc page** → `knowledgebase/research/docs/<page-slug>.md`:
```markdown
# <Page title>

Source: <url> — fetched YYYY-MM-DD

<full page content, verbatim>
```
If several pages were fetched for one question, also write a short synthesis note `knowledgebase/research/docs/<topic>-synthesis.md` with the direct answer up top and pointers to the saved pages.

**Plugin** → `knowledgebase/research/plugins/<slug>.md`:
```markdown
# <Plugin name>

- URL: <plugin page url>
- Author:
- Price: Free (verified YYYY-MM-DD)   |   paid — skip ($X)
- Filament versions: 5.x? yes/no
- Health score: N/100
- Repo:
- Install: composer require <vendor>/<package>

## What it does
<2-4 sentences>

## Key usage
<the one or two code patterns that matter>

## Notes / gotchas
<decisions, caveats, free-alternative if paid>
```

### 4. Update the knowledgebase
- `knowledgebase/INDEX.md`: add one line per new note, newest first:
  `YYYY-MM-DD | kind | research/docs/file.md | one-line summary`
- `knowledgebase/CONTEXT.md`: add any new term the user may meet again. Keep it to one or two lines per term.

### 5. Answer
Answer the user's actual question concisely, using the note's content. Cite the note file path so the user can open it. If the answer was a "does a free plugin exist for X" question, list the top free candidates with one line each and your pick.

## Refresh policy
- Docs: treat saved notes as a snapshot. If the user says behavior differs, or a page 404s, re-fetch and overwrite the note (bump the fetch date).
- `docs-index.md`: re-mirror from llms.txt when a doc page is missing or a new section appears.
- Plugin "Free" status: re-verify on the plugin page if the note is older than 30 days and the decision matters.
