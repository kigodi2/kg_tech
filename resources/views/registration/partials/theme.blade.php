<style>
    .registration-shell {
        width: 100%;
        padding: 28px 28px 10px;
        background:
            radial-gradient(circle at top right, rgba(53, 92, 154, 0.07), transparent 26%),
            linear-gradient(180deg, #f4f7fb 0%, #eef3f9 100%);
    }

    .registration-page-stack {
        display: grid;
        gap: 20px;
    }

    .registration-page-header {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        padding: 28px 28px 24px;
        background:
            linear-gradient(135deg, rgba(20, 48, 88, 0.97) 0%, rgba(37, 82, 145, 0.94) 58%, rgba(16, 116, 95, 0.9) 100%);
        color: #ffffff;
        box-shadow: 0 24px 54px rgba(15, 23, 42, 0.14);
    }

    .registration-page-header::before,
    .registration-page-header::after {
        content: "";
        position: absolute;
        border-radius: 999px;
        pointer-events: none;
    }

    .registration-page-header::before {
        width: 320px;
        height: 320px;
        right: -90px;
        top: -140px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.14) 0%, rgba(255, 255, 255, 0) 70%);
    }

    .registration-page-header::after {
        width: 250px;
        height: 250px;
        left: -80px;
        bottom: -110px;
        background: radial-gradient(circle, rgba(252, 209, 22, 0.18) 0%, rgba(252, 209, 22, 0) 70%);
    }

    .registration-page-header-grid {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(260px, 0.8fr);
        gap: 20px;
        align-items: center;
    }

    .registration-page-kicker {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.78);
    }

    .registration-page-kicker::before {
        content: "";
        width: 36px;
        height: 4px;
        border-radius: 999px;
        background: linear-gradient(90deg, #1EB53A 0%, #FCD116 55%, #00A3DD 100%);
    }

    .registration-page-title {
        margin: 0 0 10px;
        font-size: clamp(1.95rem, 3.6vw, 3rem);
        line-height: 1.03;
        letter-spacing: -0.04em;
        color: #ffffff;
    }

    .registration-page-subtitle {
        margin: 0;
        max-width: 760px;
        color: rgba(255, 255, 255, 0.86);
        font-size: 0.98rem;
        line-height: 1.75;
    }

    .registration-page-highlights {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 20px;
    }

    .registration-page-chip {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        min-height: 38px;
        padding: 0 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.14);
        color: rgba(255, 255, 255, 0.92);
        font-size: 0.85rem;
        font-weight: 600;
    }

    .registration-page-chip i {
        color: #fcd116;
    }

    .registration-page-aside {
        display: grid;
        gap: 12px;
    }

    .registration-page-note {
        border-radius: 22px;
        padding: 18px 18px 16px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.14);
        backdrop-filter: blur(6px);
    }

    .registration-page-note h2 {
        margin: 0 0 8px;
        font-size: 1rem;
        color: #ffffff;
    }

    .registration-page-note p {
        margin: 0;
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.88rem;
        line-height: 1.7;
    }

    .registration-page-note-list {
        display: grid;
        gap: 10px;
        margin-top: 14px;
    }

    .registration-page-note-item {
        display: grid;
        grid-template-columns: 38px minmax(0, 1fr);
        gap: 10px;
        align-items: center;
        padding: 10px 12px;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.08);
    }

    .registration-page-note-icon {
        width: 38px;
        height: 38px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.12);
        color: #ffffff;
    }

    .registration-page-note-item strong {
        display: block;
        color: #ffffff;
        font-size: 0.88rem;
    }

    .registration-page-note-item span {
        display: block;
        color: rgba(255, 255, 255, 0.76);
        font-size: 0.78rem;
        margin-top: 2px;
    }

    .registration-surface {
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid rgba(203, 213, 225, 0.88);
        border-radius: 24px;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.05);
    }

    .registration-toolbar-card {
        padding: 24px;
    }

    .registration-toolbar-card .registration-toolbar-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        align-items: flex-end;
    }

    .registration-shell input[type="text"],
    .registration-shell input[type="file"],
    .registration-shell input[type="email"],
    .registration-shell select,
    .registration-shell textarea,
    .registration-shell .filter-input,
    .registration-shell .filter-dropdown-btn {
        min-height: 42px;
        border-radius: 14px !important;
        border: 1px solid #cfd6df !important;
        background: #ffffff;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
    }

    .registration-shell input:focus,
    .registration-shell select:focus,
    .registration-shell textarea:focus,
    .registration-shell .filter-dropdown-btn:focus {
        outline: none;
        border-color: #355c9a !important;
        box-shadow: inset 0 0 0 2px rgba(53, 92, 154, 0.55) !important;
    }

    .registration-shell input.filter-search-input[type="text"] {
        border-radius: 0 !important;
    }

    .registration-shell input.filter-search-input[type="text"]:focus {
        border-radius: 0 !important;
        box-shadow: none !important;
        outline: none;
    }

    .registration-shell .filter-input,
    .registration-shell .filter-dropdown-btn,
    .registration-shell .filter-dropdown-menu,
    .registration-shell input.filter-search-input[type="text"],
    .registration-shell .relative > div.absolute,
    .registration-shell .relative > div.absolute input[type="text"] {
        border-radius: 0 !important;
    }

    .registration-shell .relative > div.absolute {
        top: calc(100% - 1px);
        overflow: hidden;
    }

    .registration-shell label,
    .registration-shell .filter-label {
        color: #334155;
        font-weight: 700;
        font-size: 0.84rem;
        letter-spacing: 0.01em;
    }

    .registration-shell .filter-dropdown-menu,
    .registration-shell [x-show] .bg-white.border.border-gray-300.rounded-lg.shadow-lg,
    .registration-shell [x-show] .bg-white.border.border-gray-300.rounded.shadow-lg {
        border-radius: 0 !important;
        border-color: rgba(203, 213, 225, 0.9) !important;
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.09) !important;
    }

    .registration-modal-shell {
        width: 100%;
        max-width: 52rem;
        max-height: min(90vh, 980px);
        overflow: hidden;
        border-radius: 30px;
        border: 1px solid rgba(203, 213, 225, 0.92);
        background: #ffffff;
        box-shadow: 0 34px 70px rgba(15, 23, 42, 0.24);
    }

    .registration-modal-header {
        position: relative;
        overflow: hidden;
        padding: 24px 28px;
        border-bottom: 1px solid rgba(203, 213, 225, 0.88);
        background:
            radial-gradient(circle at top right, rgba(255, 255, 255, 0.18), transparent 34%),
            linear-gradient(135deg, rgba(20, 48, 88, 0.98) 0%, rgba(37, 82, 145, 0.96) 58%, rgba(16, 116, 95, 0.92) 100%);
        color: #ffffff;
    }

    .registration-modal-header::after {
        content: "";
        position: absolute;
        width: 240px;
        height: 240px;
        right: -80px;
        top: -120px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(252, 209, 22, 0.18) 0%, rgba(252, 209, 22, 0) 70%);
        pointer-events: none;
    }

    .registration-modal-header-content {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }

    .registration-modal-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 5px 12px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.16);
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.73rem;
        font-weight: 700;
        letter-spacing: 0.15em;
        text-transform: uppercase;
    }

    .registration-modal-title {
        margin: 16px 0 0;
        font-size: clamp(1.65rem, 3vw, 2.1rem);
        line-height: 1.05;
        letter-spacing: -0.04em;
        color: #ffffff;
    }

    .registration-modal-subtitle {
        margin: 10px 0 0;
        max-width: 640px;
        color: rgba(255, 255, 255, 0.82);
        font-size: 0.92rem;
        line-height: 1.7;
    }

    .registration-modal-close {
        display: inline-flex;
        width: 40px;
        height: 40px;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.16);
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.82);
        font-size: 1.4rem;
        line-height: 1;
        transition: background 160ms ease, color 160ms ease, transform 160ms ease;
    }

    .registration-modal-close:hover {
        background: rgba(255, 255, 255, 0.14);
        color: #ffffff;
        transform: translateY(-1px);
    }

    .registration-modal-body {
        background:
            linear-gradient(180deg, rgba(248, 250, 252, 0.85) 0%, rgba(241, 245, 249, 0.72) 100%);
        padding: 24px 28px 28px;
        overflow-y: auto;
        max-height: calc(min(90vh, 980px) - 122px);
    }

    .registration-modal-panel {
        border: 1px solid rgba(203, 213, 225, 0.88);
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 18px 32px rgba(15, 23, 42, 0.06);
    }

    .registration-modal-note {
        display: grid;
        grid-template-columns: 44px minmax(0, 1fr);
        gap: 14px;
        align-items: flex-start;
        padding: 18px 20px;
        border-radius: 20px;
        border: 1px solid rgba(147, 197, 253, 0.6);
        background: linear-gradient(135deg, rgba(239, 246, 255, 0.98) 0%, rgba(226, 239, 255, 0.9) 100%);
        color: #1e3a8a;
    }

    .registration-modal-note-icon {
        display: inline-flex;
        width: 44px;
        height: 44px;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        background: rgba(59, 130, 246, 0.12);
        color: #2563eb;
        font-size: 1rem;
    }

    .registration-modal-note strong {
        display: block;
        margin-bottom: 4px;
        font-size: 0.95rem;
    }

    .registration-modal-note p {
        margin: 0;
        font-size: 0.9rem;
        line-height: 1.7;
        color: #274690;
    }

    .registration-dropzone {
        border: 2px dashed rgba(148, 163, 184, 0.5);
        border-radius: 24px;
        padding: 38px 24px;
        text-align: center;
        background:
            radial-gradient(circle at top, rgba(59, 130, 246, 0.08), transparent 46%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.98) 100%);
        transition: border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
    }

    .registration-dropzone:hover {
        border-color: rgba(59, 130, 246, 0.52);
        box-shadow: 0 18px 30px rgba(59, 130, 246, 0.08);
        transform: translateY(-1px);
    }

    .registration-dropzone-icon {
        display: inline-flex;
        width: 70px;
        height: 70px;
        align-items: center;
        justify-content: center;
        border-radius: 24px;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.14) 0%, rgba(37, 99, 235, 0.2) 100%);
        color: #2563eb;
        font-size: 1.7rem;
        margin-bottom: 16px;
    }

    .registration-modal-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .registration-modal-stat {
        padding: 16px 14px;
        border-radius: 20px;
        border: 1px solid rgba(203, 213, 225, 0.72);
        background: rgba(255, 255, 255, 0.96);
        text-align: center;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }

    .registration-modal-stat-value {
        font-size: 1.85rem;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -0.04em;
    }

    .registration-modal-stat-label {
        margin-top: 8px;
        font-size: 0.73rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .registration-modal-actions {
        display: flex;
        gap: 14px;
        padding-top: 18px;
        border-top: 1px solid rgba(226, 232, 240, 0.92);
    }

    .registration-modal-actions > * {
        flex: 1 1 0;
    }

    .registration-modal-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 50px;
        padding: 0 18px;
        border-radius: 18px;
        font-weight: 700;
        transition: transform 160ms ease, box-shadow 160ms ease, background 160ms ease, color 160ms ease;
    }

    .registration-modal-button:hover {
        transform: translateY(-1px);
    }

    .registration-modal-button:disabled {
        transform: none;
    }

    .registration-modal-button-primary {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        box-shadow: 0 16px 28px rgba(37, 99, 235, 0.22);
    }

    .registration-modal-button-secondary {
        background: #e2e8f0;
        color: #1e293b;
    }

    .registration-modal-button-muted {
        background: #64748b;
        color: #ffffff;
    }

    .registration-modal-button-success {
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        color: #ffffff;
        box-shadow: 0 16px 28px rgba(34, 197, 94, 0.2);
    }

    .registration-modal-button-warn {
        background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
        color: #ffffff;
        box-shadow: 0 16px 28px rgba(249, 115, 22, 0.18);
    }

    @media (max-width: 768px) {
        .registration-modal-shell {
            border-radius: 24px;
        }

        .registration-modal-header,
        .registration-modal-body {
            padding-left: 20px;
            padding-right: 20px;
        }

        .registration-modal-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .registration-modal-actions {
            flex-direction: column;
        }
    }

    .registration-shell table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .registration-table-card {
        overflow: hidden;
    }

    .registration-table-card thead {
        background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%) !important;
    }

    .registration-table-card thead th {
        padding-top: 16px !important;
        padding-bottom: 16px !important;
        color: #334155 !important;
        font-size: 0.76rem !important;
        font-weight: 800 !important;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        border-bottom: 1px solid rgba(203, 213, 225, 0.9);
    }

    .registration-table-card tbody tr {
        transition: background-color 0.2s ease, transform 0.2s ease;
    }

    .registration-table-card tbody tr:hover {
        background: rgba(239, 246, 255, 0.9) !important;
    }

    .registration-table-card tbody td {
        padding-top: 18px !important;
        padding-bottom: 18px !important;
        border-color: rgba(226, 232, 240, 0.9) !important;
        color: #334155 !important;
        font-size: 0.94rem;
    }

    .registration-status-pill,
    .registration-shell .bg-green-100.text-green-800,
    .registration-shell .bg-blue-100.text-blue-800,
    .registration-shell .bg-orange-100.text-orange-800 {
        border-radius: 999px !important;
        padding: 0.35rem 0.8rem !important;
        font-weight: 700 !important;
        font-size: 0.75rem !important;
        display: inline-flex;
        align-items: center;
    }

    .registration-bulk-bar {
        margin-top: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        border-radius: 18px;
        background: linear-gradient(180deg, rgba(239, 246, 255, 0.96) 0%, rgba(219, 234, 254, 0.9) 100%);
        border: 1px solid rgba(147, 197, 253, 0.55);
    }

    .registration-shell .bg-blue-600,
    .registration-shell .hover\:bg-blue-700:hover {
        background-color: #2d62d6 !important;
    }

    .registration-shell .bg-green-600,
    .registration-shell .hover\:bg-green-700:hover {
        background-color: #1b9448 !important;
    }

    .registration-shell .bg-red-600,
    .registration-shell .hover\:bg-red-700:hover {
        background-color: #cc3a3a !important;
    }

    .registration-shell .rounded-lg {
        border-radius: 18px !important;
    }

    .registration-shell .shadow,
    .registration-shell .shadow-lg,
    .registration-shell .shadow-xl,
    .registration-shell .shadow-2xl {
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.07) !important;
    }

    .registration-mini-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    .registration-empty {
        padding: 28px 18px;
        text-align: center;
        color: #64748b;
        background: #f8fafc;
        border: 1px dashed rgba(191, 219, 254, 0.95);
        border-radius: 18px;
    }

    @media (max-width: 1100px) {
        .registration-page-header-grid,
        .registration-mini-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 720px) {
        .registration-shell {
            padding: 18px 16px 0;
        }

        .registration-page-header,
        .registration-toolbar-card,
        .registration-surface {
            border-radius: 22px;
        }

        .registration-page-header {
            padding: 22px 20px;
        }

        .registration-toolbar-card {
            padding: 18px;
        }

        .registration-page-highlights {
            display: grid;
        }
    }
</style>
