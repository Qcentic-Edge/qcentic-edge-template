<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Concerns\CanBeLengthConstrained;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasExtraInputAttributes;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Contracts\CanBeLengthConstrained as CanBeLengthConstrainedContract;
use Filament\Forms\Components\Field;

class BrandedTextInput extends Field implements CanBeLengthConstrainedContract
{
    use CanBeLengthConstrained;
    use CanBeReadOnly;
    use HasExtraInputAttributes;
    use HasPlaceholder;

    protected string $view = 'filament.forms.components.branded-text-input';

    protected string|Closure $brandMark = 'F';

    public function brandMark(string|Closure $brandMark): static
    {
        $this->brandMark = $brandMark;

        return $this;
    }

    public function getBrandMark(): string
    {
        return $this->evaluate($this->brandMark);
    }
}
