@extends('layouts.guest')

@section('content')
<div class="bg-white border border-border-color rounded-md shadow-sm w-full max-w-md sm:p-8 p-5">
    <div class="text-center mb-6">
        <div class="invoice-logo block dark:hidden">
            <a href="{{ route('login') }}"><img src="{{ erp_logo('light') }}" class="h-10 mx-auto mb-4" alt="logo"></a>
        </div>
        <div class="invoice-logo-white hidden dark:block">
            <a href="{{ route('login') }}"><img src="{{ erp_logo('dark') }}" class="h-10 mx-auto mb-4" alt="logo"></a>
        </div>
        <h1 class="text-xl font-bold text-title mb-1">Welcome Back!</h1>
        <p class="text-sm text-default mb-0">Sign in to your {{ erp_setting('trade_name', 'Dreams ERP') }} account</p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-md border border-success/30 bg-success-transparent px-3 py-2 text-xs text-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-md border border-danger/30 bg-danger-transparent px-3 py-2 text-xs text-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <form class="space-y-4" method="POST" action="{{ route('login.post') }}">
        @csrf
        <div>
            <label class="text-sm font-semibold text-gray-900 mb-1 block">Email Address</label>
            <input id="login_email" type="email" name="email" value="{{ old('email', 'admin@dreamserp.com') }}" required class="w-full px-3 py-2.5 text-sm border border-border-color rounded-md bg-white focus:outline-none focus:ring-0" placeholder="you@example.com">
        </div>
        <div>
            <div class="flex justify-between items-center mb-1">
                <label class="text-sm font-semibold text-gray-900">Password</label>
                <a href="{{ route('password.request') }}" class="text-sm text-primary hover:underline">Forgot Password?</a>
            </div>
            <div class="relative">
                <input id="login_password" type="password" name="password" value="password" required class="form-input form-input-icon w-full px-3 py-2.5 text-sm border border-border-color rounded-md bg-white focus:outline-none focus:border-border-color focus:ring-0 h-10" placeholder="************">
                <span class="absolute start-0 top-2.5 ms-3"><i class="icon icon-lock"></i></span>
            </div>
        </div>
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <input type="checkbox" name="remember" class="size-4 rounded border-border-color" id="remember">
                <label for="remember" class="text-sm text-default">Remember me</label>
            </div>
        </div>
        <button type="submit" class="w-full bg-dark text-white py-2.5 rounded-md text-sm font-semibold hover:bg-primary-hover cursor-pointer">Sign In</button>

        <div class="border-t border-border-color pt-4">
            <p class="text-[11px] font-semibold uppercase text-default text-center mb-2">Quick Demo Credentials (password: <code class="text-title">password</code>)</p>
            <div class="grid grid-cols-3 gap-1.5 text-xs">
                <button type="button" onclick="document.getElementById('login_email').value='admin@dreamserp.com';document.getElementById('login_password').value='password'" class="border border-border-color rounded py-1.5 px-2 hover:bg-light text-title cursor-pointer">Admin</button>
                <button type="button" onclick="document.getElementById('login_email').value='manager@dreamserp.com';document.getElementById('login_password').value='password'" class="border border-border-color rounded py-1.5 px-2 hover:bg-light text-title cursor-pointer">Manager</button>
                <button type="button" onclick="document.getElementById('login_email').value='staff@dreamserp.com';document.getElementById('login_password').value='password'" class="border border-border-color rounded py-1.5 px-2 hover:bg-light text-title cursor-pointer">Staff</button>
            </div>
        </div>

        <p class="text-center text-sm text-default mb-0">Don't have an account? <a href="{{ route('register') }}" class="text-primary hover:underline">Sign up</a></p>
    </form>
</div>
@endsection
