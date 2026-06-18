@extends('public.results.psle.layout')

@section('title', 'Standard Seven Mock ' . $examYear . ' Schools - ' . $district->name)
@section('eyebrow', 'Standard Seven Mock ' . $examYear . ' Public Portal')
@section('page_title', strtoupper($district->name))
@section('page_copy', 'Browse the schools available in ' . strtoupper($district->name) . ' using the same ACSEE portal frame and styling.')
@section('hero_badge', 'Professional Reporting Portal')
@section('hero_title', 'School entries for ' . strtoupper($district->name) . '.')
@section('hero_copy', 'Open a school entry to view the public Standard Seven Mock school-results page in the same portal workspace.')

@section('top_actions')
    <a href="{{ route('public.results.psle.districts', ['examYear' => $examYear, 'region' => $region->id]) }}" class="top-btn secondary">
        <span>&larr;</span>
        <span>Back</span>
    </a>
@endsection

@section('hero_panel')
    <div class="glass-card">
        <small>Schools</small>
        <strong>{{ $schools->count() }}</strong>
        <span>Total schools currently available in this district for Standard Seven Mock results.</span>
    </div>
    <div class="glass-card">
        <small>Current District</small>
        <strong>{{ strtoupper($district->name) }}</strong>
        <span>Each card opens one school’s public results page directly.</span>
    </div>
@endsection

@section('stats')
    <section class="stats-grid">
        <article class="stat-card">
            <small><span>Entries</span><span>01</span></small>
            <strong>{{ $schools->count() }}</strong>
            <span>Total school entries currently available inside this district view.</span>
        </article>
        <article class="stat-card">
            <small><span>Stage</span><span>02</span></small>
            <strong>Schools</strong>
            <span>This is the third hierarchy level before the final school-results page.</span>
        </article>
        <article class="stat-card">
            <small><span>District</span><span>03</span></small>
            <strong>{{ strtoupper($district->name) }}</strong>
            <span>The school list is scoped to this district only.</span>
        </article>
        <article class="stat-card">
            <small><span>Flow</span><span>04</span></small>
            <strong>Open</strong>
            <span>Select a school card to open the final public results page.</span>
        </article>
    </section>
@endsection

@section('toolbar')
    <section class="toolbar">
        <div class="toolbar-left">
            <h3>Browse available schools</h3>
            <p>Search by school name or centre number, then open the target school results entry.</p>
            <div style="margin-top: 14px;">
                <a href="{{ route('public.results.psle.districts', ['examYear' => $examYear, 'region' => $region->id]) }}" class="back-link">&larr; Back</a>
            </div>
        </div>
        <div class="toolbar-right">
            <div class="search-row">
                <input type="text" id="schoolSearch" class="search-input" placeholder="Search from the list">
                <button type="button" class="search-btn" onclick="applyFilters()">Search</button>
            </div>
        </div>
    </section>
@endsection

@section('alpha')
    <section class="alpha-wrap">
        <span class="alpha-title">Filter by alphabet</span>
        <div class="alpha-actions" id="alphaLetters">
            <button class="alpha-link active" data-letter="ALL">ALL ENTRIES</button>
        </div>
    </section>
@endsection

@section('content')
    <section class="cards-grid" id="schoolsContainer">
        @foreach($schools as $index => $school)
            <a href="{{ route('public.results.psle.school', ['examYear' => $examYear, 'region' => $region->id, 'district' => $district->id, 'school' => $school->id]) }}" class="portal-card item" data-label="{{ strtoupper(trim($school->code . ' - ' . $school->name)) }}">
                <span class="card-index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                <h3 class="card-label">{{ $school->code }}</h3>
                <p class="card-copy">{{ strtoupper($school->name) }}</p>
                <p class="card-copy">{{ $school->candidates_count }} candidates with result-bearing public data are available for this school entry.</p>
                <span class="card-link">Open entry &rarr;</span>
            </a>
        @endforeach
    </section>
    <div id="noResults" class="no-results">No matching entry was found for the current search criteria. Please refine your keywords and try again.</div>
@endsection

@section('scripts')
    @include('public.results.psle.partials.filter-script')
@endsection
