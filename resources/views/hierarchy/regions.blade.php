@extends('layout')

@section('content')
<div class="container mx-auto px-4 py-8">
    
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Select Region</h1>
        <p class="mt-2 text-gray-600">Choose a region to view districts</p>
    </div>

    <!-- 4-Column Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        @forelse($regions as $index => $region)
            <a href="{{ route('hierarchy.districts', $region->id) }}"
               class="group relative overflow-hidden shadow-md transition-all duration-300 hover:shadow-xl hover:scale-105 transform cursor-pointer"
               style="background: linear-gradient(135deg, 
                   {{ $index % 4 == 0 ? '#EF4444' : ($index % 4 == 1 ? '#10B981' : ($index % 4 == 2 ? '#1F2937' : '#6B7280')) }} 0%, 
                   {{ $index % 4 == 0 ? '#DC2626' : ($index % 4 == 1 ? '#059669' : ($index % 4 == 2 ? '#111827' : '#4B5563')) }} 100%);">
                
                <div class="absolute inset-0 opacity-0 group-hover:opacity-20 bg-white transition-opacity duration-300"></div>
                
                <div class="relative p-1 h-11 flex flex-col items-center justify-center text-white">
                    <div class="text-center">
                        <p class="text-base font-bold uppercase tracking-wider">{{ $region->name }}</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-4 text-center py-12">
                <p class="text-gray-500 text-lg">No regions available</p>
            </div>
        @endforelse
    </div>
</div>

<style>
    a:hover {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
    }
</style>
@endsection
