@extends('layouts.app')

@section('title', 'Edit Customer')

@section('content')
    <div class="card">
        <h1>Edit Customer</h1>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('customers.update', $customer) }}">
            @csrf
            @method('PUT')
            <label for="name">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name', $customer->name) }}" required>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $customer->email) }}" required>

            <label for="phone">Phone</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone', $customer->phone) }}">

            <label for="address">Address</label>
            <input id="address" name="address" type="text" value="{{ old('address', $customer->address) }}">

            <button type="submit" class="btn">Update Customer</button>
            <a class="btn btn-secondary" href="{{ route('customers.index') }}">Cancel</a>
        </form>
    </div>
@endsection
