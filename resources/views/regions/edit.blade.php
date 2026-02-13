@extends('layout')

@section('content')
<div class="mt-8 max-w-md">
    <a href="/regions" class="text-blue-600 mb-4 block hover:text-blue-800">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-bold mb-6">Edit Region</h2>
        
        <form method="POST" action="/regions/{{ $region->id }}">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Code</label>
                <input type="text" name="code" value="{{ $region->code }}" class="w-full border p-2 rounded" required>
                @error('code')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Name</label>
                <input type="text" name="name" value="{{ $region->name }}" class="w-full border p-2 rounded" required>
                @error('name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Description</label>
                <textarea name="description" class="w-full border p-2 rounded">{{ $region->description }}</textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">
                    <input type="checkbox" name="is_active" value="1" {{ $region->is_active ? 'checked' : '' }}>
                    Active
                </label>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded">Update</button>
        </form>
    </div>
</div>
@endsection
