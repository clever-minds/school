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
                                    <select name="teacher_interview_category_id" class="form-control">
                                        <option value="">{{ __('Select Category') }}</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('Status') }}</label>
                                    <select name="status" class="form-control">
                                        <option value="active">{{ __('Active') }}</option>
                                        <option value="inactive">{{ __('Inactive') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-5">
                                    <label>{{ __('Type') }}</label>
                                    <select name="type" id="create-type" class="form-control">
                                        <option value="">{{ __('Select Type') }}</option>
                                        <option value="Yes/No">{{ __('Yes / No / N/A') }}</option>
                                        <option value="Conditional">{{ __('Conditional (Yes/No -> Rating)') }}</option>
                                        <option value="Custom">{{ __('Custom (Radio Buttons)') }}</option>
                                        <option value="Text">{{ __('Text (Short Answer)') }}</option>
                                        <option value="Paragraph">{{ __('Paragraph (Long Answer)') }}</option>
                                        <option value="Number">{{ __('Number') }}</option>
                                        <option value="Rating">{{ __('Rating (Excellent, Good, Average, Unsatisfactory)') }}</option>
                                        <option value="Date">{{ __('Date') }}</option>
                                    </select>
                                    <small class="text-muted mt-1 d-block"><i class="fa fa-info-circle"></i> e.g., Select <strong>Conditional</strong> for questions that open rating when YES.</small>
                                </div>
                                <div class="form-group col-sm-12 col-md-5 custom-options-wrapper" style="display: none;">
                                    <label>{{ __('Custom Options') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="custom_options" id="create-custom-options" class="form-control" placeholder="e.g. Good, Bad or Inside, Outside">
                                    <small class="text-muted mt-1 d-block"><i class="fa fa-info-circle"></i> Enter options separated by comma (,).</small>
                                </div>
                                <div class="col-sm-12 col-md-2 d-flex align-items-center mt-3">
                                    <button type="submit" class="btn btn-primary theme-btn btn-block">{{ __('Submit') }}</button>
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
                        
                        <form action="{{ route('teacher-interview-feedback-questions.index') }}" method="GET" class="mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control" placeholder="{{ __('Search...') }}" value="{{ request()->search }}">
                                        <div class="input-group-append">
                                            <button class="btn btn-sm btn-primary" type="submit">{{ __('Search') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

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
                                            <td>{{ ($questions->firstItem() ?? 1) + $key }}</td>
                                            <td>{{ $question->feedback_question }}</td>
                                            <td>{{ $question->category->name ?? '-' }}</td>
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
                                                                        <select name="teacher_interview_category_id" class="form-control">
                                                                            <option value="">{{ __('Select Category') }}</option>
                                                                            @foreach($categories as $category)
                                                                                <option value="{{ $category->id }}" {{ $question->teacher_interview_category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>{{ __('Status') }}</label>
                                                                        <select name="status" class="form-control">
                                                                            <option value="active" {{ $question->status == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                                                            <option value="inactive" {{ $question->status == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>{{ __('Type') }}</label>
                                                                        <select name="type" id="edit-type-{{ $question->id }}" class="form-control edit-type-select" data-id="{{ $question->id }}">
                                                                            <option value="">{{ __('Select Type') }}</option>
                                                                            <option value="Yes/No" {{ $question->type == 'Yes/No' ? 'selected' : '' }}>{{ __('Yes / No / N/A') }}</option>
                                                                            <option value="Conditional" {{ $question->type == 'Conditional' ? 'selected' : '' }}>{{ __('Conditional (Yes/No -> Rating)') }}</option>
                                                                            <option value="Custom" {{ $question->type == 'Custom' ? 'selected' : '' }}>{{ __('Custom (Radio Buttons)') }}</option>
                                                                            <option value="Text" {{ $question->type == 'Text' ? 'selected' : '' }}>{{ __('Text (Short Answer)') }}</option>
                                                                            <option value="Paragraph" {{ $question->type == 'Paragraph' ? 'selected' : '' }}>{{ __('Paragraph (Long Answer)') }}</option>
                                                                            <option value="Number" {{ $question->type == 'Number' ? 'selected' : '' }}>{{ __('Number') }}</option>
                                                                            <option value="Rating" {{ $question->type == 'Rating' ? 'selected' : '' }}>{{ __('Rating (Excellent, Good, Average, Unsatisfactory)') }}</option>
                                                                            <option value="Date" {{ $question->type == 'Date' ? 'selected' : '' }}>{{ __('Date') }}</option>
                                                                        </select>
                                                                        <small class="text-muted mt-1 d-block"><i class="fa fa-info-circle"></i> e.g., Select <strong>Conditional</strong> for questions that open rating when YES.</small>
                                                                    </div>
                                                                    <div class="form-group edit-custom-options-wrapper-{{ $question->id }}" style="{{ in_array($question->type, ['Custom', 'Conditional']) ? '' : 'display: none;' }}">
                                                                        <label>{{ __('Custom Options') }} <span class="text-danger">*</span></label>
                                                                        <input type="text" name="custom_options" id="edit-custom-options-{{ $question->id }}" class="form-control" value="{{ $question->custom_options }}" placeholder="e.g. Good, Bad or Inside, Outside" {{ in_array($question->type, ['Custom']) ? 'required' : '' }}>
                                                                        <small class="text-muted mt-1 d-block"><i class="fa fa-info-circle"></i> Enter options separated by comma (,).</small>
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
                        <div class="mt-3">
                            {{ $questions->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    $('#create-type').on('change', function() {
        if ($(this).val() === 'Custom' || $(this).val() === 'Conditional') {
            $('.custom-options-wrapper').show();
            $('#create-custom-options').prop('required', $(this).val() === 'Custom');
        } else {
            $('.custom-options-wrapper').hide();
            $('#create-custom-options').prop('required', false);
        }
    });

    $('.edit-type-select').on('change', function() {
        let id = $(this).data('id');
        if ($(this).val() === 'Custom' || $(this).val() === 'Conditional') {
            $('.edit-custom-options-wrapper-' + id).show();
            $('#edit-custom-options-' + id).prop('required', $(this).val() === 'Custom');
        } else {
            $('.edit-custom-options-wrapper-' + id).hide();
            $('#edit-custom-options-' + id).prop('required', false);
        }
    });

    $(document).ready(function() {
        // Any initial setup
    });
</script>
@endsection
