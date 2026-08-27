<?php

namespace QcenticEdge\PluginUpdates\Ledger;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Where each package's database has got to: one row per package, holding the
 * version its schema and seed data are at.
 *
 * The table ships as no migration file of its own, and cannot. It has to exist
 * before the machinery that runs migrations can report anything, and on a
 * stateless edge host there is no shell to create it with. So it is ensured
 * idempotently behind a table-existence check on first use, which behaves
 * identically whether the installer is present, absent, or added later.
 */
final class VersionLedger
{
    public const TABLE = 'plugin_update_versions';

    public function ensureTable(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string('package')->unique();
            $table->string('version');
            $table->timestamps();
        });
    }

    /**
     * The version this package's database is at, or null when the package has
     * never recorded one. Never a version string it did not write: a package
     * the ledger has not heard of is absent, not at some assumed version.
     */
    public function storedVersion(string $package): ?string
    {
        $this->ensureTable();

        $version = DB::table(self::TABLE)
            ->where('package', $package)
            ->value('version');

        return $version === null ? null : (string) $version;
    }

    /**
     * Record where a package's database now is. Written only after a full
     * successful run, so a partial run leaves the package visibly behind.
     */
    public function record(string $package, string $version): void
    {
        $this->ensureTable();

        $now = now();

        DB::table(self::TABLE)->upsert(
            ['package' => $package, 'version' => $version, 'created_at' => $now, 'updated_at' => $now],
            ['package'],
            ['version', 'updated_at'],
        );
    }
}
