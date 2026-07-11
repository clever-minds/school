@extends('layouts.master')

@section('title')
    {{ __('Conduct School Audit') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Conduct School Audit') }}
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
                            <div class="col-md-4">
                                <h5><strong>{{ __('Audit Type') }}:</strong> {{ $audit->audit_type ?? '-' }}</h5>
                            </div>
                            <div class="col-md-4">
                                <h5><strong>{{ __('Audit Date') }}:</strong> {{ date('d M, Y', strtotime($audit->audit_date)) }}</h5>
                            </div>
                        </div>

                        <hr>

                        <form class="pt-3" action="{{ route('school-audits.update', $audit->id) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <h4 class="card-title mt-4 mb-4">{{ __('Audit Checklist') }}</h4>
                            
                            @if(count($audit->answers) > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="5%">#</th>
                                                <th width="50%">{{ __('Question') }}</th>
                                                <th width="20%">{{ __('Answer') }} <span class="text-danger">*</span></th>
                                                <th width="25%">{{ __('Remarks') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $index = 0; @endphp
                                            @foreach($audit->answers->groupBy('question.category') as $category => $categoryAnswers)
                                                @if($category)
                                                    <tr class="table-secondary">
                                                        <td colspan="4"><strong>{{ $category }}</strong></td>
                                                    </tr>
                                                @endif
                                                @foreach($categoryAnswers as $answer)
                                                    <tr>
                                                        <td>{{ ++$index }}</td>
                                                        <td>
                                                            {{ $answer->question ? $answer->question->question : '-' }}
                                                            <input type="hidden" name="answers[{{ $index }}][id]" value="{{ $answer->id }}">
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="form-check form-check-inline mt-0 mb-0 mr-3">
                                                                    <label class="form-check-label">
                                                                        <input type="radio" class="form-check-input" name="answers[{{ $index }}][answer]" value="Yes" required {{ $answer->answer == 'Yes' ? 'checked' : '' }}> {{ __('Yes') }}
                                                                    </label>
                                                                </div>
                                                                <div class="form-check form-check-inline mt-0 mb-0 mr-3">
                                                                    <label class="form-check-label">
                                                                        <input type="radio" class="form-check-input" name="answers[{{ $index }}][answer]" value="No" required {{ $answer->answer == 'No' ? 'checked' : '' }}> {{ __('No') }}
                                                                    </label>
                                                                </div>
                                                                <div class="form-check form-check-inline mt-0 mb-0">
                                                                    <label class="form-check-label">
                                                                        <input type="radio" class="form-check-input" name="answers[{{ $index }}][answer]" value="N/A" required {{ $answer->answer == 'N/A' ? 'checked' : '' }}> {{ __('N/A') }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="answers[{{ $index }}][remarks]" class="form-control" placeholder="{{ __('Optional remarks...') }}" value="{{ $answer->remarks }}">
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    {{ __('No questions assigned to this audit.') }}
                                </div>
                            @endif

                            <div class="row form-group mt-4">
                                <div class="col-sm-12 text-right">
                                    <a href="{{ route('school-audits.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                                    <input class="btn btn-theme" type="submit" value="{{ __('Submit Audit') }}">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
