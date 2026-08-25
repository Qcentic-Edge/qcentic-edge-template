# Contributing to Qcentic Edge Template

Thanks for helping make this template better. This repo is the canonical,
always-up-to-date template: a clone of it must become a working application in
minutes, with no product-specific branding or one-off client logic baked in.

## Ground rules

- **Stay generic.** The template ships reusable infrastructure (panel, auth,
  media, realtime, Docker). Product features and client branding belong in your
  own clone, not here.
- **Filament 5.x only.** Do not add 3.x/4.x-specific code paths.
- **Free dependencies only.** Third-party Filament plugins consumed by the
  template must be free and support Filament 5.x.
- **Hardening is a feature.** The production image stays non-root, read-only,
  and capability-free. PRs that weaken this need a very good reason.

## Ways to contribute

- **Bug reports** — open an issue with: what you ran, what you expected, what
  happened, and the relevant `docker compose` output or test failure.
- **Recipes** — got a clean pattern for a common need (a new resource's authz
  tests, a deploy variant, a provider integration)? Propose it as a PR.
- **Code** — fork, branch, PR against `main`.

## Development setup

```bash
cp .env.docker.dev.example .env.docker.dev   # fill in APP_KEY
docker compose -f docker-compose.dev.yml --env-file .env.docker.dev up -d
```

App on http://localhost:8090, admin on http://localhost:8090/admin/login.
See the [README](README.md) for the full stack description.

## Before opening a PR

Run the full gate from inside the dev container — all three must pass:

```bash
docker compose -f docker-compose.dev.yml --env-file .env.docker.dev exec app php artisan test
docker compose -f docker-compose.dev.yml --env-file .env.docker.dev exec app vendor/bin/pint --test
docker compose -f docker-compose.dev.yml --env-file .env.docker.dev exec app vendor/bin/phpstan analyse
```

- Tests: Pest, hermetic (sqlite `:memory:`, faked S3 — no live MinIO/Redis).
- Style: Pint (Laravel preset). Run `vendor/bin/pint` to auto-fix.
- Static analysis: Larastan level 5.

If you add a Filament resource, copy the authorization test matrix described in
the README (guest / wrong-role / owner / `super_admin`) into
`tests/Feature/Security/`.

## Commits and PRs

- Write commit messages as plain imperative sentences that say **why**
  ("Add X so clones get Y"), not just what.
- Keep PRs small and single-purpose. One concern per PR beats one big sweep.
- Update the README when you change behavior it documents (ports, env vars,
  compose services, commands).

## License

By contributing, you agree that your contributions are licensed under the
[Apache-2.0 license](LICENSE).
