<?php

namespace QcenticEdge\PluginUpdates\Runner;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\Seeder;
use QcenticEdge\PluginUpdates\Ledger\VersionLedger;
use QcenticEdge\PluginUpdates\Registry\PackageRegistry;
use QcenticEdge\PluginUpdates\Registry\UpdatablePackage;
use QcenticEdge\PluginUpdates\Report\PackageStatus;
use QcenticEdge\PluginUpdates\Report\UpdateReport;

/**
 * Catching one package's database up to its code: the unapplied migrations in
 * its own path, then its seeder if any pending release owes one, then the
 * stored version advanced to the code version.
 *
 * There is no catch-up mode, because there is no catch-up mode to get wrong.
 * A site one release behind and a site five releases behind take this same
 * single pass: one migrator call over one directory, one union over the pending
 * releases, one write of the code version. The migrator replays from wherever
 * this particular database stopped, and nothing here asks how far back that was.
 *
 * Only ever reached from an explicit action — an operator's click. Nothing
 * calls it on boot; several replicas starting the same schema change on deploy
 * is the failure that avoids.
 */
final class UpdateRunner
{
    public function __construct(
        private readonly PackageRegistry $registry,
        private readonly UpdateReport $report,
        private readonly VersionLedger $ledger,
        private readonly Migrator $migrator,
        private readonly Container $container,
    ) {}

    /**
     * Run one package's update: its unapplied migrations, its seeder if a
     * pending release owes one, and then — only once all of that has
     * succeeded — its stored version.
     *
     * @throws UnrunnablePackage before anything has been touched, when the
     *                           package is not registered or declared something
     *                           the library will not guess at.
     */
    public function run(string $package): void
    {
        $declaration = $this->registry->get($package)
            ?? throw UnrunnablePackage::notRegistered($package);

        // What this package owes, read through the report rather than derived
        // again here: the run does exactly what the operator was shown, and
        // nothing gets a private view of update state. The report reads the
        // same registry, so a package the registry holds is one it can answer
        // for.
        $status = $this->report->status($package);

        $this->refuseUnlessRunnable($status, $declaration);

        $this->migrate($declaration->migrationPath());

        if ($status->seedOwed()) {
            $this->seed($declaration->seederClass());
        }

        // Last, and only here. A run that died before this line leaves the
        // package visibly behind, so the button stays and a second attempt
        // resumes from the first file the migrator has not recorded.
        $this->ledger->record($package, $status->codeVersion);
    }

    /**
     * The three facts a run cannot proceed without, checked before it has done
     * anything, so that a refusal never leaves a half-finished update behind.
     */
    private function refuseUnlessRunnable(PackageStatus $status, UpdatablePackage $declaration): void
    {
        // Its manifest could not be read, so whether a seed is owed is unknown.
        if ($status->isBroken()) {
            throw UnrunnablePackage::withUnreadableDeclaration($status->name, $status->problem);
        }

        // Composer does not know the package, so there is no version to advance
        // to, and recording an invented one would report a stale database as
        // current for ever after.
        if (! $status->codeVersionKnown()) {
            throw UnrunnablePackage::withoutCodeVersion($status->name);
        }

        // A release asked for a seed from a package that owns no seeder. The
        // library refuses to guess rather than skipping the release's data.
        if ($status->seedOwed() && $declaration->seederClass() === null) {
            throw UnrunnablePackage::withoutSeeder($status->name, $status->seedingVersions);
        }
    }

    /**
     * Laravel's own migrator, scoped to this package's own directory.
     *
     * `Migrator::run()` is the migration files in the given paths minus the
     * ones the `migrations` ledger already records, which is why a path-scoped
     * call is exactly incremental over an arbitrary gap and needs no
     * bookkeeping of its own — and why running one package can never reach
     * another package's files.
     *
     * Deliberately not wrapped in a transaction. Laravel already runs each file
     * in one where the connection supports it, and logs each success as it
     * happens; a transaction around the whole batch would throw away every
     * completed file when a later one failed, and a host with a request timeout
     * would then never finish a long catch-up however many times it retried.
     *
     * A package that declared no migration path owes no schema work and never
     * can, and neither does one whose path holds no files.
     */
    private function migrate(?string $path): void
    {
        if ($path === null || ! is_dir($path)) {
            return;
        }

        // On a fresh edge host this may be the first thing that ever runs a
        // migration, and there is no shell to prepare the database with. The
        // `migrate` command makes the ledger the same way before it runs.
        if (! $this->migrator->repositoryExists()) {
            $this->migrator->getRepository()->createRepository();
        }

        $this->migrator->run([$path]);
    }

    /**
     * The one idempotent seeder the package declared, run once however many
     * pending releases asked for it.
     *
     * @param  class-string<Seeder>  $seeder
     */
    private function seed(string $seeder): void
    {
        $this->container->make($seeder)
            ->setContainer($this->container)
            ->__invoke();
    }
}
