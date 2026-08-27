# tests

Pest 4 + Orchestra Testbench. The library depends on nothing but the framework, so
the test app boots with nothing but the framework and the library's own provider.

## Files
- `Pest.php` — Feature/Unit use TestCase, Feature adds RefreshDatabase; shared helpers: `registerFixturePackage()` declares the fixture the way a plugin's provider does, plus `libraryPath()`, `libraryComposer()` and `librarySource()`
- `TestCase.php` — Testbench with the library provider only

## Subdirectories
- `Feature/` — registration and enumeration, the version ledger
- `Unit/` — Composer code-version resolution, not-a-panel-plugin guards
- `Fixtures/` — `FixtureSeeder` and `FixturePackage/` (manifest plus a migration path)
