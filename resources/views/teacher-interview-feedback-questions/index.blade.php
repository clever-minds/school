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
                                    <select name="type" class="form-control">
                                        <option value="">{{ __('Select Type') }}</option>
                                        <option value="rating">{{ __('Rating') }}</option>
                                        <option value="boolean">{{ __('Boolean (Yes/No)') }}</option>
                                        <option value="text">{{ __('Text') }}</option>
                                        <option value="conditional">{{ __('Conditional') }}</option>
                                    </select>
                                    <small class="text-muted mt-1 d-block"><i class="fa fa-info-circle"></i> e.g., Select <strong>Rating</strong> for multiple choices, or <strong>Conditional</strong> for Yes/No based answers.</small>
                                </div>
                                <div class="form-group col-sm-12 col-md-5">
                                    <label>{{ __('Option Group') }}</label>
                                    <select name="audit_option_group_id" id="create-option-group" class="form-control" onchange="renderOptionPreview(this, 'create-option-preview')">
                                        <option value="">{{ __('Select Option Group') }}</option>
                                        @foreach($optionGroups as $group)
                                            <option value="{{ $group->id }}" data-options="{{ json_encode($group->option_values) }}">{{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted mt-1 d-block"><i class="fa fa-info-circle"></i> e.g., Select the exact answer choices (like "4-Point Rating"). Leave blank for Text type.</small>
                                    <small id="create-option-preview" class="text-info mt-2 d-block"></small>
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
                        
                        <div class="table-responsive">
                            <table class="table" id="table_list">
                                <thead>
                                    <tr>
                                        <th>{{ __('No.') }}</th>
                                        <th>{{ __('Question') }}</th>
                                        <th>{{ __('Category') }}</th>
                                        <th>{{ __('Option Group') }}</th>
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
                                            <td>{{ $question->optionGroup ? $question->optionGroup->name : '-' }}</td>
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
                                                                    <div class="form-group">
                                                                        <label>{{ __('Type') }}</label>
                                                                        <select name="type" class="form-control">
                                                                            <option value="">{{ __('Select Type') }}</option>
                                                                            <option value="rating" {{ $question->type == 'rating' ? 'selected' : '' }}>{{ __('Rating') }}</option>
                                                                            <option value="boolean" {{ $question->type == 'boolean' ? 'selected' : '' }}>{{ __('Boolean (Yes/No)') }}</option>
                                                                            <option value="text" {{ $question->type == 'text' ? 'selected' : '' }}>{{ __('Text') }}</option>
                                                                            <option value="conditional" {{ $question->type == 'conditional' ? 'selected' : '' }}>{{ __('Conditional') }}</option>
                                                                        </select>
                                                                        <small class="text-muted mt-1 d-block"><i class="fa fa-info-circle"></i> e.g., Select <strong>Rating</strong> for multiple choices, or <strong>Conditional</strong> for Yes/No based answers.</small>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>{{ __('Option Group') }}</label>
                                                                        <select name="audit_option_group_id" class="form-control option-group-edit" data-preview-id="edit-option-preview-{{ $question->id }}" onchange="renderOptionPreview(this, 'edit-option-preview-{{ $question->id }}')">
                                                                            <option value="">{{ __('Select Option Group') }}</option>
                                                                            @foreach($optionGroups as $group)
                                                                                <option value="{{ $group->id }}" data-options="{{ json_encode($group->option_values) }}" {{ $question->audit_option_group_id == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                        <small class="text-muted mt-1 d-block"><i class="fa fa-info-circle"></i> e.g., Select the exact answer choices (like "4-Point Rating"). Leave blank for Text type.</small>
                                                                        <small id="edit-option-preview-{{ $question->id }}" class="text-info mt-2 d-block"></small>
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

@section('script')
<script>
    function renderOptionPreview(selectElement, previewContainerId) {
        let selectedOption = $(selectElement).find('option:selected');
        let optionsData = selectedOption.attr('data-options');
        let previewHtml = '';

        if (optionsData) {
            try {
                let options = JSON.parse(optionsData);
                previewHtml = '<strong>Preview:</strong> ';
                
                function extractLabels(obj) {
                    if (Array.isArray(obj)) {
                        return obj.map(item => item.label).join(', ');
                    } else if (typeof obj === 'object' && obj !== null) {
                        let res = [];
                        for (let key in obj) {
                            if (key !== 'has_sub_options' && typeof obj[key] === 'object') {
                                res.push(key + ' -> [' + extractLabels(obj[key]) + ']');
                            }
                        }
                        return res.join(' | ');
                    }
                    return '';
                }

                previewHtml += extractLabels(options);
            } catch (e) {
                console.error("Invalid JSON in option group");
            }
        }
        $('#' + previewContainerId).html(previewHtml);
    }

    $(document).ready(function() {
        $('.option-group-edit').each(function() {
            let previewId = $(this).data('preview-id');
            renderOptionPreview(this, previewId);
        });
    });
</script>
@endsection
