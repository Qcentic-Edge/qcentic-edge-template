<?php

namespace QcenticEdge\PluginUpdates\Registry;

/**
 * Every package that has declared itself. Bound as a singleton, so a package
 * registers once from its service provider and everything that reports on it
 * reads the same list.
 *
 * Keyed by package name: a package that registers twice — two providers, a
 * host overriding a declaration — replaces its entry rather than appearing
 * twice in the operator's list.
 */
final class PackageRegistry
{
    /** @var array<string, UpdatablePackage> */
    private array $packages = [];

    public function add(UpdatablePackage ...$packages): self
    {
        foreach ($packages as $package) {
            $this->packages[$package->name()] = $package;
        }

        return $this;
    }

    /** @return array<string, UpdatablePackage> keyed by package name, in registration order */
    public function all(): array
    {
        return $this->packages;
    }

    public function get(string $name): ?UpdatablePackage
    {
        return $this->packages[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return isset($this->packages[$name]);
    }

    public function isEmpty(): bool
    {
        return $this->packages === [];
    }
}
