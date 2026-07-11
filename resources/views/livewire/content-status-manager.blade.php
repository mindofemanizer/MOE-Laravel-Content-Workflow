<div class="moe-status-manager space-y-3">
    <div class="flex items-center gap-3">
        <span class="text-sm font-medium text-gray-600">Status:</span>
        {!! $currentStatus !!}
    </div>

    @if (!empty($availableTransitions))
        <div class="space-y-2">
            <span class="text-sm font-medium text-gray-600">Transitions:</span>
            <div class="flex flex-wrap gap-2">
                @foreach ($availableTransitions as $status)
                    @php
                        $info = app(\MOE\ContentWorkflow\Services\StateMachineService::class)->getStatusInfo($status);
                        $label = $info['label'] ?? ucfirst($status);
                    @endphp
                    <button
                        wire:click="changeStatus('{{ $status }}')"
                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            @if ($showReason)
                <div class="flex items-center gap-2">
                    <input
                        wire:model="reason"
                        type="text"
                        placeholder="Reason (optional)..."
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                </div>
            @endif

            @error('transition')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif
</div>
