@extends('layout')

@section('content')
<div class="mt-8 max-w-2xl">
    <a href="/regions" class="text-blue-600 mb-4 block hover:text-blue-800">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-bold mb-6">{{ $region->name }}</h2>
        
        <div class="mb-4">
            <label class="block text-gray-600 text-sm">Code</label>
            <p class="text-lg">{{ $region->code }}</p>
        </div>
        
        <div class="mb-4">
            <label class="block text-gray-600 text-sm">Description</label>
            <p class="text-lg">{{ $region->description ?? 'N/A' }}</p>
        </div>
        
        <div class="mt-8">
            <h3 class="text-xl font-bold mb-4">Schools in Region</h3>
            <div class="bg-gray-50 rounded p-4">
                @if ($region->schools->count())
                    <ul class="space-y-2">
                        @foreach ($region->schools as $school)
                            <li class="text-blue-600">
                                <a href="/schools/{{ $school->id }}">{{ $school->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-600">No schools in this region</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
