@extends('layouts.master')

@section('title')
    {{ __('Audit Questions') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('Audit Questions') }}
            </h3>
        </div>

        <div class="row">
            {{-- We assume super admin can do this --}}
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('create') . ' ' . __('Audit Questions') }}
                        </h4>
                        <form class="create-form pt-3" id="create-form" action="{{route('audit-questions.store')}}" method="POST" novalidate="novalidate">
                            @csrf
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('question') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('question', null, ['required', 'placeholder' => __('question'), 'class' => 'form-control']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-3">
                                    <label>{{ __('category') }}</label>
                                    <select name="category" class="form-control">
                                        <option value="">{{ __('Select Category') }}</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat }}">{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-sm-12 col-md-3">
                                    <label>{{ __('status') }} <span class="text-danger">*</span></label>
                                    <select name="status" class="form-control" required>
                                        <option value="1">{{ __('Active') }}</option>
                                        <option value="0">{{ __('Inactive') }}</option>
                                    </select>
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('Type') }}</label>
                                    <select name="type" class="form-control">
                                        <option value="">{{ __('Select Type') }}</option>
                                        <option value="rating">{{ __('Rating') }}</option>
                                        <option value="boolean">{{ __('Boolean (Yes/No)') }}</option>
                                        <option value="text">{{ __('Text') }}</option>
                                        <option value="conditional">{{ __('Conditional') }}</option>
                                    </select>
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('Option Group') }}</label>
                                    <select name="audit_option_group_id" id="create-option-group" class="form-control">
                                        <option value="">{{ __('Select Option Group') }}</option>
                                        @foreach($optionGroups as $group)
                                            <option value="{{ $group->id }}" data-options="{{ json_encode($group->option_values) }}">{{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                    <small id="create-option-preview" class="text-muted mt-2 d-block"></small>
                                </div>
                            </div>
                            <input class="btn btn-theme float-right ml-3" id="create-btn" type="submit" value={{ __('submit') }}>
                            <input class="btn btn-secondary float-right" type="reset" value={{ __('reset') }}>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('list') . ' ' . __('Audit Questions') }}
                        </h4>
                        <div class="row">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                                        data-url="{{ route('audit-questions.show', 1) }}" data-click-to-select="true"
                                        data-side-pagination="server" data-pagination="true"
                                        data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                                        data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                                        data-fixed-columns="false" data-fixed-number="2" data-fixed-right-number="1"
                                        data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                                        data-sort-order="desc" data-maintain-selected="true"
                                        data-export-types='["txt","excel"]'
                                        data-export-options='{ "fileName": "audit-questions-list-<?= date('d-m-y') ?>","ignoreColumn": ["operate"]}'
                                        data-query-params="queryParams" data-escape="true">
                                    <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false"> {{ __('id') }} </th>
                                        <th scope="col" data-field="no"> {{ __('no.') }} </th>
                                        <th scope="col" data-field="question">{{ __('question') }} </th>
                                        <th scope="col" data-field="category">{{ __('category') }} </th>
                                        <th scope="col" data-field="option_group_name">{{ __('Option Group') }} </th>
                                        <th scope="col" data-field="status_text" data-escape="false">{{ __('status') }} </th>
                                        <th data-events="auditQuestionEvents" data-width="150" scope="col" data-field="operate" data-escape="false">{{ __('action') }}</th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="editModal" data-backdrop="static" tabindex="-1" role="dialog"
         aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"> {{ __('edit') . ' ' . __('Audit Questions') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fa fa-close"></i></span>
                    </button>
                </div>
                <form id="formdata" class="edit-form" action="{{url('audit-questions')}}" novalidate="novalidate">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" name="id" id="id">
                        <div class="row form-group">
                            <div class="col-sm-12 col-md-12">
                                <label>{{ __('question') }} <span class="text-danger">*</span></label>
                                {!! Form::text('question', null, ['required','placeholder' => __('question'), 'class' => 'form-control', 'id' => 'edit-question']) !!}
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-sm-12 col-md-12">
                                <label>{{ __('category') }}</label>
                                <select name="category" id="edit-category" class="form-control">
                                    <option value="">{{ __('Select Category') }}</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}">{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-sm-12 col-md-12">
                                <label>{{ __('status') }} <span class="text-danger">*</span></label>
                                <select name="status" class="form-control" id="edit-status" required>
                                    <option value="1">{{ __('Active') }}</option>
                                    <option value="0">{{ __('Inactive') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-sm-12 col-md-12">
                                <label>{{ __('Type') }}</label>
                                <select name="type" id="edit-type" class="form-control">
                                    <option value="">{{ __('Select Type') }}</option>
                                    <option value="rating">{{ __('Rating') }}</option>
                                    <option value="boolean">{{ __('Boolean (Yes/No)') }}</option>
                                    <option value="text">{{ __('Text') }}</option>
                                    <option value="conditional">{{ __('Conditional') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-sm-12 col-md-12">
                                <label>{{ __('Option Group') }}</label>
                                <select name="audit_option_group_id" id="edit-audit_option_group_id" class="form-control">
                                    <option value="">{{ __('Select Option Group') }}</option>
                                    @foreach($optionGroups as $group)
                                        <option value="{{ $group->id }}" data-options="{{ json_encode($group->option_values) }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                                <small id="edit-option-preview" class="text-muted mt-2 d-block"></small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                    </div>
                </form>
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
                
                // Helper to render simple nested options if it's conditional
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

    $('#create-option-group').on('change', function() {
        renderOptionPreview(this, 'create-option-preview');
    });

    $('#edit-audit_option_group_id').on('change', function() {
        renderOptionPreview(this, 'edit-option-preview');
    });

    window.auditQuestionEvents = {
        'click .edit-data': function (e, value, row, index) {
            $('#id').val(row.id);
            $('#edit-question').val(row.question);
            $('#edit-category').val(row.category);
            $('#edit-status').val(row.status);
            $('#edit-type').val(row.type);
            $('#edit-audit_option_group_id').val(row.audit_option_group_id).trigger('change');
            $('#formdata').attr('action', "{{url('audit-questions')}}/" + row.id);
        }
    };
</script>
@endsection
