<?php

namespace App\Livewire\App\Core;

use Livewire\Component;

class CurrentVersion extends Component
{
    protected $listeners = ['coreCurrentVersionComponent' => '$refresh'];

    public function render()
    {
        return view('livewire.app.core.current-version')->with([
            'instance' => filament()->getTenant(),
        ]);
    }
}
