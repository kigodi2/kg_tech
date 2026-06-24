<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TAARIFA MOCK DRS VII 2026 TASIDO - SETTINGS</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
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
            padding: 40px 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
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

        .panel-header {
            padding: 30px;
            background: linear-gradient(135deg, var(--primary) 0%, #1e293b 100%);
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .panel-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .panel-header p {
            font-size: 0.88rem;
            opacity: 0.8;
            margin-top: 6px;
        }

        .panel-body {
            padding: 30px;
        }

        .section-title {
            font-family: 'Outfit', sans-serif;
            font-size: 0.9rem;
            text-transform: uppercase;
            font-weight: 800;
            color: #475569;
            margin-bottom: 16px;
            margin-top: 24px;
            letter-spacing: 1.2px;
            border-left: 4px solid var(--accent);
            padding-left: 10px;
        }
        
        .section-title:first-of-type {
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            margin-bottom: 6px;
            color: #475569;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.9rem;
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
            gap: 15px;
        }

        .panel-footer {
            padding: 24px 30px;
            border-top: 1px solid rgba(0,0,0,0.06);
            background: rgba(255,255,255,0.7);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.92rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            border: none;
            gap: 8px;
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
    </style>
</head>
<body>

<div id="toast-container"></div>

<div class="container">
    <div class="glass-panel">
        <div class="panel-header">
            <h1>⚙️ TAARIFA MOCK DRS VII 2026 TASIDO - SETTINGS</h1>
            <p>Hariri mipangilio na vigezo vya kuzalisha ripoti ya kitaifa ya darasa la saba TASIDO</p>
        </div>
        
        <form id="settingsForm" method="POST" action="{{ route('evaluations.psle.zonalwise.taarifa-tasido.save-settings') }}" class="panel-body">
            @csrf
            
            <div class="section-title">Maelezo ya Jalada (Cover Page)</div>
            
            <div class="form-group">
                <label for="report_title">Report Title (Menu/Tab Title)</label>
                <input type="text" id="report_title" name="report_title" class="form-control" value="{{ $settings['report_title'] ?? 'TAARIFA MOCK DRS VII 2026 TASIDO' }}">
            </div>
            
            <div class="form-group">
                <label for="cover_title">Cover Title (Jina la Jalada)</label>
                <input type="text" id="cover_title" name="cover_title" class="form-control" value="{{ $settings['cover_title'] ?? 'TAARIFA YA MTIHANI WA UTAMILIFU DARASA LA SABA MWAKA 2026 TASIDO' }}">
            </div>
            
            <div class="form-group">
                <label for="subtitle">Subtitle (Mikoa ya Kanda)</label>
                <input type="text" id="subtitle" name="subtitle" class="form-control" value="{{ $settings['subtitle'] ?? 'TABORA, SINGIDA, IRINGA NA DODOMA' }}">
            </div>
            
            <div class="form-group">
                <label for="office_heading">Office Heading (Ofisi Kuu)</label>
                <input type="text" id="office_heading" name="office_heading" class="form-control" value="{{ $settings['office_heading'] ?? 'OFISI YA WAZIRI MKUU / TAWALA ZA MIKOA NA SERIKALI ZA MITAA' }}">
            </div>

            <div class="form-group">
                <label for="secretariat">Secretariat / Eneo la Sekretarieti</label>
                <input type="text" id="secretariat" name="secretariat" class="form-control" value="{{ $settings['secretariat'] ?? 'SEKRETARIETI YA KANDA, / TASIDO / DODOMA / JUNI, 2026' }}">
            </div>

            <div class="form-group">
                <label for="exam_dates">Tarehe za Kufanyika Mtihani</label>
                <input type="text" id="exam_dates" name="exam_dates" class="form-control" value="{{ $settings['exam_dates'] ?? '20/05/2026 na 21/05/2026' }}">
            </div>

            <div class="section-title">Muundo wa PDF & Margins</div>

            <div class="row-grid">
                <div class="form-group">
                    <label for="font_family">Font Family</label>
                    <select id="font_family" name="font_family" class="form-control">
                        <option value="default" {{ ($settings['font_family'] ?? 'default') === 'default' ? 'selected' : '' }}>Default</option>
                        <option value="times new roman" {{ ($settings['font_family'] ?? '') === 'times new roman' ? 'selected' : '' }}>Times New Roman</option>
                        <option value="arial narrow" {{ ($settings['font_family'] ?? '') === 'arial narrow' ? 'selected' : '' }}>Arial Narrow</option>
                        <option value="maiandra gd" {{ ($settings['font_family'] ?? '') === 'maiandra gd' ? 'selected' : '' }}>Maiandra GD</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="show_logo">Onyesha Nembo ya Taifa (Emblem)</label>
                    <select id="show_logo" name="show_logo" class="form-control">
                        <option value="1" {{ ($settings['show_logo'] ?? '1') == '1' ? 'selected' : '' }}>Ndiyo</option>
                        <option value="0" {{ ($settings['show_logo'] ?? '') == '0' ? 'selected' : '' }}>Hapana</option>
                    </select>
                </div>
            </div>

            <div class="row-grid">
                <div class="form-group">
                    <label for="margin_left">Margin Kushoto (Left - mm)</label>
                    <input type="number" id="margin_left" name="margin_left" class="form-control" value="{{ $settings['margin_left'] ?? 20 }}">
                </div>
                <div class="form-group">
                    <label for="margin_right">Margin Kulia (Right - mm)</label>
                    <input type="number" id="margin_right" name="margin_right" class="form-control" value="{{ $settings['margin_right'] ?? 20 }}">
                </div>
            </div>

            <div class="row-grid">
                <div class="form-group">
                    <label for="margin_top">Margin Juu (Top - mm)</label>
                    <input type="number" id="margin_top" name="margin_top" class="form-control" value="{{ $settings['margin_top'] ?? 20 }}">
                </div>
                <div class="form-group">
                    <label for="margin_bottom">Margin Chini (Bottom - mm)</label>
                    <input type="number" id="margin_bottom" name="margin_bottom" class="form-control" value="{{ $settings['margin_bottom'] ?? 20 }}">
                </div>
            </div>

            <div class="section-title">Viongozi & Wasimamizi (Sign-offs)</div>
            
            <div class="form-group">
                <label for="reo_name">Jina la REO (Afisa Elimu Mkoa)</label>
                <input type="text" id="reo_name" name="reo_name" class="form-control" value="{{ $settings['reo_name'] ?? '' }}">
            </div>
            
            <div class="form-group">
                <label for="rto_name">Jina la RTO (Afisa Taaluma Mkoa)</label>
                <input type="text" id="rto_name" name="rto_name" class="form-control" value="{{ $settings['rto_name'] ?? '' }}">
            </div>
            
            <div class="form-group">
                <label for="rso_name">Jina la RSO (Afisa Usalama Mkoa)</label>
                <input type="text" id="rso_name" name="rso_name" class="form-control" value="{{ $settings['rso_name'] ?? '' }}">
            </div>
            
            <div class="form-group">
                <label for="exam_coordinator_name">Mratibu Ndg. (Kamati ya Taaluma)</label>
                <input type="text" id="exam_coordinator_name" name="exam_coordinator_name" class="form-control" value="{{ $settings['exam_coordinator_name'] ?? '' }}">
            </div>

            <div class="section-title">Kituo cha Usahihishaji</div>
            
            <div class="form-group">
                <label for="marking_center">Jina la Kituo cha Usahihishaji</label>
                <input type="text" id="marking_center" name="marking_center" class="form-control" value="{{ $settings['marking_center'] ?? '' }}">
            </div>
            
            <div class="form-group">
                <label for="moderation_region">Mkoa wa Moderation</label>
                <input type="text" id="moderation_region" name="moderation_region" class="form-control" value="{{ $settings['moderation_region'] ?? '' }}">
            </div>

            <div class="section-title">Takwimu za Uendeshaji & Bajeti</div>
            
            <div class="row-grid">
                <div class="form-group">
                    <label for="production_days">Siku za Chapa (Production Days)</label>
                    <input type="number" id="production_days" name="production_days" class="form-control" value="{{ $settings['production_days'] ?? 0 }}">
                </div>
                <div class="form-group">
                    <label for="marking_days">Siku za Usahihishaji (Marking Days)</label>
                    <input type="number" id="marking_days" name="marking_days" class="form-control" value="{{ $settings['marking_days'] ?? 0 }}">
                </div>
            </div>

            <div class="row-grid">
                <div class="form-group">
                    <label for="markers_count">Wasahihishaji (Markers Count)</label>
                    <input type="number" id="markers_count" name="markers_count" class="form-control" value="{{ $settings['markers_count'] ?? 0 }}">
                </div>
                <div class="form-group">
                    <label for="students_assistants_count">Wasaidizi Wataalamu</label>
                    <input type="number" id="students_assistants_count" name="students_assistants_count" class="form-control" value="{{ $settings['students_assistants_count'] ?? 0 }}">
                </div>
            </div>

            <div class="form-group">
                <label for="budget_amount">Bajeti Iliyoidhinishwa (Tsh)</label>
                <input type="number" id="budget_amount" name="budget_amount" class="form-control" value="{{ $settings['budget_amount'] ?? 0 }}">
            </div>

            <div class="row-grid">
                <div class="form-group">
                    <label for="risso_machine_count">Idadi Mashine RISSO</label>
                    <input type="number" id="risso_machine_count" name="risso_machine_count" class="form-control" value="{{ $settings['risso_machine_count'] ?? 0 }}">
                </div>
                <div class="form-group">
                    <label for="risso_machine_value">Thamani Mashine RISSO (Tsh)</label>
                    <input type="number" id="risso_machine_value" name="risso_machine_value" class="form-control" value="{{ $settings['risso_machine_value'] ?? 0 }}">
                </div>
            </div>

            <div class="section-title">Ushirikiano wa Kanda (Historical)</div>
            
            <div class="form-group">
                <label for="collaborating_regions">Mikoa Shiriki (Mfano: Singida, Tabora na Dodoma)</label>
                <input type="text" id="collaborating_regions" name="collaborating_regions" class="form-control" value="{{ $settings['collaborating_regions'] ?? '' }}">
            </div>

            <div class="form-group">
                <label for="prepared_by_title">Cheo cha Mtayarishaji</label>
                <input type="text" id="prepared_by_title" name="prepared_by_title" class="form-control" value="{{ $settings['prepared_by_title'] ?? 'AFISA TAALUMA MKOA (RTO)' }}">
            </div>
            
            <div class="form-group">
                <label for="approved_by_title">Cheo cha Mthibitishaji</label>
                <input type="text" id="approved_by_title" name="approved_by_title" class="form-control" value="{{ $settings['approved_by_title'] ?? 'AFISA ELIMU MKOA (REO)' }}">
            </div>

        </form>
        
        <div class="panel-footer">
            <a href="{{ route('evaluations.psle.zonalwise.taarifa-tasido') }}" class="btn btn-secondary">
                ← Ghairi & Rudi
            </a>
            <button type="submit" form="settingsForm" class="btn btn-success">
                💾 Hifadhi Mipangilio
            </button>
        </div>
    </div>
</div>

<script>
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
