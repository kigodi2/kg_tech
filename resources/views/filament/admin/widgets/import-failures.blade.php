<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-danger-50 border border-danger-200 rounded-lg p-4">
                    <div class="text-sm font-medium text-danger-700">7-Day Bulk Import Failures</div>
                    <div class="text-3xl font-bold text-danger-900 mt-1">{{ $this->get7DayFailures() }}</div>
                    <div class="text-xs text-danger-600 mt-2">
                        Today: <span class="font-semibold">{{ $this->getTodayFailures() }}</span> |
                        Yesterday: <span class="font-semibold">{{ $this->getYesterdayFailures() }}</span>
                    </div>
                </div>

                <div class="bg-warning-50 border border-warning-200 rounded-lg p-4">
                    <div class="text-sm font-medium text-warning-700">Batches with Errors (7 Days)</div>
                    <div class="text-3xl font-bold text-warning-900 mt-1">{{ $this->getBatchesWithErrors() }}</div>
                    <div class="text-xs text-warning-600 mt-2">Mark import batches needing review</div>
                </div>
            </div>

            <!-- Top Failing Schools -->
            <div class="bg-white border rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-3">Top 5 Failing Schools (Last 7 Days)</h3>
                @if($this->getTopFailingSchools()->isEmpty())
                    <div class="text-sm text-gray-500">No failures recorded</div>
                @else
                    <ul class="space-y-2">
                        @foreach($this->getTopFailingSchools() as $school)
                            <li class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">{{ $school['school'] }}</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger-100 text-danger-800">
                                    {{ $school['count'] }} failures
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- Top Failing Districts -->
            <div class="bg-white border rounded-lg p-4">
                <h3 class="font-semibold text-gray-900 mb-3">Top 5 Failing Districts (Last 7 Days)</h3>
                @if($this->getTopFailingDistricts()->isEmpty())
                    <div class="text-sm text-gray-500">No failures recorded</div>
                @else
                    <ul class="space-y-2">
                        @foreach($this->getTopFailingDistricts() as $district)
                            <li class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">{{ $district['district'] }}</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger-100 text-danger-800">
                                    {{ $district['count'] }} failures
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
