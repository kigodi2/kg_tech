@extends('layout')

@section('content')
<div style="background-color: #B0E0E6; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Maiandra GD', sans-serif; padding: 2rem;">
    <div style="background-color: #ffffff; max-width: 600px; width: 100%; border-radius: 1.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 3rem; text-align: center; border: 2px solid #003366;">
        
        <div style="margin-bottom: 2rem;">
            <img src="{{ asset('images/emblem.png') }}" alt="Coat of Arms" style="height: 100px; width: 100px; object-contain: contain; margin: 0 auto;">
        </div>

        @if($status === 'failed')
            <h2 style="color: #dc2626; font-size: 1.75rem; margin-bottom: 1rem;">Evaluation Report Failed</h2>
            <p style="color: #4b5563; font-size: 1.1rem; line-height: 1.6; margin-bottom: 2rem;">
                Something went wrong while precalculating the <strong>{{ strtoupper(str_replace('-', ' ', $evaluation)) }}</strong> report for 
                @if($scopeType === 'regional')
                    <strong>{{ strtoupper($regionName) }} Region</strong>
                @else
                    <strong>TASIDO Zone</strong>
                @endif 
                ({{ $examYearValue }}).
            </p>

            @if($isAdmin)
                <form action="{{ route('evaluations.psle.rebuild') }}" method="POST" style="margin-top: 1.5rem;">
                    @csrf
                    <input type="hidden" name="scope_type" value="{{ $scopeType }}">
                    <input type="hidden" name="scope_id" value="{{ $scopeId }}">
                    <input type="hidden" name="evaluation" value="{{ $evaluation }}">
                    <input type="hidden" name="exam_year" value="{{ $examYearValue }}">
                    <button type="submit" style="background: linear-gradient(180deg, #dc2626 0%, #b91c1c 100%); color: white; border: none; padding: 0.75rem 2rem; font-size: 1.1rem; font-weight: bold; border-radius: 0.75rem; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.1s;">
                        Rebuild Report
                    </button>
                </form>
            @else
                <div style="background-color: #fef2f2; border: 1px solid #fee2e2; border-radius: 0.75rem; padding: 1rem; color: #991b1b; display: inline-block;">
                    Please contact system administrators to rebuild this report.
                </div>
            @endif
        @else
            <div style="display: flex; flex-direction: column; align-items: center; gap: 1.5rem;">
                <!-- Spinning Loader -->
                <div class="loader" style="border: 6px solid #e5e7eb; border-top: 6px solid #003366; border-radius: 50%; width: 60px; height: 60px; animation: spin 1s linear infinite;"></div>
                
                <h2 style="color: #003366; font-size: 1.75rem; margin-bottom: 0.5rem; margin-top: 1rem;">Preparing Evaluation Report</h2>
                
                <p style="color: #4b5563; font-size: 1.1rem; line-height: 1.6; max-width: 480px; margin: 0 auto 1.5rem auto;">
                    The <strong>{{ strtoupper(str_replace('-', ' ', $evaluation)) }}</strong> report for 
                    @if($scopeType === 'regional')
                        <strong>{{ strtoupper($regionName) }} Region</strong>
                    @else
                        <strong>TASIDO Zone</strong>
                    @endif
                    ({{ $examYearValue }}) is being prepared. It will open automatically as soon as it is ready.
                </p>

                <div style="font-size: 0.95rem; color: #6b7280; background-color: #f3f4f6; padding: 0.5rem 1.5rem; border-radius: 9999px;">
                    Current Status: <span style="font-weight: bold; color: #003366;">{{ strtoupper($status ?: 'pending') }}</span>
                </div>
            </div>
        @endif

        <div style="margin-top: 3rem; border-top: 1px solid #e5e7eb; padding-top: 1.5rem;">
            <a href="{{ $scopeType === 'regional' ? route('evaluations.psle.regionalwise.region', ['region' => $scopeId]) : route('evaluations.psle.zonalwise') }}" style="color: #003366; text-decoration: none; font-weight: bold;">
                &larr; Back to Evaluations Dashboard
            </a>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

@if($status !== 'failed')
<script>
    (function() {
        const checkUrl = window.location.pathname + '?check_status=1';
        
        function pollStatus() {
            fetch(checkUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ready') {
                    window.location.reload();
                } else if (data.status === 'failed') {
                    window.location.reload();
                } else {
                    setTimeout(pollStatus, 3000);
                }
            })
            .catch(() => {
                setTimeout(pollStatus, 5000);
            });
        }
        
        setTimeout(pollStatus, 3000);
    })();
</script>
@endif
@endsection
