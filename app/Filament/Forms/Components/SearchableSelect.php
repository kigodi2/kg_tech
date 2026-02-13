<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class SearchableSelect extends Field
{
    protected string $view = 'filament.forms.components.searchable-select';

    protected array $options = [];

    protected string | null $placeholder = null;

    protected string | null $searchPlaceholder = null;

    public function options(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder ?? 'Select an option';
    }

    public function searchPlaceholder(string $searchPlaceholder): static
    {
        $this->searchPlaceholder = $searchPlaceholder;
        return $this;
    }

    public function getSearchPlaceholder(): string
    {
        return $this->searchPlaceholder ?? 'Start typing to search...';
    }
}
