@extends('results.acsee.layout')

@section('page-title', $resultsModuleLabel . ' Results Dashboard')
@section('page-subtitle', $resultsModuleLabel === 'PSLE' ? 'Overview of PSLE grading, processing, and release status' : 'Overview of results processing and status')
@section('breadcrumb-active', 'Dashboard')

@section('results-content')
<div class="space-y-6">
    
    <!-- Key Metrics Row 1 -->
    <div class="grid grid-cols-4 gap-6">
        
        <!-- Total Registered Candidates -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Total Candidates</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($metrics['total_candidates'] ?? 0) }}</p>
                    <p class="text-xs text-gray-500 mt-2">Registered for {{ $resultsModuleLabel }} {{ $exam_year }}</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Schools Submitted -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">{{ $resultsModuleLabel === 'PSLE' ? 'Schools in Scope' : 'Schools Submitted' }}</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($metrics['schools_submitted'] ?? 0) }}</p>
                    <p class="text-xs text-gray-500 mt-2">{{ $resultsModuleLabel === 'PSLE' ? 'Schools with ' . $resultsModuleLabel . ' registrations in scope' : 'Out of ' . number_format($metrics['total_schools'] ?? 0) . ' schools' }}</p>
                </div>
                <div class="bg-green-100 p-3 rounded-lg">
                    <i class="fas fa-school text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Processing Status -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Processing Status</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $metrics['processing_percentage'] ?? 0 }}%</p>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-orange-500 h-2 rounded-full" style="width: {{ $metrics['processing_percentage'] ?? 0 }}%"></div>
                    </div>
                </div>
                <div class="bg-orange-100 p-3 rounded-lg">
                    <i class="fas fa-sync-alt text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Results Status -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Results Status</p>
                    <div class="mt-2 space-y-1">
                        <p class="text-sm"><span class="font-bold text-gray-900">{{ $metrics['draft_count'] ?? 0 }}</span> <span class="text-gray-600">Draft</span></p>
                        <p class="text-sm"><span class="font-bold text-gray-900">{{ $metrics['final_count'] ?? 0 }}</span> <span class="text-gray-600">Final</span></p>
                        <p class="text-sm"><span class="font-bold text-gray-900">{{ $metrics['published_count'] ?? 0 }}</span> <span class="text-gray-600">Published</span></p>
                    </div>
                </div>
                <div class="bg-purple-100 p-3 rounded-lg">
                    <i class="fas fa-flag-checkered text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Key Metrics Row 2 -->
    <div class="grid grid-cols-3 gap-6">
        
        <!-- Active Grading Profile -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Active Grading Profile</h3>
                <i class="fas fa-info-circle text-gray-400"></i>
            </div>
            <div class="space-y-2">
                <p class="text-sm"><span class="text-gray-600">Profile:</span> <span class="font-bold text-gray-900">{{ $grading_profile?->name ?? 'Not Set' }}</span></p>
                <p class="text-sm"><span class="text-gray-600">Version:</span> <span class="font-bold text-gray-900">{{ $grading_profile?->version ?? '-' }}</span></p>
                <p class="text-sm"><span class="text-gray-600">Status:</span> 
                    @if($grading_profile?->is_locked)
                        <span class="inline-block px-2 py-1 bg-red-100 text-red-700 text-xs font-bold rounded">LOCKED</span>
                    @else
                        <span class="inline-block px-2 py-1 bg-green-100 text-green-700 text-xs font-bold rounded">ACTIVE</span>
                    @endif
                </p>
                <a href="{{ route($resultsRoutePrefix . '.grading.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium mt-2 inline-block">
                    Manage Grading <i class="fas fa-arrow-right text-xs ml-1"></i>
                </a>
            </div>
        </div>
        
        <!-- Last Processing Date -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Last Processing</h3>
                <i class="fas fa-calendar text-gray-400"></i>
            </div>
            <div class="space-y-2">
                <p class="text-sm"><span class="text-gray-600">Date:</span> <span class="font-bold text-gray-900">{{ $last_processing?->processed_at?->format('M d, Y') ?? 'Never' }}</span></p>
                <p class="text-sm"><span class="text-gray-600">Time:</span> <span class="font-bold text-gray-900">{{ $last_processing?->processed_at?->format('H:i A') ?? '-' }}</span></p>
                <p class="text-sm"><span class="text-gray-600">Type:</span> <span class="font-bold text-gray-900">{{ ucfirst($last_processing?->type ?? '-') }}</span></p>
                <p class="text-sm"><span class="text-gray-600">Processed By:</span> <span class="font-bold text-gray-900">{{ $last_processing?->user?->name ?? '-' }}</span></p>
            </div>
        </div>
        
        <!-- Result Linking Status -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start justify-between mb-4">
                <h3 class="font-semibold text-gray-900">{{ $resultsModuleLabel === 'PSLE' ? 'Data Readiness' : 'Result Linking' }}</h3>
                <i class="fas fa-link text-gray-400"></i>
            </div>
            <div class="space-y-2">
                <p class="text-sm"><span class="text-gray-600">Status:</span> 
                    @if($linking_status['is_complete'])
                        <span class="inline-block px-2 py-1 bg-green-100 text-green-700 text-xs font-bold rounded">COMPLETE</span>
                    @else
                        <span class="inline-block px-2 py-1 bg-yellow-100 text-yellow-700 text-xs font-bold rounded">INCOMPLETE</span>
                    @endif
                </p>
                <p class="text-sm"><span class="text-gray-600">{{ $resultsModuleLabel === 'PSLE' ? 'Missing Setup:' : 'Missing Links:' }}</span> <span class="font-bold text-red-600">{{ $linking_status['missing_count'] ?? 0 }}</span></p>
                <p class="text-sm"><span class="text-gray-600">{{ $resultsModuleLabel === 'PSLE' ? 'Invalid Configurations:' : 'Invalid Combos:' }}</span> <span class="font-bold text-red-600">{{ $linking_status['invalid_configurations'] ?? 0 }}</span></p>
                <a href="{{ route($resultsRoutePrefix . '.linking.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium mt-2 inline-block">
                    {{ $resultsModuleLabel === 'PSLE' ? 'Review Readiness' : 'Review Linking' }} <i class="fas fa-arrow-right text-xs ml-1"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="grid grid-cols-3 gap-6">
        
        <!-- Data Integrity Check -->
        <a href="{{ route($resultsRoutePrefix . '.linking.index') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-semibold text-gray-900 mb-1">Data Integrity Check</h4>
                    <p class="text-sm text-gray-600">{{ $resultsModuleLabel === 'PSLE' ? 'Validate PSLE marks and grading readiness before processing' : 'Validate marks completeness before processing' }}</p>
                </div>
                <i class="fas fa-check-circle text-yellow-500 text-2xl"></i>
            </div>
        </a>
        
        <!-- Start Processing -->
        <a href="{{ route($resultsRoutePrefix . '.processing.index') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-semibold text-gray-900 mb-1">Result Processing</h4>
                    <p class="text-sm text-gray-600">{{ $resultsModuleLabel === 'PSLE' ? 'Run draft or final PSLE processing batch' : 'Run draft or final processing batch' }}</p>
                </div>
                <i class="fas fa-cog text-blue-500 text-2xl"></i>
            </div>
        </a>
        
        <!-- View Results -->
        <a href="{{ route($resultsRoutePrefix . '.results.index') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow cursor-pointer border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-semibold text-gray-900 mb-1">Manage Results</h4>
                    <p class="text-sm text-gray-600">{{ $resultsModuleLabel === 'PSLE' ? 'View and release final PSLE results' : 'View and publish final results' }}</p>
                </div>
                <i class="fas fa-list-check text-green-500 text-2xl"></i>
            </div>
        </a>
    </div>
    
    <!-- Recent Activity -->
    <div class="grid grid-cols-2 gap-6">
        
        <!-- Processing History -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-history text-blue-600"></i>
                    Processing History
                </h3>
            </div>
            <div class="divide-y">
                @forelse($recent_processing as $process)
                    <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between mb-1">
                            <p class="font-medium text-gray-900">{{ ucfirst($process->type) }} Run</p>
                            <span class="text-xs px-2 py-1 bg-gray-100 rounded {{ $process->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ ucfirst($process->status) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600">{{ $process->processed_at->diffForHumans() }} by {{ $process->user->name }}</p>
                    </div>
                @empty
                    <div class="px-6 py-4 text-center text-gray-500">
                        <i class="fas fa-inbox text-2xl mb-2"></i>
                        <p class="text-sm">No processing history yet</p>
                    </div>
                @endforelse
            </div>
        </div>
        
        <!-- Recent Audit Logs -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-shield-alt text-purple-600"></i>
                    Recent Audit Logs
                </h3>
            </div>
            <div class="divide-y">
                @forelse($recent_audit_logs as $log)
                    <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center justify-between mb-1">
                            <p class="font-medium text-gray-900 text-sm">{{ $log->action }}</p>
                            <span class="text-xs text-gray-600">{{ $log->created_at->format('M d H:i') }}</span>
                        </div>
                        <p class="text-xs text-gray-600">by {{ $log->user->name }}</p>
                    </div>
                @empty
                    <div class="px-6 py-4 text-center text-gray-500">
                        <i class="fas fa-inbox text-2xl mb-2"></i>
                        <p class="text-sm">No audit logs yet</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
