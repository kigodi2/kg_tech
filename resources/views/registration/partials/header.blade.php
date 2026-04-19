@php
    $kicker = $kicker ?? 'Registration Workspace';
    $highlights = $highlights ?? [];
    $noteTitle = $noteTitle ?? null;
    $noteText = $noteText ?? null;
    $noteItems = $noteItems ?? [];
@endphp

<section class="registration-page-header">
    <div class="registration-page-header-grid">
        <div>
            <div class="registration-page-kicker">{{ $kicker }}</div>
            <h1 class="registration-page-title">{{ $title }}</h1>
            <p class="registration-page-subtitle">{{ $subtitle }}</p>

            @if (!empty($highlights))
                <div class="registration-page-highlights">
                    @foreach ($highlights as $item)
                        <div class="registration-page-chip">
                            <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $item['text'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($noteTitle || $noteText || !empty($noteItems))
            <aside class="registration-page-aside">
                <div class="registration-page-note">
                    @if ($noteTitle)
                        <h2>{{ $noteTitle }}</h2>
                    @endif
                    @if ($noteText)
                        <p>{{ $noteText }}</p>
                    @endif

                    @if (!empty($noteItems))
                        <div class="registration-page-note-list">
                            @foreach ($noteItems as $item)
                                <div class="registration-page-note-item">
                                    <div class="registration-page-note-icon">
                                        <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <strong>{{ $item['title'] }}</strong>
                                        <span>{{ $item['text'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </aside>
        @endif
    </div>
</section>
