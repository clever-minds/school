@extends('layouts.master')

@section('title')
    {{ __('payroll') }}
@endsection

@section('css')
<style>
    /* Exact Attendance table compact styling */
    .show_attendance_student_list .table td, 
    .show_attendance_student_list .table th {
        padding: 4px 5px !important;
        vertical-align: middle;
        white-space: nowrap;
    }

    /* Compact inputs matching attendance */
    .show_attendance_student_list .basic-salary-input,
    .show_attendance_student_list .net-salary-input {
        height: 26px !important;
        line-height: 26px !important;
        padding: 1px 6px !important;
        font-size: 12px !important;
        border: 1px solid #ced4da !important;
        border-radius: 3px !important;
        background-color: #ffffff !important;
        display: inline-block;
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
                            <div class="row align-items-center mb-2" id="toolbar">
                                <div class="col-auto">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnPrintSalarySheet">
                                        <i class="fa fa-print mr-1"></i> {{ __('Print Salary Sheet') }}
                                    </button>
                                </div>
                            </div>
                            <div class="show_attendance_student_list">

                                <table aria-describedby="mydesc" class='table student_table' id='table_list'
                                       data-toggle="table" data-url="{{ route('payroll.show', [1]) }}"
                                       data-click-to-select="true" data-side-pagination="server" data-pagination="false"
                                       data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-show-columns="false"
                                       data-show-refresh="true" data-fixed-columns="true" data-fixed-number="3"
                                       data-fixed-right-number="0" data-trim-on-search="false" data-mobile-responsive="true"
                                       data-sort-name="id" data-sort-order="desc" data-maintain-selected="true" data-query-params="payrollQueryParams"
                                       data-toolbar="#toolbar"
                                       data-export-options='{ "fileName": "payroll-salary-sheet-<?= date('d-m-y') ?>", "ignoreColumn":["state", "status", "operate"]}'
                                       data-show-export="true" data-export-data-type="all" data-export-types="['excel', 'csv', 'pdf', 'txt', 'json']"
                                       data-escape="true" data-response-handler="responseHandler" data-check-on-init="true">
                                    <thead>
                                        <tr>
                                            <th data-field="state" data-checkbox="true"></th>
                                            <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                            <th scope="col" data-field="no" data-sortable="false" data-align="center">{{ __('Sr.no.') }}</th>
                                            <th scope="col" data-field="user_full_name" data-sortable="false" data-align="left">{{ __('NAME') }}</th>
                                            <th scope="col" data-field="designation" data-sortable="false" data-align="left">{{ __('Designation') }}</th>
                                            <th scope="col" data-field="salary" data-formatter="salaryInputFormatter" data-sortable="false" data-align="right">{{ __('Basic Sa') }}</th>
                                            <th scope="col" data-field="perfo" data-formatter="perfoFormatter" data-sortable="false" data-escape="false" data-align="right">{{ __('Perfo') }}</th>
                                            <th scope="col" data-field="per_day" data-sortable="false" data-align="right">{{ __('Per Day') }}</th>
                                            <th scope="col" data-field="total_gross" data-sortable="false" data-align="right">{{ __('Total') }}</th>
                                            <th scope="col" data-field="working_days" data-sortable="false" data-align="center">{{ __('Wor') }}</th>
                                            <th scope="col" data-field="absent_days" data-sortable="false" data-align="center">{{ __('Abse') }}</th>
                                            <th scope="col" data-field="paid_leaves" data-sortable="false" data-align="center">{{ __('Paid') }}</th>
                                            <th scope="col" data-field="pen" data-sortable="false" data-align="center">{{ __('Pen') }}</th>
                                            <th scope="col" data-field="attended_days" data-sortable="false" data-align="center">{{ __('Atten') }}</th>
                                            <th scope="col" data-field="other_deductions" data-sortable="false" data-align="right">{{ __('OTHER') }}</th>
                                            <th scope="col" data-field="net_salary" data-formatter="netSalaryInputFormatter" data-sortable="false" data-align="right">{{ __('Salary paid') }}</th>
                                            <th scope="col" data-field="signature" data-formatter="signatureFormatter" data-sortable="false" data-escape="false" data-align="center">{{ __('Sign.') }}</th>
                                            <th scope="col" data-formatter="salaryStatusFormatter" data-field="status" data-align="center">{{ __('status') }}</th>
                                            <th scope="col" data-field="operate" data-escape="false" data-align="center">{{ __('action') }}</th>
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
        function salaryInputFormatter(value, row) {
            let val = parseFloat(value) || 0;
            return '<input style="min-width: 90px; max-width: 105px;" type="number" step="any" min="0" required name="basic_salary[' + row.id + ']" class="form-control form-control-sm text-right basic-salary-input" readonly value="' + val.toFixed(2) + '">';
        }

        function perfoFormatter(value, row) {
            if (row.perfo_html) {
                return row.perfo_html;
            }
            return (parseFloat(value) || 0).toFixed(2);
        }

        function netSalaryInputFormatter(value, row) {
            let val = parseFloat(value) || 0;
            let html = '<input style="min-width: 100px; max-width: 115px;" type="number" step="any" min="0" required name="net_salary[' + row.id + ']" class="form-control form-control-sm text-right font-weight-bold net-salary-input" value="' + val.toFixed(2) + '">';
            html += '<input type="hidden" required name="paid_leave[' + row.id + ']" value="' + (row.paid_leaves !== undefined ? row.paid_leaves : 0) + '">';
            return html;
        }

        function signatureFormatter(value, row) {
            return '<span class="signature-line" style="display:inline-block; width: 60px; border-bottom: 1px dashed #bbb;">&nbsp;</span>';
        }


        function setupExportOptions() {
            let opts = $('#table_list').bootstrapTable('getOptions');
            if (opts) {
                opts.exportOptions = opts.exportOptions || {};
                let monthName = $('#month option:selected').text().trim() || 'Payroll';
                let yearName = $('#year').val() || '';
                opts.exportOptions.fileName = 'salary-sheet-' + monthName + '-' + yearName;
                opts.exportOptions.ignoreColumn = ['state', 'status', 'operate'];
                opts.exportOptions.onCellHtmlData = function (cell, rowIndex, colIndex, htmlData) {
                    if (cell.is('th')) {
                        return (cell.find('.th-inner').text() || cell.text()).trim();
                    }
                    var $input = cell.find('input:not([type="hidden"])');
                    if ($input.length > 0) {
                        var val = $input.val();
                        return (val !== undefined && val !== null && val !== '') ? String(val) : '0';
                    }
                    var $select = cell.find('select');
                    if ($select.length > 0) {
                        return $select.find('option:selected').text().trim();
                    }
                    var $raw = cell.find('.export-raw');
                    if ($raw.length > 0) {
                        return $raw.text().trim();
                    }
                    return cell.text().replace(/\s+/g, ' ').trim();
                };
            }
        }

        $('#btnPrintSalarySheet').on('click', function() {
            let data = $('#table_list').bootstrapTable('getData');
            if (!data || data.length === 0) {
                alert('No salary records loaded to print.');
                return;
            }

            let schoolName = "{{ $schoolSetting['school_name'] ?? 'GLOBAL KNOWLEDGE INTERNATIONAL SCHOOL' }}";
            let monthName = $('#month option:selected').text().trim();
            let yearName = $('#year').val();

            let totalBasic = 0;
            let totalPerfo = 0;
            let totalGross = 0;
            let totalSalaryPaid = 0;

            let rowsHtml = '';
            $.each(data, function(i, row) {
                let basicVal = parseFloat(row.salary) || 0;
                let perfoVal = parseFloat(row.perfo) || 0;
                let perDayVal = parseFloat(row.per_day) || 0;
                let grossVal = parseFloat(row.total_gross) || 0;
                let netVal = parseFloat(row.net_salary) || 0;
                let otherDeductionsVal = parseFloat(row.other_deductions) || 0;

                totalBasic += basicVal;
                totalPerfo += perfoVal;
                totalGross += grossVal;
                totalSalaryPaid += netVal;

                rowsHtml += '<tr>' +
                    '<td style="text-align: center;">' + (row.no || (i + 1)) + '</td>' +
                    '<td>' + (row.user_full_name || (row.user && row.user.full_name) || '') + '</td>' +
                    '<td>' + (row.designation || '-') + '</td>' +
                    '<td style="text-align: right;">' + basicVal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                    '<td style="text-align: right;">' + perfoVal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                    '<td style="text-align: right;">' + perDayVal.toFixed(2) + '</td>' +
                    '<td style="text-align: right; font-weight: bold;">' + grossVal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                    '<td style="text-align: center;">' + (row.working_days || 30) + '</td>' +
                    '<td style="text-align: center;">' + (row.absent_days !== undefined ? row.absent_days : 0) + '</td>' +
                    '<td style="text-align: center;">' + (row.paid_leaves !== undefined ? row.paid_leaves : 0) + '</td>' +
                    '<td style="text-align: center;">' + (row.pen || '-') + '</td>' +
                    '<td style="text-align: center; font-weight: bold;">' + (row.attended_days !== undefined ? row.attended_days : 30) + '</td>' +
                    '<td style="text-align: right;">' + (otherDeductionsVal > 0 ? otherDeductionsVal.toFixed(2) : '') + '</td>' +
                    '<td style="text-align: right; font-weight: bold;">' + netVal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                    '<td style="width: 80px;"></td>' +
                '</tr>';
            });

            let totalsHtml = '<tr style="font-weight: bold; background-color: #f2f2f2;">' +
                '<td colspan="3" style="text-align: right;">TOTAL:</td>' +
                '<td style="text-align: right;">' + totalBasic.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                '<td style="text-align: right;">' + totalPerfo.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                '<td></td>' +
                '<td style="text-align: right;">' + totalGross.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                '<td colspan="5"></td>' +
                '<td></td>' +
                '<td style="text-align: right;">' + totalSalaryPaid.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
                '<td></td>' +
            '</tr>';

            let printContent = '<!DOCTYPE html>' +
                '<html>' +
                '<head>' +
                '<title>Salary Sheet - ' + monthName + ' ' + yearName + '</title>' +
                '<style>' +
                    '@page { size: landscape; margin: 8mm; }' +
                    'body { font-family: "Segoe UI", Arial, sans-serif; font-size: 11px; color: #000; margin: 0; padding: 10px; }' +
                    '.header-title { text-align: center; font-size: 16px; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; letter-spacing: 0.5px; }' +
                    '.header-sub { text-align: center; font-size: 13px; font-weight: 600; margin-bottom: 15px; }' +
                    'table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }' +
                    'th, td { border: 1px solid #333; padding: 4px 6px; font-size: 10.5px; }' +
                    'th { background-color: #f7f7f7; text-align: center; font-weight: bold; font-size: 10.5px; }' +
                    '.signatures { display: flex; justify-content: space-between; margin-top: 40px; padding: 0 30px; font-weight: 600; font-size: 11px; }' +
                '</style>' +
                '</head>' +
                '<body>' +
                '<div class="header-title">' + schoolName + '</div>' +
                '<div class="header-sub">Salary for the Month of ' + monthName + ' - ' + yearName + ' (IDFC / PNB)</div>' +
                '<table>' +
                    '<thead>' +
                        '<tr>' +
                            '<th style="width: 45px;">Sr.no.</th>' +
                            '<th>NAME</th>' +
                            '<th>Designation</th>' +
                            '<th>Basic Sa</th>' +
                            '<th>Perfo</th>' +
                            '<th>Per Day</th>' +
                            '<th>Total</th>' +
                            '<th style="width: 35px;">Wor</th>' +
                            '<th style="width: 35px;">Abse</th>' +
                            '<th style="width: 35px;">Paid</th>' +
                            '<th style="width: 35px;">Pen</th>' +
                            '<th style="width: 40px;">Atten</th>' +
                            '<th>OTHER</th>' +
                            '<th>Salary paid</th>' +
                            '<th style="width: 80px;">Sign.</th>' +
                        '</tr>' +
                    '</thead>' +
                    '<tbody>' +
                        rowsHtml +
                        totalsHtml +
                    '</tbody>' +
                '</table>' +
                '<div class="signatures">' +
                    '<div>Prepared By: ___________________</div>' +
                    '<div>Checked By: ___________________</div>' +
                    '<div>Principal / Director: ___________________</div>' +
                '</div>' +
                '</body>' +
                '</html>';

            let printWindow = window.open('', '_blank');
            printWindow.document.open();
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(function() {
                printWindow.print();
            }, 500);
        });

        $('#search').click(function (e) {
            e.preventDefault();
            $('.show_attendance_student_list').show();
            $('#table_list').bootstrapTable('refresh');
            $('#table_list').bootstrapTable('uncheckAll');
            setTimeout(function () {
                $('#table_list').bootstrapTable('resetView');
                setupExportOptions();
            }, 300);
        });

        $(document).ready(function() {
            $('.show_attendance_student_list').hide();
        });
    </script>

    <script>
        var $tableList = $('#table_list');
        var selections = [];
        var user_list = [];

        function responseHandler(res) {
            $.each(res.rows, function (i, row) {
                row.state = $.inArray(row.id, selections) !== -1;
            });
            return res;
        }

        $(function () {
            setupExportOptions();

            $tableList.on('load-success.bs.table post-body.bs.table', function () {
                $('.fixed-columns input, .fixed-columns select').not('[type="checkbox"]').removeAttr('name');
                setupExportOptions();
            });

            $(window).on('resize', function () {
                $('#table_list').bootstrapTable('resetView');
            });

            $(document).on('click', '.export .dropdown-menu a, .export .dropdown-menu li, .export button', function () {
                setupExportOptions();
            });

            // Delegate checkbox clicks from fixed columns overlay to Bootstrap Table
            $(document).on('click', '.fixed-columns [name="btSelectItem"]', function (e) {
                e.stopPropagation();
                var idx = $(this).closest('tr').data('index');
                if (idx !== undefined) {
                    if ($(this).prop('checked')) {
                        $tableList.bootstrapTable('check', idx);
                    } else {
                        $tableList.bootstrapTable('uncheck', idx);
                    }
                }
            });

            $(document).on('click', '.fixed-columns [name="btSelectAll"]', function (e) {
                e.stopPropagation();
                if ($(this).prop('checked')) {
                    $tableList.bootstrapTable('checkAll');
                } else {
                    $tableList.bootstrapTable('uncheckAll');
                }
            });

            $tableList.on('check.bs.table check-all.bs.table uncheck.bs.table uncheck-all.bs.table',
                function (e, rowsAfter, rowsBefore) {
                    user_list = [];
                    var rows = rowsAfter;
                    if (e.type === 'uncheck-all') {
                        rows = rowsBefore;
                    }
                    var ids = $.map(!$.isArray(rows) ? [rows] : rows, function (row) {
                        return row.id;
                    });

                    var func = $.inArray(e.type, ['check', 'check-all']) > -1 ? 'union' : 'difference';
                    selections = window._[func](selections, ids);
                    selections.forEach(element => {
                        user_list.push(element);
                    });
                    $('textarea#user_id').val(user_list);
                });
        });

        function formSuccessFunction(response) {
            setTimeout(() => {
                user_list = [];
                $('#table_list').bootstrapTable('uncheckAll');
                $('.show_attendance_student_list').hide();
            }, 2000);
        }
    </script>
@endsection
