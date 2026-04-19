<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRMS - Integrated Results Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold">IRMS</h1>
            <div class="flex gap-4">
                @auth
                    <a href="/dashboard" class="hover:bg-blue-700 px-4 py-2 rounded">Dashboard</a>
                    <form method="POST" action="/logout" class="inline">
                        @csrf
                        <button type="submit" class="hover:bg-blue-700 px-4 py-2 rounded">Logout</button>
                    </form>
                @else
                    <a href="/login" class="hover:bg-blue-700 px-4 py-2 rounded">Login</a>
                @endauth
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-4">
        <div class="mt-20 text-center">
            <h2 class="text-4xl font-bold mb-4">Integrated Results Management System</h2>
            <p class="text-xl text-gray-600 mb-8">Manage exam results, candidates, and schools efficiently</p>
            
            @guest
                <div class="flex gap-4 justify-center">
                    <a href="/login" class="bg-gray-600 text-white px-8 py-3 rounded text-lg">Sign In</a>
                </div>
            @else
                <a href="/dashboard" class="bg-blue-600 text-white px-8 py-3 rounded text-lg inline-block">Go to Dashboard</a>
            @endguest
        </div>
    </div>
</body>
</html>
