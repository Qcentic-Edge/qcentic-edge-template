# tests

Pest 4 + Orchestra Testbench. The library depends on nothing but the framework, so
the test app boots with nothing but the framework and the library's own provider.

## Files
- `Pest.php` — Feature/Unit use TestCase, Feature adds RefreshDatabase; helpers to register each fixture package the way a plugin's provider does (`registerFixturePackage()`, `registerHistoryPackage()`, `registerInstalledPackage()`, `registerOutOfOrderPackage()`, `registerBrokenPackage()`) and to find its files (`fixturePackagePath()`, `historyPackagePath()`, `installedPackagePath()`, `outOfOrderPackagePath()`); to place a database at any point in the history fixture's releases (`historyReleases()`, `applyHistoryThrough()`, `applyFixtureMigration()`, `placeHistoryAt()`, `historyStatus()`); to drive the run fixture (`runPackagePath()`, `registerRunPackage()`, `runReleases()`, `applyRunThrough()`, `placeRunAt()`, `runStatus()`, `runCodeVersion()`) and to read what a run left behind (`appliedMigrations()`, `seededRows()`); to read the library itself (`libraryPath()`, `libraryComposer()`, `librarySource()`); and `countingQueries()`, the row-count queries in the query log, for asserting what the cheap question does not pay for
- `TestCase.php` — Testbench with the library provider only

## Subdirectories
- `Feature/` — registration, the version ledger, the update report, schema owed, the multi-version gap, running an update, the resume guarantee, and never-on-boot
- `Unit/` — manifest parsing and version ordering, Composer code-version resolution, not-a-panel-plugin guards
- `Fixtures/` — `FixtureSeeder`, `FixturePackage/` (one release, one migration), `HistoryPackage/` (five releases, four migrations, the multi-version fixture), `OutOfOrderPackage/` (an unsorted manifest spanning 0.9.0 and 0.10.0), `InstalledPackage/` (`updates.php`, a manifest pinned to this library's own installed version, and `ahead-of-code.php`, the same plus a release the deployed code has not reached), `RunPackage/` (four releases below the installed version and four migrations, the fixture a run test can actually run), `RunPackageSeeder` (appends a row, so a second run is visible in the data), `MidRunFailure` (arms the third run migration to fail, for the resume guarantee) and `BootRegisteringProvider` (a package declaring itself from its own provider)
