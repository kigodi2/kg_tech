<div class="max-w-7xl mx-auto px-4 pt-6">
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-4 flex flex-wrap gap-3 items-center justify-between">
        <div>
            <p class="text-xs font-semibold tracking-[0.24em] text-slate-500 uppercase">Exam Development</p>
            <h1 class="text-2xl font-bold text-slate-900">{{ $title ?? 'Exam Development Platform' }}</h1>
            @if(!empty($subtitle ?? null))
                <p class="text-sm text-slate-600 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('exam-development.dashboard') }}" class="px-3 py-2 rounded-xl text-sm font-semibold {{ request()->routeIs('exam-development.dashboard') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">Dashboard</a>
            <a href="{{ route('exam-development.formats.index') }}" class="px-3 py-2 rounded-xl text-sm font-semibold {{ request()->routeIs('exam-development.formats.*') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">Formats</a>
            <a href="{{ route('exam-development.projects.index') }}" class="px-3 py-2 rounded-xl text-sm font-semibold {{ request()->routeIs('exam-development.projects.*') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">Projects</a>
            <a href="{{ route('exam-development.questions.index') }}" class="px-3 py-2 rounded-xl text-sm font-semibold {{ request()->routeIs('exam-development.questions.*') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700' }}">Question Bank</a>
        </div>
    </div>
</div>
