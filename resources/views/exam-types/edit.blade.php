@extends('layout')

@section('content')
<div class="mt-8 max-w-md">
    <a href="/exam-types" class="text-blue-600 mb-4 block hover:text-blue-800">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-bold mb-6">Edit Exam Type</h2>
        
        <form method="POST" action="/exam-types/{{ $examType->id }}">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Code</label>
                <input type="text" name="code" value="{{ $examType->code }}" class="w-full border p-2 rounded" required>
                @error('code')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Name</label>
                <input type="text" name="name" value="{{ $examType->name }}" class="w-full border p-2 rounded" required>
                @error('name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Education Level</label>
                <select name="education_level" class="w-full border p-2 rounded" required>
                    <option value="PRIMARY" {{ $examType->education_level == 'PRIMARY' ? 'selected' : '' }}>Primary</option>
                    <option value="SECONDARY" {{ $examType->education_level == 'SECONDARY' ? 'selected' : '' }}>Secondary</option>
                    <option value="BOTH" {{ $examType->education_level == 'BOTH' ? 'selected' : '' }}>Both</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Description</label>
                <textarea name="description" class="w-full border p-2 rounded">{{ $examType->description }}</textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">
                    <input type="checkbox" name="is_active" value="1" {{ $examType->is_active ? 'checked' : '' }}>
                    Active
                </label>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded">Update</button>
        </form>
    </div>
</div>
@endsection
