@extends('layout')

@section('content')
<div class="max-w-md mx-auto mt-10 bg-white p-6 rounded shadow">
    <h2 class="text-2xl font-bold mb-6">Register</h2>
    
    <form method="POST" action="/register" id="registerForm">
        @csrf
        
        <div class="mb-4">
            <label class="block text-gray-700 mb-2">Name</label>
            <input type="text" name="name" value="{{ old('name') }}" class="w-full border p-2 rounded" required autofocus>
            @if ($errors->has('name'))
                <span class="text-red-500 text-sm">{{ $errors->first('name') }}</span>
            @endif
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 mb-2">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="w-full border p-2 rounded" required>
            @if ($errors->has('email'))
                <span class="text-red-500 text-sm">{{ $errors->first('email') }}</span>
            @endif
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 mb-2">Password</label>
            <input type="password" name="password" class="w-full border p-2 rounded" required>
            @if ($errors->has('password'))
                <span class="text-red-500 text-sm">{{ $errors->first('password') }}</span>
            @endif
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 mb-2">Confirm Password</label>
            <input type="password" name="password_confirmation" class="w-full border p-2 rounded" required>
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">Register</button>
    </form>

    <p class="mt-4">Already have an account? <a href="/login" class="text-blue-600">Login</a></p>
</div>
@endsection
