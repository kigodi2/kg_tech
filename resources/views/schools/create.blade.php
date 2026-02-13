@extends('layout')

@section('content')
<div class="mt-8 max-w-md">
    <a href="/schools" class="text-blue-600 mb-4 block hover:text-blue-800">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-bold mb-6">Add School</h2>
        
        <form method="POST" action="/schools">
            @csrf
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Code</label>
                <input type="text" name="code" value="{{ old('code') }}" class="w-full border p-2 rounded" required>
                @error('code')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full border p-2 rounded" required>
                @error('name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Region</label>
                <select name="region_id" class="w-full border p-2 rounded" required>
                    <option value="">Select Region</option>
                    @foreach ($regions as $region)
                        <option value="{{ $region->id }}" {{ old('region_id') == $region->id ? 'selected' : '' }}>
                            {{ $region->name }}
                        </option>
                    @endforeach
                </select>
                @error('region_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Type</label>
                <select name="school_type" class="w-full border p-2 rounded" required>
                    <option value="PRIMARY" {{ old('school_type') == 'PRIMARY' ? 'selected' : '' }}>Primary</option>
                    <option value="SECONDARY" {{ old('school_type') == 'SECONDARY' ? 'selected' : '' }}>Secondary</option>
                    <option value="BOTH" {{ old('school_type') == 'BOTH' ? 'selected' : '' }}>Both</option>
                </select>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded">Create</button>
        </form>
    </div>
</div>
@endsection
