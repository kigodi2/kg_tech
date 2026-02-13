@extends('results.acsee.layout')

@section('page-title', 'Combination Results')
@section('page-subtitle', $combination->code ?? 'View combination results')
@section('breadcrumb-active', 'Combination Results')

@section('results-content')
<div class="space-y-6">
    
    <!-- Combination Information -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $combination->code ?? 'Unknown' }}</h2>
                <p class="text-gray-600 mt-2">
                    <strong>Name:</strong> {{ $combination->name ?? 'N/A' }}
                </p>
                <p class="text-gray-600 mt-1">
                    <strong>Subjects:</strong> {{ $combination->subjects ?? 'N/A' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Combination Statistics -->
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-semibold mb-2">Total Candidates</p>
            <p class="text-3xl font-bold text-gray-900">{{ $results->total() ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-semibold mb-2">Average GPA</p>
            <p class="text-3xl font-bold text-purple-600">-</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-semibold mb-2">Average Score</p>
            <p class="text-3xl font-bold text-blue-600">-</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-gray-600 text-sm font-semibold mb-2">Pass Rate</p>
            <p class="text-3xl font-bold text-green-600">-</p>
        </div>
    </div>

    <!-- Combination Results Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Index No.</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Candidate Name</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">School</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Total Score</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Grade</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">GPA</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($results ?? [] as $result)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-mono text-sm text-gray-700">{{ $result->candidate?->index_number ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $result->candidate?->fullname ?? 'Unknown' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $result->candidate?->school?->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-center text-sm font-semibold text-gray-900">{{ $result->total_score ?? '-' }}/500</td>
                        <td class="px-6 py-4 text-center text-sm font-bold">
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded">{{ $result->grade ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4 text-center text-sm font-semibold text-gray-900">{{ $result->gpa ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ route('results.acsee.results.candidate', $result->candidate_id) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-3xl mb-2 block"></i>
                            <p>No results for this combination</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($results && $results->hasPages())
        <div class="bg-white rounded-lg shadow p-4">
            {{ $results->links() }}
        </div>
    @endif

    <!-- Combination Analysis -->
    <div class="grid grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Subject Performance</h3>
            <div class="space-y-3">
                @php $subjects = ['Math', 'Physics', 'Chemistry']; @endphp
                @foreach($subjects as $subject)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-semibold text-gray-700">{{ $subject }}</span>
                            <span class="text-gray-600">- avg</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2"></div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Grade Distribution</h3>
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Grade A:</span>
                    <span class="font-bold">- candidates</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Grade B:</span>
                    <span class="font-bold">- candidates</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Grade C:</span>
                    <span class="font-bold">- candidates</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Grade D:</span>
                    <span class="font-bold">- candidates</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Grade F:</span>
                    <span class="font-bold">- candidates</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
