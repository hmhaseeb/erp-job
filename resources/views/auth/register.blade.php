@extends('layouts.guest')

@section('content')
<div class="bg-white border border-border-color rounded-md shadow-sm w-full max-w-md sm:p-8 p-5">
    <div class="text-center mb-6">
        <a href="{{ route('login') }}"><img src="{{ erp_logo('light') }}" class="h-10 mx-auto mb-4" alt="logo"></a>
        <h1 class="text-xl font-bold text-title mb-1">Create Account</h1>
        <p class="text-sm text-default mb-0">Register a staff account for {{ erp_setting('trade_name', 'Dreams ERP') }}</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-md border border-danger/30 bg-danger-transparent px-3 py-2 text-xs text-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form class="space-y-4" method="POST" action="{{ route('register.post') }}">
        @csrf
        <div>
            <label class="text-sm font-semibold text-gray-900 mb-1 block">Full Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white" placeholder="Jane Doe">
        </div>
        <div>
            <label class="text-sm font-semibold text-gray-900 mb-1 block">Email Address</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white" placeholder="you@example.com">
        </div>
        <div>
            <label class="text-sm font-semibold text-gray-900 mb-1 block">Phone</label>
            <input type="text" name="phone" value="{{ old('phone') }}" class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white" placeholder="+1 555 0100">
        </div>
        <div>
            <label class="text-sm font-semibold text-gray-900 mb-1 block">Password</label>
            <input type="password" name="password" required class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white" placeholder="Min 6 characters">
        </div>
        <div>
            <label class="text-sm font-semibold text-gray-900 mb-1 block">Confirm Password</label>
            <input type="password" name="password_confirmation" required class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white" placeholder="Repeat password">
        </div>
        <button type="submit" class="w-full bg-dark text-white py-2.5 rounded-md text-sm font-semibold hover:bg-primary-hover cursor-pointer">Create Account</button>
        <p class="text-center text-sm text-default mb-0">Already have an account? <a href="{{ route('login') }}" class="text-primary hover:underline">Sign in</a></p>
    </form>
</div>
@endsection
