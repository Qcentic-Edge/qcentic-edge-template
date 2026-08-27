<?php

namespace QcenticEdge\PluginUpdates\Schema;

use Illuminate\Database\Migrations\Migrator;

/**
 * Which migration files in one package's own path the database has not run.
 *
 * This is the whole of the library's answer to "does this package owe schema
 * work". Laravel's `migrations` table records every file individually, so the
 * diff is exact, cannot disagree with the database, and is already incremental
 * over an arbitrary version gap — a site five releases behind and a site one
 * release behind take the same single pass through the same directory.
 *
 * Scoping the diff to one path is what makes it precise, and is the whole
 * difference from the installer's global scan, which could only ever say that
 * *something* somewhere was pending.
 *
 * Deliberately absent: any map from migration file to release. The operator is
 * shown the version gap from the stored and code versions and the row counts
 * from the declared tables; a file-to-version map would be a third
 * hand-maintained copy of a fact the migrator already holds, and would drift
 * the same way a manifest schema flag would.
 */
final class PendingMigrations
{
    public function __construct(private readonly Migrator $migrator) {}

    /**
     * The unapplied migration names in this path, in the order the migrator
     * would run them — file-name order, which is the order they were written.
     *
     * A package that declared no path owes no schema work and never can, and
     * neither does one whose path holds no migration files.
     *
     * @return list<string>
     */
    public function inPath(?string $path): array
    {
        if ($path === null || ! is_dir($path)) {
            return [];
        }

        $files = $this->migrator->getMigrationFiles([$path]);

        if (! $this->migrator->repositoryExists()) {
            return array_keys($files);
        }

        $ran = $this->migrator->getRepository()->getRan();

        return array_keys(array_diff_key($files, array_flip($ran)));
    }
}
