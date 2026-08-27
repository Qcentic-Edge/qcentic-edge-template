<?php

namespace QcenticEdge\PluginUpdates\Report;

use Closure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use QcenticEdge\PluginUpdates\Runner\UnrunnablePackage;

/**
 * What one package owes, read once and answered in full.
 *
 * Everything downstream — the installer's page, the topbar notice, the run
 * action — is a view of this object, and nothing else in the system queries
 * update state directly. That includes the runner: what it owes, whether a run
 * would be refused, and where its migrations and seeder are all come from here,
 * so no consumer holds a second view of the registry beside the report.
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

    /** The refusal, built once. Null is an answer here, so a flag rather than a null check. */
    private bool $refusalResolved = false;

    private ?UnrunnablePackage $refusal = null;

    /**
     * @param  string|null  $storedVersion  the version this package's database is at, null when it has never recorded one
     * @param  string|null  $codeVersion  the version of the code deployed, null when Composer does not know the package
     * @param  list<string>  $pendingVersions  manifest releases above the stored version and at or below the code version, oldest first
     * @param  list<string>  $pendingMigrations  unapplied migration names in this package's own path, in run order
     * @param  list<string>  $seedingVersions  the pending releases that asked for a seed
     * @param  string|null  $migrationPath  the directory this package's own migrations live in, null when it declared none
     * @param  class-string<Seeder>|null  $seederClass  the one idempotent seeder it declared, null when it declared none
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
        public readonly ?string $migrationPath,
        public readonly ?string $seederClass,
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
     * reasons `refusal()` gives.
     *
     * It still carries where its migrations and seeder are, because those are
     * facts about the declaration and are readable whatever the manifest does.
     * Every status has the same shape, so nothing reading one has to ask first
     * whether this is the broken kind.
     */
    public static function broken(
        string $name,
        string $title,
        ?string $storedVersion,
        ?string $codeVersion,
        ?string $migrationPath,
        ?string $seederClass,
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
            migrationPath: $migrationPath,
            seederClass: $seederClass,
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
        return $this->refusal() === null;
    }

    /**
     * The refusal a run would make, built and handed back rather than thrown —
     * or null when a run would go ahead.
     *
     * This is the one place the two audiences meet. The runner throws exactly
     * this object; a renderer shows exactly this object's message. They cannot
     * be worded differently because they are not two strings, they are one.
     *
     * Three cases, in the order a run meets them. Each is the same principle:
     * the library refuses to guess, because guessing here would either invent a
     * version the database is then wrongly recorded at, or silently skip work a
     * release declared — and both report a stale database as healthy, which is
     * the one failure this whole arrangement exists to prevent. The sentences
     * themselves are on `UnrunnablePackage`, with the rest of this package's
     * operator-facing prose.
     */
    public function refusal(): ?UnrunnablePackage
    {
        if ($this->refusalResolved) {
            return $this->refusal;
        }

        $this->refusalResolved = true;

        return $this->refusal = match (true) {
            $this->isBroken() => UnrunnablePackage::manifestUnreadable($this->name, $this->problem),
            ! $this->codeVersionKnown() => UnrunnablePackage::codeVersionUnknown($this->name),
            $this->seedOwed() && $this->seederClass === null => UnrunnablePackage::seederUndeclared($this->name, $this->seedingVersions),
            default => null,
        };
    }

    /**
     * Why a run would be refused, in a sentence fit to show an operator. Null
     * when a run would go ahead.
     *
     * Literally the message of the exception a run would throw — see
     * `refusal()` — so a renderer needs no exception handling to print it.
     */
    public function unrunnableReason(): ?string
    {
        return $this->refusal()?->getMessage();
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

    /**
     * How far behind this package's database is, in a sentence fit to show an
     * operator. Named here for the same reason as `needsAttention()`: both
     * renderers had grown their own copy of this cascade, in two templating
     * languages, and two copies of a sentence are two sentences waiting to
     * disagree.
     *
     * A version gap is not the only way to owe work — a package with unapplied
     * migrations and no pending release is behind on schema without being
     * behind on releases — so zero releases behind still has something to say.
     * Only a renderer that has already decided this package owes work should
     * ask; for one that owes nothing the sentence is true and pointless.
     */
    public function behindSummary(): string
    {
        if ($this->versionsBehind() === 0) {
            return 'Database update pending';
        }

        return $this->versionsBehind().' '.Str::plural('release', $this->versionsBehind()).' behind';
    }
}
