@extends('layouts.master')

@section('title')
    {{ __('Consent Forms') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Consent Forms') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('List of Consent Forms') }}
                        </h4>

                        <div id="toolbar" class="row">
                            <div class="col-sm-12 col-md-12">
                                <select name="class_section_id" id="class_section_id" class="form-control">
                                    <option value="">{{ __('All Classes') }}</option>
                                    @foreach ($classSections as $classSection)
                                        <option value="{{ $classSection->id }}">
                                            {{ $classSection->class->name }} - {{ $classSection->section->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <table aria-describedby="mydesc" class='table' id='table_list'
                               data-toggle="table" data-url="{{ route('consent-forms.list') }}"
                               data-click-to-select="true" data-side-pagination="server"
                               data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                               data-search="true" data-toolbar="#toolbar" data-show-columns="true"
                               data-show-refresh="true" data-trim-on-search="false" data-mobile-responsive="true"
                               data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                               data-show-export="true" data-export-data-type="all" data-escape="true" data-query-params="consentFormParams">
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="no">{{ __('No.') }}</th>
                                <th scope="col" data-field="student_name" data-sortable="false">{{ __('Student Name') }}</th>
                                <th scope="col" data-field="class_section" data-sortable="false">{{ __('Class') }} & {{ __('Section') }}</th>
                                <th scope="col" data-field="consent_form_date" data-sortable="true">{{ __('Consent Form Date') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function consentFormParams(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search,
                class_section_id: $('#class_section_id').val()
            };
        }

        $('#class_section_id').on('change', function () {
            $('#table_list').bootstrapTable('refresh');
        });
    </script>
@endsection
