@extends('layout')

@section('content')
<div class="mt-8 max-w-2xl">
    <a href="/candidates" class="text-blue-600 mb-4 block hover:text-blue-800">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-bold mb-6">{{ $candidate->full_name }}</h2>
        
        <div class="mb-4">
            <label class="block text-gray-600 text-sm">Candidate ID</label>
            <p class="text-lg">{{ $candidate->candidate_id }}</p>
        </div>
        
        <div class="mb-4">
            <label class="block text-gray-600 text-sm">School</label>
            <p class="text-lg">
                <a href="/schools/{{ $candidate->school->id }}" class="text-blue-600">
                    {{ $candidate->school->name }}
                </a>
            </p>
        </div>
        
        <div class="mb-4">
            <label class="block text-gray-600 text-sm">Gender</label>
            <p class="text-lg">{{ $candidate->gender === 'M' ? 'Male' : 'Female' }}</p>
        </div>
        
        <div class="mb-4">
            <label class="block text-gray-600 text-sm">Date of Birth</label>
            <p class="text-lg">{{ $candidate->date_of_birth?->format('d/m/Y') ?? 'N/A' }}</p>
        </div>
    </div>
</div>
@endsection
