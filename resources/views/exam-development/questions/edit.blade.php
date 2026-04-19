@extends('layouts.auth-rms')

@section('title', 'Edit Question')

@section('content')
@include('exam-development.partials.nav', [
    'title' => 'Edit Question',
    'subtitle' => $question->subject?->name . ' · version ' . $question->current_version_no,
])

@include('exam-development.questions.partials.form', [
    'action' => route('exam-development.questions.update', $question),
    'method' => 'PUT',
    'question' => $question,
    'examTypes' => $examTypes,
    'subjects' => $subjects,
])
@endsection
