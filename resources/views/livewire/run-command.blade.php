<div class="flex flex-col sm:flex-row gap-2 items-center">
    <button
        wire:click="run"
        wire:loading.attr="disabled"
        class="px-3 py-1.5 rounded bg-blue-600 text-white text-xs hover:bg-blue-700 disabled:opacity-50"
    >
        <span wire:loading.remove>{{ $label }}</span>
        <span wire:loading>Running...</span>
    </button>

    @if ($filePlaceholder !== 'No input file required')
        <input
            type="file"
            wire:model="uploadedFile"
            class="text-xs"
            accept=".txt"
        >
    @endif
</div>