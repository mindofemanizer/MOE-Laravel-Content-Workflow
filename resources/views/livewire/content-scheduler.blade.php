<div class="moe-scheduler space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Action</label>
            <select
                wire:model="action"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
                <option value="publish">Publish</option>
                <option value="unpublish">Unpublish</option>
                <option value="archive">Archive</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input
                type="date"
                wire:model="scheduledAt"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
            <input
                type="time"
                wire:model="scheduledTime"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
        </div>
    </div>

    <div>
        <button
            wire:click="schedule"
            class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
            Schedule
        </button>
    </div>

    @error('schedule')
        <p class="text-sm text-red-600">{{ $message }}</p>
    @enderror

    @if (!empty($pendingSchedules))
        <div class="space-y-2">
            <span class="text-sm font-medium text-gray-600">Pending Schedules:</span>
            <div class="space-y-2">
                @foreach ($pendingSchedules as $schedule)
                    <div class="flex items-center justify-between bg-gray-50 rounded-md px-4 py-2 text-sm">
                        <div>
                            <span class="font-medium">{{ ucfirst($schedule['action']) }}</span>
                            <span class="text-gray-500">&middot;</span>
                            <span>{{ \Carbon\Carbon::parse($schedule['scheduled_at'])->format('M j, Y g:i A') }}</span>
                        </div>
                        <button
                            wire:click="cancelSchedule({{ $schedule['id'] }})"
                            class="text-red-600 hover:text-red-800 text-sm font-medium"
                        >
                            Cancel
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
