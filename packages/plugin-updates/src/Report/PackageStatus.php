<?php

namespace QcenticEdge\PluginUpdates\Report;

/**
 * What one package owes, read once and answered in full.
 *
 * Everything downstream — the installer's page, the topbar notice, the run
 * action — is a view of this object, and nothing else in the system queries
 * update state directly.
 *
 * The two obligations come from two different places, and that split is the
 * load-bearing decision of the whole design. `pendingMigrations` is the diff
 * of this package's own migration path against Laravel's `migrations` ledger,
 * so it can never disagree with the database. `seedingVersions` is the union
 * of the manifest entries above the stored version, because seeding is the one
 * obligation with no ledger anywhere.
 *
 * A version gap on its own is not an obligation: a package whose path is fully
 * applied and whose pending releases all decline a seed owes nothing, however
 * many releases it is behind.
 */
final class PackageStatus
{
    /**
     * @param  string|null  $installedVersion  the version this package's database is at, null when it has never recorded one
     * @param  string|null  $codeVersion  the version of the code deployed, null when Composer does not know the package
     * @param  list<string>  $pendingVersions  manifest releases above the stored version, oldest first
     * @param  list<string>  $pendingMigrations  unapplied migration names in this package's own path, in run order
     * @param  list<string>  $seedingVersions  the pending releases that asked for a seed
     * @param  list<TableCount>  $tables  the tables this package declared, with their row counts
     */
    public function __construct(
        public readonly string $name,
        public readonly string $title,
        public readonly ?string $installedVersion,
        public readonly ?string $codeVersion,
        public readonly array $pendingVersions,
        public readonly array $pendingMigrations,
        public readonly array $seedingVersions,
        public readonly array $tables,
    ) {}

    /** How many releases the database is catching up. Zero is not the same as owing nothing. */
    public function versionsBehind(): int
    {
        return count($this->pendingVersions);
    }

    public function schemaOwed(): bool
    {
        return $this->pendingMigrations !== [];
    }

    /** Owed once however many pending releases asked for it. */
    public function seedOwed(): bool
    {
        return $this->seedingVersions !== [];
    }

    public function owesWork(): bool
    {
        return $this->schemaOwed() || $this->seedOwed();
    }

    /** A package that owes nothing says nothing: no badge, no row, no button. */
    public function owesNothing(): bool
    {
        return ! $this->owesWork();
    }
}
