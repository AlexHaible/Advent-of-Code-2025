<div class="flex flex-col sm:flex-row gap-2 items-center">
    <button
        wire:click="run"
        wire:loading.attr="disabled"
        @disabled(! $this->isUnlocked())
        class="px-3 py-1.5 rounded text-xs
        @if(!$this->isUnlocked()) bg-gray-600 text-gray-300 cursor-not-allowed
        @else bg-blue-600 text-white hover:bg-blue-700
        @endif"
    >
        <span wire:loading.remove>{{ $label }}</span>
        <span wire:loading>Running...</span>
    </button>

    @if ($filePlaceholder !== 'No input file required')
        <label class="relative px-3 py-1.5 rounded text-xs
                @if(!$this->isUnlocked()) bg-gray-600 text-gray-300 cursor-not-allowed
                @else bg-gray-700 text-white hover:bg-gray-600 cursor-pointer
                @endif">
            <span>
                @if($uploadedFile)
                    {{ $uploadedFile->getClientOriginalName() }}
                @else
                    No file picked
                @endif
            </span>
            <input
                type="file"
                wire:model="uploadedFile"
                @disabled(! $this->isUnlocked())
                class="absolute inset-0 opacity-0
                @if(!$this->isUnlocked()) cursor-not-allowed
                @else cursor-pointer
                @endif"
                accept=".txt"
            >
        </label>
        @if($uploadedFile)
            <button
                type="button"
                wire:click="$set('uploadedFile', null)"
                class="px-2 py-1 rounded text-xs bg-red-600 text-white hover:bg-red-700"
            >
                Clear
            </button>
        @endif
    @endif
</div>