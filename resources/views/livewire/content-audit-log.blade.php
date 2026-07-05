<div class="moe-audit-log space-y-3">
    <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-gray-600">Audit Trail</span>
        <div class="flex items-center gap-2">
            <select
                wire:change="filterBy($event.target.value)"
                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
            >
                <option value="">All actions</option>
                @foreach ($availableFilters as $action)
                    <option value="{{ $action }}" @if ($filter === $action) selected @endif>
                        {{ ucfirst(str_replace('_', ' ', $action)) }}
                    </option>
                @endforeach
            </select>
            <button
                wire:click="refreshAudits"
                class="text-sm text-indigo-600 hover:text-indigo-800"
            >
                Refresh
            </button>
        </div>
    </div>

    <div class="space-y-2">
        @forelse ($audits as $audit)
            <div class="bg-gray-50 rounded-md px-4 py-2 text-sm space-y-1">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $audit['action'])) }}</span>
                        @if ($audit['field'])
                            <span class="text-gray-400">&middot; {{ $audit['field'] }}</span>
                        @endif
                    </div>
                    <span class="text-gray-400 text-xs">
                        {{ \Carbon\Carbon::parse($audit['created_at'])->diffForHumans() }}
                    </span>
                </div>

                @if ($audit['old_value'] || $audit['new_value'])
                    <div class="flex items-start gap-2 text-xs ml-2">
                        @if ($audit['old_value'])
                            <div class="text-red-600">
                                <span class="font-medium">From:</span> {{ Str::limit($audit['old_value'], 100) }}
                            </div>
                        @endif
                        @if ($audit['new_value'])
                            <div class="text-green-600">
                                <span class="font-medium">To:</span> {{ Str::limit($audit['new_value'], 100) }}
                            </div>
                        @endif
                    </div>
                @endif

                <div class="text-xs text-gray-400 flex items-center gap-3 ml-2">
                    @if ($audit['ip_address'])
                        <span>{{ $audit['ip_address'] }}</span>
                    @endif
                    @if ($audit['user_id'])
                        <span>User #{{ $audit['user_id'] }}</span>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">No audit records yet.</p>
        @endforelse
    </div>

    @if (count($audits) >= $perPage)
        <button
            wire:click="loadMore"
            class="w-full text-center text-sm text-indigo-600 hover:text-indigo-800 py-2"
        >
            Load more
        </button>
    @endif
</div>
