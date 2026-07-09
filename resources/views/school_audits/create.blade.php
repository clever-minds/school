@extends('layouts.master')

@section('title')
    {{ __('create') . ' ' . __('School Audit') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('create') . ' ' . __('School Audit') }}
            </h3>
            <a href="{{ route('school-audits.index') }}" class="btn btn-theme btn-sm">{{ __('Back') }}</a>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <form class="pt-3" action="{{ route('school-audits.store') }}" method="POST">
                            @csrf
                            <div class="row form-group">
                                <div class="col-sm-12 col-md-4">
                                    <label>{{ __('School') }} <span class="text-danger">*</span></label>
                                    <select name="school_id" id="school_id" class="form-control" required>
                                        <option value="">{{ __('Select School') }}</option>
                                        @foreach($schools as $school)
                                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-12 col-md-4">
                                    <label>{{ __('Audit Type') }} <span class="text-danger">*</span></label>
                                    <select name="audit_type" id="audit_type" class="form-control" required>
                                        <option value="">{{ __('Select Type') }}</option>
                                        <option value="Monthly">{{ __('Monthly') }}</option>
                                        <option value="Quarterly">{{ __('Quarterly') }}</option>
                                        <option value="Half Yearly">{{ __('Half Yearly') }}</option>
                                        <option value="Yearly">{{ __('Yearly') }}</option>
                                    </select>
                                </div>
                                <div class="col-sm-12 col-md-4">
                                    <label>{{ __('Audit Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="audit_date" class="form-control" required value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                            
                            <div class="row form-group">
                                <div class="col-sm-12">
                                    <label>{{ __('General Remarks') }}</label>
                                    <textarea name="remarks" class="form-control" rows="3" placeholder="{{ __('General remarks about the audit...') }}"></textarea>
                                </div>
                            </div>

                            <hr>
                            <h4 class="card-title mt-4 mb-4">{{ __('Audit Questions') }}</h4>
                            
                            @if(count($questions) > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="5%">#</th>
                                                <th width="45%">{{ __('Question') }}</th>
                                                <th width="20%">{{ __('Answer') }} <span class="text-danger">*</span></th>
                                                <th width="30%">{{ __('Remarks') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $index = 0; @endphp
                                            @foreach($questions->groupBy('category') as $category => $categoryQuestions)
                                                @if($category)
                                                    <tr class="table-secondary">
                                                        <td colspan="4"><strong>{{ $category }}</strong></td>
                                                    </tr>
                                                @endif
                                                @foreach($categoryQuestions as $question)
                                                    <tr>
                                                        <td>{{ ++$index }}</td>
                                                        <td>
                                                            {{ $question->question }}
                                                            <input type="hidden" name="answers[{{ $index }}][question_id]" value="{{ $question->id }}">
                                                        </td>
                                                        <td>
                                                            <select name="answers[{{ $index }}][answer]" class="form-control" required>
                                                                <option value="">{{ __('Select Answer') }}</option>
                                                                <option value="Yes">{{ __('Yes') }}</option>
                                                                <option value="No">{{ __('No') }}</option>
                                                                <option value="N/A">{{ __('N/A') }}</option>
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text" name="answers[{{ $index }}][remarks]" class="form-control" placeholder="{{ __('Optional remarks...') }}">
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    {{ __('No active audit questions found. Please add questions in the Audit Questions module first.') }}
                                </div>
                            @endif

                            <div class="row form-group mt-4">
                                <div class="col-sm-12 text-right">
                                    <a href="{{ route('school-audits.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                                    <input class="btn btn-theme" type="submit" value="{{ __('submit') }}">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


