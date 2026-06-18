<style>
/* Admin Control Panel Theme Overrides for Mark Entry Portal */
@import url('https://fonts.cdnfonts.com/css/maiandra-gd');

:root {
    --tz-green: #1EB53A;
    --tz-yellow: #FCD116;
    --tz-blue: #00A3DD;
    --tz-dark: #0b1014;
    --tz-card: #101518;
    --tz-text: #f0f4f7;
    --tz-muted: rgba(255,255,255,.45);
}

.mark-entry-shell, body.bg-gray-100, .bg-gray-100 {
    font-family: 'Maiandra GD', sans-serif !important;
    background: #0f1117 !important;
    color: var(--tz-text) !important;
}

/* Base structural overrides */
.mark-entry-shell .bg-white, .bg-white { 
    background: var(--tz-card) !important; 
    border-color: rgba(255,255,255,0.06) !important; 
}
.mark-entry-shell .bg-gray-50, .bg-gray-50 { 
    background: rgba(255,255,255,0.02) !important; 
}
.mark-entry-shell .text-gray-900, .text-gray-900, 
.mark-entry-shell .text-gray-800, .text-gray-800 { 
    color: var(--tz-text) !important; 
}
.mark-entry-shell .text-gray-700, .text-gray-700 { 
    color: rgba(255,255,255,0.85) !important; 
}
.mark-entry-shell .text-gray-600, .text-gray-600 { 
    color: rgba(255,255,255,0.6) !important; 
}
.mark-entry-shell .text-gray-500, .text-gray-500 { 
    color: var(--tz-muted) !important; 
}
.mark-entry-shell .border-gray-200, .mark-entry-shell .border-gray-300,
.border-gray-200, .border-gray-300 { 
    border-color: rgba(255,255,255,0.08) !important; 
}

/* Blue highlights */
.mark-entry-shell .bg-blue-50, .bg-blue-50 { 
    background: rgba(0, 163, 221, 0.1) !important; 
    border-color: rgba(0, 163, 221, 0.2) !important; 
}
.mark-entry-shell .text-blue-600, .mark-entry-shell .text-blue-700, .mark-entry-shell .text-blue-800,
.text-blue-600, .text-blue-700, .text-blue-800 { 
    color: var(--tz-blue) !important; 
}
.mark-entry-shell .bg-blue-600, .bg-blue-600, .sidebar-item-active { 
    background: var(--tz-blue) !important; 
    color: #fff !important; 
}

/* Yellow highlights */
.mark-entry-shell .bg-yellow-50, .bg-yellow-50 { 
    background: rgba(252, 209, 22, 0.1) !important; 
    border-color: rgba(252, 209, 22, 0.2) !important; 
}
.mark-entry-shell .text-yellow-600, .mark-entry-shell .text-yellow-800,
.text-yellow-600, .text-yellow-800 { 
    color: var(--tz-yellow) !important; 
}
.mark-entry-shell .bg-yellow-600, .bg-yellow-600 { 
    background: var(--tz-yellow) !important; 
    color: #0b1014 !important; 
}

/* Green highlights */
.mark-entry-shell .bg-green-50, .bg-green-50 { 
    background: rgba(30, 181, 58, 0.1) !important; 
    border-color: rgba(30, 181, 58, 0.2) !important; 
}
.mark-entry-shell .text-green-600, .mark-entry-shell .text-green-800,
.text-green-600, .text-green-800 { 
    color: var(--tz-green) !important; 
}
.mark-entry-shell .bg-green-600, .bg-green-600 { 
    background: var(--tz-green) !important; 
    color: #fff !important; 
}

/* Red highlights */
.mark-entry-shell .bg-red-50, .bg-red-50 { 
    background: rgba(239, 68, 68, 0.1) !important; 
    border-color: rgba(239, 68, 68, 0.2) !important; 
}
.mark-entry-shell .text-red-600, .mark-entry-shell .text-red-800,
.text-red-600, .text-red-800 { 
    color: #fca5a5 !important; 
}
.mark-entry-shell .bg-red-600, .bg-red-600 { 
    background: rgba(239, 68, 68, 0.8) !important; 
    color: #fff !important; 
}

/* Inputs and Forms */
.mark-entry-shell input[type="text"], 
.mark-entry-shell input[type="number"], 
.mark-entry-shell input[type="email"], 
.mark-entry-shell select, 
.mark-entry-shell textarea,
input[type="text"], input[type="number"], input[type="email"], select, textarea {
    background: rgba(255,255,255,0.04) !important;
    border: 1px solid rgba(255,255,255,0.1) !important;
    color: var(--tz-text) !important;
}
.mark-entry-shell input:focus, .mark-entry-shell select:focus, .mark-entry-shell textarea:focus,
input:focus, select:focus, textarea:focus {
    border-color: var(--tz-blue) !important;
    box-shadow: 0 0 0 2px rgba(0, 163, 221, 0.25) !important;
    outline: none !important;
}
.mark-entry-shell input:disabled, .mark-entry-shell select:disabled,
input:disabled, select:disabled {
    background: rgba(255,255,255,0.02) !important;
    color: rgba(255,255,255,0.3) !important;
    cursor: not-allowed;
}

/* Sidebars and headers */
.mark-entry-sidebar, .bg-gray-900 {
    background: linear-gradient(180deg, #0d1b2a, #11202e) !important;
    border-right: 1px solid rgba(187,164,94,.18) !important;
}

.border-gray-700 {
    border-color: rgba(187,164,94,.15) !important;
}

/* Page Header Overrides (to match admin style) */
.registration-page-header {
    background: linear-gradient(135deg, #111821, #0d1218) !important;
    border: 1px solid rgba(0,163,221,.14) !important;
    box-shadow: 0 12px 32px rgba(0,0,0,.5) !important;
}

/* Modal Overrides */
.registration-modal-shell {
    background: var(--tz-card) !important;
    border: 1px solid rgba(255,255,255,0.08) !important;
}
.registration-modal-header {
    background: linear-gradient(135deg, rgba(0,163,221,.16), rgba(0,61,82,.34)) !important;
    border-bottom: 1px solid rgba(255,255,255,0.05) !important;
}
.registration-modal-body {
    background: var(--tz-card) !important;
}
.registration-modal-panel {
    background: rgba(255,255,255,0.02) !important;
    border: 1px solid rgba(255,255,255,0.06) !important;
}

/* Tables */
.registration-table-card thead {
    background: rgba(255,255,255,0.03) !important;
}
.registration-table-card thead th {
    color: var(--tz-muted) !important;
    border-bottom-color: rgba(255,255,255,0.05) !important;
}
.registration-table-card tbody tr:hover {
    background: rgba(255,255,255,0.02) !important;
}
.registration-table-card tbody td {
    color: rgba(255,255,255,0.78) !important;
    border-color: rgba(255,255,255,0.05) !important;
}

/* Empty states */
.registration-empty {
    background: rgba(255,255,255,0.02) !important;
    border-color: rgba(255,255,255,0.08) !important;
    color: var(--tz-muted) !important;
}

/* Specific button styles matching admin */
.mark-entry-shell button.bg-blue-600 {
    background: linear-gradient(135deg, var(--tz-blue), #006fa3) !important;
}
</style>
