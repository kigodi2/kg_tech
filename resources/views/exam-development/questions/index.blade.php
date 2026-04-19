@extends('layouts.auth-rms')

@section('title', 'Question Bank')

@section('content')
@include('exam-development.partials.nav', [
    'title' => 'Question Bank',
    'subtitle' => 'Reusable approved questions for slot-based paper assembly.',
])

<div class="max-w-7xl mx-auto px-4 py-6">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900">Questions</h2>
            <a href="{{ route('exam-development.questions.create') }}" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">New Question</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Question</th>
                        <th class="px-4 py-3 text-left">Subject</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-left">Marks</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($questions as $question)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">{{ $question->title ?: \Illuminate\Support\Str::limit(strip_tags($question->question_text), 60) }}</div>
                                <div class="text-slate-600">{{ $question->topic_name }}</div>
                            </td>
                            <td class="px-4 py-3">{{ $question->subject?->name }}</td>
                            <td class="px-4 py-3">{{ $question->question_type }}</td>
                            <td class="px-4 py-3">{{ $question->marks }}</td>
                            <td class="px-4 py-3"><span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold uppercase">{{ str_replace('_', ' ', $question->status) }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex gap-3">
                                    <a href="{{ route('exam-development.questions.edit', $question) }}" class="font-semibold text-slate-900">Edit</a>
                                    <a href="{{ route('exam-development.marking-schemes.show', $question) }}" class="font-semibold text-slate-700">Scheme</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-5 border-t border-slate-200">{{ $questions->links() }}</div>
    </div>
</div>
@endsection
