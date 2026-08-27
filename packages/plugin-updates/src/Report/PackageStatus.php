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
 * Owing work and being able to do it are two different questions, and both
 * renderers ask both. A package can owe work the library will refuse to run —
 * see `runnable()` — and that third state has to be told apart from owing
 * nothing, or a renderer draws a button that throws the moment it is pressed.
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
     * @param  bool  $seederDeclared  whether the package declared a seeder for those releases to use
     * @param  Closure(): list<TableCount>  $countTables  the declared tables and their row counts, counted only if asked
     * @param  string|null  $problem  why this package's own declaration could not be read, null when it could
     */
    public function __construct(
        public readonly string $name,
        public readonly string $title,
        public readonly ?string $storedVersion,
        public readonly ?string $codeVersion,
        public readonly array $pendingVersions,
        public readonly array $pendingMigrations,
        public readonly array $seedingVersions,
        public readonly bool $seederDeclared,
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
     * that says which package is misdeclared and how, and is the first of the
     * reasons `unrunnableReason()` gives.
     */
    public static function broken(
        string $name,
        string $title,
        ?string $storedVersion,
        ?string $codeVersion,
        bool $seederDeclared,
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
            seederDeclared: $seederDeclared,
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

    /**
     * Whether `PluginUpdates::run()` would go ahead, or refuse before it had
     * touched anything.
     *
     * Answered here and nowhere else. A renderer has to know this *before* it
     * draws anything, or it offers a button whose only effect is an exception;
     * and the alternative — a renderer asking the registry what a package
     * declared — would be a second view of update state beside the report,
     * which is exactly what the one-seam rule forbids. The runner asks the same
     * question of the same object, so what an operator is shown and what a run
     * decides can never disagree.
     */
    public function runnable(): bool
    {
        return $this->unrunnableReason() === null;
    }

    /**
     * Why a run would be refused, in a sentence that names what is missing —
     * fit to show an operator, and to carry as the exception's message when a
     * run is attempted anyway. Null when a run would go ahead.
     *
     * Three cases, in the order a run meets them. Each is the same principle:
     * the library refuses to guess, because guessing here would either invent a
     * version the database is then wrongly recorded at, or silently skip work a
     * release declared — and both report a stale database as healthy, which is
     * the one failure this whole arrangement exists to prevent.
     */
    public function unrunnableReason(): ?string
    {
        if ($this->isBroken()) {
            return 'Its own release manifest cannot be read, so whether it owes a seed is unknown and '
                ."running it would be running blind: {$this->problem}";
        }

        if (! $this->codeVersionKnown()) {
            return 'Composer does not know what version of its code is deployed, so there is no version '
                .'to advance its database to. Check that it is installed under the name it registered.';
        }

        if ($this->seedOwed() && ! $this->seederDeclared) {
            return 'The release(s) ['.implode(', ', $this->seedingVersions).'] ask for a seed and it '
                .'declared no seeder. Declare one with UpdatablePackage::make(...)->seeder(YourSeeder::class), '
                .'or set seed => false for those releases. Skipping the seed quietly would lose the data '
                .'those releases meant to add.';
        }

        return null;
    }

    /**
     * Owes work, and none of it can be run — the third state.
     *
     * Named here so that both renderers say it the same way. Such a package
     * gets no button: what it needs is a person looking at the reason, not a
     * click that would be refused.
     */
    public function needsAttention(): bool
    {
        return $this->owesWork() && ! $this->runnable();
    }
}
