<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Collection;

trait SearchableSelectCardsGridRecords
{
    public array $records = [];

    public string $search = '';

    public bool $allRecordSelected = false;

    public function getRecords(): Collection|array
    {
        return [];
    }

    public function selectRecord(int $recordId): void
    {
        if (! in_array($recordId, $this->records)) {
            $this->records[] = $recordId;
        } else {
            if (($key = array_search($recordId, $this->records)) !== false) {
                unset($this->records[$key]);
            }
        }

        $this->areAllValuesSelected();
    }

    public function isSelected(int $recordId): bool
    {
        return in_array($recordId, $this->records);
    }

    public function toggleAll(): void
    {
        if (! $this->allRecordSelected) {
            $this->records = $this->getRecords()->pluck('id')->toArray();
        } else {
            $this->records = [];
        }

        $this->areAllValuesSelected();
    }

    public function areAllValuesSelected(): bool
    {
        $areAllValuesSelected = count($this->getDiffBetweenPossibleAndSelectedRecords()) != 0;
        $this->allRecordSelected = ! (bool) $areAllValuesSelected;

        return $areAllValuesSelected;
    }

    public function getDiffBetweenPossibleAndSelectedRecords(): array
    {
        return array_diff($this->getAllPossibleIds(), $this->records);
    }

    public function getAllPossibleIds(): array
    {
        return $this->getRecords()->pluck('id')->toArray();
    }
}
