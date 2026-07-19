@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    <div class="card">
        <div class="header-row">
            <h1>Customers</h1>
            <a class="btn" href="{{ route('customers.create') }}">Add Customer</a>
        </div>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($customers->isEmpty())
            <p class="empty">No customers yet. Add your first one.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->email }}</td>
                            <td>{{ $customer->phone ?? '—' }}</td>
                            <td>{{ $customer->address ?? '—' }}</td>
                            <td class="actions">
                                <a class="btn btn-small btn-secondary" href="{{ route('customers.edit', $customer) }}">Edit</a>
                                <form action="{{ route('customers.destroy', $customer) }}" method="POST" onsubmit="return confirm('Delete {{ $customer->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="pagination">
                @if ($customers->onFirstPage())
                    <span class="btn btn-small btn-secondary btn-disabled">Previous</span>
                @else
                    <a class="btn btn-small btn-secondary" href="{{ $customers->previousPageUrl() }}">Previous</a>
                @endif

                <span class="pagination-info">Page {{ $customers->currentPage() }} of {{ $customers->lastPage() }}</span>

                @if ($customers->hasMorePages())
                    <a class="btn btn-small btn-secondary" href="{{ $customers->nextPageUrl() }}">Next</a>
                @else
                    <span class="btn btn-small btn-secondary btn-disabled">Next</span>
                @endif
            </div>
        @endif
    </div>
@endsection
