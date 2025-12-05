<div class="h-full w-full flex flex-col bg-black text-green-200 font-mono text-xs overflow-hidden">
    <div class="flex items-center justify-between px-3 py-2 border-b border-gray-700 flex-shrink-0">
        <span class="text-[10px] uppercase tracking-wide text-gray-400">Terminal</span>
        <button
            wire:click="clear"
            class="px-2 py-1 text-[10px] rounded bg-gray-700 text-gray-100 hover:bg-gray-600"
        >
            Clear
        </button>
    </div>

    <div class="h-full overflow-y-auto px-3 py-2" data-terminal-scroller>
        @if (empty($entries))
            <div class="text-gray-500 text-[11px]">
                No commands run yet.
            </div>
        @else
            @foreach ($entries as $entry)
                <div class="border-t border-gray-800 first:border-t-0 py-1">
                    <pre class="whitespace-pre leading-tight">
<span class="text-gray-400">$ {{ $entry['command'] }}</span>@if($entry['output'] !== ''){!! "\n" !!}{{ rtrim($entry['output']) }}@endif
                    </pre>
                </div>
            @endforeach
        @endif
    </div>
</div>