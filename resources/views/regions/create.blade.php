@extends('layout')

@section('content')
<div class="mt-8 max-w-md">
    <a href="/regions" class="text-blue-600 mb-4 block hover:text-blue-800">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-bold mb-6">Add Region</h2>
        
        <form method="POST" action="/regions">
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
                <label class="block text-gray-700 mb-2">Description</label>
                <textarea name="description" class="w-full border p-2 rounded">{{ old('description') }}</textarea>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded">Create</button>
        </form>
    </div>
</div>
@endsection
