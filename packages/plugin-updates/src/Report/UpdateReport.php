<?php

namespace QcenticEdge\PluginUpdates\Report;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use QcenticEdge\PluginUpdates\Ledger\VersionLedger;
use QcenticEdge\PluginUpdates\Manifest\ReleaseManifest;
use QcenticEdge\PluginUpdates\Manifest\UnreadableManifest;
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
        return iterator_to_array($this->statuses());
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

    /**
     * Short-circuits on the first package with work, for a badge on every page.
     *
     * The same derivation as `owing()`, walked one package at a time so it can
     * stop early rather than a second, cheaper reading of update state — there
     * is only ever one.
     */
    public function anythingOwed(): bool
    {
        foreach ($this->statuses() as $status) {
            if ($status->owesWork()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every registered package's status, one at a time. Lazy so that the
     * question "does anything owe work" can stop at the first yes.
     *
     * @return iterable<string, PackageStatus>
     */
    private function statuses(): iterable
    {
        foreach ($this->registry->all() as $name => $package) {
            yield $name => $this->statusOf($package);
        }
    }

    /**
     * One package's status. A manifest this package declared but the library
     * cannot read is that package's problem and no other package's: it is
     * surfaced as a broken status here rather than thrown, so that one
     * misdeclared package cannot take down the panel that was going to report
     * it. `ReleaseManifest` still refuses to read a broken manifest as empty —
     * that would report a package four releases behind as owing nothing.
     */
    private function statusOf(UpdatablePackage $package): PackageStatus
    {
        $storedVersion = $this->ledger->storedVersion($package->name());
        $codeVersion = $package->codeVersion();

        try {
            $manifest = ReleaseManifest::read($package->manifestPath());
        } catch (UnreadableManifest $failure) {
            return PackageStatus::broken(
                name: $package->name(),
                title: $package->displayTitle(),
                storedVersion: $storedVersion,
                codeVersion: $codeVersion,
                problem: $failure->getMessage(),
            );
        }

        $pendingVersions = $manifest->pendingBetween($storedVersion, $codeVersion);

        return new PackageStatus(
            name: $package->name(),
            title: $package->displayTitle(),
            storedVersion: $storedVersion,
            codeVersion: $codeVersion,
            pendingVersions: $pendingVersions,
            pendingMigrations: $this->migrations->inPath($package->migrationPath()),
            seedingVersions: $manifest->seedingAmong($pendingVersions),
            countTables: fn (): array => array_map($this->countRows(...), $package->tableNames()),
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
