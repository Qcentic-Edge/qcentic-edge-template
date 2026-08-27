# tests

Pest 4 + Orchestra Testbench. The default test app is a panel, because the notice renders
into one: Filament's providers, Livewire's, one panel provider and the library's own,
listed rather than discovered and in the order Composer's discovery would produce.
`Host/` is the exception — two other shapes of host, each naming its own base class at the
top of the file, for what the library does where there is no panel and where its own
provider never registered.

## Files
- `Pest.php` — Feature/Unit use TestCase, Feature adds RefreshDatabase; helpers to register each fixture package the way a plugin's provider does (`registerFixturePackage()`, `registerHistoryPackage()`, `registerInstalledPackage()`, `registerOutOfOrderPackage()`, `registerBrokenPackage()`) and to find its files (`fixturePackagePath()`, `historyPackagePath()`, `installedPackagePath()`, `outOfOrderPackagePath()`); to place a database at any point in the history fixture's releases (`historyReleases()`, `applyHistoryThrough()`, `applyFixtureMigration()`, `placeHistoryAt()`, `historyStatus()`); to drive the run fixture (`runPackagePath()`, `registerRunPackage()`, `runReleases()`, `applyRunThrough()`, `placeRunAt()`, `runStatus()`, `runCodeVersion()`) and to read what a run left behind (`appliedMigrations()`, `seededRows()`); to read the library itself (`libraryPath()`, `libraryComposer()`, `librarySource()`); to render the panel's topbar hook and to put an installer-shaped plugin on the panel (`renderTopbar()`, `installerPluginPresent()`); and `countingQueries()`, the row-count queries in the query log, for asserting what the cheap question does not pay for
- `TestCase.php` — Testbench with Filament, Livewire, the test panel and the library's provider, plus the app key Livewire's signed payloads need
- `PanellessTestCase.php` — Testbench with the library's provider and nothing else, discovery off: the test app of a package that consumes the library for reporting alone
- `ProviderlessTestCase.php` — Testbench with no providers and discovery off: an application where the library is on the autoloader but its provider never registered

## Subdirectories
- `Feature/` — registration, the version ledger, the update report (including whether a run would be refused and why), schema owed, the multi-version gap, running an update, the resume guarantee, never-on-boot, and the topbar notice
- `Unit/` — manifest parsing and version ordering, Composer code-version resolution, not-a-panel-plugin guards
- `Host/` — what the library does in a host that is not a panel: `PanellessHostTest` (it boots with no Livewire, registers no notice, and still registers packages and still refuses half-declared ones), `MissingProviderTest` (registering or reading with no library provider is refused by name, and the notice's quiet skip does not stand in for it)
- `Fixtures/` — `FixtureSeeder`, `FixturePackage/` (one release, one migration), `HistoryPackage/` (five releases, four migrations, the multi-version fixture), `OutOfOrderPackage/` (an unsorted manifest spanning 0.9.0 and 0.10.0), `InstalledPackage/` (`updates.php`, a manifest pinned to this library's own installed version, and `ahead-of-code.php`, the same plus a release the deployed code has not reached), `RunPackage/` (four releases below the installed version and four migrations, the fixture a run test can actually run), `RunPackageSeeder` (appends a row, so a second run is visible in the data), `MidRunFailure` (arms the third run migration to fail, for the resume guarantee), `BootRegisteringProvider` (a package declaring itself from its own provider), `TestPanelProvider` (the panel the notice renders into), `InstallerLikePlugin` (a panel plugin under the id the installer uses, so suppression can be tested without depending on the installer) and `RoleAwareUser` (a user model that exposes roles, for the authorisation rule)
