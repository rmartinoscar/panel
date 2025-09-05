<?php

namespace App\Filament\Components\Forms\Fields;

use Closure;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;

class AffixedInput extends TextInput
{
    protected string $view = 'filament.components.affixed-input';

    protected Component|Closure|null $prefixComponent = null;

    protected Component|Closure|null $suffixComponent = null;

    public function prefixComponent(Component|Closure|null $component): static
    {
        $this->prefixComponent = $component;

        return $this;
    }

    public function getPrefixComponent(): ?Component
    {
        return $this->evaluate($this->prefixComponent);
    }

    public function suffixComponent(Component|Closure|null $component): static
    {
        $this->suffixComponent = $component;

        return $this;
    }

    public function getSuffixComponent(): ?Component
    {
        return $this->evaluate($this->suffixComponent);
    }
}
