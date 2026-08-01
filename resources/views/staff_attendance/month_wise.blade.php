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
                        <h4 class="card-title">
                            {{ __('view').' '.__('attendance') }}
                        </h4>
                        <div class="row mt-4">
                            <div class="form-group col-sm-12 col-md-3">
                                <label class="filter-menu">{{ __('month') }} <span class="text-danger">*</span></label>
                                {!! Form::selectMonth('month',null,['class' => 'form-control','id' => 'month']) !!}
                            </div>
                        </div>

                        <div class="show_attendance_student_list">
                            <table aria-describedby="mydesc" class='table student_table' id='table_list'
                                   data-toggle="table"  data-click-to-select="true"
                                   data-side-pagination="server" data-pagination="false"
                                   data-page-list="[5, 10, 20, 50, 100, 200,All]" data-search="false" data-toolbar="#toolbar"
                                   data-show-columns="false" data-show-refresh="false" data-fixed-columns="false"
                                   data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false"
                                   data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                                   data-maintain-selected="true" data-export-data-type='all' data-show-export="false"
                                   data-export-options='{ "fileName": "view-attendance-list-<?= date('d-m-y') ?>" ,"ignoreColumn": ["operate"]}'
                                   data-query-params="AttendanceReportqueryParams" data-escape="true">
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
@endsection

@section('script')


    <script>
        
        const monthSelect = document.getElementById('month');

        async function handleSelectChange() {
            var month = $('#month').val();
            var table = $('#table_list');
            if(month) {
                const response = await fetch(`/staff-attendance/month-wise/list?month=${month}`);
            const data = await response.json();
            table.bootstrapTable('load', data);
            try {
                // Fetch the attendance data
                // Ensure data is loaded before refreshing the table
                        
                // Update the table columns dynamically based on the month
                table.bootstrapTable('refreshOptions', {
                    columns: [
                        {
                            field: 'full_name',
                            title: 'Staff Name'
                        },
                        ...generateDayColumns(month)
                    ]
                });
                
                
            } catch (error) {
                console.error('Error fetching attendance data:', error);
            }
        }

        monthSelect.addEventListener('change', handleSelectChange);

        function generateDayColumns(month) {
            var currentYear = new Date().getFullYear();
            const daysInMonth = new Date(currentYear, month, 0).getDate(); // Month is zero-indexed, so no need to subtract 1
            const columns = [];

            for (let day = 1; day <= daysInMonth; day++) {
                columns.push({
                    field: `day_${day}`,
                    title: `${day}`,
                    formatter: attendanceFormatter
                });
            }
            return columns;
            
        }

    
        function attendanceFormatter(value, row, index, field) {
            let day = field.replace('day_', '');
            let userId = row.user_id;

            let pSelected = value === 'P' ? 'selected' : '';
            let aSelected = value === 'A' ? 'selected' : '';
            let wfhSelected = value === 'WFH' ? 'selected' : '';

            let colorClass = 'text-secondary';
            if (value === 'P') colorClass = 'text-success';
            else if (value === 'A') colorClass = 'text-danger';
            else if (value === 'WFH') colorClass = 'text-info';

            return `
                <select class="form-control form-control-sm ${colorClass} update-attendance font-weight-bold" style="width: 70px; padding: 2px; height: auto;" data-userid="${userId}" data-day="${day}">
                    <option value="">-</option>
                    <option value="P" class="text-success" ${pSelected}>P</option>
                    <option value="A" class="text-danger" ${aSelected}>A</option>
                    <option value="WFH" class="text-info" ${wfhSelected}>WFH</option>
                </select>
            `;
        }    

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
            } else if (val === 'WFH') {
                status = 1;
                type = 'Work From Home';
            }

            let selectElem = $(this);
            selectElem.removeClass('text-success text-danger text-info text-secondary');
            if (val === 'P') selectElem.addClass('text-success');
            else if (val === 'A') selectElem.addClass('text-danger');
            else if (val === 'WFH') selectElem.addClass('text-info');
            else selectElem.addClass('text-secondary');

            if(val !== '') {
                $.ajax({
                    url: '{{ route("staff-attendance.month-wise-save") }}',
                    type: 'POST',
                    data: {
                        user_id: userId,
                        date: date,
                        status: status,
                        type: type,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (!response.error) {
                            showToastMessage(response.message, 'success');
                        } else {
                            showToastMessage(response.message, 'error');
                        }
                    },
                    error: function() {
                        showToastMessage('An error occurred', 'error');
                    }
                });
            }
        });
    
    </script>

@endsection
