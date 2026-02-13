@extends('layout')

@section('content')
<div class="mt-8 max-w-md">
    <a href="/candidates" class="text-blue-600 mb-4 block hover:text-blue-800">
        <i class="fas fa-arrow-left"></i> Back
    </a>
    
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-bold mb-6">Add Candidate</h2>
        
        <form method="POST" action="/candidates">
            @csrf
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">School</label>
                <select name="school_id" class="w-full border p-2 rounded" required>
                    <option value="">Select School</option>
                    @foreach ($schools as $school)
                        <option value="{{ $school->id }}" {{ old('school_id') == $school->id ? 'selected' : '' }}>
                            {{ $school->name }}
                        </option>
                    @endforeach
                </select>
                @error('school_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Candidate ID</label>
                <input type="text" name="candidate_id" value="{{ old('candidate_id') }}" class="w-full border p-2 rounded" required>
                @error('candidate_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">First Name</label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" class="w-full border p-2 rounded" required>
                @error('first_name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Last Name</label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" class="w-full border p-2 rounded" required>
                @error('last_name')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Gender</label>
                <select name="gender" class="w-full border p-2 rounded" required>
                    <option value="M" {{ old('gender') == 'M' ? 'selected' : '' }}>Male</option>
                    <option value="F" {{ old('gender') == 'F' ? 'selected' : '' }}>Female</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Date of Birth</label>
                <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="w-full border p-2 rounded">
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded">Create</button>
        </form>
    </div>
</div>
@endsection
