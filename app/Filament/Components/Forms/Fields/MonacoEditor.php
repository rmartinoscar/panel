<?php

namespace App\Filament\Components\Forms\Fields;

use AbdelhamidErrahmouni\FilamentMonacoEditor\MonacoEditor as Base;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;

class MonacoEditor extends Base
{
    use CanBeReadOnly;

    protected string $view = 'filament.plugins.monaco-editor';

    protected bool|Closure $hasMinimap = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hiddenLabel();
        $this->showPlaceholder(fn () => $this->getPlaceholderText());
    }

    public function minimap(bool|Closure $condition = true): static
    {
        $this->hasMinimap = $condition;

        return $this;
    }

    public function hasMinimap(): bool
    {
        return (bool) $this->evaluate($this->hasMinimap);
    }
}
