@extends('layouts.master')

@section('title')
    {{ __('Staff Performance Evaluation') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Staff Performance Evaluation') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('Evaluate Staff Performance & Incentives') }}
                        </h4>
                        <p class="card-description text-muted">
                            Based on 10 performance criteria. Each negative feedback deducts 1 point (1/10th) from the staff's base performance incentive.
                        </p>

                        <div class="row" id="toolbar">
                            <div class="form-group col-sm-12 col-md-3">
                                <label class="font-weight-bold">{{ __('session_year') }} <span class="text-danger">*</span></label>
                                <select name="session_year_id" id="filter_session_year_id" class="form-control">
                                    @foreach($sessionYears as $sy)
                                        <option value="{{ $sy->id }}"
                                            data-start-date="{{ $sy->start_date }}"
                                            data-end-date="{{ $sy->end_date }}"
                                            data-start-year="{{ date('Y', strtotime($sy->start_date)) }}"
                                            data-end-year="{{ date('Y', strtotime($sy->end_date)) }}"
                                            data-start-month="{{ (int)date('n', strtotime($sy->start_date)) }}"
                                            {{ (isset($defaultSessionYear) && $defaultSessionYear->id == $sy->id) || $sy->default == 1 ? 'selected' : '' }}>
                                            {{ $sy->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-12 col-md-3">
                                <label class="font-weight-bold">{{ __('month') }} <span class="text-danger">*</span></label>
                                <select name="month" id="filter_month" class="form-control">
                                    @foreach($months as $m => $mName)
                                        <option value="{{ $m }}" {{ $m == $currentMonth ? 'selected' : '' }}>
                                            {{ $mName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-12 col-md-3">
                                <label class="font-weight-bold">{{ __('year') }} <span class="text-danger">*</span></label>
                                <select name="year" id="filter_year" class="form-control">
                                    <option value="{{ $currentYear }}">{{ $currentYear }}</option>
                                </select>
                            </div>
                            <div class="form-group col-sm-12 col-md-3">
                                <label class="font-weight-bold">{{ __('role') }}</label>
                                <select name="role_id" id="filter_role" class="form-control">
                                    <option value="">{{ __('all') }}</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table aria-describedby="mydesc" class="table" id="performance_table"
                                   data-toggle="table" data-url="{{ route('staff-performance.list') }}"
                                   data-click-to-select="true" data-side-pagination="server"
                                   data-pagination="true" data-page-list="[5, 10, 20, 50, 100]"
                                   data-search="true" data-toolbar="#toolbar"
                                   data-show-columns="true" data-show-refresh="true"
                                   data-trim-on-search="false" data-mobile-responsive="true"
                                   data-sort-name="name" data-sort-order="asc"
                                   data-maintain-selected="true" data-show-export="true"
                                   data-export-data-type="all"
                                   data-query-params="performanceQueryParams" data-escape="false">
                                <thead>
                                <tr>
                                    <th scope="col" data-field="no">{{ __('no.') }}</th>
                                    <th scope="col" data-field="name">{{ __('staff_name') }}</th>
                                    <th scope="col" data-field="role">{{ __('role') }}</th>
                                    <th scope="col" data-field="base_incentive">{{ __('Base Incentive (₹)') }}</th>
                                    <th scope="col" data-field="score">{{ __('Points Score') }}</th>
                                    <th scope="col" data-field="earned_incentive">{{ __('Calculated Incentive (₹)') }}</th>
                                    <th scope="col" data-field="status">{{ __('status') }}</th>
                                    <th scope="col" data-field="operate">{{ __('action') }}</th>
                                </tr>
                                </thead>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Evaluation Modal -->
    <div class="modal fade" id="evaluateModal" tabindex="-1" role="dialog" aria-labelledby="evaluateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="evaluateModalLabel">
                        <i class="fa fa-star text-warning mr-1"></i> Staff Performance Evaluation
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="evaluationForm" method="POST" action="{{ route('staff-performance.store') }}">
                    @csrf
                    <input type="hidden" name="staff_id" id="modal_staff_id">
                    <input type="hidden" name="session_year_id" id="modal_session_year_id">
                    <input type="hidden" name="month" id="modal_month">
                    <input type="hidden" name="year" id="modal_year">
                    <input type="hidden" id="modal_base_incentive">

                    <div class="modal-body">
                        <!-- Summary Header Card -->
                        <div class="card bg-light mb-3 border">
                            <div class="card-body p-3">
                                <div class="row align-items-center">
                                    <div class="col-md-5">
                                        <h5 class="mb-1 text-primary font-weight-bold" id="modal_staff_name">-</h5>
                                        <div class="text-muted small">Month: <strong id="modal_period_text">-</strong></div>
                                    </div>
                                    <div class="col-md-7 text-md-right mt-2 mt-md-0">
                                        <span class="badge badge-secondary p-2 mr-1">Base Incentive: ₹<span id="badge_base_incentive">0</span></span>
                                        <span class="badge badge-success p-2 mr-1">Score: <span id="badge_score">10</span>/10</span>
                                        <span class="badge badge-info p-2">Net Incentive: ₹<span id="badge_net_incentive">0</span></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 10 Criteria List -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 50px;" class="text-center align-middle">
                                            <input type="checkbox" id="checkAllCriteria" checked title="Select / Deselect All" style="width: 18px; height: 18px; cursor: pointer;">
                                        </th>
                                        <th class="align-middle">{{ __('Evaluation Criteria') }}</th>
                                        <th style="width: 120px;" class="text-center align-middle">{{ __('Points') }}</th>
                                        <th class="align-middle">{{ __('Remarks / Note (Optional)') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="criteriaTableBody">
                                    @php $idx = 1; @endphp
                                    @foreach($criteriaList as $key => $title)
                                        <tr data-key="{{ $key }}">
                                            <td class="text-center align-middle">
                                                <input type="hidden" name="criteria[{{ $key }}][status]" value="0">
                                                <input type="checkbox" name="criteria[{{ $key }}][status]" value="1" checked class="criteria-checkbox" id="crit_{{ $key }}" style="width: 18px; height: 18px; cursor: pointer;">
                                            </td>
                                            <td class="align-middle">
                                                <label for="crit_{{ $key }}" class="mb-0 font-weight-500" style="cursor: pointer;">
                                                    <span class="text-muted mr-1 font-weight-bold">{{ $idx++ }}.</span> {{ $title }}
                                                </label>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge badge-success criteria-points-badge font-weight-bold" style="font-size: 11px; padding: 5px 10px;">
                                                    <i class="fa fa-check mr-1"></i> 1 Point
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                <input type="text" name="criteria[{{ $key }}][remark]" class="form-control form-control-sm criteria-remark" placeholder="Remark if unchecked or note">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="form-group mt-3">
                            <label class="font-weight-bold">{{ __('General Remarks') }}</label>
                            <textarea name="remarks" id="modal_remarks" class="form-control" rows="2" placeholder="Overall review or reason for deductions..."></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
                        <button type="submit" class="btn btn-theme" id="saveEvaluationBtn">
                            <i class="fa fa-save mr-1"></i> {{ __('Save Evaluation') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    function syncYearDropdown(preferSelected) {
        let selectedSy = $('#filter_session_year_id option:selected');
        let startYear = parseInt(selectedSy.data('start-year')) || new Date().getFullYear();
        let endYear = parseInt(selectedSy.data('end-year')) || startYear;
        let startMonth = parseInt(selectedSy.data('start-month')) || 1;
        let currentMonth = parseInt($('#filter_month').val()) || (new Date().getMonth() + 1);

        let expectedYear = (currentMonth >= startMonth) ? startYear : endYear;

        let yearSelect = $('#filter_year');
        let currentVal = parseInt(yearSelect.val());
        yearSelect.empty();

        let minYear = Math.min(startYear, endYear);
        let maxYear = Math.max(startYear, endYear);

        for (let y = minYear; y <= maxYear; y++) {
            yearSelect.append(new Option(y, y));
        }

        let targetYear = expectedYear;
        if (preferSelected && currentVal && yearSelect.find('option[value="' + currentVal + '"]').length) {
            targetYear = currentVal;
        }

        yearSelect.val(targetYear);
    }

    function performanceQueryParams(p) {
        return {
            limit: p.limit,
            sort: p.sort,
            order: p.order,
            offset: p.offset,
            search: p.search,
            session_year_id: $('#filter_session_year_id').val(),
            month: $('#filter_month').val(),
            year: $('#filter_year').val(),
            role_id: $('#filter_role').val(),
        };
    }

    $(document).ready(function() {
        syncYearDropdown(false);

        $('#filter_session_year_id').on('change', function() {
            syncYearDropdown(false);
            $('#performance_table').bootstrapTable('refresh');
        });

        $('#filter_month').on('change', function() {
            syncYearDropdown(false);
            $('#performance_table').bootstrapTable('refresh');
        });

        $('#filter_year, #filter_role').on('change', function() {
            $('#performance_table').bootstrapTable('refresh');
        });

        // Recalculate score & net incentive in modal
        function recalculateModalScore() {
            let base = parseFloat($('#modal_base_incentive').val()) || 0;
            let positiveCount = 0;

            $('#criteriaTableBody tr').each(function() {
                let isChecked = $(this).find('input.criteria-checkbox').is(':checked');
                let badge = $(this).find('.criteria-points-badge');
                if (isChecked) {
                    positiveCount++;
                    badge.removeClass('badge-danger').addClass('badge-success').html('<i class="fa fa-check mr-1"></i> 1 Point');
                } else {
                    badge.removeClass('badge-success').addClass('badge-danger').html('<i class="fa fa-times mr-1"></i> 0 Point');
                }
            });

            let earned = (positiveCount / 10) * base;
            $('#badge_score').text(positiveCount);
            $('#badge_net_incentive').text(earned.toFixed(2));

            let allChecked = ($('#criteriaTableBody input.criteria-checkbox:checked').length === $('#criteriaTableBody input.criteria-checkbox').length);
            $('#checkAllCriteria').prop('checked', allChecked);
        }

        // Check/Uncheck All
        $(document).on('change', '#checkAllCriteria', function() {
            let isChecked = $(this).is(':checked');
            $('#criteriaTableBody input.criteria-checkbox').prop('checked', isChecked);
            recalculateModalScore();
        });

        // Individual checkbox change
        $(document).on('change', '.criteria-checkbox', function() {
            recalculateModalScore();
        });

        // Open evaluation modal
        $(document).on('click', '.evaluate-staff-btn', function() {
            let staffId = $(this).data('staff-id');
            let staffName = $(this).data('staff-name');
            let sessionYearId = $(this).data('session-year-id') || $('#filter_session_year_id').val();
            let month = $(this).data('month');
            let year = $(this).data('year');
            let baseIncentive = parseFloat($(this).data('base-incentive')) || 0;

            $('#modal_staff_id').val(staffId);
            $('#modal_session_year_id').val(sessionYearId);
            $('#modal_month').val(month);
            $('#modal_year').val(year);
            $('#modal_base_incentive').val(baseIncentive);

            $('#modal_staff_name').text(staffName);
            $('#badge_base_incentive').text(baseIncentive.toFixed(2));

            let monthNames = ["", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            $('#modal_period_text').text((monthNames[month] || month) + ' ' + year);

            // Fetch current evaluation data
            $.ajax({
                url: '{{ route("staff-performance.get") }}',
                type: 'GET',
                data: { staff_id: staffId, month: month, year: year },
                success: function(res) {
                    if (!res.error && res.data) {
                        $('#modal_remarks').val(res.data.remarks || '');
                        let criteria = res.data.criteria || {};

                        $.each(criteria, function(key, val) {
                            let tr = $('#criteriaTableBody tr[data-key="' + key + '"]');
                            if (tr.length) {
                                tr.find('.criteria-remark').val(val.remark || '');
                                if (val.status === 0 || val.status === '0') {
                                    tr.find('input.criteria-checkbox').prop('checked', false);
                                } else {
                                    tr.find('input.criteria-checkbox').prop('checked', true);
                                }
                            }
                        });
                    } else {
                        // Default: All checked (10/10)
                        $('#criteriaTableBody input.criteria-checkbox').prop('checked', true);
                        $('#modal_remarks').val('');
                        $('#criteriaTableBody .criteria-remark').val('');
                    }
                    recalculateModalScore();
                    $('#evaluateModal').modal('show');
                },
                error: function() {
                    $('#criteriaTableBody input.criteria-checkbox').prop('checked', true);
                    recalculateModalScore();
                    $('#evaluateModal').modal('show');
                }
            });
        });

        // Submit form
        $('#evaluationForm').on('submit', function(e) {
            e.preventDefault();
            let form = $(this);
            let btn = $('#saveEvaluationBtn');
            let origHtml = btn.html();

            btn.html('<i class="fa fa-spinner fa-spin mr-1"></i> Saving...').prop('disabled', true);

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    btn.html(origHtml).prop('disabled', false);
                    if (!response.error) {
                        $('#evaluateModal').modal('hide');
                        if (typeof showToastMessage === 'function') {
                            showToastMessage(response.message || 'Saved successfully', 'success');
                        }
                        $('#performance_table').bootstrapTable('refresh');
                    } else {
                        if (typeof showToastMessage === 'function') {
                            showToastMessage(response.message || 'Error saving', 'error');
                        }
                    }
                },
                error: function(xhr) {
                    btn.html(origHtml).prop('disabled', false);
                    if (typeof showToastMessage === 'function') {
                        showToastMessage('An error occurred while saving evaluation', 'error');
                    }
                }
            });
        });
    });
</script>
@endsection
