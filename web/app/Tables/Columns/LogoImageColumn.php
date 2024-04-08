<?php

namespace App\Tables\Columns;

use Closure;
use Filament\Tables\Columns\Column;

class LogoImageColumn extends Column
{
    protected string $view = 'tables.columns.logo-image-column';

    public bool|Closure $isStacked = false;

    protected int|Closure|null $limit = null;

    protected bool|Closure $hasLimitedRemainingText = false;

    public function stacked(bool|Closure $condition = true): static
    {
        $this->isStacked = $condition;

        return $this;
    }

    public function isStacked(): bool
    {
        return (bool) $this->evaluate($this->isStacked);
    }

    public function limit(int|Closure|null $condition = null): static
    {
        $this->limit = $condition;

        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->evaluate($this->limit);
    }

    public function limitedRemainingText(bool|Closure $condition = true): static
    {
        $this->hasLimitedRemainingText = $condition;

        return $this;
    }

    public function hasLimitedRemainingText(): bool
    {
        return (bool) $this->evaluate($this->hasLimitedRemainingText);
    }
}
