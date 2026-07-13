@extends('layouts.master')

@section('title')
    {{ __('Teacher Interview Feedback Questions') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Manage Teacher Interview Feedback Questions') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('Create New Question') }}
                        </h4>
                        
                        <form action="{{ route('teacher-interview-feedback-questions.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('Question') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="feedback_question" class="form-control" placeholder="{{ __('Question Text') }}" required>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('Category') }}</label>
                                    <input type="text" name="category" class="form-control" placeholder="{{ __('Category (Optional)') }}">
                                </div>
                                <div class="form-group col-sm-12 col-md-2">
                                    <label>{{ __('Status') }}</label>
                                    <select name="status" class="form-control">
                                        <option value="active">{{ __('Active') }}</option>
                                        <option value="inactive">{{ __('Inactive') }}</option>
                                    </select>
                                </div>
                                <div class="col-sm-12 col-md-2 d-flex align-items-end mb-3">
                                    <button type="submit" class="btn btn-primary theme-btn">{{ __('Submit') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('Questions List') }}
                        </h4>
                        
                        <div class="table-responsive">
                            <table class="table" id="table_list">
                                <thead>
                                    <tr>
                                        <th>{{ __('No.') }}</th>
                                        <th>{{ __('Question') }}</th>
                                        <th>{{ __('Category') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($questions as $key => $question)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ $question->feedback_question }}</td>
                                            <td>{{ $question->category ?? '-' }}</td>
                                            <td>
                                                @if($question->status == 'active')
                                                    <span class="badge badge-success">{{ __('Active') }}</span>
                                                @else
                                                    <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info btn-rounded btn-icon" data-toggle="modal" data-target="#editModal{{ $question->id }}" title="{{ __('Edit') }}">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                
                                                <form action="{{ route('teacher-interview-feedback-questions.destroy', $question->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('{{ __('Are you sure you want to delete this question?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger btn-rounded btn-icon" title="{{ __('Delete') }}">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>

                                                <!-- Edit Modal -->
                                                <div class="modal fade" id="editModal{{ $question->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">{{ __('Edit Question') }}</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <form action="{{ route('teacher-interview-feedback-questions.update', $question->id) }}" method="POST">
                                                                @csrf
                                                                @method('PUT')
                                                                <div class="modal-body">
                                                                    <div class="form-group">
                                                                        <label>{{ __('Question') }} <span class="text-danger">*</span></label>
                                                                        <input type="text" name="feedback_question" class="form-control" value="{{ $question->feedback_question }}" required>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>{{ __('Category') }}</label>
                                                                        <input type="text" name="category" class="form-control" value="{{ $question->category }}">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>{{ __('Status') }}</label>
                                                                        <select name="status" class="form-control">
                                                                            <option value="active" {{ $question->status == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                                                            <option value="inactive" {{ $question->status == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                                                                    <button type="submit" class="btn btn-primary theme-btn">{{ __('Update') }}</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
