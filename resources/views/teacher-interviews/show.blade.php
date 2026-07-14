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
                            <li><strong>{{ __('School') }}:</strong> {{ $application->school->name ?? '-' }}</li>
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

        @if(count($feedbackQuestions) > 0)
        <div class="row mt-4">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Interview Performance Feedback') }}</h4>
                        
                        <form action="{{ route('teacher-interviews.save-feedback', $application->id) }}" method="POST">
                            @csrf
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Category') }}</th>
                                            <th>{{ __('Question') }}</th>
                                            <th>{{ __('Feedback / Remarks') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($feedbackQuestions as $question)
                                            <tr>
                                                <td>{{ $question->category ?? '-' }}</td>
                                                <td class="text-wrap" style="min-width: 200px;">{{ $question->feedback_question }}</td>
                                                <td>
                                                    @php
                                                        $currentAnswer = isset($feedbacks[$question->id]) ? $feedbacks[$question->id]->interviewer_feedback : '';
                                                    @endphp
                                                    
                                                    @if($question->type == 'rating' && $question->optionGroup)
                                                        @foreach($question->optionGroup->option_values as $opt)
                                                            <div class="form-check form-check-inline mt-0 mb-2">
                                                                <input class="form-check-input" type="radio" name="feedbacks[{{ $question->id }}]" id="q_{{ $question->id }}_{{ $loop->index }}" value="{{ $opt['label'] }}" {{ $currentAnswer == $opt['label'] ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="q_{{ $question->id }}_{{ $loop->index }}">{{ $opt['label'] }}</label>
                                                            </div>
                                                        @endforeach
                                                    @elseif($question->type == 'boolean')
                                                        <div class="form-check form-check-inline mt-0 mb-2">
                                                            <input class="form-check-input" type="radio" name="feedbacks[{{ $question->id }}]" id="q_{{ $question->id }}_yes" value="Yes" {{ $currentAnswer == 'Yes' ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="q_{{ $question->id }}_yes">{{ __('Yes') }}</label>
                                                        </div>
                                                        <div class="form-check form-check-inline mt-0 mb-2">
                                                            <input class="form-check-input" type="radio" name="feedbacks[{{ $question->id }}]" id="q_{{ $question->id }}_no" value="No" {{ $currentAnswer == 'No' ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="q_{{ $question->id }}_no">{{ __('No') }}</label>
                                                        </div>
                                                    @else
                                                        <textarea name="feedbacks[{{ $question->id }}]" class="form-control" rows="2" placeholder="{{ __('Enter your feedback here...') }}">{{ $currentAnswer }}</textarea>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary theme-btn">{{ __('Save Feedback') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection
