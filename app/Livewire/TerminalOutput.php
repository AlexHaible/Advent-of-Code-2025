<?php

namespace App\Livewire;

use Livewire\Component;

class TerminalOutput extends Component
{
    /** @var array<int,array{command:string,output:string}> */
    public array $entries = [];

    protected $listeners = [
        'commandExecuted' => 'appendEntry',
    ];

    public function appendEntry(array $payload): void
    {
        $this->entries[] = [
            'command' => $payload['command'] ?? '',
            'output'  => $payload['output'] ?? '',
        ];

        $this->dispatch('terminalUpdated');
    }

    public function clear(): void
    {
        $this->entries = [];
    }

    public function render()
    {
        return view('livewire.terminal-output');
    }
}
