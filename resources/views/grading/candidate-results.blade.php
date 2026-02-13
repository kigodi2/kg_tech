@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold mb-2">{{ $candidate->full_name }}</h1>
        <p class="text-gray-600">Candidate Number: {{ $candidate->candidate_id }}</p>
    </div>

    <!-- Summary Section -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <p class="text-gray-600 text-sm">Total Marks</p>
            <p class="text-2xl font-bold">{{ number_format($report['total_marks'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <p class="text-gray-600 text-sm">Total Points</p>
            <p class="text-2xl font-bold">{{ number_format($report['total_points'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
            <p class="text-gray-600 text-sm">GPA</p>
            <p class="text-2xl font-bold">{{ number_format($report['gpa'], 2) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500">
            <p class="text-gray-600 text-sm">Division</p>
            <p class="text-2xl font-bold">{{ $report['division']['division'] }}</p>
        </div>
    </div>

    <!-- Overall Results -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold mb-4">Overall Results</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-gray-600 mb-2">Overall Grade</p>
                <p class="text-2xl font-bold">{{ $report['overall_grade'] }}</p>
            </div>
            <div>
                <p class="text-gray-600 mb-2">Competence Level</p>
                <p class="text-lg font-semibold">{{ $report['competence_level'] }}</p>
            </div>
            <div>
                <p class="text-gray-600 mb-2">Division</p>
                <p class="text-xl font-bold">{{ $report['division']['division'] }} - {{ $report['division']['competence'] }}</p>
            </div>
        </div>
    </div>

    <!-- Subjects Performance -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
        <div class="px-6 py-4 bg-gray-100 border-b">
            <h2 class="text-xl font-bold">Subjects Performance</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Subject</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Marks</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Grade</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Points</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Competence Level</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($report['subject_grades'] as $subject)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $subject['subject_name'] }}</td>
                            <td class="px-6 py-4 text-center text-sm">{{ number_format($subject['marks_obtained'], 2) }}</td>
                            <td class="px-6 py-4 text-center text-sm font-bold">{{ $subject['grade'] }}</td>
                            <td class="px-6 py-4 text-center text-sm">{{ $subject['points'] }}</td>
                            <td class="px-6 py-4 text-center text-sm">
                                <span style="background-color: {{ $subject['color'] }}; padding: 6px 12px; border-radius: 4px; font-weight: 600; color: #fff;">
                                    {{ $subject['competence_level'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm">
                                @if ($subject['is_excluded'])
                                    <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-semibold">
                                        Excluded
                                    </span>
                                @else
                                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-semibold">
                                        Included
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Excluded Subjects Note -->
    @if (count($report['excluded_subject_grades']) > 0)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8">
            <p class="font-semibold text-yellow-800 mb-2">Note on Excluded Subjects</p>
            <p class="text-yellow-700 text-sm mb-3">
                The following subjects are not included in GPA and total points calculations (per NECTA standards):
            </p>
            <ul class="text-sm text-yellow-700">
                @foreach ($report['excluded_subject_grades'] as $subject)
                    <li>• {{ $subject['subject_name'] }}: {{ $subject['grade'] }} ({{ $subject['competence'] }})</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Summary Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-100 border-b">
            <h2 class="text-xl font-bold">Grade Summary</h2>
        </div>
        <div class="px-6 py-4">
            <table class="w-full text-sm">
                <tr class="border-b">
                    <td class="py-2 font-semibold text-gray-700">Total Candidates Subjects</td>
                    <td class="py-2 text-right">{{ count($report['subject_grades']) }}</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-semibold text-gray-700">Included Subjects (for GPA)</td>
                    <td class="py-2 text-right">{{ count($report['included_subject_grades']) }}</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-semibold text-gray-700">Excluded Subjects</td>
                    <td class="py-2 text-right">{{ count($report['excluded_subject_grades']) }}</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-semibold text-gray-700">Total Marks (All Subjects)</td>
                    <td class="py-2 text-right font-bold">{{ number_format($report['total_marks'], 2) }}</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-semibold text-gray-700">Total Points (Included Only)</td>
                    <td class="py-2 text-right font-bold">{{ number_format($report['total_points'], 2) }}</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-semibold text-gray-700">GPA (Average Points)</td>
                    <td class="py-2 text-right font-bold">{{ number_format($report['gpa'], 2) }}</td>
                </tr>
                <tr>
                    <td class="py-2 font-semibold text-gray-700">Division</td>
                    <td class="py-2 text-right font-bold">
                        Division {{ $report['division']['division'] }} - {{ $report['division']['competence'] }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-8 flex gap-4">
        <a href="{{ route('candidates.index') }}" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
            Back to Candidates
        </a>
        <button onclick="window.print()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Print Results
        </button>
    </div>
</div>

<style>
    @media print {
        body {
            background: white;
        }
        .container {
            max-width: 100%;
        }
    }
</style>
@endsection
