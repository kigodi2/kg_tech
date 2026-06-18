@extends('public.results.psle.layout')

@section('title', 'Standard Seven Mock ' . $examYear . ' Districts - ' . $region->name)
@section('eyebrow', 'Standard Seven Mock ' . $examYear . ' Public Portal')
@section('page_title', strtoupper($region->name) . ' Region')
@section('page_copy', 'Browse districts inside ' . strtoupper($region->name) . ' using the same portal shell and navigation frame.')
@section('hero_badge', 'Professional Reporting Portal')
@section('hero_title', 'District results workspace for ' . strtoupper($region->name) . '.')
@section('hero_copy', 'Open a district card to continue to the school list for that public Standard Seven Mock results area.')

@section('top_actions')
    <a href="{{ route('public.results.psle.regions', ['examYear' => $examYear]) }}" class="top-btn secondary">
        <span>&larr;</span>
        <span>Back</span>
    </a>
@endsection

@section('hero_panel')
    <div class="glass-card">
        <small>Districts</small>
        <strong>{{ $districts->count() }}</strong>
        <span>Total districts currently visible in {{ strtoupper($region->name) }} for the selected Standard Seven Mock public-results view.</span>
    </div>
    <div class="glass-card">
        <small>Current Region</small>
        <strong>{{ strtoupper($region->name) }}</strong>
        <span>Select a district to move to the next hierarchy level and open its school entries.</span>
    </div>
@endsection

@section('stats')
    <section class="stats-grid">
        <article class="stat-card">
            <small><span>Entries</span><span>01</span></small>
            <strong>{{ $districts->count() }}</strong>
            <span>Total districts currently available inside this regional view.</span>
        </article>
        <article class="stat-card">
            <small><span>Stage</span><span>02</span></small>
            <strong>Districts</strong>
            <span>This is the second hierarchy level in the public Standard Seven Mock browsing flow.</span>
        </article>
        <article class="stat-card">
            <small><span>Region</span><span>03</span></small>
            <strong>{{ strtoupper($region->name) }}</strong>
            <span>The district list is scoped to this single region only.</span>
        </article>
        <article class="stat-card">
            <small><span>Flow</span><span>04</span></small>
            <strong>Next</strong>
            <span>Choose a district to move to the available school result entries.</span>
        </article>
    </section>
@endsection

@section('toolbar')
    <section class="toolbar">
        <div class="toolbar-left">
            <h3>Browse available districts</h3>
            <p>Search by district name or use alphabet shortcuts to narrow the visible cards.</p>
            <div style="margin-top: 14px;">
                <a href="{{ route('public.results.psle.regions', ['examYear' => $examYear]) }}" class="back-link">&larr; Back</a>
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
        @foreach($districts as $index => $district)
            <a href="{{ route('public.results.psle.schools', ['examYear' => $examYear, 'region' => $region->id, 'district' => $district->id]) }}" class="portal-card item" data-label="{{ strtoupper($district->name) }}">
                <span class="card-index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                <h3 class="card-label">{{ $district->name }}</h3>
                <p class="card-copy">{{ $district->schools_count }} schools and {{ $district->candidates_count }} candidates with public-result data are available in this district.</p>
                <span class="card-link">Open entry &rarr;</span>
            </a>
        @endforeach
    </section>
    <div id="noResults" class="no-results">No matching entry was found for the current search criteria. Please refine your keywords and try again.</div>
@endsection

@section('scripts')
    @include('public.results.psle.partials.filter-script')
@endsection
