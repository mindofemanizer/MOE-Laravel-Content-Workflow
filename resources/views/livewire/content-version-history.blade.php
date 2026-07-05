<div class="moe-versions space-y-3">
    <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-gray-600">Version History</span>
        <button
            wire:click="refreshVersions"
            class="text-sm text-indigo-600 hover:text-indigo-800"
        >
            Refresh
        </button>
    </div>

    @if ($showDiff && $diff)
        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">
                    Diff: v{{ $comparingFrom }} &rarr; v{{ $comparingTo }}
                </span>
                <button
                    wire:click="hideDiff"
                    class="text-sm text-gray-500 hover:text-gray-700"
                >
                    &times; Close
                </button>
            </div>
            <div class="space-y-1 text-sm">
                @forelse ($diff as $field => $change)
                    <div class="flex items-start gap-2">
                        <span class="font-medium text-gray-600 min-w-[100px]">{{ $field }}:</span>
                        <div class="flex-1">
                            <div class="text-red-600 line-through">{{ $change['old'] ?? '(empty)' }}</div>
                            <div class="text-green-600">{{ $change['new'] ?? '(empty)' }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500">No differences found.</p>
                @endforelse
            </div>
        </div>
    @endif

    <div class="space-y-2">
        @forelse ($versions as $version)
            <div class="flex items-center justify-between bg-gray-50 rounded-md px-4 py-2 text-sm {{ $version['is_current'] ? 'ring-1 ring-indigo-300' : '' }}">
                <div class="flex items-center gap-2">
                    <span class="font-medium">v{{ $version['version_number'] }}</span>
                    @if ($version['is_current'])
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">current</span>
                    @endif
                    @if ($version['version_label'])
                        <span class="text-gray-500">- {{ $version['version_label'] }}</span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-gray-400 text-xs">
                        {{ \Carbon\Carbon::parse($version['created_at'])->diffForHumans() }}
                    </span>
                    @if (!$version['is_current'])
                        <button
                            wire:click="restore({{ $version['version_number'] }})"
                            class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                            onclick="return confirm('Restore version #{{ $version['version_number'] }}?')"
                        >
                            Restore
                        </button>
                    @endif
                    @if (!$loop->last && isset($versions[$loop->index + 1]))
                        <button
                            wire:click="compare({{ $version['version_number'] }}, {{ $versions[$loop->index + 1]['version_number'] }})"
                            class="text-gray-500 hover:text-gray-700 text-xs"
                            title="Compare with previous"
                        >
                            Diff
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">No versions yet.</p>
        @endforelse
    </div>
</div>
