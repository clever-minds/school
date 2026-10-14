@extends('layouts.master')

@section('title')
    {{ __('attendance') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage').' '.__('attendance') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap pb-2 border-bottom mb-3">
                            <h4 class="card-title mb-0">
                                {{ __('view').' '.__('attendance') }}
                            </h4>
                            <ul class="nav nav-pills attendance-pills mt-2 mt-sm-0" id="attendanceViewTabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="tab-status" data-toggle="pill" href="#pane-status" role="tab" aria-selected="true" style="font-weight: 400 !important;">
                                        <i class="fa fa-calendar-check-o mr-1"></i> {{ __('Attendance Status') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="tab-punch" data-toggle="pill" href="#pane-punch" role="tab" aria-selected="false" style="font-weight: 400 !important;">
                                        <i class="fa fa-clock-o mr-1"></i> {{ __('Punch In / Out Time') }}
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="row mt-3">
                            <div class="form-group col-sm-12 col-md-3">
                                <label class="filter-menu font-weight-bold">{{ __('month') }} <span class="text-danger">*</span></label>
                                {!! Form::selectMonth('month', null, ['class' => 'form-control', 'id' => 'month']) !!}
                            </div>
                            <div class="form-group col-sm-12 col-md-3">
                                <label class="filter-menu font-weight-bold">{{ __('role') }}</label>
                                <select name="role_id" id="role_id" class="form-control">
                                    <option value="">{{ __('all') }}</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-12 col-md-6 d-flex align-items-center justify-content-md-end justify-content-start">
                                <div id="status-legend" class="mt-2">
                                    <span class="badge badge-success mr-2 p-2">P : Present</span>
                                    <span class="badge badge-danger mr-2 p-2">A : Absent</span>
                                    <span class="badge badge-warning mr-2 p-2">H : Half Day</span>
                                    <span class="badge badge-info p-2">W : Work From Home</span>
                                </div>
                            </div>
                        </div>

                        <div class="tab-content border-0 p-0 mt-3" id="attendanceTabContent">
                            <!-- Tab 1: Attendance Status View -->
                            <div class="tab-pane fade show active" id="pane-status" role="tabpanel" aria-labelledby="tab-status">
                                <div id="toolbar" class="mb-3 d-flex align-items-center justify-content-between w-100 flex-wrap">
                                    <div class="d-flex align-items-center my-1 flex-wrap">
                                        <span class="mr-3 font-weight-bold">Mark All Empty:</span>
                                        <button type="button" class="btn btn-success btn-sm mr-2" id="global-mark-p">P (Present)</button>
                                        <button type="button" class="btn btn-secondary btn-sm mr-3" id="global-uncheck">Uncheck</button>
                                        <button type="button" class="btn btn-theme btn-sm" id="save-all-attendance">
                                            <i class="fa fa-save mr-1"></i> Submit All Changes
                                        </button>
                                    </div>
                                    <div class="d-flex align-items-center my-1" id="status-toolbar-export"></div>
                                </div>

                                <div class="show_attendance_student_list">
                                    <table aria-describedby="mydesc" class='table student_table' id='table_list'
                                           data-toggle="table" data-click-to-select="true"
                                           data-pagination="false"
                                           data-search="false" data-toolbar="#toolbar"
                                           data-show-columns="false" data-show-refresh="false"
                                           data-trim-on-search="false"
                                           data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                                           data-maintain-selected="true" data-export-data-type='all' data-show-export="true"
                                           data-export-types='["excel", "csv"]'
                                           data-export-options='{ "fileName": "view-attendance-list-<?= date('d-m-y') ?>" ,"ignoreColumn": ["action"]}'
                                           data-escape="false">
                                        <thead>
                                        <tr>
                                            <th data-field="full_name">{{ __('staff_name') }}</th>
                                            <th data-field="action">{{ __('action') }}</th>
                                        </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>

                            <!-- Tab 2: Punch In / Out Time View -->
                            <div class="tab-pane fade" id="pane-punch" role="tabpanel" aria-labelledby="tab-punch">
                                <div id="punch_toolbar" class="mb-3 d-flex align-items-center justify-content-between w-100 flex-wrap">
                                    <div class="d-flex align-items-center my-1">
                                        <span class="text-muted font-italic" style="font-size: 13px;">
                                            <i class="fa fa-info-circle mr-1 text-primary"></i> Staff Punch In & Out Report
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center my-1" id="punch-toolbar-export"></div>
                                </div>

                                <div class="show_attendance_punch_list">
                                    <table aria-describedby="mydesc" class='table punch_table' id='punch_table_list'
                                           data-toggle="table" data-click-to-select="true"
                                           data-pagination="false"
                                           data-search="false" data-toolbar="#punch_toolbar"
                                           data-show-columns="false" data-show-refresh="false"
                                           data-trim-on-search="false"
                                           data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                                           data-maintain-selected="true" data-export-data-type='all' data-show-export="true"
                                           data-export-types='["excel", "csv"]'
                                           data-export-options='{ "fileName": "punch-attendance-list-<?= date('d-m-y') ?>" }'
                                           data-escape="false">
                                        <thead>
                                        <tr>
                                            <th data-field="full_name">{{ __('staff_name') }}</th>
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
    </div>
@endsection

@section('css')
<style>
    /* Nav Pills styling */
    .attendance-pills .nav-link {
        border-radius: 20px;
        padding: 6px 18px;
        color: #007bff;
        background-color: #ffffff;
        border: 1px solid #007bff;
        margin-left: 8px;
        font-size: 13px;
        font-weight: 400 !important;
        transition: all 0.2s ease-in-out;
    }
    .attendance-pills .nav-link:hover {
        background-color: #f0f7ff;
        color: #0056b3;
    }
    .attendance-pills .nav-link.active {
        background-color: #007bff !important;
        color: #ffffff !important;
        font-weight: 400 !important;
        border-color: #007bff !important;
        box-shadow: 0 2px 6px rgba(0, 123, 255, 0.35);
    }

    /* Reduce padding for table cells */
    .show_attendance_student_list .table td, 
    .show_attendance_student_list .table th {
        padding: 4px 5px !important;
        vertical-align: middle;
        text-align: center;
    }

    .show_attendance_punch_list .table td, 
    .show_attendance_punch_list .table th {
        padding: 5px 8px !important;
        vertical-align: middle;
        text-align: center;
        white-space: nowrap !important;
    }

    .show_attendance_student_list .table td:first-child,
    .show_attendance_student_list .table th:first-child,
    .show_attendance_punch_list .table td:first-child,
    .show_attendance_punch_list .table th:first-child {
        text-align: left;
    }

    .show_attendance_student_list .fixed-table-container,
    .show_attendance_punch_list .fixed-table-container {
        position: relative;
    }

    .show_attendance_student_list #table_list,
    .show_attendance_punch_list #punch_table_list {
        border-collapse: separate !important;
        border-spacing: 0 !important;
    }

    /* Status Table: Column 1 - Staff Name */
    .show_attendance_student_list #table_list th:nth-child(1),
    .show_attendance_student_list #table_list td:nth-child(1) {
        position: sticky !important;
        left: 0px !important;
        z-index: 5 !important;
        background-color: #ffffff !important;
        min-width: 160px !important;
        max-width: 220px !important;
    }

    /* Status Table: Column 2 - Action */
    .show_attendance_student_list #table_list th:nth-child(2),
    .show_attendance_student_list #table_list td:nth-child(2) {
        position: sticky !important;
        left: 160px !important;
        z-index: 5 !important;
        background-color: #ffffff !important;
        min-width: 95px !important;
        max-width: 100px !important;
        box-shadow: 4px 0 6px -2px rgba(0, 0, 0, 0.15) !important;
        border-right: 1px solid #ebedf2 !important;
    }

    /* Headers for Status Table */
    .show_attendance_student_list #table_list thead th:nth-child(1),
    .show_attendance_student_list #table_list thead th:nth-child(2) {
        z-index: 7 !important;
        background-color: #f8f9fa !important;
    }

    .show_attendance_student_list #table_list tbody tr:hover > td:nth-child(1),
    .show_attendance_student_list #table_list tbody tr:hover > td:nth-child(2) {
        background-color: #f2edf3 !important;
    }

    /* Punch Table: Column 1 - Staff Name */
    .show_attendance_punch_list #punch_table_list th:nth-child(1),
    .show_attendance_punch_list #punch_table_list td:nth-child(1) {
        position: sticky !important;
        left: 0px !important;
        z-index: 5 !important;
        background-color: #ffffff !important;
        min-width: 160px !important;
        max-width: 220px !important;
        box-shadow: 4px 0 6px -2px rgba(0, 0, 0, 0.15) !important;
        border-right: 1px solid #ebedf2 !important;
    }

    .show_attendance_punch_list #punch_table_list thead th:nth-child(1) {
        z-index: 7 !important;
        background-color: #f8f9fa !important;
    }

    .show_attendance_punch_list #punch_table_list tbody tr:hover > td:nth-child(1) {
        background-color: #f2edf3 !important;
    }

    /* Punch cell formatting matching Month dropdown font */
    .punch-cell {
        font-family: "ubuntu-regular", sans-serif !important;
        font-size: 11.5px !important;
        font-weight: 500 !important;
        line-height: 1.35 !important;
        white-space: nowrap !important;
        display: inline-flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 1px 0 !important;
    }

    .punch-cell .punch-in-text {
        font-family: "ubuntu-regular", sans-serif !important;
        font-size: 11.5px !important;
        font-weight: 500 !important;
        color: #0f5132 !important;
        white-space: nowrap !important;
        display: block !important;
    }

    .punch-cell .punch-out-text {
        font-family: "ubuntu-regular", sans-serif !important;
        font-size: 11.5px !important;
        font-weight: 500 !important;
        color: #842029 !important;
        white-space: nowrap !important;
        display: block !important;
    }
</style>
@endsection

@section('script')
    <script>
        function showToastMessage(message, type) {
            var toast_position = $('html').attr('dir') === 'rtl' ? 'top-left' : 'top-right';
            if (type === 'success' || type === 'info') {
                if (typeof showSuccessToast === 'function') {
                    showSuccessToast(message);
                } else if (typeof $.toast === 'function') {
                    $.toast({ text: message, showHideTransition: 'slide', icon: 'success', loaderBg: '#f96868', position: toast_position });
                } else {
                    console.log("Success: " + message);
                }
            } else {
                if (typeof showErrorToast === 'function') {
                    showErrorToast(message);
                } else if (typeof $.toast === 'function') {
                    $.toast({ text: message, showHideTransition: 'slide', icon: 'error', loaderBg: '#f2a654', position: toast_position, hideAfter: 5000 });
                } else {
                    console.error("Error: " + message);
                }
            }
        }
        
        const monthSelect = document.getElementById('month');
        const roleSelect = document.getElementById('role_id');

        function alignStickyColumns() {
            setTimeout(function() {
                let $statusTable = $('#table_list');
                if ($statusTable.length) {
                    let $th1 = $statusTable.find('thead th:nth-child(1)');
                    let w1 = $th1.outerWidth() || 160;

                    $statusTable.find('tr > *:nth-child(1)').css({
                        'position': 'sticky',
                        'left': '0px',
                        'z-index': '5',
                        'background-color': '#ffffff'
                    });

                    $statusTable.find('tr > *:nth-child(2)').css({
                        'position': 'sticky',
                        'left': w1 + 'px',
                        'z-index': '5',
                        'background-color': '#ffffff'
                    });

                    $statusTable.find('thead th:nth-child(1), thead th:nth-child(2)').css({
                        'z-index': '7',
                        'background-color': '#f8f9fa'
                    });
                }

                let $punchTable = $('#punch_table_list');
                if ($punchTable.length) {
                    $punchTable.find('tr > *:nth-child(1)').css({
                        'position': 'sticky',
                        'left': '0px',
                        'z-index': '5',
                        'background-color': '#ffffff'
                    });

                    $punchTable.find('thead th:nth-child(1)').css({
                        'z-index': '7',
                        'background-color': '#f8f9fa'
                    });
                }
            }, 50);
        }

        async function handleSelectChange() {
            var month = $('#month').val();
            var roleId = $('#role_id').val();
            var table = $('#table_list');
            var punchTable = $('#punch_table_list');

            if(month) {
                try {
                    table.bootstrapTable('showLoading');
                    punchTable.bootstrapTable('showLoading');

                    const response = await fetch(`{{ url('staff-attendance/month-wise/list') }}?month=${month}&role_id=${roleId}`);
                    const data = await response.json();
                    
                    var currentYear = new Date().getFullYear();

                    // Destroy and recreate Status table
                    table.bootstrapTable('destroy').bootstrapTable({
                        columns: [
                            {
                                field: 'full_name',
                                title: 'Staff Name'
                            },
                            {
                                field: 'action',
                                title: 'Action',
                                formatter: actionFormatter
                            },
                            ...generateDayColumns(month)
                        ],
                        data: data.rows,
                        pagination: false,
                        search: false,
                        toolbar: '#toolbar',
                        showColumns: false,
                        showRefresh: false,
                        mobileResponsive: true,
                        sortName: 'id',
                        sortOrder: 'desc',
                        maintainSelected: true,
                        exportDataType: 'all',
                        showExport: true,
                        exportTypes: ['excel', 'csv'],
                        exportOptions: {
                            fileName: 'staff-attendance-status-' + month + '-' + currentYear,
                            ignoreColumn: ['action'],
                            onCellHtmlData: function (cell, rowIndex, colIndex, htmlData) {
                                if (cell.is('th')) {
                                    return cell.find('.th-inner').text().trim() || cell.text().trim();
                                }
                                let select = cell.find('select.update-attendance');
                                if (select.length) {
                                    let val = select.val();
                                    return (val && val !== '-') ? val : '';
                                }
                                let txt = $('<div>').html(htmlData).text().trim();
                                return (txt === '-' || txt === '') ? '' : txt;
                            }
                        },
                        escape: false
                    });

                    // Destroy and recreate Punch table
                    punchTable.bootstrapTable('destroy').bootstrapTable({
                        columns: [
                            {
                                field: 'full_name',
                                title: 'Staff Name'
                            },
                            ...generatePunchDayColumns(month)
                        ],
                        data: data.rows,
                        pagination: false,
                        search: false,
                        toolbar: '#punch_toolbar',
                        showColumns: false,
                        showRefresh: false,
                        mobileResponsive: true,
                        sortName: 'id',
                        sortOrder: 'desc',
                        maintainSelected: true,
                        exportDataType: 'all',
                        showExport: true,
                        exportTypes: ['excel', 'csv'],
                        exportOptions: {
                            fileName: 'staff-punch-attendance-' + month + '-' + currentYear,
                            onCellHtmlData: function (cell, rowIndex, colIndex, htmlData) {
                                if (cell.is('th')) {
                                    return cell.find('.th-inner').text().trim() || cell.text().trim();
                                }
                                let inSpan = cell.find('.punch-in-text');
                                let outSpan = cell.find('.punch-out-text');
                                if (inSpan.length && outSpan.length) {
                                    return inSpan.text().trim() + ' | ' + outSpan.text().trim();
                                } else if (inSpan.length) {
                                    return inSpan.text().trim();
                                } else if (outSpan.length) {
                                    return outSpan.text().trim();
                                }
                                let txt = $('<div>').html(htmlData).text().trim();
                                return (txt === '-' || txt === '') ? '' : txt;
                            }
                        },
                        escape: false
                    });

                    table.on('post-body.bs.table post-header.bs.table', function() {
                        alignStickyColumns();
                    });
                    punchTable.on('post-body.bs.table post-header.bs.table', function() {
                        alignStickyColumns();
                    });

                    alignStickyColumns();
                    setTimeout(alignStickyColumns, 150);
                    setTimeout(alignStickyColumns, 400);

                } catch (error) {
                    console.error('Error fetching attendance data:', error);
                    table.bootstrapTable('hideLoading');
                    punchTable.bootstrapTable('hideLoading');
                }
            }
        }

        monthSelect.addEventListener('change', handleSelectChange);
        roleSelect.addEventListener('change', handleSelectChange);

        function generateDayColumns(month) {
            var currentYear = new Date().getFullYear();
            const daysInMonth = new Date(currentYear, month, 0).getDate();
            const columns = [];

            for (let day = 1; day <= daysInMonth; day++) {
                let date = new Date(currentYear, month - 1, day);
                let isSunday = date.getDay() === 0;
                
                columns.push({
                    field: `day_${day}`,
                    title: `${day}`,
                    align: 'center',
                    formatter: attendanceFormatter,
                    cellStyle: function(value, row, index) {
                        if (isSunday) {
                            return {
                                css: { "background-color": "#ffe6e6" }
                            };
                        }
                        return {};
                    }
                });
            }

            return columns;
        }

        function generatePunchDayColumns(month) {
            var currentYear = new Date().getFullYear();
            const daysInMonth = new Date(currentYear, month, 0).getDate();
            const columns = [];

            for (let day = 1; day <= daysInMonth; day++) {
                let date = new Date(currentYear, month - 1, day);
                let isSunday = date.getDay() === 0;
                
                columns.push({
                    field: `punch_day_${day}`,
                    title: `${day}`,
                    align: 'center',
                    formatter: function(value, row, index) {
                        return punchAttendanceFormatter(row, day);
                    },
                    cellStyle: function(value, row, index) {
                        if (isSunday) {
                            return {
                                css: { 
                                    "background-color": "#ffe6e6",
                                    "min-width": "100px",
                                    "white-space": "nowrap"
                                }
                            };
                        }
                        return {
                            css: {
                                "min-width": "100px",
                                "white-space": "nowrap"
                            }
                        };
                    }
                });
            }

            return columns;
        }

        function actionFormatter(value, row, index) {
            let userId = row.user_id;
            return `
                <div class="d-flex justify-content-center align-items-center" style="min-width: 90px;">
                    <span class="badge badge-success row-mark-p mr-2" data-userid="${userId}" style="cursor: pointer; font-size: 13px; padding: 5px 8px;">P</span>
                    <span class="badge badge-secondary row-uncheck" data-userid="${userId}" style="cursor: pointer; font-size: 11px; padding: 5px 8px;">Uncheck</span>
                </div>
            `;
        }

        function attendanceFormatter(value, row, index, field) {
            let day = field.replace('day_', '');
            let userId = row.user_id;

            let pSelected = value === 'P' ? 'selected' : '';
            let aSelected = value === 'A' ? 'selected' : '';
            let wfhSelected = value === 'W' ? 'selected' : '';
            let hdSelected = value === 'H' ? 'selected' : '';

            let colorClass = 'text-secondary';
            if (value === 'P') colorClass = 'text-success';
            else if (value === 'A') colorClass = 'text-danger';
            else if (value === 'W') colorClass = 'text-info';
            else if (value === 'H') colorClass = 'text-warning';

            return `
                <select class="form-control form-control-sm ${colorClass} update-attendance font-weight-bold" style="width: auto; padding: 2px; height: auto; font-size: 13px; min-width: 45px;" data-userid="${userId}" data-day="${day}">
                    <option value="">-</option>
                    <option value="P" class="text-success" ${pSelected}>P</option>
                    <option value="A" class="text-danger" ${aSelected}>A</option>
                    <option value="H" class="text-warning" ${hdSelected}>H</option>
                    <option value="W" class="text-info" ${wfhSelected}>W</option>
                </select>
            `;
        }

        function punchAttendanceFormatter(row, day) {
            let punchIn = row['punch_in_' + day];
            let punchOut = row['punch_out_' + day];

            // If neither punch in nor punch out, return blank
            if (!punchIn && !punchOut) {
                return '';
            }

            // If both punch in and punch out exist, show ek ke niche ek
            if (punchIn && punchOut) {
                return `
                    <div class="punch-cell">
                        <span class="punch-in-text">In: ${punchIn}</span>
                        <span class="punch-out-text">Out: ${punchOut}</span>
                    </div>
                `;
            }

            // If only punch in exists, show punch in only
            if (punchIn) {
                return `
                    <div class="punch-cell">
                        <span class="punch-in-text">In: ${punchIn}</span>
                    </div>
                `;
            }

            // If only punch out exists, show punch out only
            if (punchOut) {
                return `
                    <div class="punch-cell">
                        <span class="punch-out-text">Out: ${punchOut}</span>
                    </div>
                `;
            }

            return '';
        }

        // Tab change event
        $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr("href");
            if (target === '#pane-punch') {
                $('#status-legend').addClass('d-none');
                $('#punch_table_list').bootstrapTable('resetView');
            } else {
                $('#status-legend').removeClass('d-none');
                $('#table_list').bootstrapTable('resetView');
            }
            alignStickyColumns();
        });

        $(document).on('change', '.update-attendance', function() {
            let val = $(this).val();
            let userId = $(this).data('userid');
            let day = $(this).data('day');
            let month = $('#month').val();
            let currentYear = new Date().getFullYear();
            
            // Format date as YYYY-MM-DD
            let date = `${currentYear}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
            
            let status = '';
            let type = '';

            if (val === 'P') {
                status = 1; 
                type = '';
            } else if (val === 'A') {
                status = 4;
                type = '';
            } else if (val === 'H') {
                status = 3;
                type = '';
            } else if (val === 'W') {
                status = 1;
                type = 'Work From Home';
            }

            let selectElem = $(this);
            selectElem.removeClass('text-success text-danger text-info text-warning text-secondary');
            if (val === 'P') selectElem.addClass('text-success');
            else if (val === 'A') selectElem.addClass('text-danger');
            else if (val === 'H') selectElem.addClass('text-warning');
            else if (val === 'W') selectElem.addClass('text-info');
            else selectElem.addClass('text-secondary');

            // Mark as dirty to save on submit
            selectElem.addClass('is-dirty');
        });

        $(document).on('click', '#global-mark-p', function() {
            try {
                let type = 'P';
                let count = 0;

                $('.update-attendance').each(function() {
                    let currentVal = $(this).val();
                    if(!currentVal || currentVal === '' || currentVal === '-') {
                        $(this).val(type);
                        $(this).removeClass('text-success text-danger text-info text-warning text-secondary');
                        if(type === 'P') $(this).addClass('text-success');
                        else $(this).addClass('text-danger');

                        $(this).addClass('is-dirty checkbox-marked');
                        count++;
                    }
                });

                if(count === 0) {
                    if (typeof showToastMessage === 'function') {
                        showToastMessage('No empty slots found to update', 'warning');
                    }
                }
            } catch (e) {
                console.error(e);
                if (typeof showToastMessage === 'function') {
                    showToastMessage('JS Error: ' + e.message, 'error');
                }
            }
        });

        $(document).on('click', '#global-uncheck', function() {
            $('.update-attendance.checkbox-marked').each(function() {
                if($(this).val() === 'P') {
                    $(this).prop('selectedIndex', 0);
                    $(this).removeClass('text-success text-danger text-info text-warning text-secondary is-dirty checkbox-marked');
                    $(this).addClass('text-secondary');
                }
            });
        });

        $(document).on('click', '.row-mark-p', function() {
            try {
                let type = 'P';
                let userId = $(this).data('userid');
                let count = 0;

                $(`.update-attendance[data-userid="${userId}"]`).each(function() {
                    let currentVal = $(this).val();
                    if(!currentVal || currentVal === '' || currentVal === '-') {
                        $(this).val(type);
                        $(this).removeClass('text-success text-danger text-info text-warning text-secondary');
                        if(type === 'P') $(this).addClass('text-success');
                        else $(this).addClass('text-danger');

                        $(this).addClass('is-dirty checkbox-marked');
                        count++;
                    }
                });

                if(count === 0) {
                    if (typeof showToastMessage === 'function') {
                        showToastMessage('No empty slots found to update', 'warning');
                    }
                }
            } catch (e) {
                console.error(e);
                if (typeof showToastMessage === 'function') {
                    showToastMessage('JS Error: ' + e.message, 'error');
                }
            }
        });

        $(document).on('click', '.row-uncheck', function() {
            let userId = $(this).data('userid');
            $(`.update-attendance[data-userid="${userId}"].checkbox-marked`).each(function() {
                if($(this).val() === 'P') {
                    $(this).prop('selectedIndex', 0);
                    $(this).removeClass('text-success text-danger text-info text-warning text-secondary is-dirty checkbox-marked');
                    $(this).addClass('text-secondary');
                }
            });
        });

        $(document).on('click', '#save-all-attendance', function() {
            let updates = [];
            let month = $('#month').val();
            let currentYear = new Date().getFullYear();

            $('.update-attendance.is-dirty').each(function() {
                let val = $(this).val();
                if(val && val !== '-' && val !== '') {
                    let day = $(this).data('day');
                    let userId = $(this).data('userid');
                    let date = `${currentYear}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                    
                    let status = 1;
                    let type = '';
                    if (val === 'A') status = 4;
                    else if (val === 'H') status = 3;
                    else if (val === 'W') { status = 1; type = 'Work From Home'; }

                    updates.push({
                        user_id: userId,
                        date: date,
                        status: status,
                        type: type
                    });
                }
            });

            if (updates.length > 0) {
                let btn = $(this);
                let originalText = btn.html();
                btn.html('<i class="fa fa-spinner fa-spin mr-1"></i> Saving...');
                btn.prop('disabled', true);

                $.ajax({
                    url: '{{ route("staff-attendance.month-wise-bulk-save") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        attendances: updates
                    },
                    success: function(response) {
                        btn.html(originalText);
                        btn.prop('disabled', false);

                        if (!response.error) {
                            $('.update-attendance.is-dirty').removeClass('is-dirty checkbox-marked');
                            if (typeof showToastMessage === 'function') {
                                showToastMessage(response.message || 'Successfully Saved', 'success');
                            }
                        } else {
                            if (typeof showToastMessage === 'function') {
                                showToastMessage(response.message, 'error');
                            }
                        }
                    },
                    error: function(xhr) {
                        btn.html(originalText);
                        btn.prop('disabled', false);
                        console.error('Bulk save error:', xhr);
                        if (typeof showToastMessage === 'function') {
                            showToastMessage('An error occurred during bulk save', 'error');
                        }
                    }
                });
            } else {
                if (typeof showToastMessage === 'function') {
                    showToastMessage('No changes to save', 'info');
                }
            }
        });

        $(document).ready(function() {
            // Check if tab parameter is specified in URL (e.g. from sidebar)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('tab') === 'punch' || urlParams.get('type') === 'punch') {
                $('#tab-punch').tab('show');
                $('#status-legend').addClass('d-none');
            }

            if ($('#month').val()) {
                handleSelectChange();
            }
        });
    </script>
@endsection
