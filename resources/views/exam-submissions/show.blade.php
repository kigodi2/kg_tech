@extends('layouts.auth-rms')

@section('title', 'NECTA Format Validation Report')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">NECTA Format Validation Report</h1>
                    <p class="text-gray-600 mt-1">Reference No. {{ $submission->id }}</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('exam-submissions.final-report', ['exam_type_id' => $submission->exam_type_id, 'exam_year_id' => $submission->exam_year_id, 'user_id' => $submission->user_id]) }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Final Report
                    </a>
                    <a href="{{ route('exam-submissions.download', $submission) }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Download PDF
                    </a>
                    <a href="{{ route('exam-submissions.index') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        ← Back to Submissions
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-6">
            @php
                $results = $submission->validation_results ?? [];
                $rulebook = $results['rulebook'] ?? [];
                $templateComparison = $results['template_comparison'] ?? [];
                $recommendations = $results['recommendations'] ?? [];
            @endphp

            <!-- Exam Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Examination Particulars</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Exam Type</dt>
                            <dd class="text-sm text-gray-900">{{ $submission->examType->name }} ({{ $submission->examType->code }})</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Exam Year</dt>
                            <dd class="text-sm text-gray-900">{{ $submission->examYear->year }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Subject</dt>
                            <dd class="text-sm text-gray-900">{{ $submission->subject->name }} ({{ $submission->subject->code }})</dd>
                        </div>
                        @if($submission->school)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">School</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->school->name }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Submission Record</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="text-sm">
                                @php
                                    $statusClasses = [
                                        'pending' => 'text-yellow-800 bg-yellow-100',
                                        'validated' => 'text-green-800 bg-green-100',
                                        'rejected' => 'text-red-800 bg-red-100',
                                        'approved' => 'text-blue-800 bg-blue-100',
                                    ];
                                    $statusClass = $statusClasses[$submission->status] ?? 'text-gray-800 bg-gray-100';
                                @endphp
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusClass }}">
                                    {{ ucfirst($submission->status) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Submitted At</dt>
                            <dd class="text-sm text-gray-900">{{ $submission->submitted_at->format('M j, Y \a\t g:i A') }}</dd>
                        </div>
                        @if($submission->validated_at)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Validated At</dt>
                                <dd class="text-sm text-gray-900">{{ $submission->validated_at->format('M j, Y \a\t g:i A') }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Original Filename</dt>
                            <dd class="text-sm text-gray-900">{{ $submission->original_filename }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Validation Results -->
            @if($submission->validation_results)
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Validation Findings</h3>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <!-- Status -->
                        <div class="mb-4">
                            @if($results['is_valid'])
                                <div class="flex items-center">
                                    <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-green-800 font-medium">The submitted paper meets the current validation threshold.</span>
                                </div>
                            @else
                                <div class="flex items-center">
                                    <svg class="h-5 w-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-red-800 font-medium">The submitted paper does not meet the current validation threshold.</span>
                                </div>
                            @endif
                        </div>

                        <!-- Errors -->
                        @if(isset($results['errors']) && count($results['errors']) > 0)
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-red-800 mb-2">Non-Compliance Findings:</h4>
                                <ul class="list-disc list-inside text-red-700 text-sm space-y-1">
                                    @foreach($results['errors'] as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- Warnings -->
                        @if(isset($results['warnings']) && count($results['warnings']) > 0)
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-yellow-800 mb-2">Observations Requiring Attention:</h4>
                                <ul class="list-disc list-inside text-yellow-700 text-sm space-y-1">
                                    @foreach($results['warnings'] as $warning)
                                        <li>{{ $warning }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if(!empty($templateComparison))
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-800 mb-2">Official Template Comparison:</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-white border border-gray-200 rounded-lg p-3">
                                        <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Template Reference Status</p>
                                        <p class="mt-1 text-sm text-gray-900">
                                            {{ !empty($templateComparison['template_available']) ? 'Official reference template available' : 'Official reference template not available' }}
                                        </p>
                                    </div>
                                    <div class="bg-white border border-gray-200 rounded-lg p-3">
                                        <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Indicative Compliance Score</p>
                                        <p class="mt-1 text-sm text-gray-900">
                                            {{ $templateComparison['compliance_score'] ?? 'N/A' }}@if(isset($templateComparison['compliance_score']))%@endif
                                        </p>
                                    </div>
                                </div>

                                @if(!empty($templateComparison['matched_elements']))
                                    <div class="mt-3">
                                        <h5 class="text-sm font-medium text-green-800 mb-1">Confirmed Areas of Alignment:</h5>
                                        <ul class="list-disc list-inside text-sm text-green-700 space-y-1">
                                            @foreach($templateComparison['matched_elements'] as $item)
                                                <li>{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if(!empty($templateComparison['missing_elements']))
                                    <div class="mt-3">
                                        <h5 class="text-sm font-medium text-red-800 mb-1">Identified Areas of Non-Alignment:</h5>
                                        <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                                            @foreach($templateComparison['missing_elements'] as $item)
                                                <li>{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if(!empty($rulebook))
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-800 mb-2">NECTA Official Format Reference:</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-white border border-gray-200 rounded-lg p-3">
                                        <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Reference Guide</p>
                                        <p class="mt-1 text-sm text-gray-900">{{ $rulebook['guide_title'] ?? 'N/A' }}</p>
                                        @if(!empty($rulebook['edition']))
                                            <p class="text-xs text-gray-500 mt-1">{{ $rulebook['edition'] }}</p>
                                        @endif
                                    </div>
                                    <div class="bg-white border border-gray-200 rounded-lg p-3">
                                        <p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Validation Profile</p>
                                        <p class="mt-1 text-sm text-gray-900">
                                            {{ ucfirst(str_replace('_', ' ', $rulebook['profile_status'] ?? 'unknown')) }}
                                        </p>
                                        @if(!empty($rulebook['subject_name']))
                                            <p class="text-xs text-gray-500 mt-1">{{ $rulebook['subject_name'] }}</p>
                                        @endif
                                    </div>
                                </div>

                                @if(!empty($rulebook['common_sections']))
                                    <div class="mt-3 bg-white border border-gray-200 rounded-lg p-3">
                                        <h5 class="text-sm font-medium text-gray-800 mb-2">Expected Sections in the Official Format</h5>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($rulebook['common_sections'] as $section)
                                                <span class="inline-flex px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-medium">
                                                    {{ ucwords(str_replace('_', ' ', $section)) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if(!empty($rulebook['papers']))
                                    <div class="mt-3 space-y-3">
                                        <h5 class="text-sm font-medium text-gray-800">Expected Paper Structure Under the NECTA Guide</h5>
                                        @foreach($rulebook['papers'] as $paper)
                                            <div class="bg-white border border-gray-200 rounded-lg p-4">
                                                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                                                    <div>
                                                        <p class="text-sm font-semibold text-gray-900">
                                                            {{ $paper['code'] ?? 'Paper' }}
                                                            @if(!empty($paper['type']))
                                                                <span class="ml-2 text-xs font-medium text-gray-500 uppercase tracking-wide">
                                                                    {{ str_replace('_', ' ', $paper['type']) }}
                                                                </span>
                                                            @endif
                                                        </p>
                                                        <div class="mt-1 text-xs text-gray-500 space-y-1">
                                                            @if(!empty($paper['duration']))
                                                                <p>Duration: {{ $paper['duration'] }}</p>
                                                            @endif
                                                            @if(!empty($paper['duration_special_needs']))
                                                                <p>Special needs duration: {{ $paper['duration_special_needs'] }}</p>
                                                            @endif
                                                            @if(!empty($paper['total_marks']))
                                                                <p>Total marks: {{ $paper['total_marks'] }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                @if(!empty($paper['sections']))
                                                    <div class="mt-3 overflow-x-auto">
                                                        <table class="min-w-full text-sm">
                                                            <thead>
                                                                <tr class="text-left text-gray-500 border-b border-gray-200">
                                                                    <th class="py-2 pr-4 font-medium">Section</th>
                                                                    <th class="py-2 pr-4 font-medium">Question Types</th>
                                                                    <th class="py-2 font-medium">Marks</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($paper['sections'] as $section)
                                                                    <tr class="border-b border-gray-100">
                                                                        <td class="py-2 pr-4 text-gray-900">{{ $section['name'] ?? '-' }}</td>
                                                                        <td class="py-2 pr-4 text-gray-600">
                                                                            {{ isset($section['question_types']) ? ucwords(str_replace('_', ' ', implode(', ', $section['question_types']))) : '-' }}
                                                                        </td>
                                                                        <td class="py-2 text-gray-900">{{ $section['marks'] ?? '-' }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @endif

                                                @if(!empty($paper['components']))
                                                    <div class="mt-3">
                                                        <h6 class="text-sm font-medium text-gray-800 mb-1">Assessment Components</h6>
                                                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                                                            @foreach($paper['components'] as $component)
                                                                <li>
                                                                    {{ ucwords(str_replace('_', ' ', $component['name'] ?? 'Component')) }}
                                                                    @if(isset($component['marks']))
                                                                        - {{ $component['marks'] }} marks
                                                                    @endif
                                                                    @if(!empty($component['duration']))
                                                                        - {{ $component['duration'] }}
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif

                                                @if(!empty($paper['rules']))
                                                    <div class="mt-3">
                                                        <h6 class="text-sm font-medium text-gray-800 mb-1">Official Expectations</h6>
                                                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                                                            @foreach($paper['rules'] as $rule)
                                                                <li>{{ $rule }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if(!empty($recommendations))
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-800 mb-2">Formal Assessment Remarks:</h4>
                                <div class="bg-white border border-gray-200 rounded-lg p-4">
                                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                                        @foreach($recommendations as $recommendation)
                                            <li>{{ $recommendation }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        <!-- Metadata -->
                        @if(isset($results['metadata']))
                            <div>
                                <h4 class="text-sm font-medium text-gray-800 mb-2">Document Information:</h4>
                                @php
                                    $documentInspector = $results['document_inspector'] ?? [];
                                    $documentFields = $documentInspector['fields'] ?? [];
                                    $environmentClues = $documentInspector['environment_clues'] ?? [];
                                @endphp

                                @if(!empty($documentFields))
                                    <div class="mb-3 bg-white border border-gray-200 rounded-lg p-4">
                                        <h5 class="text-sm font-medium text-gray-800 mb-2">Document Metadata Inspector</h5>
                                        <dl class="text-sm space-y-1">
                                            @foreach($documentFields as $key => $value)
                                                <div>
                                                    <dt class="inline font-medium">{{ ucfirst(str_replace('_', ' ', $key)) }}:</dt>
                                                    <dd class="inline ml-2 text-gray-600">{{ $value }}</dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    </div>
                                @endif

                                @if(!empty($environmentClues))
                                    <div class="mb-3 bg-white border border-gray-200 rounded-lg p-4">
                                        <h5 class="text-sm font-medium text-gray-800 mb-2">Possible Device / Environment Clues</h5>
                                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                                            @foreach($environmentClues as $clue)
                                                <li>{{ $clue }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <dl class="text-sm space-y-1">
                                    @foreach($results['metadata'] as $key => $value)
                                        <div>
                                            <dt class="inline font-medium">{{ ucfirst(str_replace('_', ' ', $key))}:</dt>
                                            <dd class="inline ml-2 text-gray-600">{{ $value }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Rejection Reason -->
            @if($submission->rejection_reason)
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Rejection Reason</h3>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-red-800">{{ $submission->rejection_reason }}</p>
                    </div>
                </div>
            @endif

            @if(auth()->user()->isAdmin() && $submission->status === 'pending')
                <div id="admin-review" class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-3">Admin Review Actions</h3>
                    <div class="flex items-start gap-3">
                        <form method="POST" action="{{ route('exam-submissions.approve', $submission) }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Approve Submission
                            </button>
                        </form>

                        <form method="POST" action="{{ route('exam-submissions.reject', $submission) }}" class="w-full">
                            @csrf
                            <div class="space-y-2 w-full">
                                <label for="rejection_reason" class="block text-sm font-medium text-gray-700">Rejection Reason</label>
                                <textarea id="rejection_reason" name="rejection_reason" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                    Reject Submission
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
