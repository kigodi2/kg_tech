@extends('layouts.auth-rms')

@section('title', 'Create Question')

@section('content')
@include('exam-development.partials.nav', [
    'title' => 'Create Question',
    'subtitle' => 'Author structured items, MCQs, essays, matching tasks, and practical prompts.',
])

@include('exam-development.questions.partials.form', [
    'action' => route('exam-development.questions.store'),
    'method' => 'POST',
    'question' => null,
    'examTypes' => $examTypes,
    'subjects' => $subjects,
])
@endsection
