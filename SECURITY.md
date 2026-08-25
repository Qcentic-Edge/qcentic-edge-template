# Security Policy

## Reporting a vulnerability

**Please do not open a public issue for security vulnerabilities.**

Report them privately through one of these channels:

- **GitHub private vulnerability reporting** — use the
  [Security tab](../../security/advisories/new) of this repository to file a
  private advisory.
- **Email** — info@qcentic.com

Include:

- A description of the vulnerability and its impact.
- Steps to reproduce or a proof of concept.
- Affected versions/commits, if known.

You can expect an acknowledgment within a few business days. We will keep you
informed as we investigate and coordinate a fix and disclosure timeline with
you. Once a fix is released, we are happy to credit reporters who want it.

## Scope

This policy covers the template itself: the Docker images and Compose stacks,
the Laravel/Filament application code under `app/`, and the first-party
packages under `packages/`.

Vulnerabilities in upstream dependencies (Filament, Laravel, FrankenPHP,
libSQL, MinIO, …) should also be reported to their respective projects.

## Supported versions

The template is a rolling release on `main`. Only the latest commit of `main`
receives security fixes — pull and rebuild to stay current.

## Security properties of the template

Worth knowing before you report (and before you deploy):

- The production container runs **non-root** (`uid 1000`) with a **read-only
  root filesystem** and **no Linux capabilities**.
- The panel uses Spatie roles + Filament Shield; there is no `is_admin` flag.
- Laravel Passport's **password grant is disabled** by design.
- Dev conveniences (MinIO anonymous download, seeded users, `artisan serve`)
  exist only in `docker-compose.dev.yml` — never run the dev stack in
  production.
