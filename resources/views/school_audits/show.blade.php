@extends('layouts.master')

@section('title')
    {{ __('View School Audit') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('View School Audit') }}
            </h3>
            <a href="{{ route('school-audits.index') }}" class="btn btn-theme btn-sm">{{ __('Back') }}</a>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <h5><strong>{{ __('School') }}:</strong> {{ $audit->school ? $audit->school->name : '-' }}</h5>
                            </div>
                            <div class="col-md-3">
                                <h5><strong>{{ __('Auditor') }}:</strong> {{ $audit->auditor ? $audit->auditor->first_name . ' ' . $audit->auditor->last_name : '-' }}</h5>
                            </div>
                            <div class="col-md-3">
                                <h5><strong>{{ __('Audit Type') }}:</strong> {{ $audit->audit_type ?? '-' }}</h5>
                            </div>
                            <div class="col-md-3">
                                <h5><strong>{{ __('Audit Date') }}:</strong> {{ date('d M, Y', strtotime($audit->audit_date)) }}</h5>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h5><strong>{{ __('General Remarks') }}:</strong></h5>
                                <p>{{ $audit->remarks ?? '-' }}</p>
                            </div>
                        </div>

                        <hr>
                        <h4 class="card-title mt-4 mb-4">{{ __('Audit Answers') }}</h4>
                        
                        @if(count($audit->answers) > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th width="5%">#</th>
                                            <th width="35%">{{ __('Question') }}</th>
                                            <th width="20%">{{ __('Assigned To') }}</th>
                                            <th width="15%">{{ __('Answer') }}</th>
                                            <th width="25%">{{ __('Remarks') }}</th>
                                        </tr>
                                    </thead>
                                        <tbody>
                                            @php $index = 0; @endphp
                                            @foreach($audit->answers->groupBy('question.category') as $category => $categoryAnswers)
                                                @if($category)
                                                    <tr class="table-secondary">
                                                        <td colspan="5"><strong>{{ $category }}</strong></td>
                                                    </tr>
                                                @endif
                                                @foreach($categoryAnswers as $answer)
                                                    <tr>
                                                        <td>{{ ++$index }}</td>
                                                        <td>{{ $answer->question ? $answer->question->question : '-' }}</td>
                                                        <td>{{ $answer->assignedUser ? $answer->assignedUser->first_name . ' ' . $answer->assignedUser->last_name : '-' }}</td>
                                                        <td>
                                                            @if($answer->answer == 'Yes')
                                                                <span class="badge badge-success">{{ __('Yes') }}</span>
                                                            @elseif($answer->answer == 'No')
                                                                <span class="badge badge-danger">{{ __('No') }}</span>
                                                            @else
                                                                <span class="badge badge-secondary">{{ __('N/A') }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $answer->remarks ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                {{ __('No answers found for this audit.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
