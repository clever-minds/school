@extends('layouts.master')

@section('title')
    {{ __('payroll') }}
@endsection

@section('css')
<style>
    .staff-table {
        position: relative;
    }

    .staff-table .fixed-table-container {
        position: relative;
        overflow: visible;
    }

    .staff-table .fixed-table-body {
        overflow-x: auto !important;
        position: relative;
    }

    .staff-table #table_list {
        border-collapse: separate !important;
        border-spacing: 0;
    }

    /* Column 1: Checkbox */
    .staff-table #table_list th:nth-child(1),
    .staff-table #table_list td:nth-child(1) {
        position: sticky !important;
        left: 0px !important;
        z-index: 5 !important;
        background-color: #ffffff !important;
        width: 45px !important;
        min-width: 45px !important;
        max-width: 45px !important;
        text-align: center;
    }

    /* Column 2: No. */
    .staff-table #table_list th:nth-child(2),
    .staff-table #table_list td:nth-child(2) {
        position: sticky !important;
        left: 45px !important;
        z-index: 5 !important;
        background-color: #ffffff !important;
        width: 50px !important;
        min-width: 50px !important;
        max-width: 50px !important;
        text-align: center;
    }

    /* Column 3: Name - sticky on horizontal scroll */
    .staff-table #table_list th:nth-child(3),
    .staff-table #table_list td:nth-child(3) {
        position: sticky !important;
        left: 95px !important;
        z-index: 5 !important;
        background-color: #ffffff !important;
        min-width: 180px !important;
        max-width: 250px !important;
        white-space: normal;
        box-shadow: 4px 0 6px -2px rgba(0, 0, 0, 0.15) !important;
        border-right: 1px solid #ebedf2 !important;
    }

    /* Sticky Headers th */
    .staff-table #table_list thead th:nth-child(1),
    .staff-table #table_list thead th:nth-child(2),
    .staff-table #table_list thead th:nth-child(3) {
        z-index: 7 !important;
        background-color: #f8f9fa !important;
    }

    /* Row hover and selected states for sticky cells */
    .staff-table #table_list tbody tr:hover > td:nth-child(1),
    .staff-table #table_list tbody tr:hover > td:nth-child(2),
    .staff-table #table_list tbody tr:hover > td:nth-child(3),
    .staff-table #table_list tbody tr.selected > td:nth-child(1),
    .staff-table #table_list tbody tr.selected > td:nth-child(2),
    .staff-table #table_list tbody tr.selected > td:nth-child(3) {
        background-color: #f2edf3 !important;
    }
</style>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('payroll') }}
            </h3>
        </div>
        <form action="{{ route('payroll.store') }}" method="post" class="create-form" novalidate="novalidate" data-success-function="formSuccessFunction">
            @csrf
            <div class="row">

                <div class="col-md-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">
                                {{ __('create') . ' ' . __('payroll') }}
                            </h4>

                            <div class="row">
                                <div class="form-group col-sm-12 col-md-3">
                                    <label>{{ __('select') }} {{ __('month') }} <span
                                            class="text-danger">*</span></label>
                                    {!! Form::select('month', $months, null, ['class' => 'form-control', 'id' => 'month']) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-3">
                                    <label>{{ __('select') }} {{ __('year') }} <span
                                            class="text-danger">*</span></label>
                                    {!! Form::selectRange(
                                        'year',
                                        $sessionYear,
                                        date('Y', strtotime(Carbon\Carbon::now())),
                                        date('Y', strtotime(Carbon\Carbon::now())),
                                        ['class' => 'form-control', 'id' => 'year'],
                                    ) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-2 mt-4">
                                    <input class="btn btn-theme" id="search" type="button" value={{ __('search') }}>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">{{ __('list') . ' ' . __('payroll') }}</h4>
                            <div class="row" id="toolbar">

                            </div>
                            <div class="staff-table">

                                <table aria-describedby="mydesc" class='table' id='table_list'
                                       data-toggle="table" data-url="{{ route('payroll.show', [1]) }}"
                                       data-click-to-select="true" data-side-pagination="server" data-pagination="false"
                                       data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="false"
                                       data-show-refresh="true" data-fixed-columns="false" data-fixed-number="2"
                                       data-fixed-right-number="1" data-trim-on-search="false" data-mobile-responsive="true"
                                       data-sort-name="id" data-sort-order="desc" data-maintain-selected="true" data-query-params="payrollQueryParams"
                                       data-toolbar="#toolbar"
                                       data-export-options='{ "fileName": "payroll-list-<?= date('d-m-y') ?>"
                                    ,"ignoreColumn":["operate"]}' data-show-export="true" data-export-data-type="all" data-escape="true" data-response-handler="responseHandler" data-check-on-init="true">
                                    <thead>
                                        <tr>
                                            <th data-field="state" data-checkbox="true"></th>
                                            <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                            <th scope="col" data-field="no">{{ __('no.') }}</th>
                                            <th scope="col" data-field="user.full_name">{{ __('name') }}</th>
                                            <th scope="col" data-formatter="salaryStatusFormatter" data-field="status">{{ __('status') }}</th>
                                            <th scope="col" data-field="salary" data-formatter="salaryInputFormatter" data-sortable="false">{{ __('basic_salary') }}</th>
                                            <th scope="col" class="text-wrap" data-field="paid_leaves" data-sortable="false">{{ __('Monthly Allowed Paid Leaves') }}</th>
                                            <th scope="col" class="text-wrap" data-field="total_leaves" data-sortable="false">{{ __('taken_leaves') }}</th>
                                            <th scope="col" class="text-wrap" data-field="salary_deduction" data-sortable="false" data-escape="false">{{ __('salary_deduction') }}</th>
                                            <th scope="col" class="text-wrap" data-field="allowances" data-sortable="false" data-escape="false">{{ __('allowances') }}</th>
                                            {{-- <th scope="col" class="text-wrap" data-field="other_allowances" data-sortable="false" data-escape="false">{{ __('other').' '.__('allowances') }}</th> --}}
                                            <th scope="col" class="text-wrap" data-field="deductions" data-sortable="false" data-escape="false">{{ __('deductions') }}</th>
                                            {{-- <th scope="col" class="text-wrap" data-field="other_deductions" data-sortable="false" data-escape="false">{{ __('other').' '.__('deductions') }}</th> --}}
                                            <th scope="col" data-field="net_salary" data-formatter="netSalaryInputFormatter" data-sortable="false">{{ __('net_salary') }}</th>
                                            <th scope="col" data-field="operate" data-escape="false">{{ __('action') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                                <textarea id="user_id" name="user_id" style="display: none"></textarea>
                                <input type="submit" class="btn btn-theme mt-3 float-right" value="{{ __('submit') }}">

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('script')
    <script>
        function alignStickyColumns() {
            let $table = $('#table_list');
            if (!$table.length) return;

            let idxState = $table.find('thead th[data-field="state"]').index() + 1;
            let idxNo = $table.find('thead th[data-field="no"]').index() + 1;
            let idxName = $table.find('thead th[data-field="user.full_name"]').index() + 1;

            if (idxState <= 0) idxState = 1;
            if (idxNo <= 0) idxNo = 2;
            if (idxName <= 0) idxName = 3;

            let $thState = $table.find('thead th:nth-child(' + idxState + ')');
            let $thNo = $table.find('thead th:nth-child(' + idxNo + ')');
            let $thName = $table.find('thead th:nth-child(' + idxName + ')');

            let wState = $thState.outerWidth() || 45;
            let wNo = $thNo.outerWidth() || 50;

            let leftNo = wState;
            let leftName = wState + wNo;

            $table.find('tr > *:nth-child(' + idxState + ')').css({
                'position': 'sticky',
                'left': '0px',
                'z-index': '5'
            });

            $table.find('tr > *:nth-child(' + idxNo + ')').css({
                'position': 'sticky',
                'left': leftNo + 'px',
                'z-index': '5'
            });

            $table.find('tr > *:nth-child(' + idxName + ')').css({
                'position': 'sticky',
                'left': leftName + 'px',
                'z-index': '5'
            });

            $thState.css('z-index', '7');
            $thNo.css('z-index', '7');
            $thName.css('z-index', '7');
        }

        function setupExportOptions() {
            let opts = $('#table_list').bootstrapTable('getOptions');
            if (opts) {
                opts.exportOptions = opts.exportOptions || {};
                opts.exportOptions.onCellHtmlData = function (cell, rowIndex, colIndex, htmlData) {
                    if (cell.is('th')) {
                        return cell.find('.th-inner').text() || cell.text();
                    }
                    var $input = cell.find('input:not([type="hidden"])');
                    if ($input.length > 0) {
                        var val = $input.val();
                        return (val !== undefined && val !== null && val !== '') ? String(val) : '0';
                    }
                    var $select = cell.find('select');
                    if ($select.length > 0) {
                        return $select.find('option:selected').text();
                    }
                    return htmlData;
                };
            }
        }

        $('#search').click(function (e) {
            e.preventDefault();
            $('#table_list').bootstrapTable('refresh');
            $('.staff-table').show();
            let month = $('#month').val();
            let year = $('#year').val();
            $('#table_list').bootstrapTable('uncheckAll');
            setTimeout(function () {
                alignStickyColumns();
                setupExportOptions();
            }, 300);
        });
        window.onload = $('.staff-table').hide();
    </script>

    <script>
        var $tableList = $('#table_list')
        var selections = []
        var user_list = [];

        function responseHandler(res) {
            $.each(res.rows, function (i, row) {
                row.state = $.inArray(row.id, selections) !== -1
            })
            return res
        }

        $(function () {
            alignStickyColumns();
            setupExportOptions();

            $tableList.on('post-body.bs.table post-header.bs.table load-success.bs.table', function () {
                alignStickyColumns();
                setupExportOptions();
            });

            $(window).on('resize', function () {
                alignStickyColumns();
            });

            $(document).on('click', '.export .dropdown-menu a, .export .dropdown-menu li, .export button', function () {
                setupExportOptions();
            });

            $tableList.on('check.bs.table check-all.bs.table uncheck.bs.table uncheck-all.bs.table',
                function (e, rowsAfter, rowsBefore) {
                    user_list = [];
                    var rows = rowsAfter
                    if (e.type === 'uncheck-all') {
                        rows = rowsBefore
                    }
                    var ids = $.map(!$.isArray(rows) ? [rows] : rows, function (row) {
                        return row.id
                    })

                    var func = $.inArray(e.type, ['check', 'check-all']) > -1 ? 'union' : 'difference'
                    selections = window._[func](selections, ids)
                    selections.forEach(element => {
                        user_list.push(element);
                    });
                    $('textarea#user_id').val(user_list);
                })
        })


        function formSuccessFunction(response) {
            setTimeout(() => {
                user_list = [];
                $('#table_list').bootstrapTable('uncheckAll');
                $('.staff-table').hide();
            }, 2000);
        }
    </script>
@endsection
