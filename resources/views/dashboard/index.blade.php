@extends('layout')

@section('content')
<div class="mt-8">
    <h2 class="text-3xl font-bold mb-8">Dashboard</h2>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded shadow">
            <h3 class="text-gray-600 text-sm">Regions</h3>
            <p class="text-3xl font-bold">{{ $regions }}</p>
            <a href="/regions" class="text-blue-600 text-sm mt-2 block">View Regions →</a>
        </div>

        <div class="bg-white p-6 rounded shadow">
            <h3 class="text-gray-600 text-sm">Schools</h3>
            <p class="text-3xl font-bold">{{ $schools }}</p>
            <a href="/schools" class="text-blue-600 text-sm mt-2 block">View Schools →</a>
        </div>

        <div class="bg-white p-6 rounded shadow">
            <h3 class="text-gray-600 text-sm">Candidates</h3>
            <p class="text-3xl font-bold">{{ $candidates }}</p>
            <a href="/candidates" class="text-blue-600 text-sm mt-2 block">View Candidates →</a>
        </div>

        <div class="bg-white p-6 rounded shadow">
            <h3 class="text-gray-600 text-sm">Exam Types</h3>
            <p class="text-3xl font-bold">{{ $exam_types }}</p>
        </div>
    </div>
</div>
@endsection
