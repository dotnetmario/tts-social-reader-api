@extends('auth.authlayout')


@section('content')
    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif







    <h1 class="auth-title">{{ __('app.register.title') }}</h1>
    <p class="auth-subtitle mb-5">{{ __('app.register.subtitle') }}</p>

    <form method="POST" action="{{ route('register') }}">

        @csrf




        {{-- firstname --}}
        <div class="form-group position-relative has-icon-left mb-4">
            <input type="text" class="form-control form-control-xl @error('firstname') is-invalid @enderror"
                placeholder="{{ __('app.user.firstname') }}" name="firstname" id="firstname" value="{{ old('firstname') }}"
                required autofocus>
            <div class="form-control-icon">
                <i class="bi bi-person"></i>
            </div>
            @error('firstname')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group position-relative has-icon-left mb-4">
            <input type="text" class="form-control form-control-xl @error('lastname') is-invalid @enderror"
                placeholder="{{ __('app.user.lastname') }}" name="lastname" id="lastname" value="{{ old('lastname') }}"
                required>
            <div class="form-control-icon">
                <i class="bi bi-person"></i>
            </div>
            @error('lastname')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group position-relative has-icon-left mb-4">
            <input type="email" class="form-control form-control-xl @error('email') is-invalid @enderror"
                placeholder="{{ __('app.user.email') }}" name="email" id="email" value="{{ old('email') }}"
                required>
            <div class="form-control-icon">
                <i class="bi bi-envelope"></i>
            </div>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>


        <div class="form-group position-relative has-icon-left mb-4">
            <input type="tel" class="form-control form-control-xl @error('phone') is-invalid @enderror"
                placeholder="{{ __('app.user.phone') }}" name="phone" id="phone" value="{{ old('phone') }}"
                pattern="^\+\d{1,3}\d{9,10}$">
            <div class="form-control-icon">
                <i class="bi bi-telephone"></i>
            </div>
            @error('phone')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>



        {{-- password --}}
        <div class="form-group position-relative has-icon-left mb-4">
            <input type="password" class="form-control form-control-xl @error('password') is-invalid @enderror"
                placeholder="{{ __('app.user.password') }}" name="password" id="password" required value="password">
            <div class="form-control-icon">
                <i class="bi bi-shield-lock"></i>
            </div>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group position-relative has-icon-left mb-4">
            <input type="password" class="form-control form-control-xl @error('password_confirmation') is-invalid @enderror"
                placeholder="{{ __('app.user.password_confirmation') }}" name="password_confirmation"
                id="password_confirmation" required value="password">
            <div class="form-control-icon">
                <i class="bi bi-shield-lock"></i>
            </div>
            @error('password_confirmation')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- remember me --}}
        {{-- <div class="form-check form-check-lg d-flex align-items-end">
            <input class="form-check-input me-2 @error('password') is-invalid @enderror" type="checkbox" value=""
                id="flexCheckDefault" name="remember_me">
            <label class="form-check-label text-gray-600" for="flexCheckDefault">
                {{ __('app.login.keepmeloggedin') }}
            </label>
        </div> --}}

        <button type="submit"
            class="btn btn-primary btn-block btn-lg shadow-lg mt-5">{{ __('app.register.title') }}</button>
    </form>
    {{-- redirect to login --}}
    <div class="text-center mt-5 text-lg fs-4">
        <p class="text-gray-600">{{ __('app.register.alreadyhaveaccount') }}
            <a href="{{ route('login') }}" class="font-bold">{{ __('app.login.title') }}</a>.
        </p>
        <p><a class="font-bold" href="{{ route('register') }}">{{ __('app.login.forgotpassword') }}</a>.
        </p>
    </div>







    {{--     
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title mb-4">Register</h4>

                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="firstname" class="form-label">Firstname</label>
                            <input type="text" class="form-control @error('firstname') is-invalid @enderror" name="firstname"
                                id="firstname" value="{{ old('firstname') }}" required autofocus>
                            @error('firstname')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="lastname" class="form-label">Lastname</label>
                            <input type="text" class="form-control @error('lastname') is-invalid @enderror" name="lastname"
                                id="lastname" value="{{ old('lastname') }}" required autofocus>
                            @error('lastname')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                                id="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="phone" class="form-control @error('phone') is-invalid @enderror" name="phone"
                                id="phone" value="{{ old('phone') }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                name="password" id="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" name="password_confirmation"
                                id="password_confirmation" required>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Register</button>
                    </form>

                    <p class="mt-3 text-center small">
                        Already have an account?
                        <a href="{{ route('login') }}">Login here</a>
                    </p>
                </div>
            </div>
        </div>
    </div> --}}
@endsection
