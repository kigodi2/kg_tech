@extends('layout')

@section('content')
@include('registration.partials.theme')
<style>
    .exam-type-search-input {
        width: 100%;
        min-height: 46px;
        padding: 0 14px;
        border: 1px solid #cbd5e1 !important;
        border-radius: 0 !important;
        background: #ffffff;
        color: #0f172a;
        font-size: 14px;
    }

    .exam-type-search-input:focus {
        outline: 2px solid rgba(59, 130, 246, 0.15);
        outline-offset: 0;
        border-color: #3b82f6 !important;
    }
</style>
<div class="registration-shell">
    <div class="registration-page-stack">
    @include('registration.partials.header', [
        'kicker' => 'Exam Setup Workspace',
        'title' => 'Add Exam Type',
        'subtitle' => 'Create a new exam type definition with its code, education level, and descriptive context.',
        'highlights' => [
            ['icon' => 'fas fa-plus', 'text' => 'Create new exam type'],
            ['icon' => 'fas fa-code', 'text' => 'Code and naming control'],
        ],
        'noteTitle' => 'Form Guidance',
        'noteText' => 'Keep exam type codes concise and consistent because they are referenced across registration and result workflows.',
    ])

    <div class="registration-surface registration-toolbar-card" style="max-width:760px;">
        <a href="/exam-types" class="text-blue-600 mb-5 inline-flex items-center gap-2 hover:text-blue-800 font-semibold">
            <i class="fas fa-arrow-left"></i> Back to Exam Types
        </a>
        
        <form method="POST" action="/exam-types" class="space-y-4">
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
                <label class="block text-gray-700 mb-2">Education Level</label>
                <input type="text" name="education_level" value="{{ old('education_level') }}" list="education_level_options" class="exam-type-search-input" placeholder="Search education level" autocomplete="off" required>
                <datalist id="education_level_options">
                    <option value="PRIMARY"></option>
                    <option value="SECONDARY"></option>
                    <option value="BOTH"></option>
                </datalist>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Description</label>
                <textarea name="description" class="w-full border p-2 rounded">{{ old('description') }}</textarea>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white p-3 rounded-lg font-semibold">Create</button>
        </form>
    </div>
</div>
</div>
@endsection
