<?php

namespace QcenticEdge\PluginUpdates\Registry;

use InvalidArgumentException;

/**
 * A package registered without declaring something the library cannot guess.
 *
 * The library refuses to guess: an undeclared manifest is a package whose
 * releases nobody can read, and guessing a conventional path would quietly
 * skip the work a release owes. Failing at registration means the mistake
 * surfaces the moment the provider boots, in the developer's own test run,
 * rather than as a typed-property error somewhere downstream.
 */
final class IncompleteDeclaration extends InvalidArgumentException
{
    public static function manifest(string $package): self
    {
        return new self(
            "The package [{$package}] registered with no manifest. Declare one with "
            .'UpdatablePackage::make(...)->manifest(__DIR__."/../database/updates.php").'
        );
    }
}
