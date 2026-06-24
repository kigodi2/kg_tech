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
            background: white;
            width: 820px;
            min-height: 1160px;
            height: auto;
            padding: 50px;
            box-shadow: 0 10px 30px rgba(15,23,42,0.12);
            border-radius: 6px;
            position: relative;
            box-sizing: border-box;
            color: #1e293b;
            overflow: visible;
            flex-shrink: 0;
        }

        .cover-page {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
            font-family: 'Times New Roman', Times, serif;
        }

        .cover-header {
            width: 100%;
            margin-top: 10px;
        }

        .cover-header h1 {
            font-size: 1.35rem;
            font-weight: 800;
            color: #000;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .cover-header h2 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #111;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 4px;
            line-height: 1.4;
        }

        .emblem-img {
            height: 130px;
            margin: 40px auto;
            display: block;
        }

        .cover-title {
            margin: 30px 0;
            width: 100%;
        }

        .cover-title h3 {
            font-size: 2.1rem;
            font-weight: 800;
            color: #000;
            letter-spacing: 1px;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .cover-title h4 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 10px;
            margin-bottom: 25px;
        }

        .cover-title p {
            font-size: 1.25rem;
            font-weight: 700;
            color: #334155;
            line-height: 1.5;
            text-transform: uppercase;
        }

        .cover-footer {
            border-top: 2px solid #000;
            padding-top: 24px;
            margin-top: 40px;
            text-align: left;
            max-width: 580px;
            margin-left: auto;
            margin-right: auto;
            width: 100%;
        }

        .cover-footer table {
            width: 100%;
            border-collapse: collapse;
        }

        .cover-footer table td {
            padding: 6px 0;
            font-size: 0.95rem;
            color: #000;
        }

        .cover-footer table td.label {
            font-weight: 700;
            width: 260px;
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

<div class="workspace">
    
    <!-- Left Sidebar: Settings overrides -->
    <div class="glass-panel sidebar">
        <div class="panel-header">
            <h2>⚙️ Control Panel</h2>
            <p>Sanidi ripoti ya TASIDO Mock Std VII 2026</p>
        </div>
        
        <form id="settingsForm" method="POST" action="{{ route('evaluations.psle.zonalwise.taarifa-tasido.save-settings') }}" class="panel-body">
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
                        <option value="Helvetica" {{ ($inputs['font_family'] ?? 'Helvetica') === 'Helvetica' ? 'selected' : '' }}>Helvetica</option>
                        <option value="Arial" {{ ($inputs['font_family'] ?? '') === 'Arial' ? 'selected' : '' }}>Arial</option>
                        <option value="Times" {{ ($inputs['font_family'] ?? '') === 'Times' ? 'selected' : '' }}>Times New Roman</option>
                        <option value="Courier" {{ ($inputs['font_family'] ?? '') === 'Courier' ? 'selected' : '' }}>Courier</option>
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
            <a href="{{ route('evaluations.psle.zonalwise') }}" class="btn btn-secondary">
                ← Rudi Zonal Workspace
            </a>
        </div>
    </div>
    
    <!-- Right Preview Area -->
    <div class="glass-panel preview-panel">
        <div class="panel-header" style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);">
            <h2>📄 Hakiki ya Taarifa ya Mtihani (Live PDF Preview)</h2>
            <p>Toleo la Kielektroniki la Ripoti ya Kanda ya TASIDO</p>
        </div>
        
        <div class="preview-body" id="preview-container">
            
            <!-- Page 1: COVER -->
            <div class="document-page cover-page">
                <div class="cover-header">
                    <h1 class="preview-office_heading">{{ $inputs['office_heading'] ?? '' }}</h1>
                    <h2 class="preview-subtitle">{{ $inputs['subtitle'] ?? '' }}</h2>
                    
                    @if(file_exists(public_path('images/emblem.png')))
                        <img src="{{ asset('images/emblem.png') }}" class="emblem-img" id="preview-logo-img" alt="Coat of Arms">
                    @else
                        <div id="preview-logo-placeholder" style="height: 120px; display:flex; align-items:center; justify-content:center; border:2px dashed #cbd5e1; margin: 30px auto; max-width:120px; border-radius:50%; font-size:0.75rem; color:#94a3b8;">Emblem</div>
                    @endif
                </div>
                
                <div class="cover-title">
                    <h3 class="preview-cover_title">{{ $inputs['cover_title'] ?? '' }}</h3>
                    <h4>(TASIDO ACADEMIC ZONE)</h4>
                    <p>TAARIFA YA TATHMINI YA MTIHANI WA UTAMILIFU WA DARASA LA SABA</p>
                    <p style="margin-top: 10px; font-size: 1.4rem;">JUNI, 2026</p>
                </div>
                
                <div class="cover-footer">
                    <table>
                        <tr>
                            <td class="label">Kanda ya Taaluma:</td>
                            <td>TASIDO Academic Zone</td>
                        </tr>
                        <tr>
                            <td class="label">Afisa Elimu Mkoa (REO):</td>
                            <td class="preview-reo_name">{{ $inputs['reo_name'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Afisa Taaluma Mkoa (RTO):</td>
                            <td class="preview-rto_name">{{ $inputs['rto_name'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Kituo cha Usahihishaji:</td>
                            <td class="preview-marking_center">{{ $inputs['marking_center'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Mkoa wa Moderation:</td>
                            <td class="preview-moderation_region">{{ $inputs['moderation_region'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Tarehe ya Mtihani:</td>
                            <td class="preview-exam_dates">{{ $inputs['exam_dates'] ?? '' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Page 2: Table of Contents -->
            <div class="document-page">
                <div style="text-align: center; margin-bottom: 30px; font-family: 'Times New Roman', Times, serif;">
                    <h2 style="font-size: 1.5rem; font-weight: 800; color: #000; text-transform: uppercase;">YALIYOMO (TABLE OF CONTENTS)</h2>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 20px; font-family: 'Times New Roman', Times, serif;">
                    @php
                        $tocItems = [
                            "1. Muhtasari wa Kitendaji (Executive Summary)",
                            "2. Utangulizi na Maudhui ya Kanda",
                            "3. Uratibu na Usimamizi wa Mtihani wa Kanda",
                            "4. Utungaji na Mchakato wa Moderation",
                            "5. Uzalishaji, Ulinzi na Usambazaji wa Karatasi za Mitihani",
                            "6. Ufanyikaji wa Mtihani na Usimamizi Vituoni",
                            "7. Mchakato wa Usahihishaji na Uingizaji Alama (Marks Entry)",
                            "8. Takwimu za Usajili na Mahudhurio katika Kanda",
                            "9. Uchambuzi wa Ufaulu wa Kanda kwa Ujumla",
                            "10. Tathmini ya Ufaulu Ki-Mkoa (Regional Ranking)",
                            "11. Tathmini ya Ufaulu Ki-Halmashauri (Council Ranking)",
                            "12. Halmashauri Bora Kumi (Top 10 Councils)",
                            "13. Halmashauri za Mwisho Kumi (Bottom 10 Councils)",
                            "14. Shule Bora Kumi Kikanda (Top 10 Schools)",
                            "15. Shule za Mwisho Kumi Kikanda (Bottom 10 Schools)",
                            "16. Tathmini ya Ufaulu kwa Masomo (Subjectwise Analysis)",
                            "17. Uchambuzi wa Ufaulu kwa Umiliki wa Shule (Government vs Private)",
                            "18. Uhakiki wa Ubora wa Data (Data Quality Check)",
                            "19. Changamoto Zilizojitokeza kwenye Mtihani",
                            "20. Mapendekezo ya Kuboresha Elimu Kanda",
                            "21. Hitimisho la Uendeshaji",
                            "22. Karatasi ya Uidhinishaji (Approval Page)"
                        ];
                    @endphp
                    @foreach($tocItems as $item)
                        <div style="display: flex; justify-content: space-between; align-items: baseline; font-size: 0.95rem; color: #000; padding: 4px 0; border-bottom: 1px dashed #94a3b8;">
                            <span style="font-weight: 700;">{{ $item }}</span>
                            <span style="font-size: 0.85rem; color: #475569; font-style: italic;">Ukurasa wa Ripoti</span>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <!-- Page 3: Narrative Chapters -->
            <div class="document-page" style="font-family: 'Times New Roman', Times, serif;">
                <!-- Section 1 -->
                <div class="doc-section-title">1. MUHTASARI WA KITENDAJI (EXECUTIVE SUMMARY)</div>
                <div class="doc-paragraph">
                    Ripoti hii inatoa muhtasari wa kina na uchambuzi wa matokeo ya mtihani wa utamilifu wa Darasa la Saba (Mock) kwa Mwaka {{ $examYear }} katika Kanda ya Academic Zone ya <strong>TASIDO</strong> (Tabora, Singida, Iringa na Dodoma). Mtihani huu ulihusisha jumla ya shule <strong>{{ number_format($data['zone_profile']['total_schools']) }}</strong> na watahiniwa <strong>{{ number_format($data['attendance']['registered_total']) }}</strong> waliosajiliwa. Kati yao, watahiniwa <strong>{{ number_format($data['attendance']['sat_total']) }}</strong> walifanya mtihani, ikiwa ni sawa na asilimia <strong>{{ number_format($data['attendance']['attendance_rate'], 2) }}%</strong> ya mahudhurio ya jumla. Ufaulu wa jumla wa Kanda (Daraja A-D) umefikia asilimia <strong>{{ number_format(($data['performance']['regional']['pass'] / max(1, $data['performance']['regional']['sat'])) * 100, 2) }}%</strong> ya watahiniwa wote waliofanya mtihani. Mkoa ulioongoza kitaaluma katika Kanda ni <strong>{{ $data['performance']['regions'][0]['name'] ?? 'N/A' }}</strong> ukiwa na wastani wa alama <strong>{{ number_format($data['performance']['regions'][0]['average_marks'] ?? 0, 2) }}</strong>.
                </div>

                <!-- Section 2 -->
                <div class="doc-section-title">2. UTANGULIZI NA MAUDHUI YA KANDA</div>
                <div class="doc-paragraph">
                    Kanda ya Academic Zone ya <strong>TASIDO</strong> ina jumla ya shule za Msingi <strong>{{ number_format($data['zone_profile']['total_schools']) }}</strong> ambapo shule za Serikali ni <strong>{{ number_format($data['zone_profile']['government_schools']) }}</strong> na shule za Binafsi/Zisizo za Serikali ni <strong>{{ number_format($data['zone_profile']['private_schools']) }}</strong>. Kiutawala, Kanda inajumuisha Mikoa minne (4) ambayo ni Tabora, Singida, Iringa na Dodoma. Kanda yetu ina jumla ya Halmashauri/Wilaya <strong>{{ number_format($data['zone_profile']['councils_count']) }}</strong>.
                </div>
                
                <!-- Section 3 -->
                <div class="doc-section-title">3. URATIBU NA USIMAMIZI WA MTIHANI WA KANDA</div>
                <div class="doc-paragraph">
                    Maandalizi ya mtihani yalianza kwa uratibu na vikao vya pamoja vilivyowashirikisha Maafisa Elimu wa Kanda na Halmashauri (REOs/DEOs), Maafisa Taaluma (RTOs/DTOs), na Wathibiti Ubora wa Shule katika Kanda. Vikao hivyo vililenga kukubaliana juu ya miongozo ya uendeshaji, usimamizi, usahihishaji, na mifumo ya bajeti. Bajeti ya jumla ya shilingi <strong class="preview-budget_amount_formatted">{{ number_format((float)$inputs['budget_amount']) }}</strong> iliidhinishwa kwa ajili ya uzalishaji vya mitihani, ununuzi wa karatasi na uendeshaji wa mashine kubwa za chapa.
                </div>
                
                <!-- Section 4 -->
                <div class="doc-section-title">4. UTUNGAJI NA MCHAKATO WA MODERATION</div>
                <div class="doc-paragraph">
                    Walimu mahiri na wazoefu walichaguliwa kutoka mikoa yote wanachama kufanya utungaji wa mitihani (Item Generation) kwa kuzingatia ramani za mitihani (Table of Specifications) zilizotolewa na Baraza la Mitihani la Tanzania (NECTA). Baada ya mitihani kutungwa, zoezi la Moderation lilifanyika katika Kituo Teule chini ya Kamati ya Taaluma ya Mkoa wa <strong class="preview-moderation_region">{{ $inputs['moderation_region'] ?? '' }}</strong> kwa ajili ya kufanya mapitio ya kisarufi na usahihi wa maswali.
                </div>
            </div>

            <!-- Page 4: Narrative Chapters Continued -->
            <div class="document-page" style="font-family: 'Times New Roman', Times, serif;">
                <!-- Section 5 -->
                <div class="doc-section-title">5. UZALISHAJI, ULINZI NA USAMBAZAJI WA KARATASI ZA MITIHANI</div>
                <div class="doc-paragraph">
                    Mitihani yote ilizalishwa kwa siri na usalama mkubwa chini ya usimamizi wa Kamati ya Mitihani ya Kanda. Zoezi hili lilifanyika katika Chumba Maalum cha Siri (Strong Room) kwa muda wa siku <strong class="preview-production_days">{{ $inputs['production_days'] ?? 0 }}</strong> kwa kutumia mashine za chapa haraka za RISSO <strong class="preview-risso_machine_count">{{ $inputs['risso_machine_count'] ?? 0 }}</strong> yenye thamani ya shilingi <strong class="preview-risso_machine_value_formatted">{{ number_format((float)$inputs['risso_machine_value']) }}</strong>.
                </div>
                
                <!-- Section 6 -->
                <div class="doc-section-title">6. UFANYIKAJI WA MTIHANI NA USIMAMIZI VITUONI</div>
                <div class="doc-paragraph">
                    Mtihani ulianza rasmi tarehe <strong class="preview-exam_start_date">{{ $inputs['exam_start_date'] ?? '' }}</strong> na kukamilika tarehe <strong class="preview-exam_end_date">{{ $inputs['exam_end_date'] ?? '' }}</strong> katika shule zote zilizosajiliwa kama vituo vya mitihani. Zoezi zima la ufanyikaji wa mitihani lilifanyika kwa kufuata ratiba sanifu iliyotolewa na Kamati ya Kanda.
                </div>
                
                <!-- Section 7 -->
                <div class="doc-section-title">7. MCHAKATO WA USAHIHISHAJI NA UINGIZAJI ALAMA (MARKS ENTRY)</div>
                <div class="doc-paragraph">
                    Zoezi la usahihishaji lilifanyika katika Kituo Kikuu cha Usahihishaji cha Kanda kilichopo shule ya <strong class="preview-marking_center">{{ $inputs['marking_center'] ?? '' }}</strong>. Usahihishaji huu ulihudhuriwa na wasahihishaji <strong class="preview-markers_count">{{ $inputs['markers_count'] ?? 0 }}</strong> pamoja na wasaidizi wataalamu <strong class="preview-students_assistants_count">{{ $inputs['students_assistants_count'] ?? 0 }}</strong>. Semina ya maadili na usalama ilitolewa na REO <strong class="preview-reo_name">{{ $inputs['reo_name'] ?? '' }}</strong> na RTO <strong class="preview-rto_name">{{ $inputs['rto_name'] ?? '' }}</strong>, ikisimamiwa na Mratibu Taaluma Kanda Ndg. <strong class="preview-exam_coordinator_name">{{ $inputs['exam_coordinator_name'] ?? '' }}</strong>. Alama zote ziliingizwa kikamilifu kwenye mfumo wa IRMS.
                </div>
            </div>
            
            <!-- Page 5: Attendance Table -->
            <div class="document-page">
                <!-- Section 8 -->
                <div class="doc-section-title">8. TAKWIMU ZA USAJILI NA MAHUDHURIO KATIKA KANDA</div>
                <div class="doc-subsection-title">Jedwali la 1: Takwimu za Usajili na Mahudhurio Ki-Mkoa</div>
                <div class="table-responsive">
                    <table class="doc-table attendance-table">
                        <thead>
                            <tr>
                                <th rowspan="2">S/N</th>
                                <th rowspan="2">Mkoa</th>
                                <th colspan="3">WALIOSAJILIWA</th>
                                <th colspan="3">WALIOFANYA</th>
                                <th colspan="3">WASIOFANYA</th>
                                <th rowspan="2">Asilimia (%)</th>
                            </tr>
                            <tr>
                                <th>ME</th>
                                <th>KE</th>
                                <th>JUMLA</th>
                                <th>ME</th>
                                <th>KE</th>
                                <th>JUMLA</th>
                                <th>ME</th>
                                <th>KE</th>
                                <th>JUMLA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['attendance']['region_rows'] as $index => $rrow)
                                <tr>
                                    <td style="text-align: center;">{{ $index + 1 }}</td>
                                    <td>{{ $rrow['name'] }}</td>
                                    <td style="text-align: right;">{{ number_format($rrow['registered_m']) }}</td>
                                    <td style="text-align: right;">{{ number_format($rrow['registered_f']) }}</td>
                                    <td style="text-align: right; font-weight:700;">{{ number_format($rrow['registered_t']) }}</td>
                                    <td style="text-align: right;">{{ number_format($rrow['sat_m']) }}</td>
                                    <td style="text-align: right;">{{ number_format($rrow['sat_f']) }}</td>
                                    <td style="text-align: right; font-weight:700;">{{ number_format($rrow['sat_t']) }}</td>
                                    <td style="text-align: right;">{{ number_format($rrow['absent_m']) }}</td>
                                    <td style="text-align: right;">{{ number_format($rrow['absent_f']) }}</td>
                                    <td style="text-align: right;">{{ number_format($rrow['absent_t']) }}</td>
                                    <td style="text-align: right; font-weight:700;">{{ number_format($rrow['attendance_rate'], 2) }}%</td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td style="text-align: center;">-</td>
                                <td>JUMLA KUU</td>
                                <td style="text-align: right;">{{ number_format($data['attendance']['registered_male']) }}</td>
                                <td style="text-align: right;">{{ number_format($data['attendance']['registered_female']) }}</td>
                                <td style="text-align: right;">{{ number_format($data['attendance']['registered_total']) }}</td>
                                <td style="text-align: right;">{{ number_format($data['attendance']['sat_male']) }}</td>
                                <td style="text-align: right;">{{ number_format($data['attendance']['sat_female']) }}</td>
                                <td style="text-align: right;">{{ number_format($data['attendance']['sat_total']) }}</td>
                                <td style="text-align: right;">{{ number_format($data['attendance']['absent_male']) }}</td>
                                <td style="text-align: right;">{{ number_format($data['attendance']['absent_female']) }}</td>
                                <td style="text-align: right;">{{ number_format($data['attendance']['absent_total']) }}</td>
                                <td style="text-align: right;">{{ number_format($data['attendance']['attendance_rate'], 2) }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 6: General Performance Table -->
            <div class="document-page">
                <!-- Section 9 -->
                <div class="doc-section-title">9. UCHAMBUZI WA UFAULU WA KANDA KWA UJUMLA</div>
                <div class="doc-subsection-title">Jedwali la 2: Mgawanyo wa Madaraja ya Ufaulu Kimasomo (Daraja A - E)</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Jinsia</th>
                                <th>Daraja A</th>
                                <th>Daraja B</th>
                                <th>Daraja C</th>
                                <th>Daraja D</th>
                                <th>Daraja E</th>
                                <th>Waliofanya</th>
                                <th>Waliofaulu</th>
                                <th>Ufaulu %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['performance']['grade_distribution'] as $gRow)
                                <tr>
                                    <td>{{ $gRow['gender'] }}</td>
                                    <td style="text-align: right;">{{ number_format($gRow['a']) }}</td>
                                    <td style="text-align: right;">{{ number_format($gRow['b']) }}</td>
                                    <td style="text-align: right;">{{ number_format($gRow['c']) }}</td>
                                    <td style="text-align: right;">{{ number_format($gRow['d']) }}</td>
                                    <td style="text-align: right;">{{ number_format($gRow['e']) }}</td>
                                    <td style="text-align: right; font-weight:700;">{{ number_format($gRow['sat']) }}</td>
                                    <td style="text-align: right; font-weight:700;">{{ number_format($gRow['pass']) }}</td>
                                    <td style="text-align: right; font-weight:700; color:var(--accent);">{{ number_format($gRow['pct'], 2) }}%</td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td>JUMLA</td>
                                <td style="text-align: right;">{{ number_format($data['performance']['regional']['a']) }}</td>
                                <td style="text-align: right;">{{ number_format($data['performance']['regional']['b']) }}</td>
                                <td style="text-align: right;">{{ number_format($data['performance']['regional']['c']) }}</td>
                                <td style="text-align: right;">{{ number_format($data['performance']['regional']['d']) }}</td>
                                <td style="text-align: right;">{{ number_format($data['performance']['regional']['e']) }}</td>
                                <td style="text-align: right;">{{ number_format($data['performance']['regional']['sat']) }}</td>
                                <td style="text-align: right;">{{ number_format($data['performance']['regional']['pass']) }}</td>
                                <td style="text-align: right; color:var(--accent);">{{ number_format($data['performance']['regional']['pct'], 2) }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 7: Regionalwise Table -->
            <div class="document-page">
                <!-- Section 10 -->
                <div class="doc-section-title">10. TATHMINI YA UFAULU KI-MKOA (REGIONAL RANKING)</div>
                <div class="doc-subsection-title">Jedwali la 3: Msimamo wa Mikoa katika Kanda ya TASIDO</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Nafasi</th>
                                <th>Mkoa</th>
                                <th>Waliofanya</th>
                                <th>Waliofaulu (A-C)</th>
                                <th>Waliofaulu (D)</th>
                                <th>Waliofeli (E)</th>
                                <th>Wastani wa Alama</th>
                                <th>Daraja la Kundi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['performance']['regions'] as $rRow)
                                <tr>
                                    <td style="text-align: center;">{{ $rRow['position'] }}</td>
                                    <td style="font-weight: 700;">{{ $rRow['name'] }}</td>
                                    <td style="text-align: right;">{{ number_format($rRow['sat']) }}</td>
                                    <td style="text-align: right;">{{ number_format($rRow['pass_ac']) }}</td>
                                    <td style="text-align: right;">{{ number_format($rRow['pass_d']) }}</td>
                                    <td style="text-align: right;">{{ number_format($rRow['fail']) }}</td>
                                    <td style="text-align: right; font-weight:700;">{{ number_format($rRow['average_marks'], 2) }}</td>
                                    <td style="text-align: center; font-weight:700;">{{ $rRow['grade'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 8: Councilwise Table -->
            <div class="document-page">
                <!-- Section 11 -->
                <div class="doc-section-title">11. TATHMINI YA UFAULU KI-HALMASHAURI (COUNCIL RANKING)</div>
                <div class="doc-subsection-title">Jedwali la 4: Utendaji wa Halmashauri/Wilaya zote za Kanda</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Nafasi</th>
                                <th>Halmashauri</th>
                                <th>Mkoa</th>
                                <th>Waliofanya</th>
                                <th>Wastani wa Alama</th>
                                <th>Daraja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['performance']['councils'] as $cRow)
                                <tr>
                                    <td style="text-align: center;">{{ $cRow['position'] }}</td>
                                    <td>{{ $cRow['name'] }}</td>
                                    <td>{{ $cRow['region'] }}</td>
                                    <td style="text-align: right;">{{ number_format($cRow['sat']) }}</td>
                                    <td style="text-align: right; font-weight:700;">{{ number_format($cRow['average_marks'], 2) }}</td>
                                    <td style="text-align: center; font-weight:700;">{{ $cRow['grade'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 9: Top and Bottom Councils -->
            <div class="document-page">
                <!-- Section 12 -->
                <div class="doc-section-title">12. HALMASHAURI BORA KUMI (TOP 10 COUNCILS)</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Nafasi</th>
                                <th>Halmashauri</th>
                                <th>Mkoa</th>
                                <th>Waliofanya</th>
                                <th>Wastani wa Alama</th>
                                <th>Daraja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['performance']['top_councils'] as $tcRow)
                                <tr>
                                    <td style="text-align: center;">{{ $tcRow['position'] }}</td>
                                    <td>{{ $tcRow['name'] }}</td>
                                    <td>{{ $tcRow['region'] }}</td>
                                    <td style="text-align: right;">{{ number_format($tcRow['sat']) }}</td>
                                    <td style="text-align: right; font-weight:700;">{{ number_format($tcRow['average_marks'], 2) }}</td>
                                    <td style="text-align: center; font-weight:700;">{{ $tcRow['grade'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Section 13 -->
                <div class="doc-section-title" style="margin-top: 40px;">13. HALMASHAURI ZA MWISHO KUMI (BOTTOM 10 COUNCILS)</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Nafasi</th>
                                <th>Halmashauri</th>
                                <th>Mkoa</th>
                                <th>Waliofanya</th>
                                <th>Wastani wa Alama</th>
                                <th>Daraja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['performance']['bottom_councils'] as $bcRow)
                                <tr>
                                    <td style="text-align: center;">{{ $bcRow['position'] }}</td>
                                    <td>{{ $bcRow['name'] }}</td>
                                    <td>{{ $bcRow['region'] }}</td>
                                    <td style="text-align: right;">{{ number_format($bcRow['sat']) }}</td>
                                    <td style="text-align: right; font-weight:700;">{{ number_format($bcRow['average_marks'], 2) }}</td>
                                    <td style="text-align: center; font-weight:700;">{{ $bcRow['grade'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 10: Top Schools -->
            <div class="document-page">
                <!-- Section 14 -->
                <div class="doc-section-title">14. SHULE BORA KUMI KIKANDA (TOP 10 SCHOOLS)</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Nafasi</th>
                                <th>Jina la Shule</th>
                                <th>Halmashauri</th>
                                <th>Mkoa</th>
                                <th>Umiliki</th>
                                <th>Waliofanya</th>
                                <th>Wastani wa Alama</th>
                                <th>Daraja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['performance']['top_schools'] as $sRow)
                                <tr>
                                    <td style="text-align: center;">{{ $sRow['position'] }}</td>
                                    <td style="font-weight: 700;">{{ $sRow['name'] }}</td>
                                    <td>{{ $sRow['council'] }}</td>
                                    <td>{{ $sRow['region'] }}</td>
                                    <td>{{ $sRow['ownership'] }}</td>
                                    <td style="text-align: right;">{{ number_format($sRow['sat']) }}</td>
                                    <td style="text-align: right; font-weight: 700;">{{ number_format($sRow['average_marks'], 2) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ $sRow['grade'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 11: Bottom Schools -->
            <div class="document-page">
                <!-- Section 15 -->
                <div class="doc-section-title">15. SHULE ZA MWISHO KUMI KIKANDA (BOTTOM 10 SCHOOLS)</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Nafasi</th>
                                <th>Jina la Shule</th>
                                <th>Halmashauri</th>
                                <th>Mkoa</th>
                                <th>Umiliki</th>
                                <th>Waliofanya</th>
                                <th>Wastani wa Alama</th>
                                <th>Daraja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['performance']['bottom_schools'] as $sRow)
                                <tr>
                                    <td style="text-align: center;">{{ $sRow['position'] }}</td>
                                    <td style="font-weight: 700;">{{ $sRow['name'] }}</td>
                                    <td>{{ $sRow['council'] }}</td>
                                    <td>{{ $sRow['region'] }}</td>
                                    <td>{{ $sRow['ownership'] }}</td>
                                    <td style="text-align: right;">{{ number_format($sRow['sat']) }}</td>
                                    <td style="text-align: right; font-weight: 700;">{{ number_format($sRow['average_marks'], 2) }}</td>
                                    <td style="text-align: center; font-weight: 700;">{{ $sRow['grade'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 12: Subjectwise and Ownership -->
            <div class="document-page">
                <!-- Section 16 -->
                <div class="doc-section-title">16. TATHMINI YA UFAULU KWA MASOMO (SUBJECTWISE ANALYSIS)</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Nafasi</th>
                                <th>Somo</th>
                                <th>Waliotahiniwa</th>
                                <th>Waliofaulu</th>
                                <th>Waliofeli</th>
                                <th>Ufaulu %</th>
                                <th>Wastani wa Alama</th>
                                <th>Daraja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['performance']['subjects'] as $subRow)
                                <tr>
                                    <td style="text-align: center;">{{ $subRow['position'] }}</td>
                                    <td style="font-weight: 700;">{{ $subRow['name'] }}</td>
                                    <td style="text-align: right;">{{ number_format($subRow['sat']) }}</td>
                                    <td style="text-align: right;">{{ number_format($subRow['pass']) }}</td>
                                    <td style="text-align: right;">{{ number_format($subRow['fail']) }}</td>
                                    <td style="text-align: right; font-weight:700;">{{ number_format($subRow['pass_rate'], 2) }}%</td>
                                    <td style="text-align: right; font-weight:700;">{{ number_format($subRow['average_marks'], 2) }}</td>
                                    <td style="text-align: center; font-weight:700;">{{ $subRow['grade'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Section 17 -->
                <div class="doc-section-title" style="margin-top: 40px;">17. UCHAMBUZI WA UFAULU KWA UMILIKI WA SHULE (GOVERNMENT VS PRIVATE)</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Umiliki</th>
                                <th>Shule</th>
                                <th>Waliosajiliwa</th>
                                <th>Waliofanya</th>
                                <th>Waliofaulu</th>
                                <th>Ufaulu %</th>
                                <th>Wastani wa Alama</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['performance']['ownership'] as $ownRow)
                                <tr>
                                    <td style="font-weight: 700;">{{ $ownRow['ownership'] }}</td>
                                    <td style="text-align: right;">{{ number_format($ownRow['schools_count']) }}</td>
                                    <td style="text-align: right;">{{ number_format($ownRow['registered']) }}</td>
                                    <td style="text-align: right;">{{ number_format($ownRow['sat']) }}</td>
                                    <td style="text-align: right;">{{ number_format($ownRow['pass']) }}</td>
                                    <td style="text-align: right; font-weight: 700;">{{ number_format($ownRow['pass_rate'], 2) }}%</td>
                                    <td style="text-align: right; font-weight: 700;">{{ number_format($ownRow['average_marks'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 13: Data Quality and Approval Page -->
            <div class="document-page" style="font-family: 'Times New Roman', Times, serif;">
                <!-- Section 18 -->
                <div class="doc-section-title">18. UHAKIKI WA UBORA WA DATA (DATA QUALITY CHECK)</div>
                @if(count($data['data_quality']['issues']) > 0)
                    <div class="dq-alert">
                        <h4>⚠️ Changamoto katika Usajili/Mahudhurio na Matokeo:</h4>
                        <ul>
                            @foreach($data['data_quality']['issues'] as $issue)
                                <li>{{ $issue }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="dq-success">
                        ✅ Uhakiki wa data umekamilika kikamilifu. Mfumo haujagundua mgongano wowote kwenye data ya matokeo ya Kanda (No data anomalies detected).
                    </div>
                @endif

                <!-- Section 19 & 20 -->
                <div class="doc-section-title">19. CHANGAMOTO ZILIZOJITOKEZA KWENYE MTIHANI</div>
                <div class="doc-paragraph">
                    1. Kushindwa kuhudhuria kwa baadhi ya watahiniwa kutokana na changamoto za kijamii au uhamaji wa familia.<br>
                    2. Tofauti ndogo ndogo za uingizaji wa majina ya watahiniwa kati ya kanzidata ya shule na mfumo mkuu.
                </div>

                <div class="doc-section-title">20. MAPENDEKEZO YA KUBORESHA ELIMU KANDA</div>
                <div class="doc-paragraph">
                    1. Kuimarisha ufuatiliaji wa mahudhurio ya wanafunzi shuleni, hasa katika kipindi cha mitihani ya majaribio.<br>
                    2. Kuhakikisha walimu wote wa Kanda ya TASIDO wanafanya kazi kwa ushirikiano wa karibu kuboresha ufaulu wa masomo ya hisabati na sayansi.
                </div>

                <!-- Section 21 & 22 -->
                <div class="doc-section-title">21. HITIMISHO LA UENDESHAJI</div>
                <div class="doc-paragraph">
                    Uendeshaji wa Mtihani wa Utamilifu wa Darasa la Saba Mwaka {{ $examYear }} TASIDO umekamilika kwa kiwango cha juu cha ufanisi. Usimamizi thabiti, usahihishaji makini, na mfumo wa IRMS umerahisisha zoezi zima la uchambuzi wa matokeo.
                </div>

                <div class="doc-section-title">22. KARATASI YA UIDHINISHAJI (APPROVAL PAGE)</div>
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
                let fontCss = 'Times New Roman, Times, serif';
                if (this.value === 'Helvetica') {
                    fontCss = 'Helvetica, Arial, sans-serif';
                } else if (this.value === 'Arial') {
                    fontCss = 'Arial, sans-serif';
                } else if (this.value === 'Courier') {
                    fontCss = 'Courier New, Courier, monospace';
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
                // Submit current settings in form via GET/query params to pdf route
                const form = document.getElementById('settingsForm');
                const formData = new FormData(form);
                const params = new URLSearchParams();
                
                for (const pair of formData.entries()) {
                    // Skip CSRF token
                    if (pair[0] === '_token') continue;
                    params.append(pair[0], pair[1]);
                }
                
                // Redirect to PDF route
                window.location.href = "{{ route('evaluations.psle.zonalwise.taarifa-tasido.pdf') }}?" + params.toString();
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
