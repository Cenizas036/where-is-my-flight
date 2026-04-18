@extends('layouts.app')
@section('title', 'Sign In')

@section('content')
<div class="max-w-md mx-auto pt-12">

    <div class="text-center mb-8">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-wimf-400 to-wimf-600 flex items-center justify-center mx-auto shadow-lg shadow-wimf-500/20 mb-4">
            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-white">Welcome back</h1>
        <p class="text-gray-400 text-sm mt-1">Sign in to track flights and contribute gate updates</p>
    </div>

    <div class="glass-card rounded-2xl p-8">
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="text-sm text-gray-400 mb-2 block">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-3 rounded-xl bg-gray-900 border border-gray-700 text-white placeholder-gray-500 focus:border-wimf-500 focus:ring-2 focus:ring-wimf-500/20 outline-none transition-all wimf-input"
                       placeholder="you@example.com">
                @error('email')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="text-sm text-gray-400 mb-2 block">Password</label>
                <input id="password" type="password" name="password" required
                       class="w-full px-4 py-3 rounded-xl bg-gray-900 border border-gray-700 text-white placeholder-gray-500 focus:border-wimf-500 focus:ring-2 focus:ring-wimf-500/20 outline-none transition-all wimf-input"
                       placeholder="••••••••">
                @error('password')
                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" name="remember"
                           class="w-4 h-4 rounded border-gray-700 bg-gray-900 text-wimf-600 focus:ring-wimf-500/20">
                    <span class="text-sm text-gray-400">Remember me</span>
                </label>
            </div>

            <button type="submit"
                    class="w-full py-3 rounded-xl bg-wimf-600 text-white font-semibold hover:bg-wimf-500 shadow-lg shadow-wimf-600/20 transition-all">
                Sign In
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            Don't have an account?
            <a href="{{ route('register') }}" class="text-wimf-400 hover:text-wimf-300 font-medium transition-colors">
                Join now
            </a>
        </p>
    </div>
</div>
@endsection
