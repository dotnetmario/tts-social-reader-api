@extends('auth.authlayout')

@section('content')
    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif



    <h1 class="auth-title">{{ __('app.login.title') }}</h1>
    <p class="auth-subtitle mb-5">{{ __('app.login.subtitle') }}</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- email --}}
        <div class="form-group position-relative has-icon-left mb-4">
            <input type="email" class="form-control form-control-xl @error('email') is-invalid @enderror"
                placeholder="{{ __('app.user.email') }}" name="email" id="email" value="{{ old('email') ?? 'mario@email.com' }}" required
                autofocus>
            <div class="form-control-icon">
                <i class="bi bi-person"></i>
            </div>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- password --}}
        <div class="form-group position-relative has-icon-left mb-4">
            <input type="password" class="form-control form-control-xl @error('password') is-invalid @enderror"
                placeholder="{{ __('app.user.email') }}" name="password" id="password" required value="password">
            <div class="form-control-icon">
                <i class="bi bi-shield-lock"></i>
            </div>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- remember me --}}
        <div class="form-check form-check-lg d-flex align-items-end">
            <input class="form-check-input me-2 @error('password') is-invalid @enderror" type="checkbox" value=""
                id="flexCheckDefault" name="remember_me">
            <label class="form-check-label text-gray-600" for="flexCheckDefault">
                {{ __('app.login.keepmeloggedin') }}
            </label>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg shadow-lg mt-5">{{ __('app.login.title') }}</button>
    </form>
    {{-- redirect to registration --}}
    <div class="text-center mt-5 text-lg fs-4">
        <p class="text-gray-600">{{ __('app.login.donthaveaccount') }}
            <a href="{{ route('register') }}" class="font-bold">{{ __('app.register.title') }}</a>.
        </p>
        <p><a class="font-bold" href="{{ route('register') }}">{{ __('app.login.forgotpassword') }}</a>.
        </p>
    </div>











    {{-- <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                                id="email" value="{{ old('email') }}" required autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                name="password" id="password" required value="password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>

                    <p class="mt-3 text-center small">
                        Don't have an account?
                        <a href="{{ route('register') }}">Register here</a>
                    </p> --}}
@endsection
