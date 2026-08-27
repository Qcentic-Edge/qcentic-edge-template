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
 * idempotently behind a table-existence check on first write, which behaves
 * identically whether the installer is present, absent, or added later.
 *
 * Only the write path creates anything. Reading is a read: on a replica whose
 * database has never seen the ledger — or on a read-only one — asking what
 * version a package is at answers null rather than issuing DDL, which is the
 * same answer for the same reason, since a package the ledger has never heard
 * of is absent.
 */
final class VersionLedger
{
    public const TABLE = 'plugin_update_versions';

    /**
     * Memoised for the life of the request. Only ever set once the table is
     * known to exist, so a ledger asked before the first write and again after
     * it still answers correctly — the flag can go from false to true, never
     * back, and the table is never dropped in flight.
     */
    private bool $tableExists = false;

    public function ensureTable(): void
    {
        if ($this->tableExists()) {
            return;
        }

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string('package')->unique();
            $table->string('version');
            $table->timestamps();
        });

        $this->tableExists = true;
    }

    /**
     * The version this package's database is at, or null when the package has
     * never recorded one. Never a version string it did not write: a package
     * the ledger has not heard of is absent, not at some assumed version — and
     * a database with no ledger table at all has heard of no package.
     */
    public function storedVersion(string $package): ?string
    {
        if (! $this->tableExists()) {
            return null;
        }

        $version = DB::table(self::TABLE)
            ->where('package', $package)
            ->value('version');

        return $version === null ? null : (string) $version;
    }

    /**
     * Record where a package's database now is. Written only after a full
     * successful run, so a partial run leaves the package visibly behind.
     *
     * The upsert is one statement rather than a read followed by a write:
     * several replicas may be serving the panel, and the unique index on
     * `package` is what keeps a package to one row regardless of which of them
     * answered. `created_at` is stamped on the first record and left alone
     * afterwards, which is why the update column list is explicit.
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

    private function tableExists(): bool
    {
        return $this->tableExists ?: $this->tableExists = Schema::hasTable(self::TABLE);
    }
}
