@extends('layouts.master')

@section('title')
    {{ __('Fees Report') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Fees Report') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card search-container">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title"></h4>
                        <div id="toolbar">
                            <div class="row">
                                <div class="form-group col-md-3">
                                    <label class="filter-menu" for="session_year_id"> {{ __('Session Years') }} </label>
                                    <select name="session_year_id" id="session_year_id" class="form-control">
                                        <option value="">{{ __('all') }}</option>
                                        @foreach ($session_year_all as $session_year)
                                            <option value="{{ $session_year->id }}"
                                                {{ $session_year->default ? 'selected' : '' }}> {{ $session_year->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="filter-menu" for="mode">{{ __('Payment Mode') }}</label>
                                    <select name="mode" id="mode" class="form-control">
                                        <option value="">{{ __('all') }}</option>
                                        <option value="0">{{ __('Online') }}</option>
                                        <option value="1">{{ __('Cash (Offline)') }}</option>
                                        <option value="2">{{ __('Cheque (Offline)') }}</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="filter-menu" for="start_date">{{ __('Start Date') }}</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control">
                                </div>
                                <div class="form-group col-md-3">
                                    <label class="filter-menu" for="end_date">{{ __('End Date') }}</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control">
                                </div>
                            </div>
                        </div>

                        <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                            data-url="{{ route('fees.report.list') }}" data-click-to-select="true"
                            data-side-pagination="server" data-pagination="true"
                            data-page-list="[5, 10, 20, 50, 100, 200]" data-search="false" data-toolbar="#toolbar"
                            data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                            data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false"
                            data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                            data-maintain-selected="true" data-export-data-type='all'
                            data-export-options='{ "fileName": "fees-report-<?= date('d-m-y') ?>" ,"ignoreColumn": ["operate"]}'
                            data-show-export="true"
                            data-query-params="queryParams">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                    <th scope="col" data-field="session_year" data-sortable="false">{{ __('Session Year') }}</th>
                                    <th scope="col" data-field="mode" data-sortable="false">{{ __('Payment Mode') }}</th>
                                    <th scope="col" data-field="total_amount" data-sortable="false">{{ __('Total Amount') }}</th>
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
        function queryParams(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search,
                session_year_id: $('#session_year_id').val(),
                mode: $('#mode').val(),
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
            };
        }

        $('#session_year_id, #mode, #start_date, #end_date').on('change', function() {
            $('#table_list').bootstrapTable('refresh');
        });
    </script>
@endsection
