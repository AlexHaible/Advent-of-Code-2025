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
        <label class="relative cursor-pointer px-3 py-1.5 rounded bg-gray-700 text-white text-xs hover:bg-gray-600">
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
                class="absolute inset-0 opacity-0 cursor-pointer"
                accept=".txt"
            >
        </label>
        @if($uploadedFile)
            <button
                type="button"
                wire:click="$set('uploadedFile', null)"
                class="px-2 py-1 rounded bg-red-600 text-white text-xs hover:bg-red-700"
            >
                Clear
            </button>
        @endif
    @endif
</div>