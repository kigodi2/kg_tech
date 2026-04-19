@extends('public.results.psle.layout')

@section('title', 'PSLE ' . $examYear . ' Regions')
@section('eyebrow', 'PSLE 2026 Public Portal')
@section('page_title', 'PSLE ' . $examYear . ' Regions')
@section('page_copy', 'Start from the regional level, then drill down to districts, schools, and individual school result pages.')
@section('hero_badge', 'Professional Reporting Portal')
@section('hero_title', 'Browse PSLE public results by region.')
@section('hero_copy', 'This page mirrors the portal workspace frame while organizing PSLE public results through a clean region-to-school hierarchy.')

@section('hero_panel')
    <div class="glass-card">
        <small>Regions</small>
        <strong>{{ $regions->count() }}</strong>
        <span>Total regions that currently have result-bearing PSLE school data for {{ $examYear }}.</span>
    </div>
    <div class="glass-card">
        <small>Flow</small>
        <strong>Region</strong>
        <span>Open a region to continue to districts, schools, and the final school-results page.</span>
    </div>
@endsection

@section('stats')
    <section class="stats-grid">
        <article class="stat-card">
            <small><span>Entries</span><span>01</span></small>
            <strong>{{ $regions->count() }}</strong>
            <span>Total regional entries currently visible in this PSLE public portal view.</span>
        </article>
        <article class="stat-card">
            <small><span>Stage</span><span>02</span></small>
            <strong>Regions</strong>
            <span>The first hierarchy level for public PSLE result browsing.</span>
        </article>
        <article class="stat-card">
            <small><span>Mode</span><span>03</span></small>
            <strong>Public</strong>
            <span>Presentation is aligned to the portal shell rather than the internal admin pages.</span>
        </article>
        <article class="stat-card">
            <small><span>Path</span><span>04</span></small>
            <strong>Direct</strong>
            <span>Select a region card to move into the next public-results level immediately.</span>
        </article>
    </section>
@endsection

@section('toolbar')
    <section class="toolbar">
        <div class="toolbar-left">
            <h3>Browse available regions</h3>
            <p>Use the search field or alphabet shortcuts below to find the target region quickly.</p>
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
        @forelse($regions as $index => $region)
            <a href="{{ route('public.results.psle.districts', ['examYear' => $examYear, 'region' => $region->id]) }}" class="portal-card item" data-label="{{ strtoupper($region->name) }}">
                <span class="card-index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                <h3 class="card-label">{{ $region->name }}</h3>
                <p class="card-copy">{{ $region->schools_count }} schools and {{ $region->candidates_count }} candidates with public-result data are available in this region.</p>
                <span class="card-link">Open entry &rarr;</span>
            </a>
        @empty
            <div class="empty-state">No entries are available for this portal yet.</div>
        @endforelse
    </section>
    <div id="noResults" class="no-results">No matching entry was found for the current search criteria. Please refine your keywords and try again.</div>
@endsection

@section('scripts')
    @include('public.results.psle.partials.filter-script')
@endsection
