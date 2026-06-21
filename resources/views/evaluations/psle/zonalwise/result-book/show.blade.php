<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitabu cha Matokeo - Kanda ya TASIDO</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
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
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.4);
            --shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-gradient);
            color: #334155;
            min-height: 100vh;
            padding: 20px;
        }

        /* Layout Container */
        .workspace {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 20px;
            max-width: 1600px;
            margin: 0 auto;
            height: calc(100vh - 40px);
        }

        /* Glass Card Utility */
        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
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
            padding: 20px;
            background: var(--primary);
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .panel-header h2 {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .panel-header p {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 4px;
        }

        .panel-body {
            padding: 20px;
            overflow-y: auto;
            flex-grow: 1;
        }

        .section-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 12px;
            margin-top: 10px;
            letter-spacing: 1px;
            border-left: 3px solid var(--accent);
            padding-left: 8px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
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

        .panel-footer {
            padding: 20px;
            border-top: 1px solid rgba(0,0,0,0.05);
            background: rgba(255,255,255,0.5);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
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
            padding: 50px;
            box-shadow: 0 4px 20px rgba(15,23,42,0.15);
            border-radius: 4px;
            position: relative;
            box-sizing: border-box;
            color: #334155;
        }

        .cover-page {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            text-align: center;
        }

        .cover-header {
            width: 100%;
            margin-top: 20px;
        }

        .cover-header h1 {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: 1px;
            margin-bottom: 6px;
        }

        .cover-header h2 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #475569;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .emblem-img {
            height: 130px;
            margin: 40px auto;
            display: block;
        }

        .cover-title {
            margin: 20px 0;
        }

        .cover-title h3 {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: 1.5px;
        }

        .cover-title h4 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }

        .cover-title p {
            font-size: 1.3rem;
            font-weight: 700;
            color: #475569;
        }

        .cover-footer {
            border-top: 2px solid var(--primary);
            padding-top: 20px;
            margin-top: 40px;
            text-align: left;
            max-width: 550px;
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
            font-size: 0.9rem;
        }

        .cover-footer table td.label {
            font-weight: 700;
            color: var(--primary);
            width: 240px;
        }

        /* Document Typography */
        .doc-section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent);
            border-bottom: 2px solid var(--accent);
            padding-bottom: 6px;
            margin-top: 30px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .doc-subsection-title {
            font-size: 1rem;
            font-weight: 700;
            color: #334155;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .doc-paragraph {
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 12px;
            color: #334155;
            text-align: justify;
        }

        .doc-list {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        .doc-list li {
            margin-bottom: 8px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
            margin: 15px 0;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .doc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }

        .doc-table th, .doc-table td {
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }

        .doc-table th {
            background: #f1f5f9;
            font-weight: 700;
            color: var(--primary);
            text-align: center;
        }

        .attendance-table thead tr:first-child th {
            text-align: center;
            vertical-align: middle;
            background: #eaf0f7;
            font-weight: 700;
        }

        .attendance-table thead tr:nth-child(2) th {
            text-align: center;
            vertical-align: middle;
            background: #f8fafc;
            font-weight: 700;
        }

        .doc-table tr:nth-child(even) {
            background: #f8fafc;
        }

        .doc-table tr.total-row {
            background: #e2e8f0;
            font-weight: 700;
            color: var(--primary);
        }

        /* Sign-offs */
        .signoff-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .signoff-block h5 {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .signoff-block p {
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .signoff-block .line {
            border-bottom: 1px solid #94a3b8;
            width: 200px;
            margin-bottom: 6px;
        }

        /* Data Quality Alerts */
        .dq-alert {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #991b1b;
        }

        .dq-alert h4 {
            font-size: 0.95rem;
            font-weight: 700;
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
            border-radius: 8px;
            padding: 12px;
            font-size: 0.9rem;
            margin-bottom: 20px;
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

<div class="workspace">
    
    <!-- Left Sidebar: Settings overrides -->
    <div class="glass-panel sidebar">
        <div class="panel-header">
            <h2>⚙️ Control Panel</h2>
            <p>Sanidi mipangilio ya Kitabu cha Matokeo ya Kanda</p>
        </div>
        
        <form id="pdfForm" method="GET" action="{{ route('evaluations.psle.zonalwise.result-book.pdf') }}" class="panel-body">
            
            <div class="section-title">Viongozi wa Uendeshaji</div>
            
            <div class="form-group">
                <label for="reo_name">Afisa Elimu Mkoa (REO)</label>
                <input type="text" id="reo_name" name="reo_name" class="form-control sync-input" value="{{ $inputs['reo_name'] }}">
            </div>
            
            <div class="form-group">
                <label for="rto_name">Afisa Taaluma Mkoa (RTO)</label>
                <input type="text" id="rto_name" name="rto_name" class="form-control sync-input" value="{{ $inputs['rto_name'] }}">
            </div>
            
            <div class="form-group">
                <label for="rso_name">Afisa Usalama (RSO)</label>
                <input type="text" id="rso_name" name="rso_name" class="form-control sync-input" value="{{ $inputs['rso_name'] }}">
            </div>
            
            <div class="form-group">
                <label for="exam_coordinator_name">Mratibu Ndg. (Taaluma)</label>
                <input type="text" id="exam_coordinator_name" name="exam_coordinator_name" class="form-control sync-input" value="{{ $inputs['exam_coordinator_name'] }}">
            </div>

            <div class="section-title">Kituo na Moderation</div>
            
            <div class="form-group">
                <label for="marking_center">Kituo cha Usahihishaji</label>
                <input type="text" id="marking_center" name="marking_center" class="form-control sync-input" value="{{ $inputs['marking_center'] }}">
            </div>
            
            <div class="form-group">
                <label for="moderation_region">Mkoa wa Moderation</label>
                <input type="text" id="moderation_region" name="moderation_region" class="form-control sync-input" value="{{ $inputs['moderation_region'] }}">
            </div>

            <div class="section-title">Muda na Wataalamu</div>
            
            <div class="form-group">
                <label for="production_days">Siku za Uzalishaji</label>
                <input type="number" id="production_days" name="production_days" class="form-control sync-input" value="{{ $inputs['production_days'] }}">
            </div>
            
            <div class="form-group">
                <label for="marking_days">Siku za Usahihishaji</label>
                <input type="number" id="marking_days" name="marking_days" class="form-control sync-input" value="{{ $inputs['marking_days'] }}">
            </div>
            
            <div class="form-group">
                <label for="markers_count">Idadi ya Wasahihishaji</label>
                <input type="number" id="markers_count" name="markers_count" class="form-control sync-input" value="{{ $inputs['markers_count'] }}">
            </div>
            
            <div class="form-group">
                <label for="students_assistants_count">Wasaidizi Wataalamu</label>
                <input type="number" id="students_assistants_count" name="students_assistants_count" class="form-control sync-input" value="{{ $inputs['students_assistants_count'] }}">
            </div>

            <div class="section-title">Uzalishaji na Bajeti</div>
            
            <div class="form-group">
                <label for="budget_amount">Bajeti ya Uendeshaji (Tsh)</label>
                <input type="number" id="budget_amount" name="budget_amount" class="form-control sync-input" value="{{ $inputs['budget_amount'] }}">
            </div>
            
            <div class="form-group">
                <label for="risso_machine_count">Mashine za RISSO</label>
                <input type="number" id="risso_machine_count" name="risso_machine_count" class="form-control sync-input" value="{{ $inputs['risso_machine_count'] }}">
            </div>
            
            <div class="form-group">
                <label for="risso_machine_value">Thamani ya Mashine (Tsh)</label>
                <input type="number" id="risso_machine_value" name="risso_machine_value" class="form-control sync-input" value="{{ $inputs['risso_machine_value'] }}">
            </div>

            <div class="section-title">Tarehe na Ushirikiano</div>
            
            <div class="form-group">
                <label for="exam_start_date">Tarehe ya Kuanza</label>
                <input type="text" id="exam_start_date" name="exam_start_date" class="form-control sync-input" value="{{ $inputs['exam_start_date'] }}">
            </div>
            
            <div class="form-group">
                <label for="exam_end_date">Tarehe ya Kumaliza</label>
                <input type="text" id="exam_end_date" name="exam_end_date" class="form-control sync-input" value="{{ $inputs['exam_end_date'] }}">
            </div>
            
            <div class="form-group">
                <label for="collaborating_regions">Mikoa Shiriki</label>
                <input type="text" id="collaborating_regions" name="collaborating_regions" class="form-control sync-input" value="{{ $inputs['collaborating_regions'] }}">
            </div>

            <div class="section-title">Uthibitisho (Sign-off)</div>
            
            <div class="form-group">
                <label for="prepared_by_title">Cheo cha Mtayarishaji</label>
                <input type="text" id="prepared_by_title" name="prepared_by_title" class="form-control sync-input" value="{{ $inputs['prepared_by_title'] }}">
            </div>
            
            <div class="form-group">
                <label for="approved_by_title">Cheo cha Mthibitishaji</label>
                <input type="text" id="approved_by_title" name="approved_by_title" class="form-control sync-input" value="{{ $inputs['approved_by_title'] }}">
            </div>
            
        </form>
        
        <div class="panel-footer">
            <button type="submit" form="pdfForm" class="btn btn-primary">
                📥 Pakua Kitabu (PDF)
            </button>
            <a href="{{ route('evaluations.psle.zonalwise') }}" class="btn btn-secondary">
                ← Rudi Kwenye Dashboard
            </a>
        </div>
    </div>
    
    <!-- Right Preview Area -->
    <div class="glass-panel preview-panel">
        <div class="panel-header" style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);">
            <h2>📄 Ripoti Hakiki (Live Report Preview)</h2>
            <p>Toleo la Kielektroniki la Kitabu cha Matokeo ya Kanda</p>
        </div>
        
        <div class="preview-body">
            
            <!-- Page 1: COVER -->
            <div class="document-page cover-page">
                <div class="cover-header">
                    <h1>OFISI YA WAZIRI MKUU</h1>
                    <h2>REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT</h2>
                    <h2>ACADEMIC ZONE: TABORA, SINGIDA, IRINGA AND DODOMA (TASIDO)</h2>
                    
                    @if(file_exists(public_path('images/emblem.png')))
                        <img src="{{ asset('images/emblem.png') }}" class="emblem-img" alt="Coat of Arms">
                    @else
                        <div style="height: 120px; display:flex; align-items:center; justify-content:center; border:2px dashed #cbd5e1; margin: 30px auto; max-width:120px; border-radius:50%; font-size:0.75rem; color:#94a3b8;">Emblem</div>
                    @endif
                </div>
                
                <div class="cover-title">
                    <h3>PSLE ZONAL RESULT BOOK - {{ $examYear }}</h3>
                    <h4>(KITABU CHA MATOKEO YA KANDA)</h4>
                    <p>TATHMINI YA MTIHANI WA UTAMILIFU WA DARASA LA SABA (PSLE ZONAL MOCK)</p>
                    <p style="margin-top: 10px; font-size: 1.4rem;">MWAKA {{ $examYear }}</p>
                </div>
                
                <div class="cover-footer">
                    <table>
                        <tr>
                            <td class="label">Kanda ya Taaluma:</td>
                            <td>{{ $data['meta']['zone_name'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Mratibu wa Kanda (REO):</td>
                            <td class="preview-reo_name">{{ $inputs['reo_name'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Mratibu Taaluma Kanda (RTO):</td>
                            <td class="preview-rto_name">{{ $inputs['rto_name'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Kituo cha Usahihishaji:</td>
                            <td class="preview-marking_center">{{ $inputs['marking_center'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Mkoa wa Moderation:</td>
                            <td class="preview-moderation_region">{{ $inputs['moderation_region'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Tarehe ya Kuzalishwa:</td>
                            <td>{{ $data['meta']['generated_at'] }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Page 1.5: Table of Contents -->
            <div class="document-page">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--primary);">YALIYOMO</h2>
                </div>
                
                <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 20px;">
                    @php
                        $tocItems = [
                            "1. Muhtasari wa Kitendaji",
                            "2. Utangulizi",
                            "3. Maandalizi ya Mtihani",
                            "4. Utungaji na Moderation",
                            "5. Uzalishaji na Usambazaji",
                            "6. Ufanyikaji na Usimamizi wa Mtihani",
                            "7. Usahihishaji na Uingizaji wa Alama",
                            "8. Takwimu za Usajili na Mahudhurio",
                            "9. Tathmini ya Jumla ya Kanda",
                            "10. Tathmini ya Matokeo Ki-Mkoa",
                            "11. Tathmini ya Matokeo Ki-Halmashauri",
                            "12. Halmashauri Bora Kumi Kikanda",
                            "13. Halmashauri za Mwisho Kumi Kikanda",
                            "14. Shule Bora Kumi Kikanda",
                            "15. Shule za Mwisho Kumi Kikanda",
                            "16. Tathmini ya Ufaulu kwa Masomo",
                            "17. Tathmini ya Ufaulu kwa Umiliki wa Shule",
                            "18. Uhakiki wa Ubora wa Data",
                            "19. Changamoto Zilizobainika",
                            "20. Mapendekezo",
                            "21. Hitimisho",
                            "22. Uidhinishaji"
                        ];
                    @endphp
                    @foreach($tocItems as $item)
                        <div style="display: flex; justify-content: space-between; align-items: baseline; font-size: 0.95rem; color: #334155; padding: 4px 0; border-bottom: 1px dashed #e2e8f0;">
                            <span style="font-weight: 600; color: var(--primary);">{{ $item }}</span>
                            <span style="font-size: 0.85rem; color: #64748b; font-style: italic;">Sehemu ya Ripoti</span>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <!-- Page 2: NARRATIVES & STATS -->
            <div class="document-page">
                
                <!-- Section 1 -->
                <div class="doc-section-title">1. MUHTASARI WA KITENDAJI</div>
                <div class="doc-paragraph">
                    Ripoti hii inatoa muhtasari wa matokeo ya mtihani wa utamilifu wa Darasa la Saba (PSLE Mock) kwa Mwaka {{ $examYear }} katika Kanda ya Academic Zone ya <strong>TASIDO</strong> (inayojumuisha Mikoa ya Tabora, Singida, Iringa na Dodoma).
                    Mtihani huu ulihusisha jumla ya shule <strong>{{ number_format($data['zone_profile']['total_schools']) }}</strong> na watahiniwa <strong>{{ number_format($data['attendance']['registered_total']) }}</strong> waliosajiliwa.
                    Kati yao, watahiniwa <strong>{{ number_format($data['attendance']['sat_total']) }}</strong> walifanya mtihani, ikiwa ni sawa na asilimia <strong>{{ number_format($data['attendance']['attendance_rate'], 2) }}%</strong> ya mahudhurio ya jumla.
                    Ufaulu wa jumla wa Kanda (Daraja A-D) umefikia asilimia <strong>{{ number_format(($data['performance']['regional']['pass'] / max(1, $data['performance']['regional']['sat'])) * 100, 2) }}%</strong> ya watahiniwa wote waliofanya mtihani.
                    Mkoa ulioongoza kitaaluma katika Kanda ni <strong>{{ $data['performance']['regions'][0]['name'] ?? 'N/A' }}</strong> ukiwa na wastani wa alama <strong>{{ number_format($data['performance']['regions'][0]['average_marks'] ?? 0, 2) }}</strong>, na shule iliyoongoza kikanda ni <strong>{{ $data['performance']['top_schools'][0]['name'] ?? 'N/A' }}</strong> yenye wastani wa alama <strong>{{ number_format($data['performance']['top_schools'][0]['average_marks'] ?? 0, 2) }}</strong>.
                    Tathmini ya kina ya takwimu za usajili, mahudhurio, utendaji wa kimasomo, kiutawala na kiumiliki imeainishwa kikamilifu katika kurasa zinazofuata.
                </div>

                <!-- Section 2 -->
                <div class="doc-section-title">2. UTANGULIZI</div>
                <div class="doc-paragraph">
                    Kanda ya Academic Zone ya <strong>TASIDO</strong> ina jumla ya shule za Msingi <strong>{{ number_format($data['zone_profile']['total_schools']) }}</strong> ambapo shule za Serikali ni <strong>{{ number_format($data['zone_profile']['government_schools']) }}</strong> na shule za Binafsi/Zisizo za Serikali ni <strong>{{ number_format($data['zone_profile']['private_schools']) }}</strong>. Kiutawala, Kanda inajumuisha Mikoa minne (4) ambayo ni Tabora, Singida, Iringa na Dodoma. Kanda yetu ina jumla ya Halmashauri/Wilaya <strong>{{ number_format($data['zone_profile']['councils_count']) }}</strong>.
                </div>
                
                <div class="doc-paragraph">
                    Jumla ya shule <strong>{{ number_format($data['zone_profile']['total_schools']) }}</strong> zenye watahiniwa wa Darasa la Saba wa mwaka <strong>{{ $examYear }}</strong> zilifanya Mtihani wa Utamilifu wa Kanda (Mock).
                </div>
                
                <div class="doc-paragraph">
                    Jumla ya watahiniwa <strong>{{ number_format($data['attendance']['registered_total']) }}</strong> walisajiliwa kufanya mtihani huu, ikijumuisha Wavulana <strong>{{ number_format($data['attendance']['registered_male']) }}</strong> na Wasichana <strong>{{ number_format($data['attendance']['registered_female']) }}</strong>.
                </div>
                
                <div class="doc-paragraph">
                    Kati ya watahiniwa waliosajiliwa, jumla ya watahiniwa <strong>{{ number_format($data['attendance']['sat_total']) }}</strong> walifanya mtihani huo, ikijumuisha Wavulana <strong>{{ number_format($data['attendance']['sat_male']) }}</strong> na Wasichana <strong>{{ number_format($data['attendance']['sat_female']) }}</strong>, ikiwa ni sawa na asilimia <strong>{{ number_format($data['attendance']['attendance_rate'], 2) }}%</strong> ya watahiniwa wote waliosajiliwa. Jumla ya watahiniwa <strong>{{ number_format($data['attendance']['absent_total']) }}</strong> (Wavulana <strong>{{ number_format($data['attendance']['absent_male']) }}</strong>, Wasichana <strong>{{ number_format($data['attendance']['absent_female']) }}</strong>) sawa na asilimia <strong>{{ number_format($data['attendance']['registered_total'] > 0 ? ($data['attendance']['absent_total'] / $data['attendance']['registered_total']) * 100 : 0, 2) }}%</strong> hawakufanya mtihani kutokana na sababu mbalimbali kama vile utoro, ugonjwa na sababu nyingine za kijamii.
                </div>
                
                <!-- Section 3 -->
                <div class="doc-section-title">3. MAANDALIZI YA MTIHANI</div>
                <div class="doc-paragraph">
                    Maandalizi ya mtihani yalianza kwa uratibu na vikao vya pamoja vilivyowashirikisha Maafisa Elimu wa Kanda na Halmashauri (REOs/DEOs), Maafisa Taaluma (RTOs/DTOs), na Wathibiti Ubora wa Shule katika Kanda. Vikao hivyo vililenga kukubaliana juu ya miongozo ya uendeshaji, usimamizi, usahihishaji, na mifumo ya bajeti.
                </div>
                <div class="doc-paragraph">
                    Katika vikao hivyo, makubaliano yafuatayo yalifikiwa:
                </div>
                <div class="doc-paragraph">
                    1. <strong>Uratibu wa Bajeti:</strong> Kikao kiliazimia kuweka na kupitisha bajeti ya jumla ya shilingi <strong class="preview-budget_amount">{{ number_format((float)$inputs['budget_amount']) }}</strong> kwa ajili ya uzalishaji vya mitihani, ununuzi wa karatasi na vifaa vya ofisi ikijumuisha matengenezo na uendeshaji wa mashine kubwa za chapa (RISSO) ili kurahisisha zoezi la uzalishaji. Bajeti hii ilichangiwa kutoka kwenye vifungu vya ruzuku ya shule (Capitation Grants) na michango maalum ya kila Mkoa/Halmashauri.
                </div>
                <div class="doc-paragraph">
                    2. <strong>Ushirikiano wa Kikanda (Zonal Collaboration):</strong> Mtihani huu wa utamilifu uliandaliwa kupitia ushirikiano wa Kanda ya <strong class="preview-collaborating_regions">{{ $inputs['collaborating_regions'] }}</strong>, ambapo mikoa iligawana majukumu ya uandaaji wa rasimu za awali za mitihani ya masomo yote kulingana na mihutasari mipya ya masomo.
                </div>
                
                <!-- Section 4 -->
                <div class="doc-section-title">4. UTUNGAJI NA MODERATION</div>
                <div class="doc-paragraph">
                    Mchakato wa utungaji na uthibitishaji wa mitihani ulifanyika kwa kufuata kanuni za kitaaluma na usiri mkubwa:
                </div>
                <ul class="doc-list">
                    <li><strong>Uandishi wa Mitihani (Drafting):</strong> Walimu mahiri na wazoefu walichaguliwa kutoka mikoa yote wanachama kufanya utungaji wa mitihani (Item Generation) kwa kuzingatia ramani za mitihani (Table of Specifications/Format) zilizotolewa na Baraza la Mitihani la Tanzania (NECTA).</li>
                    <li><strong>Mapitio na Uhakiki (Moderation):</strong> Baada ya mitihani kutungwa, zoezi la Moderation lilifanyika katika Kituo Teule chini ya Kamati ya Taaluma ya Mkoa wa <strong class="preview-moderation_region">{{ $inputs['moderation_region'] }}</strong> kwa ajili ya kufanya mapitio ya kisarufi, usahihi wa maswali, uwiano wa alama, na kuhakikisha maswali yanapima nyanja zote za utambuzi (cognitive domains).</li>
                </ul>
                
                <!-- Section 5 -->
                <div class="doc-section-title">5. UZALISHAJI NA USAMBAZAJI</div>
                <ul class="doc-list">
                    <li><strong>Uzalishaji (Production):</strong> Mitihani yote ilizalishwa kwa siri na usalama mkubwa chini ya usimamizi wa Kamati ya Mitihani ya Kanda. Zoezi hili lilifanyika katika Chumba Maalum cha Siri (Strong Room) kwa muda wa siku <strong class="preview-production_days">{{ $inputs['production_days'] }}</strong> kwa kutumia mashine za chapa haraka za RISSO <strong class="preview-risso_machine_count">{{ $inputs['risso_machine_count'] }}</strong> yenye thamani ya shilingi <strong class="preview-risso_machine_value">{{ number_format((float)$inputs['risso_machine_value']) }}</strong>.</li>
                    <li><strong>Ulinzi na Usambazaji (Distribution):</strong> Baada ya kazi ya uzalishaji, kufungashwa kwa bahasha kulingana na idadi ya watahiniwa wa kila shule kukamilika, mitihani yote ilihifadhiwa kwenye Strong Room. Baadaye ilikabidhiwa kwa Maafisa Elimu wa Halmashauri na Kamati za Mitihani za Wilaya chini ya ulinzi thabiti wa Jeshi la Polisi na Maafisa Usalama wa Wilaya ili kusambazwa kwenye vituo vya mitihani kwa wakati.</li>
                </ul>
                
                <!-- Section 6 -->
                <div class="doc-section-title">6. UFANYIKAJI NA USIMAMIZI WA MTIHANI</div>
                <div class="doc-paragraph">
                    Mtihani ulianza rasmi tarehe <strong class="preview-exam_start_date">{{ $inputs['exam_start_date'] }}</strong> na kukamilika tarehe <strong class="preview-exam_end_date">{{ $inputs['exam_end_date'] }}</strong> katika shule zote zilizosajiliwa kama vituo vya mitihani.
                </div>
                <div class="doc-paragraph">
                    Zoezi zima la ufanyikaji wa mitihani lilifanyika kwa kufuata ratiba sanifu iliyotolewa na Kamati ya Kanda. Baada ya kukamilika kwa mtihani wa mwisho, wasimamizi wakuu wa vituo walikusanya skripti (karatasi za majibu) na kuzikabidhi kwa Maafisa Elimu wa Wilaya ambao walizisafirisha chini ya ulinzi hadi Kituo Kikuu cha Usahihishaji cha Kanda kilichopo shule ya <strong class="preview-marking_center">{{ $inputs['marking_center'] }}</strong>.
                </div>
                
                <!-- Section 7 -->
                <div class="doc-section-title">7. USAHIHISHAJI NA UINGIZAJI WA ALAMA</div>
                <ul class="doc-list">
                    <li><strong>Semina na Maelekezo ya Awali:</strong> Kabla ya kuanza kwa usahihishaji, Kamati ya Mitihani ya Kanda chini ya Mwenyekiti wake ilifanya semina ya ulinzi na maadili ya usahihishaji kwa wasahihishaji wote. Mada zilizowasilishwa ni:
                        <ol style="margin-left: 20px; margin-top: 5px;">
                            <li><em>Usalama na Usiri wa Mitihani:</em> Iliyowasilishwa na Mwakilishi wa RSO <strong class="preview-rso_name">{{ $inputs['rso_name'] }}</strong>.</li>
                            <li><em>Sheria na Taratibu za Usahihishaji na Usimamizi:</em> Iliyowasilishwa na Afisa Taaluma Ndg. <strong class="preview-rto_name">{{ $inputs['rto_name'] }}</strong>.</li>
                            <li><em>Uratibu wa Kituo:</em> Uliosimamiwa na Ndg. <strong class="preview-exam_coordinator_name">{{ $inputs['exam_coordinator_name'] }}</strong>.</li>
                        </ol>
                    </li>
                    <li><strong>Uendeshaji wa Usahihishaji:</strong> Zoezi la usahihishaji lilifanyika kwa siku <strong class="preview-marking_days">{{ $inputs['marking_days'] }}</strong> na lilihusisha jumla ya wasahihishaji <strong class="preview-markers_count">{{ $inputs['markers_count'] }}</strong> (ikijumuisha walimu na wasaidizi wataalamu <strong class="preview-students_assistants_count">{{ $inputs['students_assistants_count'] }}</strong>). Usahihishaji ulifanyika kwa makundi ya kimasomo (Subject Panels) kwa kutumia miongozo ya usahihishaji (Marking Schemes) iliyohakikiwa.</li>
                    <li><strong>Uingizaji Alama kwenye Mfumo (Data Entry):</strong> Baada ya usahihishaji wa kila somo kukamilika na kufanyiwa uhakiki wa kwanza (Audit/Verification), karatasi za alama (Score Sheets) zilikabidhiwa kwa Timu ya TEHAMA (IT Team) ya Kanda kwa ajili ya kuingiza alama (Marks Entry) kwenye Mfumo wa Usimamizi wa Matokeo wa IRMS. Mfumo huu ulifanya kazi ya kukokotoa daraja la ufaulu na wastani wa alama kwa kila mwanafunzi kiotomatiki kwa usahihi wa hali ya juu.</li>
                </ul>
            </div>
            
            <!-- Page 3: TABLES -->
            <div class="document-page">
                
                <!-- Section 8 -->
                <div class="doc-section-title">8. TAKWIMU ZA USAJILI NA MAHUDHURIO</div>
                <div class="doc-paragraph">
                    Jedwali lifuatalo linaonesha mchanganuo wa watahiniwa waliosajiliwa, waliofanya mtihani, na wasiofanya mtihani kwa kila Mkoa katika Kanda yetu:
                </div>
                
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
                                    <td style="text-align: right; font-weight:600;">{{ number_format($rrow['registered_t']) }}</td>
                                    <td style="text-align: right;">{{ number_format($rrow['sat_m']) }}</td>
                                    <td style="text-align: right;">{{ number_format($rrow['sat_f']) }}</td>
                                    <td style="text-align: right; font-weight:600;">{{ number_format($rrow['sat_t']) }}</td>
                                    <td style="text-align: right;">{{ number_format($rrow['absent_m']) }}</td>
                                    <td style="text-align: right;">{{ number_format($rrow['absent_f']) }}</td>
                                    <td style="text-align: right;">{{ number_format($rrow['absent_t']) }}</td>
                                    <td style="text-align: right; font-weight:600;">{{ number_format($rrow['attendance_rate'], 2) }}%</td>
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
                
                <!-- Section 9 -->
                <div class="doc-section-title">9. TATHMINI YA JUMLA YA KANDA</div>
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
                                    <td style="text-align: right; font-weight:600;">{{ number_format($gRow['sat']) }}</td>
                                    <td style="text-align: right; font-weight:600;">{{ number_format($gRow['pass']) }}</td>
                                    <td style="text-align: right; font-weight:600; color:var(--accent);">{{ number_format($gRow['pct'], 2) }}%</td>
                                </tr>
                            @endforeach
                            <tr class="total-row">
                                <td>{{ $data['performance']['regional']['gender'] }}</td>
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

                <!-- Section 10 -->
                <div class="doc-section-title">10. TATHMINI YA MATOKEO KI-MKOA</div>
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
                                 <th>WASTANI wa Alama</th>
                                 <th>Daraja</th>
                             </tr>
                         </thead>
                         <tbody>
                             @foreach($data['performance']['regions'] as $rRow)
                                 <tr>
                                     <td style="text-align: center;">{{ $rRow['position'] }}</td>
                                     <td>{{ $rRow['name'] }}</td>
                                     <td style="text-align: right;">{{ number_format($rRow['sat']) }}</td>
                                     <td style="text-align: right;">{{ number_format($rRow['pass_ac']) }}</td>
                                     <td style="text-align: right;">{{ number_format($rRow['pass_d']) }}</td>
                                     <td style="text-align: right;">{{ number_format($rRow['fail']) }}</td>
                                     <td style="text-align: right; font-weight:600;">{{ number_format($rRow['average_marks'], 2) }}</td>
                                     <td style="text-align: center; font-weight:600;">{{ $rRow['grade'] }}</td>
                                 </tr>
                             @endforeach
                         </tbody>
                     </table>
                </div>
            </div>

            <!-- Page 4: SCHOOLS & SUBJECTS -->
            <div class="document-page">
                
                <!-- Section 11 -->
                <div class="doc-section-title">11. TATHMINI YA MATOKEO KI-HALMASHAURI</div>
                <div class="table-responsive">
                    <table class="doc-table">
                         <thead>
                             <tr>
                                 <th>Nafasi</th>
                                 <th>Halmashauri</th>
                                 <th>Mkoa</th>
                                 <th>Waliofanya</th>
                                 <th>Waliofaulu (A-C)</th>
                                 <th>Waliofaulu (D)</th>
                                 <th>Waliofeli (E)</th>
                                 <th>WASTANI wa Alama</th>
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
                                     <td style="text-align: right;">{{ number_format($cRow['pass_ac']) }}</td>
                                     <td style="text-align: right;">{{ number_format($cRow['pass_d']) }}</td>
                                     <td style="text-align: right;">{{ number_format($cRow['fail']) }}</td>
                                     <td style="text-align: right; font-weight:600;">{{ number_format($cRow['average_marks'], 2) }}</td>
                                     <td style="text-align: center; font-weight:600;">{{ $cRow['grade'] }}</td>
                                 </tr>
                             @endforeach
                         </tbody>
                     </table>
                </div>

                <!-- Section 12 -->
                <div class="doc-section-title">12. HALMASHAURI BORA KUMI KIKANDA</div>
                <div class="table-responsive">
                    <table class="doc-table">
                         <thead>
                             <tr>
                                 <th>Nafasi</th>
                                 <th>Halmashauri</th>
                                 <th>Mkoa</th>
                                 <th>Watahiniwa</th>
                                 <th>WASTANI wa Alama</th>
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
                                     <td style="text-align: right; font-weight:600;">{{ number_format($tcRow['average_marks'], 2) }}</td>
                                     <td style="text-align: center; font-weight:600;">{{ $tcRow['grade'] }}</td>
                                 </tr>
                             @endforeach
                         </tbody>
                     </table>
                </div>

                <!-- Section 13 -->
                <div class="doc-section-title">13. HALMASHAURI ZA MWISHO KUMI KIKANDA</div>
                <div class="table-responsive">
                    <table class="doc-table">
                         <thead>
                             <tr>
                                 <th>Nafasi</th>
                                 <th>Halmashauri</th>
                                 <th>Mkoa</th>
                                 <th>Watahiniwa</th>
                                 <th>WASTANI wa Alama</th>
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
                                     <td style="text-align: right; font-weight:600;">{{ number_format($bcRow['average_marks'], 2) }}</td>
                                     <td style="text-align: center; font-weight:600;">{{ $bcRow['grade'] }}</td>
                                 </tr>
                             @endforeach
                         </tbody>
                     </table>
                </div>
            </div>

            <!-- Page 5: SCHOOLS -->
            <div class="document-page">
                <!-- Section 14 -->
                <div class="doc-section-title">14. SHULE BORA KUMI KIKANDA</div>
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
                                <th>WASTANI wa Alama</th>
                                <th>Daraja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['performance']['top_schools'] as $sRow)
                                <tr>
                                    <td style="text-align: center;">{{ $sRow['position'] }}</td>
                                    <td>{{ $sRow['name'] }}</td>
                                    <td>{{ $sRow['council'] }}</td>
                                    <td>{{ $sRow['region'] }}</td>
                                    <td>{{ $sRow['ownership'] }}</td>
                                    <td style="text-align: right;">{{ number_format($sRow['sat']) }}</td>
                                    <td style="text-align: right; font-weight: 600;">{{ number_format($sRow['average_marks'], 2) }}</td>
                                    <td style="text-align: center; font-weight: 600;">{{ $sRow['grade'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Section 15 -->
                <div class="doc-section-title">15. SHULE ZA MWISHO KUMI KIKANDA</div>
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
                                <th>WASTANI wa Alama</th>
                                <th>Daraja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['performance']['bottom_schools'] as $sRow)
                                <tr>
                                    <td style="text-align: center;">{{ $sRow['position'] }}</td>
                                    <td>{{ $sRow['name'] }}</td>
                                    <td>{{ $sRow['council'] }}</td>
                                    <td>{{ $sRow['region'] }}</td>
                                    <td>{{ $sRow['ownership'] }}</td>
                                    <td style="text-align: right;">{{ number_format($sRow['sat']) }}</td>
                                    <td style="text-align: right; font-weight: 600;">{{ number_format($sRow['average_marks'], 2) }}</td>
                                    <td style="text-align: center; font-weight: 600;">{{ $sRow['grade'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 6: SUBJECTS & OWNERSHIP -->
            <div class="document-page">
                <!-- Section 16 -->
                <div class="doc-section-title">16. TATHMINI YA UFAULU KWA MASOMO</div>
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
                                <th>WASTANI wa Alama</th>
                                <th>Daraja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['performance']['subjects'] as $subRow)
                                <tr>
                                    <td style="text-align: center;">{{ $subRow['position'] }}</td>
                                    <td>{{ $subRow['name'] }}</td>
                                    <td style="text-align: right;">{{ number_format($subRow['sat']) }}</td>
                                    <td style="text-align: right;">{{ number_format($subRow['pass']) }}</td>
                                    <td style="text-align: right;">{{ number_format($subRow['fail']) }}</td>
                                    <td style="text-align: right; font-weight:600;">{{ number_format($subRow['pass_rate'], 2) }}%</td>
                                    <td style="text-align: right; font-weight:600;">{{ number_format($subRow['average_marks'], 2) }}</td>
                                    <td style="text-align: center; font-weight:600;">{{ $subRow['grade'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Section 17 -->
                <div class="doc-section-title">17. TATHMINI YA UFAULU KWA UMILIKI WA SHULE</div>
                <div class="table-responsive">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th>Umiliki</th>
                                <th>Idadi ya Shule</th>
                                <th>Waliosajiliwa</th>
                                <th>Waliofanya</th>
                                <th>Waliofaulu</th>
                                <th>Waliofeli</th>
                                <th>Ufaulu %</th>
                                <th>WASTANI wa Alama</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['performance']['ownership'] as $ownRow)
                                <tr>
                                    <td>{{ $ownRow['ownership'] }}</td>
                                    <td style="text-align: right;">{{ number_format($ownRow['schools_count']) }}</td>
                                    <td style="text-align: right;">{{ number_format($ownRow['registered']) }}</td>
                                    <td style="text-align: right;">{{ number_format($ownRow['sat']) }}</td>
                                    <td style="text-align: right;">{{ number_format($ownRow['pass']) }}</td>
                                    <td style="text-align: right;">{{ number_format($ownRow['fail']) }}</td>
                                    <td style="text-align: right; font-weight: 600;">{{ number_format($ownRow['pass_rate'], 2) }}%</td>
                                    <td style="text-align: right; font-weight: 600;">{{ number_format($ownRow['average_marks'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Page 7: QUALITY & OBSERVATIONS -->
            <div class="document-page">
                <!-- Section 18 -->
                <div class="doc-section-title">18. UHAKIKI WA UBORA WA DATA</div>
                @if(count($data['data_quality']['issues']) > 0)
                    <div class="dq-alert">
                        <h4>⚠️ Uhakiki wa Data: Mambo yaliyobainika (Observations)</h4>
                        <ul>
                            @foreach($data['data_quality']['issues'] as $issue)
                                <li>{{ $issue }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="dq-success">
                        ✅ Uhakiki wa data umekamilika. Hakuna hitilafu yoyote iliyobainika wakati wa uhakiki wa data ya matokeo ya Kanda.
                    </div>
                @endif

                <!-- Section 19 -->
                <div class="doc-section-title">19. CHANGAMOTO ZILIZOBAINIKA</div>
                <ol class="doc-list" style="margin-left: 20px;">
                    <li><strong>Utofauti wa Taarifa za Bahasha na Skripti:</strong> Kubainika kwa tofauti kati ya idadi ya skripti zilizoandikwa kwenye taarifa ya nje ya bahasha na idadi halisi ya skripti zilizokutikana ndani.</li>
                    <li><strong>Kukosekana kwa ISAL (Individual Subject Attendance Log):</strong> Baadhi ya vituo kukosa fomu rasmi za mahudhurio ya kila somo.</li>
                    <li><strong>Kutojaza Namba na Majina:</strong> Watahiniwa kutoandika namba zao sahihi za usajili.</li>
                </ol>

                <!-- Section 20 -->
                <div class="doc-section-title">20. MAPENDEKEZO</div>
                <ol class="doc-list" style="margin-left: 20px;">
                    <li><strong>Utekelezaji wa Mfumo wa ISAL na CAL Kidijitali:</strong> Ni lazima shule zote kupitia mfumo wa IRMS kupakua karatasi rasmi za mahudhurio.</li>
                    <li><strong>Uhakiki wa Namba za Usajili Vituoni:</strong> Wasimamizi wakuu wa vituo lazima wahakiki namba za usajili za kila mtahiniwa.</li>
                </ol>

                <!-- Section 21 -->
                <div class="doc-section-title">21. HITIMISHO</div>
                <div class="doc-paragraph">
                    Kamati ya Mitihani ya Kanda inatoa shukrani za dhati kwa wadau wote wa elimu walioshiriki kufanikisha mtihani huu wa utamilifu wa Kanda (PSLE Zonal Mock). Matokeo haya yaonyeshe juhudi zinazohitajika katika kipindi kilichobaki kabla ya mtihani wa Taifa wa Darasa la Saba ya kuongeza ufaulu na ubora wa elimu katika kanda yetu.
                </div>

                <!-- Section 22 -->
                <div class="doc-section-title">22. UIDHINISHAJI</div>
                
                <div class="signoff-container">
                    <div class="signoff-block">
                        <h5>Imeandaliwa na:</h5>
                        <div class="line"></div>
                        <p style="font-weight: 700;" class="preview-rto_name">{{ $inputs['rto_name'] }}</p>
                        <p class="preview-prepared_by_title">{{ $inputs['prepared_by_title'] }}</p>
                        <p>Academic Zone: <span class="sync-region-label">TASIDO</span></p>
                        <p>Tarehe: ___________________</p>
                    </div>
                    
                    <div class="signoff-block">
                        <h5>Imehakikiwa na Kuidhinishwa na:</h5>
                        <div class="line"></div>
                        <p style="font-weight: 700;" class="preview-reo_name">{{ $inputs['reo_name'] }}</p>
                        <p class="preview-approved_by_title">{{ $inputs['approved_by_title'] }}</p>
                        <p>Academic Zone: <span class="sync-region-label">TASIDO</span></p>
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
                
                // Format numbers if it is budget or machine value
                if ((name === 'budget_amount' || name === 'risso_machine_value') && !isNaN(val) && val !== '') {
                    val = Number(val).toLocaleString('en-US');
                }
                
                // Find all elements with matching class and update text
                const targetClasses = [`.preview-${name}`, `.preview-${name}_span`];
                targetClasses.forEach(sel => {
                    const targets = document.querySelectorAll(sel);
                    targets.forEach(t => {
                        t.textContent = val;
                    });
                });
            });
        });
    });
</script>

</body>
</html>
