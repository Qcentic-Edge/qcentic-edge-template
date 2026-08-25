<?php

namespace Mamenein\FilamentInstaller\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class InstallerUserCreated
{
    use Dispatchable;

    public function __construct(public Model $user) {}
}
