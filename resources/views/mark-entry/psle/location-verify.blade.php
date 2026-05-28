@extends('layouts.auth-rms')

@section('title', 'MEO Location Verification')

@section('content')
<div class="login-shell">
    <div class="login-card login-card--compact">
        <div class="login-card-header">
            <div class="login-emblem-wrap">
                <img src="{{ asset('images/vian.png') }}" alt="System location illustration" class="login-emblem">
                <div class="login-stripes" aria-hidden="true">
                    <span style="background:#1eb53a;"></span>
                    <span style="background:#fcd116;"></span>
                    <span style="background:#000000;"></span>
                    <span style="background:#00a3dd;"></span>
                </div>
            </div>
            <h1>Location Verification</h1>
            <p>To continue with PSLE mark entry, your device location must be verified. This account is restricted to the approved marking centre for security and data protection.</p>
        </div>

        <div class="login-card-body">
            <!-- Loader State -->
            <div id="location-loader" class="location-status-container">
                <div class="location-spinner"></div>
                <p class="status-text">Acquiring secure GPS location coordinates...</p>
                <p class="sub-text">Please allow browser location permissions if prompted.</p>
            </div>

            <!-- Error State (hidden by default) -->
            <div id="location-error" class="location-status-container" style="display: none;">
                <div class="error-icon">⚠️</div>
                <div class="login-error" id="error-message" role="alert">
                    Location verification failed.
                </div>
                <button type="button" class="login-button" onclick="verifyLocation()" style="margin-top: 15px;">Retry Location Verification</button>
            </div>

            <form action="{{ route('logout') }}" method="POST" style="margin-top: 15px; text-align: center;">
                @csrf
                <button type="submit" class="logout-link-btn" style="background: none; border: none; color: #fca5a5; font-size: 0.85rem; text-decoration: underline; cursor: pointer;">
                    Cancel and Logout
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    .location-status-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 20px 10px;
    }
    
    .status-text {
        font-weight: 600;
        color: #ffffff;
        margin-top: 15px;
        font-size: 0.95rem;
    }

    .sub-text {
        color: #9ca3af;
        font-size: 0.8rem;
        margin-top: 5px;
    }

    .location-spinner {
        width: 50px;
        height: 50px;
        border: 4px solid rgba(255, 255, 255, 0.1);
        border-top-color: #1eb53a;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .error-icon {
        font-size: 3rem;
        margin-bottom: 10px;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<script>
    function verifyLocation() {
        const loader = document.getElementById('location-loader');
        const error = document.getElementById('location-error');
        const errorMsg = document.getElementById('error-message');

        loader.style.display = 'flex';
        error.style.display = 'none';

        if (!navigator.geolocation) {
            showError("Your browser or device does not support GPS location services. Please use a device with location support.");
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function(position) {
                // Success: Post coordinates to backend
                const payload = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    captured_at: new Date().toISOString()
                };

                fetch("{{ route('mark-entry.location.verify.submit') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => {
                    if (response.status === 423) {
                        return response.json().then(data => {
                            throw new Error(data.message || "Access denied. Outside radius.");
                        });
                    }
                    if (!response.ok) {
                        throw new Error("Location verification encountered a server error. Please try again.");
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.ok && data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        throw new Error("Invalid response received from the server.");
                    }
                })
                .catch(err => {
                    showError(err.message);
                });
            },
            function(geoError) {
                // Handle Geolocation Errors
                let msg = "Failed to acquire location. ";
                switch (geoError.code) {
                    case geoError.PERMISSION_DENIED:
                        msg += "Location permission was denied. Please allow GPS permissions to access mark entry.";
                        break;
                    case geoError.POSITION_UNAVAILABLE:
                        msg += "Location information is currently unavailable on this device.";
                        break;
                    case geoError.TIMEOUT:
                        msg += "Location request timed out. Please retry.";
                        break;
                    default:
                        msg += geoError.message || "An unknown location error occurred.";
                        break;
                }
                showError(msg);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }

    function showError(msg) {
        document.getElementById('location-loader').style.display = 'none';
        const errorContainer = document.getElementById('location-error');
        const errorMsg = document.getElementById('error-message');
        
        errorMsg.innerText = msg;
        errorContainer.style.display = 'flex';
    }

    // Trigger verification on page load
    document.addEventListener('DOMContentLoaded', verifyLocation);
</script>
@endsection
