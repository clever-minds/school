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
                        <form class="create-form pt-3" action="{{ route('school-audits.store') }}" method="POST" novalidate="novalidate">
                            @csrf
                            <div class="row form-group">
                                <div class="col-sm-12 col-md-6">
                                    <label>{{ __('School') }} <span class="text-danger">*</span></label>
                                    <select name="school_id" id="school_id" class="form-control select2" required>
                                        <option value="">{{ __('Select School') }}</option>
                                        @foreach($schools as $school)
                                            <option value="{{ $school->id }}">{{ $school->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-sm-12 col-md-6">
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
                                            @foreach($questions as $key => $question)
                                                <tr>
                                                    <td>{{ $key + 1 }}</td>
                                                    <td>
                                                        {{ $question->question }}
                                                        <input type="hidden" name="answers[{{ $key }}][question_id]" value="{{ $question->id }}">
                                                    </td>
                                                    <td>
                                                        <select name="answers[{{ $key }}][answer]" class="form-control" required>
                                                            <option value="">{{ __('Select Answer') }}</option>
                                                            <option value="Yes">{{ __('Yes') }}</option>
                                                            <option value="No">{{ __('No') }}</option>
                                                            <option value="N/A">{{ __('N/A') }}</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="answers[{{ $key }}][remarks]" class="form-control" placeholder="{{ __('Optional remarks...') }}">
                                                    </td>
                                                </tr>
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

@section('script')
<script>
    $(document).ready(function() {
        if ($('.select2').length) {
            $('.select2').select2({
                theme: 'bootstrap'
            });
        }
    });
</script>
@endsection
