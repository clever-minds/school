@extends('layouts.master')

@section('title')
    {{ __('Manual UPI Transactions') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Manual UPI Transactions') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Transaction List') }}</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Student Name') }}</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Transaction ID') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Date') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->id }}</td>
                                        <td>{{ $transaction->student->user->first_name ?? '' }} {{ $transaction->student->user->last_name ?? '' }}</td>
                                        <td>{{ $transaction->amount }}</td>
                                        <td>{{ $transaction->transaction_id }}</td>
                                        <td>
                                            @if($transaction->status == 'pending')
                                                <label class="badge badge-warning">{{ __('Pending') }}</label>
                                            @elseif($transaction->status == 'success')
                                                <label class="badge badge-success">{{ __('Success') }}</label>
                                            @elseif($transaction->status == 'failed')
                                                <label class="badge badge-danger">{{ __('Failed') }}</label>
                                            @else
                                                <label class="badge badge-info">{{ ucfirst($transaction->status) }}</label>
                                            @endif
                                        </td>
                                        <td>{{ $transaction->created_at->format('Y-m-d H:i:s') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">{{ __('No transactions found.') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $transactions->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
