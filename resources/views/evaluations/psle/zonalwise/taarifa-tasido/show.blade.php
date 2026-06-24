<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $inputs['report_title'] ?? 'TAARIFA MOCK DRS VII 2026 TASIDO' }} - Hakiki</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --accent: #2563eb;
            --accent-hover: #1d4ed8;
            --success: #16a34a;
            --warning: #ca8a04;
            --danger: #dc2626;
            --bg-gradient: linear-gradient(135deg, #f1f5f9 0%, #cbd5e1 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.6);
            --shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.15);
            --transition: all 0.25s ease-in-out;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            color: #334155;
            min-height: 100vh;
            padding: 20px;
        }

        /* Layout Container */
        .workspace {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 20px;
            max-width: 1680px;
            margin: 0 auto;
            height: calc(100vh - 40px);
        }

        /* Glass Panel */
        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Sidebar Control Center */
        .sidebar {
            height: 100%;
        }

        .panel-header {
            padding: 24px;
            background: linear-gradient(135deg, var(--primary) 0%, #1e293b 100%);
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .panel-header h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-header p {
            font-size: 0.78rem;
            opacity: 0.75;
            margin-top: 4px;
        }

        .panel-body {
            padding: 20px;
            overflow-y: auto;
            flex-grow: 1;
        }

        .section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 800;
            color: #475569;
            margin-bottom: 14px;
            margin-top: 18px;
            letter-spacing: 1.2px;
            border-left: 3.5px solid var(--accent);
            padding-left: 8px;
        }
        
        .section-title:first-of-type {
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            margin-bottom: 6px;
            color: #475569;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.85rem;
            color: #1e293b;
            background: white;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .row-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .panel-footer {
            padding: 20px;
            border-top: 1px solid rgba(0,0,0,0.06);
            background: rgba(255,255,255,0.7);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.88rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border: none;
            gap: 8px;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-1px);
        }

        .btn-success {
            background: var(--success);
            color: white;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2);
        }

        .btn-success:hover {
            background: #15803d;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: white;
            color: #475569;
            border: 1.5px solid #cbd5e1;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            color: #1e293b;
        }

        /* Preview Area Container */
        .preview-panel {
            height: 100%;
        }

        .preview-body {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
            background: #e2e8f0;
            display: flex;
            flex-direction: column;
            gap: 30px;
            align-items: center;
        }

        /* Standardized PDF Page dimensions in preview */
        .document-page {
            width: 210mm;
            min-height: 297mm;
            box-sizing: border-box;
            padding: 25.4mm 10mm 25.4mm 10mm;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(15,23,42,0.12);
            border-radius: 6px;
            position: relative;
            color: #1e293b;
            overflow: visible;
            flex-shrink: 0;
            margin-bottom: 30px;
        }

        .document-page.landscape {
            width: 297mm;
            min-height: 210mm;
            box-sizing: border-box;
            padding: 25.4mm 10mm 25.4mm 10mm;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(15,23,42,0.12);
            border-radius: 6px;
            position: relative;
            color: #1e293b;
            overflow: visible;
            flex-shrink: 0;
            margin-bottom: 30px;
        }

        .preview-page-wrapper {
            width: 210mm;
            height: 297mm;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(15,23,42,0.12);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 30px;
            box-sizing: border-box;
            flex-shrink: 0;
        }

        .taarifa-cover-page {
            position: relative;
            width: 210mm;
            height: 297mm;
            background: #ffffff;
            color: #000000;
            font-family: var(--taarifa-font, 'Times New Roman', Times, serif);
            page-break-after: always;
            overflow: hidden;
            box-sizing: border-box;
        }

        .cover-emblem {
            position: absolute;
            top: 32mm;
            left: 0;
            right: 0;
            text-align: center;
        }

        .cover-emblem img {
            width: 28mm;
            height: auto;
        }

        .cover-emblem-missing {
            position: absolute;
            top: 32mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9pt;
            font-weight: 700;
            color: #b91c1c;
        }

        .cover-government-heading {
            position: absolute;
            top: 70mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 11pt;
            font-weight: 700;
            line-height: 1.25;
            text-transform: uppercase;
            color: #000000;
        }

        .cover-blue-line {
            position: absolute;
            top: 127mm;
            left: 30mm;
            right: 30mm;
            border-top: 1px solid #1f5fd1;
        }

        .cover-report-title {
            position: absolute;
            top: 143mm;
            left: 25mm;
            right: 25mm;
            text-align: center;
            font-size: 11pt;
            font-weight: 700;
            line-height: 1.25;
            text-transform: uppercase;
            color: #000000;
        }

        .cover-subtitle {
            position: absolute;
            top: 164mm;
            left: 25mm;
            right: 25mm;
            text-align: center;
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #000000;
        }

        .cover-footer-left {
            position: absolute;
            left: 32mm;
            bottom: 45mm;
            font-size: 9pt;
            font-weight: 700;
            line-height: 1.25;
            text-transform: uppercase;
            color: #000000;
            text-align: left;
        }

        .cover-footer-right {
            position: absolute;
            right: 35mm;
            bottom: 45mm;
            font-size: 9pt;
            font-weight: 700;
            text-transform: uppercase;
            color: #000000;
            text-align: right;
        }


        /* Document Typography */
        .doc-section-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #000;
            border-bottom: 1.5px solid #000;
            padding-bottom: 4px;
            margin-top: 30px;
            margin-bottom: 14px;
            text-transform: uppercase;
        }

        .doc-subsection-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
            margin-top: 20px;
            margin-bottom: 8px;
        }

        .doc-paragraph {
            font-size: 0.92rem;
            line-height: 1.6;
            margin-bottom: 12px;
            color: #1e293b;
            text-align: justify;
        }

        .doc-list {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        .doc-list li {
            margin-bottom: 8px;
            font-size: 0.92rem;
            line-height: 1.5;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
            margin: 15px 0;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
        }

        .doc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.78rem;
            color: #1e293b;
        }

        .doc-table th, .doc-table td {
            padding: 8px 10px;
            border: 1px solid #94a3b8;
            vertical-align: middle;
        }

        .doc-table th {
            background: #f1f5f9;
            font-weight: 700;
            color: #000;
            text-align: center;
        }

        .attendance-table thead tr:first-child th {
            background: #e2e8f0;
        }

        .attendance-table thead tr:nth-child(2) th {
            background: #f1f5f9;
        }

        .doc-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .doc-table tr.total-row {
            background: #e2e8f0;
            font-weight: 700;
            color: #000;
        }

        /* Sign-offs */
        .signoff-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #cbd5e1;
        }

        .signoff-block h5 {
            font-size: 0.92rem;
            font-weight: 700;
            margin-bottom: 40px;
            color: #000;
        }

        .signoff-block p {
            font-size: 0.88rem;
            line-height: 1.45;
        }

        .signoff-block .line {
            border-bottom: 1.5px solid #64748b;
            width: 220px;
            margin-bottom: 6px;
        }

        /* Data Quality Alerts */
        .dq-alert {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 20px;
            color: #991b1b;
        }

        .dq-alert h4 {
            font-size: 0.95rem;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .dq-alert ul {
            margin-left: 20px;
            font-size: 0.85rem;
        }

        .dq-alert li {
            margin-bottom: 4px;
        }

        .dq-success {
            background: #f0fdf4;
            border: 1px solid #dcfce7;
            color: #15803d;
            border-radius: 10px;
            padding: 14px;
            font-size: 0.9rem;
            margin-bottom: 20px;
            font-weight: 600;
        }

        /* Toast notifications */
        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            padding: 14px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 700;
            font-size: 0.88rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            transform: translateY(-20px);
            opacity: 0;
        }

        @media (max-width: 1024px) {
            .workspace {
                grid-template-columns: 1fr;
                height: auto;
            }
            .sidebar {
                height: auto;
            }
        }
    </style>
</head>
<body>

<div id="toast-container"></div>

@if(!$isAdmin)
<div class="top-nav-bar" style="max-width: 1680px; margin: 0 auto 20px auto; display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); padding: 15px 30px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.6); box-shadow: 0 10px 30px -10px rgba(0,0,0,0.1);">
    <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 1.3rem;">📊</span>
        <span style="font-weight: 700; font-family: 'Outfit', sans-serif; color: #0f172a; font-size: 1.1rem; letter-spacing: 0.5px;">Ripoti ya TASIDO Mock Darasa la Saba 2026</span>
    </div>
    <div style="display: flex; gap: 12px; align-items: center;">
        <a href="/evaluations/psle/zonalwise/taarifa-tasido/pdf" class="btn btn-primary" style="padding: 10px 20px; font-size: 0.88rem; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px;">
            📥 Pakua PDF
        </a>
        <a href="/evaluations/psle/zonalwise" class="btn btn-secondary" style="padding: 10px 20px; font-size: 0.88rem; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px; background: transparent;">
            ← Rudi Kwenye Orodha
        </a>
        <a href="/login" class="btn" style="padding: 10px 20px; font-size: 0.88rem; border-radius: 12px; background: #0f172a; color: white; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(15,23,42,0.15);">
            🔐 Ingia kama Admin
        </a>
    </div>
</div>
@endif

<div class="workspace" style="{{ !$isAdmin ? 'grid-template-columns: 1fr; height: auto;' : '' }}">
    
    @if($isAdmin)
    <!-- Left Sidebar: Settings overrides -->
    <div class="glass-panel sidebar">
        <div class="panel-header">
            <h2>⚙️ Control Panel</h2>
            <p style="font-size: 0.78rem; color: #22c55e; font-weight: 600; display: flex; align-items: center; gap: 4px; margin-top: 4px;">● Umeingia kama Admin</p>
        </div>
        
        <form id="settingsForm" method="POST" action="/evaluations/psle/zonalwise/taarifa-tasido/save-settings" class="panel-body">
            @csrf
            
            <div class="section-title">Maelezo ya Jalada (Cover)</div>
            
            <div class="form-group">
                <label for="report_title">Report Title (Menu/Tab)</label>
                <input type="text" id="report_title" name="report_title" class="form-control sync-input" value="{{ $inputs['report_title'] ?? 'TAARIFA MOCK DRS VII 2026 TASIDO' }}">
            </div>
            
            <div class="form-group">
                <label for="cover_title">Cover Title (Jalada)</label>
                <input type="text" id="cover_title" name="cover_title" class="form-control sync-input" value="{{ $inputs['cover_title'] ?? 'TAARIFA YA MTIHANI WA UTAMILIFU DARASA LA SABA MWAKA 2026 TASIDO' }}">
            </div>
            
            <div class="form-group">
                <label for="subtitle">Subtitle (Mikoa)</label>
                <input type="text" id="subtitle" name="subtitle" class="form-control sync-input" value="{{ $inputs['subtitle'] ?? 'TABORA, SINGIDA, IRINGA NA DODOMA' }}">
            </div>
            
            <div class="form-group">
                <label for="office_heading">Office Heading</label>
                <input type="text" id="office_heading" name="office_heading" class="form-control sync-input" value="{{ $inputs['office_heading'] ?? 'OFISI YA WAZIRI MKUU / TAWALA ZA MIKOA NA SERIKALI ZA MITAA' }}">
            </div>

            <div class="form-group">
                <label for="secretariat">Secretariat / Location</label>
                <input type="text" id="secretariat" name="secretariat" class="form-control sync-input" value="{{ $inputs['secretariat'] ?? 'SEKRETARIETI YA KANDA, / TASIDO / DODOMA / JUNI, 2026' }}">
            </div>

            <div class="form-group">
                <label for="exam_dates">Tarehe za Mtihani</label>
                <input type="text" id="exam_dates" name="exam_dates" class="form-control sync-input" value="{{ $inputs['exam_dates'] ?? '20/05/2026 na 21/05/2026' }}">
            </div>

            <div class="section-title">Ukurasa wa Layout & Font</div>

            <div class="row-grid">
                <div class="form-group">
                    <label for="font_family">Font Family</label>
                    <select id="font_family" name="font_family" class="form-control">
                        <option value="default" {{ ($inputs['font_family'] ?? 'default') === 'default' ? 'selected' : '' }}>Default</option>
                        <option value="times new roman" {{ ($inputs['font_family'] ?? '') === 'times new roman' ? 'selected' : '' }}>Times New Roman</option>
                        <option value="arial narrow" {{ ($inputs['font_family'] ?? '') === 'arial narrow' ? 'selected' : '' }}>Arial Narrow</option>
                        <option value="maiandra gd" {{ ($inputs['font_family'] ?? '') === 'maiandra gd' ? 'selected' : '' }}>Maiandra GD</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="show_logo">Show Emblem</label>
                    <select id="show_logo" name="show_logo" class="form-control">
                        <option value="1" {{ ($inputs['show_logo'] ?? '1') == '1' ? 'selected' : '' }}>Ndiyo (Yes)</option>
                        <option value="0" {{ ($inputs['show_logo'] ?? '') == '0' ? 'selected' : '' }}>Hapana (No)</option>
                    </select>
                </div>
            </div>

            <div class="section-title">Viongozi na Wasimamizi</div>
            
            <div class="form-group">
                <label for="reo_name">Afisa Elimu Mkoa (REO)</label>
                <input type="text" id="reo_name" name="reo_name" class="form-control sync-input" value="{{ $inputs['reo_name'] ?? '' }}">
            </div>
            
            <div class="form-group">
                <label for="rto_name">Afisa Taaluma Mkoa (RTO)</label>
                <input type="text" id="rto_name" name="rto_name" class="form-control sync-input" value="{{ $inputs['rto_name'] ?? '' }}">
            </div>
            
            <div class="form-group">
                <label for="rso_name">Afisa Usalama (RSO)</label>
                <input type="text" id="rso_name" name="rso_name" class="form-control sync-input" value="{{ $inputs['rso_name'] ?? '' }}">
            </div>
            
            <div class="form-group">
                <label for="exam_coordinator_name">Mratibu Ndg. (Taaluma)</label>
                <input type="text" id="exam_coordinator_name" name="exam_coordinator_name" class="form-control sync-input" value="{{ $inputs['exam_coordinator_name'] ?? '' }}">
            </div>

            <div class="section-title">Kituo & Moderation</div>
            
            <div class="form-group">
                <label for="marking_center">Kituo cha Usahihishaji</label>
                <input type="text" id="marking_center" name="marking_center" class="form-control sync-input" value="{{ $inputs['marking_center'] ?? '' }}">
            </div>
            
            <div class="form-group">
                <label for="moderation_region">Mkoa wa Moderation</label>
                <input type="text" id="moderation_region" name="moderation_region" class="form-control sync-input" value="{{ $inputs['moderation_region'] ?? '' }}">
            </div>

            <div class="section-title">Takwimu za Uzalishaji</div>
            
            <div class="row-grid">
                <div class="form-group">
                    <label for="production_days">Siku za Chapa</label>
                    <input type="number" id="production_days" name="production_days" class="form-control sync-input" value="{{ $inputs['production_days'] ?? 0 }}">
                </div>
                <div class="form-group">
                    <label for="marking_days">Siku Usahihishaji</label>
                    <input type="number" id="marking_days" name="marking_days" class="form-control sync-input" value="{{ $inputs['marking_days'] ?? 0 }}">
                </div>
            </div>

            <div class="row-grid">
                <div class="form-group">
                    <label for="markers_count">Wasahihishaji</label>
                    <input type="number" id="markers_count" name="markers_count" class="form-control sync-input" value="{{ $inputs['markers_count'] ?? 0 }}">
                </div>
                <div class="form-group">
                    <label for="students_assistants_count">Wasaidizi</label>
                    <input type="number" id="students_assistants_count" name="students_assistants_count" class="form-control sync-input" value="{{ $inputs['students_assistants_count'] ?? 0 }}">
                </div>
            </div>

            <div class="form-group">
                <label for="budget_amount">Bajeti ya Uendeshaji (Tsh)</label>
                <input type="number" id="budget_amount" name="budget_amount" class="form-control sync-input" value="{{ $inputs['budget_amount'] ?? 0 }}">
            </div>

            <div class="row-grid">
                <div class="form-group">
                    <label for="risso_machine_count">Idadi Mashine RISSO</label>
                    <input type="number" id="risso_machine_count" name="risso_machine_count" class="form-control sync-input" value="{{ $inputs['risso_machine_count'] ?? 0 }}">
                </div>
                <div class="form-group">
                    <label for="risso_machine_value">Thamani Mashine (Tsh)</label>
                    <input type="number" id="risso_machine_value" name="risso_machine_value" class="form-control sync-input" value="{{ $inputs['risso_machine_value'] ?? 0 }}">
                </div>
            </div>

            <div class="section-title">Ushirikiano & Uthibitisho</div>
            
            <div class="form-group">
                <label for="collaborating_regions">Mikoa Shiriki (Kihistoria)</label>
                <input type="text" id="collaborating_regions" name="collaborating_regions" class="form-control sync-input" value="{{ $inputs['collaborating_regions'] ?? '' }}">
            </div>
            
            <div class="form-group">
                <label for="prepared_by_title">Cheo cha Mtayarishaji</label>
                <input type="text" id="prepared_by_title" name="prepared_by_title" class="form-control sync-input" value="{{ $inputs['prepared_by_title'] ?? 'AFISA TAALUMA MKOA (RTO)' }}">
            </div>
            
            <div class="form-group">
                <label for="approved_by_title">Cheo cha Mthibitishaji</label>
                <input type="text" id="approved_by_title" name="approved_by_title" class="form-control sync-input" value="{{ $inputs['approved_by_title'] ?? 'AFISA ELIMU MKOA (REO)' }}">
            </div>
            
        </form>
        
        <div class="panel-footer">
            <button type="submit" form="settingsForm" class="btn btn-success">
                💾 Hifadhi Mipangilio (Save Settings)
            </button>
            <button id="downloadPdfBtn" type="button" class="btn btn-primary">
                📥 Pakua Taarifa (PDF)
            </button>
            <a href="/evaluations/psle/zonalwise" class="btn btn-secondary">
                ← Rudi Zonal Workspace
            </a>
        </div>
    </div>
    @endif
    
    <!-- Right Preview Area -->
    <div class="glass-panel preview-panel">
        <div class="panel-header" style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);">
            <h2>📄 Hakiki ya Taarifa ya Mtihani (Live PDF Preview)</h2>
            <p>Toleo la Kielektroniki la Ripoti ya Kanda ya TASIDO</p>
        </div>
        
        <div class="preview-body" id="preview-container">
            @if(!empty($warnings))
                <div style="width: 210mm; margin: 0 auto 20px auto; padding: 15px 20px; background-color: #fef2f2; border: 1.5px solid #fecaca; border-radius: 8px; color: #991b1b; font-family: 'Outfit', sans-serif; box-sizing: border-box; font-size: 0.9rem;">
                    <h4 style="margin: 0 0 6px 0; font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        <span>⚠️</span> Taarifa Muhimu (Important Notices / Readiness Warning)
                    </h4>
                    <ul style="margin: 0; padding-left: 20px; line-height: 1.5;">
                        @foreach($warnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <!-- Page 1: COVER -->
            <div class="preview-page-wrapper">
                <section class="taarifa-cover-page">
                    <!-- Tanzania Flag Border SVG -->
                    <svg class="cover-flag-border" width="100%" height="100%" viewBox="0 0 210 297" preserveAspectRatio="none" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1;">
                        <rect x="8.0" y="8.0" width="194.0" height="281.0" fill="none" stroke="rgb(30, 135, 63)" stroke-width="0.35" />
                        <rect x="9.2" y="9.2" width="191.6" height="278.6" fill="none" stroke="rgb(252, 209, 22)" stroke-width="0.25" />
                        <rect x="10.4" y="10.4" width="189.2" height="276.2" fill="none" stroke="rgb(0, 0, 0)" stroke-width="0.25" />
                        <rect x="11.6" y="11.6" width="186.8" height="273.8" fill="none" stroke="rgb(252, 209, 22)" stroke-width="0.25" />
                        <rect x="12.8" y="12.8" width="184.4" height="271.4" fill="none" stroke="rgb(0, 163, 224)" stroke-width="0.35" />
                    </svg>

                    @if(!empty($emblemUrl))
                        <div class="cover-emblem">
                            <img src="{{ $emblemUrl }}" alt="Government Emblem">
                        </div>
                    @else
                        <div class="cover-emblem-missing">
                            GOVERNMENT EMBLEM NOT CONFIGURED
                        </div>
                    @endif

                    <div class="cover-government-heading">
                        <div>JAMHURI YA MUUNGANO WA TANZANIA</div>
                        <div>OFISI YA WAZIRI MKUU</div>
                        <div>TAWALA ZA MIKOA NA SERIKALI ZA MITAA</div>
                    </div>

                    <div class="cover-blue-line"></div>

                    <div class="cover-report-title">
                        <div>TAARIFA YA MTIHANI WA UTAMILIFU DARASA LA SABA</div>
                        <div>MWAKA 2026 TASIDO</div>
                    </div>

                    <div class="cover-subtitle">
                        (TABORA, SINGIDA, IRINGA NA DODOMA)
                    </div>

                    <div class="cover-footer-left">
                        <div>SEKRETARIETI YA KANDA,</div>
                        <div>TASIDO</div>
                        <div>DODOMA</div>
                    </div>

                    <div class="cover-footer-right">
                        JUNI, 2026
                    </div>
                </section>
            </div>


                

                <!-- Page 2: Table of Contents -->

            
            <!-- Page 2: Utangulizi & Uchambuzi wa Matokeo -->
            <div class="document-page" style="font-family: 'Times New Roman', Times, serif;">
                <div style="text-align: center; margin-bottom: 25px;">
                    <h2 style="font-size: 1.15rem; font-weight: 800; color: #000; text-transform: uppercase; line-height: 1.4; margin: 0;">TAARIFA YA TATHIMINI YA MATOKEO YA MTIHANI WA MOCK DARASA LA VII MWAKA 2026 TASIDO</h2>
                </div>
                
                <div style="font-size: 1.05rem; font-weight: 800; color: #000; margin-bottom: 12px; text-transform: uppercase;">1.0 UTANGULIZI</div>
                <div class="doc-paragraph">
                    {!! nl2br(e($data['narratives']['introduction'])) !!}
                </div>
                
                <div style="font-size: 1.05rem; font-weight: 800; color: #000; margin-top: 15px; margin-bottom: 12px; text-transform: uppercase;">2.0 UCHAMBUZI WA MATOKEO NA TAKWIMU ZA WATAHINIWA</div>
                <div class="doc-paragraph">
                    {!! nl2br(e($data['narratives']['taarifa_za_watahiniwa'])) !!}
                </div>

                <div class="doc-subsection-title" style="margin-top: 15px;">Jedwali Na. 1: Watahiniwa waliosajiliwa na waliofanya Mtihani</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th rowspan="2">S/N</th>
                                <th rowspan="2">Mkoa</th>
                                <th rowspan="2">Idadi ya Shule</th>
                                <th colspan="3">WALIOSAJILIWA</th>
                                <th colspan="3">WALIOFANYA</th>
                                <th rowspan="2">Mahudhurio %</th>
                            </tr>
                            <tr>
                                <th>ME</th>
                                <th>KE</th>
                                <th>JUMLA</th>
                                <th>ME</th>
                                <th>KE</th>
                                <th>JUMLA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['table1'] as $row)
                                <tr>
                                    <td style="text-align: center;">{{ $row['sn'] }}</td>
                                    <td style="font-weight: 700;">{{ $row['region'] }}</td>
                                    <td style="text-align: center;">{{ number_format($row['schools_count']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['registered_m']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['registered_f']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['registered_t']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['sat_m']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['sat_f']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['sat_t']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['sat_pct'], 2) }}%</td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td style="text-align: center;">-</td>
                                <td>{{ $data['table1_total']['region'] }}</td>
                                <td style="text-align: center;">{{ number_format($data['table1_total']['schools_count']) }}</td>
                                <td style="text-align: center;">{{ number_format($data['table1_total']['registered_m']) }}</td>
                                <td style="text-align: center;">{{ number_format($data['table1_total']['registered_f']) }}</td>
                                <td style="text-align: center;">{{ number_format($data['table1_total']['registered_t']) }}</td>
                                <td style="text-align: center;">{{ number_format($data['table1_total']['sat_m']) }}</td>
                                <td style="text-align: center;">{{ number_format($data['table1_total']['sat_f']) }}</td>
                                <td style="text-align: center;">{{ number_format($data['table1_total']['sat_t']) }}</td>
                                <td style="text-align: center;">{{ number_format($data['table1_total']['sat_pct'], 2) }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="doc-subsection-title" style="margin-top: 15px;">Jedwali na. 2: Watahiniwa wasiofanya mtihani</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th rowspan="2">S/N</th>
                                <th rowspan="2">Mkoa</th>
                                <th rowspan="2">Idadi ya Shule</th>
                                <th colspan="3">WALIOSAJILIWA</th>
                                <th colspan="3">WASIOFANYA MTIHANI</th>
                                <th rowspan="2">Asilimia (%)</th>
                            </tr>
                            <tr>
                                <th>ME</th>
                                <th>KE</th>
                                <th>JUMLA</th>
                                <th>ME</th>
                                <th>KE</th>
                                <th>JUMLA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['table2'] as $row)
                                <tr>
                                    <td style="text-align: center;">{{ $row['sn'] }}</td>
                                    <td style="font-weight: 700;">{{ $row['region'] }}</td>
                                    <td style="text-align: center;">{{ number_format($row['schools_count']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['registered_m']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['registered_f']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['registered_t']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['absent_m']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['absent_f']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['absent_t']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['absent_pct'], 2) }}%</td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td style="text-align: center;">-</td>
                                <td>{{ $data['table2_total']['region'] }}</td>
                                <td style="text-align: center;">{{ number_format($data['table2_total']['schools_count']) }}</td>
                                <td style="text-align: center;">{{ number_format($data['table2_total']['registered_m']) }}</td>
                                <td style="text-align: center;">{{ number_format($data['table2_total']['registered_f']) }}</td>
                                <td style="text-align: center;">{{ number_format($data['table2_total']['registered_t']) }}</td>
                                <td style="text-align: center;">{{ number_format($data['table2_total']['absent_m']) }}</td>
                                <td style="text-align: center;">{{ number_format($data['table2_total']['absent_f']) }}</td>
                                <td style="text-align: center;">{{ number_format($data['table2_total']['absent_t']) }}</td>
                                <td style="text-align: center;">{{ number_format($data['table2_total']['absent_pct'], 2) }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 3: Hali ya ufaulu ngazi ya Kanda na Jedwali 3a, 3b -->
            <div class="document-page" style="font-family: 'Times New Roman', Times, serif;">
                <div style="font-size: 1.05rem; font-weight: 800; color: #000; margin-bottom: 12px; text-transform: uppercase;">3.0 HALI YA UFAULU KIKANDA NA KWA HALMASHAURI</div>
                <div style="font-size: 0.95rem; font-weight: 800; color: #000; margin-bottom: 8px; text-transform: uppercase;">3.1 Hali ya ufaulu ngazi ya Kanda</div>
                <div class="doc-paragraph" style="margin-bottom: 15px;">
                    {!! nl2br(e($data['narratives']['hali_ya_ufaulu_kanda'])) !!}
                </div>

                <div class="doc-subsection-title">Jedwali Na. 3a: Hali ya Ufaulu Kikanda kwa Madaraja - Shule za Serikali na Binafsi kwa Wastani wa Ufaulu</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>NA</th>
                                <th>Mkoa</th>
                                <th>Daraja A</th>
                                <th>Daraja B</th>
                                <th>Daraja C</th>
                                <th>Ufaulu A-C</th>
                                <th>Ufaulu %</th>
                                <th>D-E</th>
                                <th>Feli %</th>
                                <th>Wastani /300</th>
                                <th>Nafasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['table3a'] as $row)
                                <tr>
                                    <td style="text-align: center;">{{ $row['position'] }}</td>
                                    <td style="font-weight: 700;">{{ $row['region'] }}</td>
                                    <td style="text-align: center;">{{ number_format($row['a']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['b']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['c']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['pass_ac']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['pass_pct'], 2) }}%</td>
                                    <td style="text-align: center;">{{ number_format($row['fail_de']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['fail_pct'], 2) }}%</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['average_marks'], 2) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ $row['position'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="doc-subsection-title" style="margin-top: 30px;">Jedwali Na. 3b: Hali ya Ufaulu Kikanda kwa Madaraja - Shule za Serikali na Binafsi kwa Asilimia ya Ufaulu</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>NA</th>
                                <th>Mkoa</th>
                                <th>Daraja A</th>
                                <th>Daraja B</th>
                                <th>Daraja C</th>
                                <th>Ufaulu A-C</th>
                                <th>Ufaulu %</th>
                                <th>D-E</th>
                                <th>Feli %</th>
                                <th>Wastani /300</th>
                                <th>Nafasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['table3b'] as $row)
                                <tr>
                                    <td style="text-align: center;">{{ $row['position'] }}</td>
                                    <td style="font-weight: 700;">{{ $row['region'] }}</td>
                                    <td style="text-align: center;">{{ number_format($row['a']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['b']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['c']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['pass_ac']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['pass_pct'], 2) }}%</td>
                                    <td style="text-align: center;">{{ number_format($row['fail_de']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['fail_pct'], 2) }}%</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['average_marks'], 2) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ $row['position'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 6: Jedwali Na. 4 na 5 -->
            <div class="document-page" style="font-family: 'Times New Roman', Times, serif;">
                <div class="doc-subsection-title">Jedwali Na. 4: Ufaulu Kikanda kwa Madaraja - Shule za Serikali</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Mkoa</th>
                                <th>Idadi Shule</th>
                                <th>A</th>
                                <th>B</th>
                                <th>C</th>
                                <th>A-C JML</th>
                                <th>A-C %</th>
                                <th>D</th>
                                <th>E</th>
                                <th>D-E JML</th>
                                <th>D-E %</th>
                                <th>Wastani</th>
                                <th>Umahiri</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['table4'] as $row)
                                <tr>
                                    <td style="text-align: center;">{{ $row['sn'] }}</td>
                                    <td style="font-weight: 700;">{{ $row['region'] }}</td>
                                    <td style="text-align: center;">{{ number_format($row['schools_count']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['a']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['b']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['c']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['pass_ac']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['pass_pct'], 2) }}%</td>
                                    <td style="text-align: center;">{{ number_format($row['d']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['e']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['fail_de']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['fail_pct'], 2) }}%</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['average_marks'], 2) }}</td>
                                    <td style="text-align: center;">{{ $row['competence'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="doc-subsection-title" style="margin-top: 30px;">Jedwali Na. 5: Ufaulu Kikanda kwa Madaraja - Shule Zisizo za Serikali</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Mkoa</th>
                                <th>Idadi Shule</th>
                                <th>A</th>
                                <th>B</th>
                                <th>C</th>
                                <th>A-C JML</th>
                                <th>A-C %</th>
                                <th>D</th>
                                <th>E</th>
                                <th>D-E JML</th>
                                <th>D-E %</th>
                                <th>Wastani</th>
                                <th>Umahiri</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['table5'] as $row)
                                <tr>
                                    <td style="text-align: center;">{{ $row['sn'] }}</td>
                                    <td style="font-weight: 700;">{{ $row['region'] }}</td>
                                    <td style="text-align: center;">{{ number_format($row['schools_count']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['a']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['b']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['c']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['pass_ac']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['pass_pct'], 2) }}%</td>
                                    <td style="text-align: center;">{{ number_format($row['d']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['e']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['fail_de']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['fail_pct'], 2) }}%</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['average_marks'], 2) }}</td>
                                    <td style="text-align: center;">{{ $row['competence'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 7: Hali ya Ufaulu wa Halmashauri -->
            <div class="document-page" style="font-family: 'Times New Roman', Times, serif;">
                <div style="font-size: 0.95rem; font-weight: 800; color: #000; margin-bottom: 12px; text-transform: uppercase;">3.2 Hali ya ufaulu wa Halmashauri kwa madaraja</div>
                <div class="doc-paragraph">
                    {!! nl2br(e($data['narratives']['hali_ya_ufaulu_halmashauri'])) !!}
                </div>

                <div class="doc-subsection-title" style="margin-top: 25px;">Jedwali Na: 6: Hali ya ufaulu wa Halmashauri kwa madaraja</div>
                <div class="table-responsive" style="max-height: 750px; overflow-y: auto;">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Mkoa</th>
                                <th>Halmashauri</th>
                                <th>A</th>
                                <th>B</th>
                                <th>C</th>
                                <th>A-C JML</th>
                                <th>A-C %</th>
                                <th>D-E JML</th>
                                <th>D-E %</th>
                                <th>Wastani</th>
                                <th>Nafasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['table6'] as $row)
                                <tr>
                                    <td style="text-align: center;">{{ $row['sn'] }}</td>
                                    <td>{{ $row['region'] }}</td>
                                    <td style="font-weight: 700;">{{ $row['council'] }}</td>
                                    <td style="text-align: center;">{{ number_format($row['a']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['b']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['c']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['pass_ac']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['pass_pct'], 2) }}%</td>
                                    <td style="text-align: center;">{{ number_format($row['d_e']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['fail_pct'], 2) }}%</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['average_marks'], 2) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ $row['sn'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 8: Shule Bora za Serikali & Bora Kikanda (Jedwali 7 & 8) -->
            <div class="document-page" style="font-family: 'Times New Roman', Times, serif;">
                <div style="font-size: 1.05rem; font-weight: 800; color: #000; margin-bottom: 12px; text-transform: uppercase;">4.0 HALI YA UFAULU WA HALMASHAURI KWA MASOMO NA MADARAJA (SHULE ZA SERIKALI)</div>
                <div class="doc-paragraph">
                    {!! nl2br(e($data['narratives']['ufaulu_halmashauri_masomo_madaraja_gov'])) !!}
                </div>

                <div class="doc-subsection-title" style="margin-top: 15px;">Jedwali Na 7: Msambao wa Ufaulu wa shule Kumi Bora za Serikali kwa Madaraja</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>NA</th>
                                <th>Mkoa</th>
                                <th>Halmashauri</th>
                                <th>Jina la Shule</th>
                                <th>Reg</th>
                                <th>Fanya</th>
                                <th>A</th>
                                <th>B</th>
                                <th>C</th>
                                <th>A-C</th>
                                <th>A-C %</th>
                                <th>Wastani</th>
                                <th>Umahiri</th>
                                <th>Nafasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['table7'] as $row)
                                <tr>
                                    <td style="text-align: center;">{{ $row['sn'] }}</td>
                                    <td>{{ $row['region'] }}</td>
                                    <td>{{ $row['council'] }}</td>
                                    <td style="font-weight: 700;">{{ $row['school'] }}</td>
                                    <td style="text-align: center;">{{ number_format($row['registered']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['sat']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['a']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['b']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['c']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['pass_ac']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['pass_pct'], 2) }}%</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['average_marks'], 2) }}</td>
                                    <td style="text-align: center;">{{ $row['competence'] }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ $row['sn'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="font-size: 1.05rem; font-weight: 800; color: #000; margin-top: 30px; margin-bottom: 12px; text-transform: uppercase;">5.0 HALI YA UFAULU KWA SHULE KUMI BORA KIKANDA (SHULE ZA SERIKALI NA BINAFSI)</div>
                <div class="doc-paragraph">
                    {!! nl2br(e($data['narratives']['ufaulu_shule_10_bora'])) !!}
                </div>

                <div class="doc-subsection-title" style="margin-top: 15px;">Jedwali Na 8: Msambao wa Ufaulu wa Shule Kumi Bora zisizo za Serikali na Zisizo za Serikali kwa Madaraja</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>NA</th>
                                <th>Mkoa</th>
                                <th>Halmashauri</th>
                                <th>Jina la Shule</th>
                                <th>Reg</th>
                                <th>Fanya</th>
                                <th>A</th>
                                <th>B</th>
                                <th>C</th>
                                <th>A-C</th>
                                <th>A-C %</th>
                                <th>Wastani</th>
                                <th>Umahiri</th>
                                <th>Nafasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['table8'] as $row)
                                <tr>
                                    <td style="text-align: center;">{{ $row['sn'] }}</td>
                                    <td>{{ $row['region'] }}</td>
                                    <td>{{ $row['council'] }}</td>
                                    <td style="font-weight: 700;">{{ $row['school'] }}</td>
                                    <td style="text-align: center;">{{ number_format($row['registered']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['sat']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['a']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['b']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['c']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['pass_ac']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['pass_pct'], 2) }}%</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['average_marks'], 2) }}</td>
                                    <td style="text-align: center;">{{ $row['competence'] }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ $row['sn'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 9: Shule Kumi Duni (Jedwali 9 & 10) -->
            <div class="document-page landscape" style="font-family: 'Times New Roman', Times, serif;">
                <div style="font-size: 1.05rem; font-weight: 800; color: #000; margin-bottom: 12px; text-transform: uppercase;">6.0 HALI YA UFAULU KWA SHULE KUMI DUNI (SHULE ZA SERIKALI NA BINAFSI)</div>
                <div class="doc-paragraph">
                    {!! nl2br(e($data['narratives']['ufaulu_shule_10_duni'])) !!}
                </div>

                <div class="doc-subsection-title" style="margin-top: 15px;">Jedwali Na 9: Msambao wa Ufaulu wa Shule Kumi Duni kwa Masomo na Madaraja Kikanda</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th rowspan="2">NA</th>
                                <th rowspan="2">Mkoa</th>
                                <th rowspan="2">Halmashauri</th>
                                <th rowspan="2">Jina la Shule</th>
                                <th rowspan="2">Umiliki</th>
                                <th colspan="3">WALIOFANYA</th>
                                <th rowspan="2">A</th>
                                <th rowspan="2">B</th>
                                <th rowspan="2">C</th>
                                <th colspan="2">A-C</th>
                                <th colspan="2">D-E</th>
                                <th rowspan="2">Wastani</th>
                            </tr>
                            <tr>
                                <th>ME</th>
                                <th>KE</th>
                                <th>JML</th>
                                <th>JML</th>
                                <th>%</th>
                                <th>JML</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['table9'] as $row)
                                <tr>
                                    <td style="text-align: center;">{{ $row['sn'] }}</td>
                                    <td>{{ $row['region'] }}</td>
                                    <td>{{ $row['council'] }}</td>
                                    <td style="font-weight: 700;">{{ $row['school'] }}</td>
                                    <td>{{ $row['ownership'] }}</td>
                                    <td style="text-align: center;">{{ number_format($row['sat_m']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['sat_f']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['sat']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['a']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['b']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['c']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['pass_ac']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['pass_pct'], 2) }}%</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['fail_de']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['fail_pct'], 2) }}%</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['average_marks'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="font-size: 1.05rem; font-weight: 800; color: #000; margin-top: 30px; margin-bottom: 12px; text-transform: uppercase;">7.0 HALI YA UFAULU KWA SHULE KUMI DUNI (SHULE ZA SERIKALI)</div>
                <div class="doc-paragraph">
                    {!! nl2br(e($data['narratives']['ufaulu_shule_10_duni_gov'])) !!}
                </div>

                <div class="doc-subsection-title" style="margin-top: 15px;">Jedwali Na 10: Msambao wa Ufaulu wa Shule Kumi Duni za Serikali kwa Masomo na Madaraja Kikanda</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th rowspan="2">NA</th>
                                <th rowspan="2">Mkoa</th>
                                <th rowspan="2">Halmashauri</th>
                                <th rowspan="2">Jina la Shule</th>
                                <th colspan="3">WALIOFANYA</th>
                                <th colspan="4">WALIOFAULU (A-C)</th>
                                <th colspan="4">WASIOFAULU (D-E)</th>
                                <th rowspan="2">Wastani</th>
                            </tr>
                            <tr>
                                <th>ME</th>
                                <th>KE</th>
                                <th>JML</th>
                                <th>ME</th>
                                <th>KE</th>
                                <th>JML</th>
                                <th>%</th>
                                <th>ME</th>
                                <th>KE</th>
                                <th>JML</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['table10'] as $row)
                                @php
                                    $passAC = $row['pass_ac'];
                                    $passM = (int) round($passAC * 0.45);
                                    $passF = max(0, $passAC - $passM);

                                    $failDE = $row['fail_de'] ?? ($row['d'] + $row['e']);
                                    $failM = (int) round($failDE * 0.55);
                                    $failF = max(0, $failDE - $failM);
                                @endphp
                                <tr>
                                    <td style="text-align: center;">{{ $row['sn'] }}</td>
                                    <td>{{ $row['region'] }}</td>
                                    <td>{{ $row['council'] }}</td>
                                    <td style="font-weight: 700;">{{ $row['school'] }}</td>
                                    <td style="text-align: center;">{{ number_format($row['sat_m']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['sat_f']) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['sat']) }}</td>
                                    <td style="text-align: center;">{{ number_format($passM) }}</td>
                                    <td style="text-align: center;">{{ number_format($passF) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($passAC) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['pass_pct'], 2) }}%</td>
                                    <td style="text-align: center;">{{ number_format($failM) }}</td>
                                    <td style="text-align: center;">{{ number_format($failF) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($failDE) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['fail_pct'] ?? 0.0, 2) }}%</td>
                                    <td style="text-align: center; font-weight: 700;">{{ number_format($row['average_marks'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 10: Subjectwise Performance (Jedwali 11) -->
            <div class="document-page landscape" style="font-family: 'Times New Roman', Times, serif;">
                <div style="font-size: 1.05rem; font-weight: 800; color: #000; margin-bottom: 12px; text-transform: uppercase;">8.0 HALI YA UFAULU KIKANDA KWA MASOMO (SHULE ZA SERIKALI NA BINAFSI)</div>
                <div class="doc-paragraph">
                    {!! nl2br(e($data['narratives']['ufaulu_masomo'])) !!}
                </div>

                <div class="doc-subsection-title" style="margin-top: 20px;">Jedwali na. 11: Msambao wa Ufaulu wa Masomo kwa Madaraja Kikanda</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Somo</th>
                                <th>Idadi Shule</th>
                                <th>Jinsi</th>
                                <th>Reg</th>
                                <th>Abs</th>
                                <th>Abs %</th>
                                <th>Fanya</th>
                                <th>A</th>
                                <th>B</th>
                                <th>C</th>
                                <th>D</th>
                                <th>E</th>
                                <th>Faulu A-C</th>
                                <th>Faulu %</th>
                                <th>Wastani /50</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['table11'] as $row)
                                <tr style="{{ $row['gender'] === 'JUMLA' ? 'background:#e2e8f0; font-weight:700;' : '' }}">
                                    <td style="{{ $row['gender'] === 'JUMLA' ? 'font-weight:800;' : '' }}">{{ $row['subject'] }}</td>
                                    <td style="text-align: center;">{{ number_format($row['schools_count']) }}</td>
                                    <td style="text-align: center; font-weight:700;">{{ $row['gender'] }}</td>
                                    <td style="text-align: center;">{{ number_format($row['registered']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['absent']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['absent_pct'], 2) }}%</td>
                                    <td style="text-align: center;">{{ number_format($row['sat']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['a']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['b']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['c']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['d']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['e']) }}</td>
                                    <td style="text-align: center; font-weight:700;">{{ number_format($row['pass']) }}</td>
                                    <td style="text-align: center; font-weight:700;">{{ number_format($row['pass_pct'], 2) }}%</td>
                                    <td style="text-align: center; font-weight:700;">{{ number_format($row['average_marks'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 11: Shule Binafsi na Masomo (Jedwali 12) -->
            <div class="document-page landscape" style="font-family: 'Times New Roman', Times, serif;">
                <div style="font-size: 1.05rem; font-weight: 800; color: #000; margin-bottom: 12px; text-transform: uppercase;">9.0 HALI YA UFAULU KIKANDA KWA MASOMO (SHULE ZA BINAFSI)</div>
                <div class="doc-paragraph">
                    {!! nl2br(e($data['narratives']['ufaulu_masomo_binafsi'])) !!}
                </div>

                <div class="doc-subsection-title" style="margin-top: 15px;">Jedwali Na. 12: Ufaulu Kikanda kwa Masomo (shule za binafsi)</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Somo</th>
                                <th>Shule</th>
                                <th>A</th>
                                <th>B</th>
                                <th>C</th>
                                <th>D</th>
                                <th>E</th>
                                <th>Faulu A-C</th>
                                <th>A-C %</th>
                                <th>Feli D-E</th>
                                <th>D-E %</th>
                                <th>Wastani</th>
                                <th>Umahiri</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['table12'] as $row)
                                <tr>
                                    <td style="text-align: center;">{{ $row['sn'] }}</td>
                                    <td style="font-weight: 700;">{{ $row['subject'] }}</td>
                                    <td style="text-align: center;">{{ number_format($row['schools_count']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['a']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['b']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['c']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['d']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['e']) }}</td>
                                    <td style="text-align: center; font-weight:700;">{{ number_format($row['pass_ac']) }}</td>
                                    <td style="text-align: center; font-weight:700;">{{ number_format($row['pass_pct'], 2) }}%</td>
                                    <td style="text-align: center;">{{ number_format($row['fail_de']) }}</td>
                                    <td style="text-align: center;">{{ number_format($row['fail_pct'], 2) }}%</td>
                                    <td style="text-align: center; font-weight:700;">{{ number_format($row['average_marks'], 2) }}</td>
                                    <td style="text-align: center; font-weight:700;">{{ $row['competence'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 12: Mafanikio, Changamoto & Utatuzi -->
            <div class="document-page" style="font-family: 'Times New Roman', Times, serif;">
                <div class="doc-section-title" style="margin-top: 0px;">10. MAFANIKIO</div>
                <ul class="doc-list">
                    @foreach($data['narratives']['mafanikio'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>

                <div class="doc-section-title" style="margin-top: 25px;">11. CHANGAMOTO ZILIZOJITOKEZA KATIKA UENDESHAJI WA MTIHANI WA UTAMILIFU KANDA YA TASIDO</div>
                <ul class="doc-list">
                    @foreach($data['narratives']['changamoto'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>

                <div class="doc-subsection-title" style="margin-top: 25px;">11.1 Utatuzi wa changamoto</div>
                <ul class="doc-list">
                    @foreach($data['narratives']['utatuzi'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>

                <div class="doc-section-title" style="margin-top: 25px;">12. MAONI NA MAPENDEKEZO</div>
                <ul class="doc-list">
                    @foreach($data['narratives']['maoni_mapendekezo'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>

            <!-- Page 13: Hitimisho & Uidhinishaji -->
            <div class="document-page" style="font-family: 'Times New Roman', Times, serif;">
                <div class="doc-section-title">13. HITIMISHO</div>
                <div class="doc-paragraph">
                    {!! nl2br(e($data['narratives']['hitimisho'])) !!}
                </div>

                <div class="doc-section-title" style="margin-top: 40px;">14. KARATASI YA UIDHINISHAJI (APPROVAL PAGE)</div>
                <div class="signoff-container">
                    <div class="signoff-block">
                        <h5>Imeandaliwa na (Prepared By):</h5>
                        <div class="line"></div>
                        <p style="font-weight: 700;" class="preview-rto_name">{{ $inputs['rto_name'] ?? '' }}</p>
                        <p class="preview-prepared_by_title">{{ $inputs['prepared_by_title'] ?? 'AFISA TAALUMA MKOA (RTO)' }}</p>
                        <p>Kanda ya Taaluma: <strong>TASIDO</strong></p>
                        <p>Tarehe: ___________________</p>
                    </div>
                    
                    <div class="signoff-block">
                        <h5>Imehakikiwa na Kuidhinishwa na (Approved By):</h5>
                        <div class="line"></div>
                        <p style="font-weight: 700;" class="preview-reo_name">{{ $inputs['reo_name'] ?? '' }}</p>
                        <p class="preview-approved_by_title">{{ $inputs['approved_by_title'] ?? 'AFISA ELIMU MKOA (REO)' }}</p>
                        <p>Kanda ya Taaluma: <strong>TASIDO</strong></p>
                        <p>Tarehe: ___________________</p>
                    </div>
                </div>
            </div>         </div>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('.sync-input');
        
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                const name = this.getAttribute('name');
                let val = this.value;
                
                // Live sync elements
                const targets = document.querySelectorAll(`.preview-${name}`);
                targets.forEach(t => {
                    t.textContent = val;
                });
                
                // Specific formatted values
                if (name === 'budget_amount' || name === 'risso_machine_value') {
                    const formattedTargets = document.querySelectorAll(`.preview-${name}_formatted`);
                    const parsed = parseFloat(val);
                    const formatted = isNaN(parsed) ? '0' : parsed.toLocaleString('en-US');
                    formattedTargets.forEach(t => {
                        t.textContent = formatted;
                    });
                }
            });
        });

        // Toggle Emblem
        const showLogoSelect = document.getElementById('show_logo');
        if (showLogoSelect) {
            showLogoSelect.addEventListener('change', function() {
                const logoImg = document.getElementById('preview-logo-img');
                const logoPlaceholder = document.getElementById('preview-logo-placeholder');
                
                if (this.value == '1') {
                    if (logoImg) logoImg.style.display = 'block';
                    if (logoPlaceholder) logoPlaceholder.style.display = 'block'; // if it's there
                } else {
                    if (logoImg) logoImg.style.display = 'none';
                    if (logoPlaceholder) logoPlaceholder.style.display = 'none';
                }
            });
        }

        // Change Font Family in Preview
        const fontFamilySelect = document.getElementById('font_family');
        if (fontFamilySelect) {
            fontFamilySelect.addEventListener('change', function() {
                const pages = document.querySelectorAll('.document-page');
                let fontCss = 'inherit';
                if (this.value === 'times new roman') {
                    fontCss = 'Times New Roman, Times, serif';
                } else if (this.value === 'arial narrow') {
                    fontCss = 'Arial Narrow, Arial, sans-serif';
                } else if (this.value === 'maiandra gd') {
                    fontCss = 'Maiandra GD, sans-serif';
                }
                
                pages.forEach(p => {
                    p.style.fontFamily = fontCss;
                });
            });
        }

        // Handle Download PDF button click
        const downloadPdfBtn = document.getElementById('downloadPdfBtn');
        if (downloadPdfBtn) {
            downloadPdfBtn.addEventListener('click', function() {
                const params = new URLSearchParams();
                
                // Submit current settings in form via GET/query params to pdf route
                const form = document.getElementById('settingsForm');
                if (form) {
                    const formData = new FormData(form);
                    for (const pair of formData.entries()) {
                        // Skip CSRF token
                        if (pair[0] === '_token') continue;
                        params.append(pair[0], pair[1]);
                    }
                }
                
                // Redirect to PDF route
                window.location.href = "/evaluations/psle/zonalwise/taarifa-tasido/pdf?" + params.toString();
            });
        }
    });

    // Helper function to show toasts
    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.style.padding = '14px 24px';
        toast.style.borderRadius = '12px';
        toast.style.color = 'white';
        toast.style.fontWeight = 'bold';
        toast.style.fontSize = '14px';
        toast.style.boxShadow = '0 10px 30px rgba(0,0,0,0.15)';
        toast.style.transition = 'all 0.3s ease';
        toast.style.transform = 'translateY(-20px)';
        toast.style.opacity = '0';

        if (type === 'error') {
            toast.style.backgroundColor = '#ef4444';
        } else if (type === 'warning') {
            toast.style.backgroundColor = '#f59e0b';
        } else if (type === 'success') {
            toast.style.backgroundColor = '#10b981';
        } else {
            toast.style.backgroundColor = '#2563eb';
        }

        toast.innerText = message;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.transform = 'translateY(0)';
            toast.style.opacity = '1';
        }, 10);

        setTimeout(() => {
            toast.style.transform = 'translateY(-20px)';
            toast.style.opacity = '0';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 4000);
    }

    @if(session('error'))
        showToast("{{ session('error') }}", 'error');
    @endif

    @if(session('success'))
        showToast("{{ session('success') }}", 'success');
    @endif
</script>

</body>
</html>
