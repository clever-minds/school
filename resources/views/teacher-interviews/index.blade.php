@extends('layouts.master')

@section('title')
    {{ __('Teacher Interviews') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Manage Teacher Interviews') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('Applications List') }}
                        </h4>
                        
                        <div class="table-responsive">
                            <table class="table" id="table_list">
                                <thead>
                                    <tr>
                                        <th>{{ __('No.') }}</th>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Email') }}</th>
                                        <th>{{ __('Phone') }}</th>
                                        <th>{{ __('Applied On') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($applications as $key => $application)
                                        <tr>
                                            <td>{{ $applications->firstItem() + $key }}</td>
                                            <td>{{ $application->name }}</td>
                                            <td>{{ $application->email }}</td>
                                            <td>{{ $application->phone }}</td>
                                            <td>{{ $application->created_at->format('d M, Y') }}</td>
                                            <td>
                                                @if($application->status == 'Pending')
                                                    <span class="badge badge-warning">{{ $application->status }}</span>
                                                @elseif($application->status == 'Rejected')
                                                    <span class="badge badge-danger">{{ $application->status }}</span>
                                                @elseif($application->status == 'Hired')
                                                    <span class="badge badge-success">{{ $application->status }}</span>
                                                @else
                                                    <span class="badge badge-info">{{ $application->status }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('teacher-interviews.show', $application->id) }}" class="btn btn-sm btn-info btn-rounded btn-icon" title="{{ __('View Details') }}">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @if($application->resume_path)
                                                    <a href="{{ asset('storage/' . $application->resume_path) }}" target="_blank" class="btn btn-sm btn-primary btn-rounded btn-icon" title="{{ __('Download Resume') }}">
                                                        <i class="fa fa-download"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $applications->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
