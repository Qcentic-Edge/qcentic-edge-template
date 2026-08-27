<?php

namespace QcenticEdge\PluginUpdates\Report;

/**
 * One table a package declared, and how many rows are in it — what the
 * operator judges "run it now or during quiet hours" by.
 *
 * A table a package has declared but whose create-table migration has not run
 * yet is absent, not empty, and the two are worth telling apart: absent means
 * the pending schema work will create it.
 */
final class TableCount
{
    /** @param  int|null  $rows  null when the table does not exist yet */
    public function __construct(
        public readonly string $name,
        public readonly ?int $rows,
    ) {}

    public function exists(): bool
    {
        return $this->rows !== null;
    }
}
