<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>IRMS - Integrated Results Management System</title>
    <link rel="icon" type="image/png" href="/images/emblem.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
        }

        body {
            font-family: 'Maiandra GD', "Ubuntu Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f7fa;
            color: #212529;
            line-height: 1.6;
            padding-top: 140px;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Maiandra GD', sans-serif;
            font-weight: 700;
            line-height: 1.3;
        }

        button, input, select, textarea {
            font-family: inherit;
        }

        /* Official Header */
        .official-header {
            background: linear-gradient(135deg, #1b5e3f 0%, #2d7a4f 50%, #1b5e3f 100%);
            color: white;
            padding: 0.8rem 2rem;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 999;
            width: 100%;
            height: 100px;
            overflow: hidden;
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 3rem;
            position: relative;
            height: 100%;
        }

        .header-emblem {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .left-emblem {
            left: 2rem;
        }

        .right-emblem {
            right: 2rem;
        }

        .header-emblem img {
            max-width: 100%;
            max-height: 100%;
        }

        .header-text {
            flex: 1;
            text-align: center;
        }

        .header-line-1 {
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.05em;
            margin-bottom: 0;
            line-height: 1.2;
        }

        .header-line-2 {
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.05em;
            margin-bottom: 0;
            line-height: 1.2;
        }

        .header-line-3 {
            font-size: 0.65rem;
            font-weight: 400;
            letter-spacing: 0.02em;
            margin-bottom: 0.3rem;
            opacity: 0.95;
            line-height: 1.2;
        }

        .header-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #ffc107;
            letter-spacing: 0.05em;
            line-height: 1.2;
        }

        /* Navigation Bar - using Tailwind */

        .nav-link {
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 0.25rem 0;
            transition: all 0.2s;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .nav-link:hover {
            color: #ffc107;
            text-decoration: none;
        }

        .nav-dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-toggle {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            background-color: #2d2d2d;
            border: 1px solid #404040;
            border-bottom: 1px solid #2d2d2d;
            padding: 0.5rem 1rem;
            border-radius: 6px 6px 0 0;
            margin: 0;
        }

        .dropdown-toggle i {
            margin-left: 0rem;
            font-size: 0.75rem;
            transition: transform 0.3s ease;
        }

        .nav-dropdown:hover .dropdown-toggle i {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: #2d2d2d;
            border: 1px solid #404040;
            border-top: none;
            border-radius: 0 0 6px 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3), 0 0 1px rgba(0, 0, 0, 0.5);
            min-width: 220px;
            margin-top: 0;
            padding: 0.5rem 0;
            z-index: 1001;
            opacity: 0;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            visibility: hidden;
            pointer-events: none;
        }

        /* Show dropdown on hover */
        .nav-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateY(0);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1rem;
            color: #d0d0d0;
            text-decoration: none !important;
            transition: all 0.15s ease;
            font-weight: 400;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            border-left: 3px solid transparent;
        }

        .dropdown-item:hover {
            background-color: #3a3a3a;
            color: #ffc107;
            border-left-color: #ffc107;
            padding-left: calc(1rem + 3px);
            text-decoration: none !important;
        }

        .dropdown-item i {
            width: 18px;
            text-align: center;
            flex-shrink: 0;
        }

        .dropdown-menu hr {
            margin: 0.4rem 0;
            border-top: 1px solid #404040;
        }

        /* Nested Dropdown */
        .dropdown-submenu {
            position: relative;
        }

        .submenu-toggle {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 0.5rem 1rem !important;
        }

        .submenu-toggle i {
            margin-left: auto;
            font-size: 0.75rem;
        }

        .dropdown-submenu-menu {
            position: absolute;
            top: 0;
            left: 100%;
            background: #2a2a2a;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            min-width: 150px;
            margin-left: -4px;
            padding: 0.5rem 0;
            z-index: 1002;
            opacity: 0;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            visibility: hidden;
            pointer-events: none;
        }

        /* Show submenu on hover */
        .dropdown-submenu:hover > .dropdown-submenu-menu {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }

        .navbar-user {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            position: absolute;
            right: 2rem;
        }

        .user-info {
            color: #ddd;
            font-size: 0.8rem;
        }

        main {
            min-height: 100vh;
            padding-top: 0;
        }

        /* Sidebar Styling */
        .sidebar-link {
            display: inline-block;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s ease;
            padding: 0.25rem 0;
        }

        .sidebar-link:hover {
            text-decoration: none;
            padding-left: 0.5rem;
        }

        /* Mark Entry Sidebar */
        aside {
            box-shadow: inset -2px 0 4px rgba(0, 0, 0, 0.1);
        }

        aside::-webkit-scrollbar {
            width: 6px;
        }

        aside::-webkit-scrollbar-track {
            background: #1f2937;
        }

        aside::-webkit-scrollbar-thumb {
            background: #4b5563;
            border-radius: 3px;
        }

        aside::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 130px;
            }

            .header-emblem {
                width: 60px;
                height: 60px;
            }

            .header-title {
                font-size: 1.2rem;
            }

            .official-header {
                height: 80px;
            }

            .navbar {
                top: 80px;
                height: auto;
            }

            .navbar-content {
                padding: 0 1rem;
                flex-wrap: wrap;
                gap: 1rem;
            }

            .navbar-nav {
                order: 3;
                flex-basis: 100%;
                gap: 1rem;
            }

            .navbar-user {
                order: 2;
                margin-left: auto;
            }

            /* Hide sidebar on mobile */
            aside {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Official Header -->
    <div class="official-header">
        <div class="header-content">
            <div class="header-text">
                <div class="header-line-1">PRIME MINISTER'S OFFICE</div>
                <div class="header-line-2">REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT</div>
                <div class="header-line-3">TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, TABORA, LINDI AND MTWARA</div>
                <div class="header-title">INTEGRATED RESULT MANAGEMENT SYSTEM</div>
            </div>
            <div class="header-emblem left-emblem">
                <img src="/images/emblem.png" alt="Emblem">
            </div>
            <div class="header-emblem right-emblem">
                <img src="/images/emblem.png" alt="Emblem">
            </div>
        </div>
    </div>

    <!-- Navigation Header -->
    <nav class="navbar fixed top-[100px] left-0 right-0 w-full h-10 shadow-md" style="z-index: 1000; background-color: #2d2d2d; border-bottom: 2px solid #404040;">
        <div class="navbar-content max-w-7xl mx-auto px-8 flex items-center justify-center h-full">
            <div class="navbar-nav flex gap-8 items-center">
                <a href="/dashboard" class="nav-link">HOME</a>

                <!-- REGISTRATION Dropdown -->
                <div class="nav-dropdown">
                    <button class="nav-link dropdown-toggle">REGISTRATION <i class="fas fa-chevron-down"></i></button>
                    <div class="dropdown-menu">
                        <a href="/registration" class="dropdown-item">Dashboard</a>
                        <a href="/registration/regions" class="dropdown-item">Regions</a>
                        <a href="/registration/districts" class="dropdown-item">Districts</a>
                        <a href="/registration/schools" class="dropdown-item">Schools</a>
                        <a href="/registration/candidates" class="dropdown-item">Candidates</a>
                    </div>
                </div>

                <!-- EXAM TYPE Dropdown -->
                <div class="nav-dropdown">
                    <button class="nav-link dropdown-toggle">EXAM TYPE <i class="fas fa-chevron-down"></i></button>
                    <div class="dropdown-menu">
                        <a href="/exam-types/psle" class="dropdown-item">PSLE</a>
                        <a href="/exam-types/csee" class="dropdown-item">CSEE</a>
                        <a href="/exam-types/acsee" class="dropdown-item">ACSEE</a>
                    </div>
                </div>

                <!-- MARK ENTRY Dropdown -->
                <div class="nav-dropdown">
                    <button class="nav-link dropdown-toggle">MARK ENTRY <i class="fas fa-chevron-down"></i></button>
                    <div class="dropdown-menu">
                        <a href="/mark-entry/psle" class="dropdown-item">PSLE</a>
                        <a href="/mark-entry/csee" class="dropdown-item">CSEE</a>
                        <a href="/mark-entry/acsee" class="dropdown-item">ACSEE</a>
                    </div>
                </div>

                <!-- RESULTS Dropdown -->
                <div class="nav-dropdown">
                    <button class="nav-link dropdown-toggle">RESULTS <i class="fas fa-chevron-down"></i></button>
                    <div class="dropdown-menu">
                        <a href="/results/psle" class="dropdown-item">PSLE</a>
                        <a href="/results/csee" class="dropdown-item">CSEE</a>
                        <a href="/results/acsee" class="dropdown-item">ACSEE</a>
                        <hr style="margin: 0.4rem 0; border-top: 1px solid #404040;">
                        <a href="/results/2026/acsee" class="dropdown-item">
                            <i class="fas fa-globe"></i> PUBLIC RESULTS (2026 ACSEE)
                        </a>
                    </div>
                </div>

                <!-- EVALUATIONS Dropdown -->
                <div class="nav-dropdown">
                    <button class="nav-link dropdown-toggle">EVALUATIONS <i class="fas fa-chevron-down"></i></button>
                    <div class="dropdown-menu">
                        <a href="/evaluations/psle" class="dropdown-item">PSLE</a>
                        <a href="/evaluations/csee" class="dropdown-item">CSEE</a>
                        <a href="/evaluations/acsee" class="dropdown-item">ACSEE</a>
                    </div>
                </div>

                <!-- SETTINGS Dropdown -->
                <div class="nav-dropdown">
                    <button class="nav-link dropdown-toggle">SETTINGS <i class="fas fa-chevron-down"></i></button>
                    <div class="dropdown-menu">
                        @auth
                            @if(auth()->user()->isAdmin())
                                <a href="/admin" class="dropdown-item">
                                    <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                                </a>
                                <a href="/admin/users" class="dropdown-item">
                                    <i class="fas fa-users"></i> User Management
                                </a>
                                <a href="/admin/backups" class="dropdown-item">
                                    <i class="fas fa-database"></i> Backups & Restore
                                </a>
                                <a href="/admin/system-settings" class="dropdown-item">
                                    <i class="fas fa-sliders-h"></i> System Settings
                                </a>
                                <a href="/admin/exam-years" class="dropdown-item">
                                    <i class="fas fa-calendar-alt"></i> Exam Years
                                </a>
                                <hr class="my-2 opacity-30">
                            @endif
                        @endauth
                        <a href="/" class="dropdown-item">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </div>
                </div>
            </div>

            <div class="navbar-user absolute right-8 flex items-center gap-6">
                @auth
                    <form method="POST" action="/logout" class="inline">
                        @csrf
                        <button type="submit" class="nav-link">Logout</button>
                    </form>
                @else
                    <a href="/login" class="nav-link">Login</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="min-h-screen p-0">
        @if (isset($errors) && is_object($errors) && method_exists($errors, 'any') && $errors->any())
            <div class="w-full px-8 mt-6">
                <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4 border border-red-300">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li class="text-sm">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if (session('success'))
            <div class="w-full px-8 mt-6">
                <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-4 border border-green-300">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Modal -->
    <div id="modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-2xl max-w-md w-full mx-4">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h2 id="modal-title" class="text-2xl font-bold text-gray-800"></h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="modal-content" class="p-6">
            </div>
        </div>
    </div>

    <script>
        function openModal(title, content) {
            document.getElementById('modal-title').textContent = title;
            document.getElementById('modal-content').innerHTML = content;
            document.getElementById('modal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('modal').classList.add('hidden');
        }

        document.getElementById('modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
