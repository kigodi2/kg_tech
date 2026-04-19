@extends('layouts.auth-rms')

@section('title', 'Review and Approval')

@section('content')
@include('exam-development.partials.nav', [
    'title' => 'Review and Approval',
    'subtitle' => $project->project_name . ' · controlled review comments and status transitions',
])

<div class="max-w-7xl mx-auto px-4 py-6 grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
    <div class="space-y-6">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <h2 class="text-lg font-bold text-slate-900">Project Transition</h2>
            <form action="{{ route('exam-development.review.projects.transition', $project) }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <input type="text" name="status" placeholder="approved / locked / published" class="w-full rounded-xl border-slate-300">
                <textarea name="comment" rows="3" placeholder="Transition comment" class="w-full rounded-xl border-slate-300"></textarea>
                <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Update Project Status</button>
            </form>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
            <h2 class="text-lg font-bold text-slate-900">Add Review Comment</h2>
            <form action="{{ route('exam-development.review.comments.store', $project) }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <select name="exam_project_paper_id" class="w-full rounded-xl border-slate-300">
                    <option value="">Optional paper target</option>
                    @foreach($project->papers as $paper)
                        <option value="{{ $paper->id }}">{{ $paper->paper_code }} - {{ $paper->paper_name }}</option>
                    @endforeach
                </select>
                <input type="text" name="comment_type" placeholder="comment type" class="w-full rounded-xl border-slate-300">
                <textarea name="comment_text" rows="4" placeholder="Review comment" class="w-full rounded-xl border-slate-300"></textarea>
                <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Add Comment</button>
            </form>
        </div>
    </div>

    <div class="space-y-6">
        @foreach($project->papers as $paper)
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ $paper->paper_code }} · {{ $paper->paper_name }}</h2>
                        <p class="text-sm text-slate-600">{{ ucfirst($paper->paper_type) }} · {{ str_replace('_', ' ', $paper->status) }}</p>
                    </div>
                </div>
                <form action="{{ route('exam-development.review.papers.transition', $paper) }}" method="POST" class="mt-4 grid gap-3 md:grid-cols-[0.55fr_1fr_auto]">
                    @csrf
                    <input type="text" name="status" placeholder="reviewed / approved / locked" class="rounded-xl border-slate-300">
                    <input type="text" name="comment" placeholder="Paper transition comment" class="rounded-xl border-slate-300">
                    <button class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Update</button>
                </form>

                <div class="space-y-3 mt-4">
                    @foreach($paper->sections as $section)
                        @foreach($section->slots as $slot)
                            @if($slot->assignedQuestion)
                                <div class="rounded-xl bg-slate-50 border border-slate-200 p-3">
                                    <div class="flex items-center justify-between gap-4">
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $slot->slot_label }} · {{ $slot->assignedQuestion->title ?: $slot->assignedQuestion->topic_name }}</p>
                                            <p class="text-sm text-slate-600">{{ $slot->assignedQuestion->status }} · schemes {{ $slot->assignedQuestion->markingSchemes->count() }}</p>
                                        </div>
                                        <form action="{{ route('exam-development.review.questions.transition', $slot->assignedQuestion) }}" method="POST" class="flex gap-2">
                                            @csrf
                                            <input type="text" name="status" placeholder="approved" class="rounded-xl border-slate-300 text-sm">
                                            <button class="px-3 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold">Question</button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endforeach
                </div>

                @if($paper->reviewComments->isNotEmpty())
                    <div class="mt-4 space-y-2">
                        @foreach($paper->reviewComments as $comment)
                            <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-700">{{ $comment->comment_text }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
