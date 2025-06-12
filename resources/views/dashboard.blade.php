<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Laravel App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container py-5">
        <h1 class="mb-4">Welcome to My Laravel App</h1>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- Error Message --}}
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <p class="lead">This is a simple landing page example. You can customize it however you like.</p>

        <div class="row">
            <div>
                <form action="{{ route('paypal.create') }}" method="post">
                    @csrf

                    <input type="hidden" name="subscription_id" value="1">

                    <input type="submit" value="Subscribe" class="btn btn-primary">
                </form>
            </div>
        </div>

        @if (Route::has('login'))
            <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
            <a href="{{ route('register') }}" class="btn btn-outline-secondary">Register</a>
        @endif
    </div>

</body>

</html>
