<?php

namespace QcenticEdge\PluginUpdates\Registry;

use Illuminate\Database\Seeder;
use QcenticEdge\PluginUpdates\Support\CodeVersion;

/**
 * Everything one package declares about its own updates, built in a single
 * call from that package's service provider:
 *
 *     PluginUpdates::register(
 *         UpdatablePackage::make('qcentic-edge/filament-seo')
 *             ->title('SEO')
 *             ->manifest(__DIR__.'/../database/updates.php')
 *             ->migrations(__DIR__.'/../database/migrations')
 *             ->seeder(SeoSeeder::class)
 *             ->tables(['seo_meta', 'seo_settings']),
 *     );
 *
 * Name and manifest are what every package has, and registering without a
 * manifest is refused on the spot rather than left to fail later. The title
 * defaults to the package name. Migration path, seeder and tables are optional
 * because a package may own no schema, no seed data, or no tables at all — and
 * the library refuses to guess at any of them.
 */
final class UpdatablePackage
{
    private string $title;

    private ?string $manifest = null;

    private ?string $migrations = null;

    /** @var class-string<Seeder>|null */
    private ?string $seeder = null;

    /** @var list<string> */
    private array $tables = [];

    private function __construct(private readonly string $name)
    {
        $this->title = $name;
    }

    /**
     * @param  string  $name  the Composer package name, e.g. `qcentic-edge/filament-seo`
     */
    public static function make(string $name): self
    {
        return new self($name);
    }

    /** The name the operator sees in the panel. */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /** Absolute path to the package's manifest file, which returns seeds per release. */
    public function manifest(string $path): self
    {
        $this->manifest = $path;

        return $this;
    }

    /** Absolute path to the package's own migration directory. */
    public function migrations(string $path): self
    {
        $this->migrations = $path;

        return $this;
    }

    /**
     * The one idempotent seeder the package owns, run whenever any pending
     * release owes a seed.
     *
     * @param  class-string<Seeder>  $seeder
     */
    public function seeder(string $seeder): self
    {
        $this->seeder = $seeder;

        return $this;
    }

    /**
     * The tables the package owns. Declaring them is what lets the operator
     * see row counts without the package writing any reporting code.
     *
     * @param  list<string>  $tables
     */
    public function tables(array $tables): self
    {
        $this->tables = array_values($tables);

        return $this;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function displayTitle(): string
    {
        return $this->title;
    }

    /** Whether the package declared a manifest at all. Registration insists it did. */
    public function hasManifest(): bool
    {
        return $this->manifest !== null;
    }

    public function manifestPath(): string
    {
        return $this->manifest ?? throw IncompleteDeclaration::manifest($this->name);
    }

    public function migrationPath(): ?string
    {
        return $this->migrations;
    }

    /** @return class-string<Seeder>|null */
    public function seederClass(): ?string
    {
        return $this->seeder;
    }

    /** @return list<string> */
    public function tableNames(): array
    {
        return $this->tables;
    }

    /**
     * The version of the code currently deployed, as Composer resolved it.
     * Null when the package is not installed under that name.
     */
    public function codeVersion(): ?string
    {
        return CodeVersion::for($this->name);
    }
}
