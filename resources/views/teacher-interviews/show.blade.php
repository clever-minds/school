@extends('layouts.master')

@section('title')
    {{ __('Teacher Interview Details') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Application Details') }}
            </h3>
            <a href="{{ route('teacher-interviews.index') }}" class="btn btn-primary btn-sm"><i class="fa fa-arrow-left"></i> {{ __('Back') }}</a>
        </div>

        <div class="row">
            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Applicant Information') }}</h4>
                        <ul class="list-ticked">
                            <li><strong>{{ __('Name') }}:</strong> {{ $application->name }}</li>
                            <li><strong>{{ __('Email') }}:</strong> {{ $application->email }}</li>
                            <li><strong>{{ __('Phone') }}:</strong> {{ $application->phone }}</li>
                            <li><strong>{{ __('Applied On') }}:</strong> {{ $application->created_at->format('d M, Y h:i A') }}</li>
                            <li>
                                <strong>{{ __('Resume') }}:</strong> 
                                @if($application->resume_path)
                                    <a href="{{ asset('storage/' . $application->resume_path) }}" target="_blank">{{ __('View / Download') }}</a>
                                @else
                                    <span class="text-muted">{{ __('Not Provided') }}</span>
                                @endif
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Update Status') }}</h4>
                        
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <form action="{{ route('teacher-interviews.update-status', $application->id) }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label>{{ __('Current Status') }}</label>
                                <select name="status" class="form-control" required>
                                    <option value="Pending" {{ $application->status == 'Pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                    <option value="Shortlisted" {{ $application->status == 'Shortlisted' ? 'selected' : '' }}>{{ __('Shortlisted') }}</option>
                                    <option value="Interview Scheduled" {{ $application->status == 'Interview Scheduled' ? 'selected' : '' }}>{{ __('Interview Scheduled') }}</option>
                                    <option value="Hired" {{ $application->status == 'Hired' ? 'selected' : '' }}>{{ __('Hired') }}</option>
                                    <option value="Rejected" {{ $application->status == 'Rejected' ? 'selected' : '' }}>{{ __('Rejected') }}</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>{{ __('Remarks (Optional)') }}</label>
                                <textarea name="remarks" class="form-control" rows="4" placeholder="{{ __('Add any private remarks here...') }}">{{ $application->remarks }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary theme-btn">{{ __('Update Status') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
