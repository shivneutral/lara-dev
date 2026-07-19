@extends('layouts.app')

@section('title', 'Add Customer')

@section('content')
    <div class="card">
        <h1>Add Customer</h1>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('customers.store') }}">
            @csrf
            <label for="name">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required>

            <label for="phone">Phone</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone') }}">

            <label for="address">Address</label>
            <input id="address" name="address" type="text" value="{{ old('address') }}">

            <button type="submit" class="btn">Save Customer</button>
            <a class="btn btn-secondary" href="{{ route('customers.index') }}">Cancel</a>
        </form>
    </div>
@endsection
