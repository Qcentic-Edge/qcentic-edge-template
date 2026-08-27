<?php

namespace QcenticEdge\PluginUpdates\Report;

use Closure;

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
 * of the manifest entries that are pending, because seeding is the one
 * obligation with no ledger anywhere.
 *
 * A version gap on its own is not an obligation: a package whose path is fully
 * applied and whose pending releases all decline a seed owes nothing, however
 * many releases it is behind.
 *
 * There is a third state besides owing and not owing. A package whose manifest
 * cannot be read is *broken*: what it owes is unknown, and unknown is reported
 * as owing attention rather than as owing nothing — see `broken()`.
 */
final class PackageStatus
{
    /** @var list<TableCount>|null resolved on first ask, never on the way past */
    private ?array $tables = null;

    /**
     * @param  string|null  $storedVersion  the version this package's database is at, null when it has never recorded one
     * @param  string|null  $codeVersion  the version of the code deployed, null when Composer does not know the package
     * @param  list<string>  $pendingVersions  manifest releases above the stored version and at or below the code version, oldest first
     * @param  list<string>  $pendingMigrations  unapplied migration names in this package's own path, in run order
     * @param  list<string>  $seedingVersions  the pending releases that asked for a seed
     * @param  Closure(): list<TableCount>  $countTables  the declared tables and their row counts, counted only if asked
     * @param  string|null  $problem  why this package cannot be reported on, null when it can be
     */
    public function __construct(
        public readonly string $name,
        public readonly string $title,
        public readonly ?string $storedVersion,
        public readonly ?string $codeVersion,
        public readonly array $pendingVersions,
        public readonly array $pendingMigrations,
        public readonly array $seedingVersions,
        private readonly Closure $countTables,
        public readonly ?string $problem = null,
    ) {}

    /**
     * A package whose own declaration cannot be read — a manifest that is
     * missing, or that is not a set of releases.
     *
     * It reports no obligations because it has none that can be known, and
     * `owesWork()` is true anyway: a package the library cannot read must never
     * render as quiet beside the ones it can, or a misdeclared package would be
     * indistinguishable from an up-to-date one. `$problem` carries the message
     * that says which package is misdeclared and how.
     */
    public static function broken(
        string $name,
        string $title,
        ?string $storedVersion,
        ?string $codeVersion,
        string $problem,
    ): self {
        return new self(
            name: $name,
            title: $title,
            storedVersion: $storedVersion,
            codeVersion: $codeVersion,
            pendingVersions: [],
            pendingMigrations: [],
            seedingVersions: [],
            countTables: static fn (): array => [],
            problem: $problem,
        );
    }

    /**
     * The tables this package declared, with their row counts.
     *
     * Counted here rather than on the way in, because row counts are what the
     * operator judges "now or during quiet hours" by and nothing else — they
     * are never an input to `owesWork()`. A badge on every page of the panel
     * asks the cheap question, and must not pay for a full count sweep of every
     * declared table of every registered package to get an answer.
     *
     * @return list<TableCount>
     */
    public function tables(): array
    {
        return $this->tables ??= ($this->countTables)();
    }

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

    /** A package whose declaration the library could not read. `$problem` says why. */
    public function isBroken(): bool
    {
        return $this->problem !== null;
    }

    /**
     * Whether Composer knows what version of this package's code is deployed.
     *
     * Running an update ends by advancing the stored version to the code
     * version, and with no code version there is nothing to advance it to. The
     * run gates on this rather than re-deriving it from a null check of its
     * own.
     */
    public function codeVersionKnown(): bool
    {
        return $this->codeVersion !== null;
    }

    public function owesWork(): bool
    {
        return $this->isBroken() || $this->schemaOwed() || $this->seedOwed();
    }
}
