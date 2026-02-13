@extends('layout')

@section('content')
<div class="mt-8 max-w-2xl">
    <a href="/schools" class="text-blue-600 mb-4 block hover:text-blue-800">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-bold mb-6">{{ $school->name }}</h2>
        
        <div class="mb-4">
            <label class="block text-gray-600 text-sm">Code</label>
            <p class="text-lg">{{ $school->code }}</p>
        </div>
        
        <div class="mb-4">
            <label class="block text-gray-600 text-sm">Region</label>
            <p class="text-lg">{{ $school->region->name }}</p>
        </div>
        
        <div class="mb-4">
            <label class="block text-gray-600 text-sm">Type</label>
            <p class="text-lg">{{ $school->school_type }}</p>
        </div>
        
        <div class="mt-8">
            <h3 class="text-xl font-bold mb-4 text-center">Candidates ({{ $school->candidates->count() }})</h3>
            <div class="bg-gray-50 rounded p-4">
                @if ($school->candidates->count())
                    <ul class="space-y-2 text-center">
                        @foreach ($school->candidates as $candidate)
                            <li class="text-blue-600">
                                <a href="/candidates/{{ $candidate->id }}">{{ $candidate->full_name }}</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-600 text-center">No candidates in this school</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
