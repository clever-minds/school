@extends('layouts.master')

@section('title')
    {{ __('sent_notifications') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('sent_notifications') }} - {{ $teacher->first_name }} {{ $teacher->last_name }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('sent_notifications') }}
                        </h4>
                        <div class="row" id="toolbar">
                            <div class="form-group col-sm-12 col-md-3">
                                <label class="filter-menu">{{ __('date') }}</label>
                                <input type="date" name="date" id="filter_date" class="form-control">
                            </div>
                        </div>

                        <table aria-describedby="mydesc" class='table' id='table_list'
                               data-toggle="table" data-url="{{ route('teachers.sent-notifications', $teacher->id) }}"
                               data-click-to-select="true" data-side-pagination="server"
                               data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                               data-search="true" data-toolbar="#toolbar" data-show-columns="true"
                               data-show-refresh="true" data-fixed-columns="false" data-fixed-number="2"
                               data-fixed-right-number="1" data-trim-on-search="false" data-mobile-responsive="true"
                               data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                               data-export-data-type='all'
                               data-query-params="notificationQueryParams" data-escape="true">
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="no">{{ __('no.') }}</th>
                                <th scope="col" data-field="title" data-sortable="true">{{ __('title') }}</th>
                                <th scope="col" data-field="message" data-sortable="true">{{ __('message') }}</th>
                                <th scope="col" data-field="classes" data-sortable="false">{{ __('classes') }}</th>
                                <th scope="col" data-field="date" data-sortable="true">{{ __('date') }}</th>
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
        function notificationQueryParams(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search,
                date: $('#filter_date').val(),
            };
        }

        $('#filter_date').on('change', function() {
            $('#table_list').bootstrapTable('refresh');
        });
    </script>
@endsection
