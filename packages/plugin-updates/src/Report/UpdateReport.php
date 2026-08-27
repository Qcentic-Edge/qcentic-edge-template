<?php

namespace QcenticEdge\PluginUpdates\Report;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use QcenticEdge\PluginUpdates\Ledger\VersionLedger;
use QcenticEdge\PluginUpdates\Manifest\ReleaseManifest;
use QcenticEdge\PluginUpdates\Registry\PackageRegistry;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;
use QcenticEdge\PluginUpdates\Schema\PendingMigrations;

/**
 * The one reading seam: what every registered package owes, right now.
 *
 *     PluginUpdates::report()->all();                  // every package
 *     PluginUpdates::report()->status($packageName);   // one of them
 *     PluginUpdates::report()->owing();                // only those with work
 *     PluginUpdates::report()->anythingOwed();         // for the badge
 *
 * The installer's page, the topbar notice and the run action are all views of
 * this, and nothing else queries update state directly. Nothing is cached:
 * every call is a fresh read, because row counts and the migration ledger both
 * move underneath it.
 */
final class UpdateReport
{
    public function __construct(
        private readonly PackageRegistry $registry,
        private readonly VersionLedger $ledger,
        private readonly PendingMigrations $migrations,
    ) {}

    /** @return array<string, PackageStatus> keyed by package name, in registration order */
    public function all(): array
    {
        return array_map($this->statusOf(...), $this->registry->all());
    }

    public function status(string $package): ?PackageStatus
    {
        $declaration = $this->registry->get($package);

        return $declaration === null ? null : $this->statusOf($declaration);
    }

    /** @return array<string, PackageStatus> only the packages with work to do */
    public function owing(): array
    {
        return array_filter($this->all(), fn (PackageStatus $status) => $status->owesWork());
    }

    /** Short-circuits on the first package with work, for a badge on every page. */
    public function anythingOwed(): bool
    {
        foreach ($this->registry->all() as $package) {
            if ($this->statusOf($package)->owesWork()) {
                return true;
            }
        }

        return false;
    }

    private function statusOf(UpdatablePackage $package): PackageStatus
    {
        $manifest = ReleaseManifest::read($package->manifestPath());

        $storedVersion = $this->ledger->storedVersion($package->name());
        $pendingVersions = $manifest->releasesAbove($storedVersion);

        return new PackageStatus(
            name: $package->name(),
            title: $package->displayTitle(),
            installedVersion: $storedVersion,
            codeVersion: $package->codeVersion(),
            pendingVersions: $pendingVersions,
            pendingMigrations: $this->migrations->inPath($package->migrationPath()),
            seedingVersions: $manifest->seedingAmong($pendingVersions),
            tables: array_map($this->countRows(...), $package->tableNames()),
        );
    }

    /**
     * A declared table whose create-table migration has not run yet is absent,
     * not empty. Asking for its rows anyway is how a report that is supposed to
     * tell an operator about pending schema work dies on the pending schema
     * work it was going to tell them about.
     */
    private function countRows(string $table): TableCount
    {
        return new TableCount(
            name: $table,
            rows: Schema::hasTable($table) ? DB::table($table)->count() : null,
        );
    }
}
