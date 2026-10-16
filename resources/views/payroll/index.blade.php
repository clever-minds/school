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
                            <div class="row align-items-center mb-2" id="toolbar">
                                <div class="col-auto">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnPrintSalarySheet">
                                        <i class="fa fa-print mr-1"></i> {{ __('Print Salary Sheet') }}
                                    </button>
                                </div>
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
                                       data-export-options='{ "fileName": "payroll-salary-sheet-<?= date('d-m-y') ?>", "ignoreColumn":["state", "status", "operate"]}'
                                       data-show-export="true" data-export-data-type="all" data-export-types="['excel', 'csv', 'pdf', 'txt', 'json']"
                                       data-escape="true" data-response-handler="responseHandler" data-check-on-init="true">
                                    <thead>
                                        <tr>
                                            <th data-field="state" data-checkbox="true"></th>
                                            <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                            <th scope="col" data-field="no" data-sortable="false">{{ __('Sr.no.') }}</th>
                                            <th scope="col" data-field="user_full_name" data-sortable="false">{{ __('NAME') }}</th>
                                            <th scope="col" data-field="designation" data-sortable="false">{{ __('Designation') }}</th>
                                            <th scope="col" data-field="salary" data-formatter="salaryInputFormatter" data-sortable="false">{{ __('Basic Sa') }}</th>
                                            <th scope="col" data-field="perfo" data-formatter="perfoFormatter" data-sortable="false" data-escape="false">{{ __('Perfo') }}</th>
                                            <th scope="col" data-field="per_day" data-sortable="false">{{ __('Per Day') }}</th>
                                            <th scope="col" data-field="total_gross" data-sortable="false">{{ __('Total') }}</th>
                                            <th scope="col" data-field="working_days" data-sortable="false">{{ __('Wor') }}</th>
                                            <th scope="col" data-field="absent_days" data-sortable="false">{{ __('Abse') }}</th>
                                            <th scope="col" data-field="paid_leaves" data-sortable="false">{{ __('Paid') }}</th>
                                            <th scope="col" data-field="pen" data-sortable="false">{{ __('Pen') }}</th>
                                            <th scope="col" data-field="attended_days" data-sortable="false">{{ __('Atten') }}</th>
                                            <th scope="col" data-field="other_deductions" data-sortable="false">{{ __('OTHER') }}</th>
                                            <th scope="col" data-field="net_salary" data-formatter="netSalaryInputFormatter" data-sortable="false">{{ __('Salary paid') }}</th>
                                            <th scope="col" data-field="signature" data-formatter="signatureFormatter" data-sortable="false" data-escape="false">{{ __('Sign.') }}</th>
                                            <th scope="col" data-formatter="salaryStatusFormatter" data-field="status">{{ __('status') }}</th>
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

        function alignStickyColumns() {
            let $table = $('#table_list');
            if (!$table.length) return;

            let idxState = $table.find('thead th[data-field="state"]').index() + 1;
            let idxNo = $table.find('thead th[data-field="no"]').index() + 1;
            let idxName = $table.find('thead th[data-field="user_full_name"]').index() + 1;
            if (idxName <= 0) {
                idxName = $table.find('thead th[data-field="user.full_name"]').index() + 1;
            }

            if (idxState <= 0) idxState = 1;
            if (idxNo <= 0) idxNo = 2;
            if (idxName <= 0) idxName = 3;

            let $thState = $table.find('thead th:nth-child(' + idxState + ')');
            let $thNo = $table.find('thead th:nth-child(' + idxNo + ')');
            let $thName = $table.find('thead th:nth-child(' + idxName + ')');

            let wState = $thState.outerWidth() || 45;
            let wNo = $thNo.outerWidth() || 60;

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
