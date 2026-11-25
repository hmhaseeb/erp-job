@extends('layouts.guest')

@section('content')
<div class="bg-white border border-border-color rounded-md shadow-sm w-full max-w-md sm:p-8 p-5">
    <div class="text-center mb-6">
        <a href="{{ route('login') }}"><img src="{{ erp_logo('light') }}" class="h-10 mx-auto mb-4" alt="logo"></a>
        <h1 class="text-xl font-bold text-title mb-1">Reset Password</h1>
        <p class="text-sm text-default mb-0">Enter your email and a new password</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-md border border-danger/30 bg-danger-transparent px-3 py-2 text-xs text-danger">
            {{ $errors->first() }}
        </div>
    @endif
    @if (session('status'))
        <div class="mb-4 rounded-md border border-info/30 bg-info-transparent px-3 py-2 text-xs text-info">
            {{ session('status') }}
        </div>
    @endif

    <form class="space-y-4" method="POST" action="{{ route('password.email') }}">
        @csrf
        <div>
            <label class="text-sm font-semibold text-gray-900 mb-1 block">Email Address</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white" placeholder="you@example.com">
        </div>
        <div>
            <label class="text-sm font-semibold text-gray-900 mb-1 block">New Password</label>
            <input type="password" name="new_password" required class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white" placeholder="Min 6 characters">
        </div>
        <div>
            <label class="text-sm font-semibold text-gray-900 mb-1 block">Confirm New Password</label>
            <input type="password" name="new_password_confirmation" required class="w-full px-3 py-2 text-sm border border-border-color rounded-md bg-white" placeholder="Repeat password">
        </div>
        <button type="submit" class="w-full bg-dark text-white py-2.5 rounded-md text-sm font-semibold hover:bg-primary-hover cursor-pointer">Update Password</button>
        <p class="text-center text-sm text-default mb-0"><a href="{{ route('login') }}" class="text-primary hover:underline">Back to Sign In</a></p>
    </form>
</div>
@endsection
