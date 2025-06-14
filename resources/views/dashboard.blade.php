

@extends('layout')

@section('content')
    <p class="lead">This is a simple landing page example. You can customize it however you like.</p>
    @if (Auth::check())
        <p class="lead">User connected is {{ auth()->user()->email }}</p>
    @endif

    <div class="row">
        <div>
            <form action="{{ route('paypal.create') }}" method="post">
                @csrf

                <input type="hidden" name="subscription_id" value="1">

                <input type="submit" value="Subscribe" class="btn btn-primary">
            </form>
        </div>
    </div>
@endsection
