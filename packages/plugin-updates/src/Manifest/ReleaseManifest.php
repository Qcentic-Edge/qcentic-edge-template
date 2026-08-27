<?php

namespace QcenticEdge\PluginUpdates\Manifest;

/**
 * What each release of a package owes, as that package declared it:
 *
 *     return [
 *         '0.4.0' => ['seed' => false],
 *         '0.5.0' => ['seed' => false],
 *         '0.6.0' => ['seed' => true],
 *     ];
 *
 * Seeds only. Schema work is never declared here — it is read from the
 * migrator's own per-file ledger, so the manifest and the database cannot
 * disagree about what has been applied. Writing the migration file *is* the
 * declaration.
 *
 * Releases are ordered by version rather than by string, so a manifest that
 * lists 0.10.0 before 0.9.0 — or in no particular order at all — still reports
 * correctly. Nothing keeps a manifest sorted, so nothing may depend on it.
 */
final class ReleaseManifest
{
    /** @param array<string, array<string, mixed>> $releases version-ordered */
    private function __construct(private readonly array $releases) {}

    /**
     * @throws UnreadableManifest when the file is absent or is not a set of
     *                            releases. Reading an unreadable manifest as
     *                            empty would report a package several releases
     *                            behind as owing nothing.
     */
    public static function read(string $path): self
    {
        if (! is_file($path)) {
            throw UnreadableManifest::missing($path);
        }

        $releases = require $path;

        if (! is_array($releases)) {
            throw UnreadableManifest::notReleases($path);
        }

        foreach ($releases as $version => $flags) {
            if (! is_array($flags)) {
                throw UnreadableManifest::notFlags($path, (string) $version);
            }
        }

        uksort($releases, version_compare(...));

        return new self($releases);
    }

    /** @return list<string> every release the package declared, oldest first */
    public function versions(): array
    {
        return array_map(strval(...), array_keys($this->releases));
    }

    /**
     * Every release above the version the database is at, oldest first.
     *
     * A package with no stored version has never recorded one, so it is
     * treated as being below the oldest release and every entry is pending.
     * Note this says nothing about schema: an already-deployed site whose
     * migrations are all applied still reports no schema work, because that
     * answer comes from the migrator and not from here.
     *
     * @return list<string>
     */
    public function releasesAbove(?string $storedVersion): array
    {
        if ($storedVersion === null) {
            return $this->versions();
        }

        return array_values(array_filter(
            $this->versions(),
            fn (string $version) => version_compare($version, $storedVersion, '>'),
        ));
    }

    /**
     * Which of these releases owe a seed. The caller unions them: if any
     * pending release says `seed: true` the seeder is owed, once, however many
     * of them asked. Reading only the newest entry is the bug this exists to
     * prevent.
     *
     * @param  list<string>  $versions
     * @return list<string>
     */
    public function seedingAmong(array $versions): array
    {
        return array_values(array_filter(
            $versions,
            fn (string $version) => ($this->releases[$version]['seed'] ?? false) === true,
        ));
    }
}
