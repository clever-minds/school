@extends('layouts.master')

@section('title')
    {{ __('Student Pickup Requests') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h3 class="page-title">
                {{ __('Student Pickup Requests') }}
            </h3>
            @canany(['student-pickup-verify', 'student-pickup-verify-otp', 'student-pickup-edit'])
                <button type="button" class="btn btn-theme btn-sm" data-toggle="modal" data-target="#verifyOtpModal" id="topVerifyModalBtn">
                    <i class="fa fa-shield"></i> {{ __('Verify Pickup OTP') }}
                </button>
            @endcanany
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div id="toolbar">
                            @canany(['student-pickup-verify', 'student-pickup-verify-otp', 'student-pickup-edit'])
                                <button type="button" class="btn btn-theme btn-sm" data-toggle="modal" data-target="#verifyOtpModal" id="toolbarVerifyModalBtn">
                                    <i class="fa fa-shield"></i> {{ __('Verify Pickup OTP') }}
                                </button>
                            @endcanany
                        </div>

                        <table aria-describedby="mydesc" class='table' id='table_list'
                               data-toggle="table" data-url="{{ route('student-pickup.list') }}"
                               data-click-to-select="true" data-side-pagination="server"
                               data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                               data-search="true" data-toolbar="#toolbar" data-show-columns="true"
                               data-show-refresh="true" data-trim-on-search="false" data-mobile-responsive="true"
                               data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                               data-show-export="true" data-export-data-type="all" data-escape="true">
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                <th scope="col" data-field="student_name">{{ __('Student Name') }}</th>
                                <th scope="col" data-field="class_name">{{ __('Class') }}</th>
                                <th scope="col" data-field="parent_name">{{ __('Parent Name') }}</th>
                                <th scope="col" data-field="pickup_person_name">{{ __('Pickup Person') }}</th>
                                <th scope="col" data-field="otp">{{ __('OTP') }}</th>
                                <th scope="col" data-field="status" data-formatter="pickupStatusFormatter">{{ __('Status') }}</th>
                                <th scope="col" data-field="verified_by">{{ __('Verified By') }}</th>
                                <th scope="col" data-field="verified_at" data-sortable="true">{{ __('Verified At') }}</th>
                                <th scope="col" data-field="created_at" data-sortable="true">{{ __('Created At') }}</th>
                                <th scope="col" data-field="operate" data-events="pickupActionEvents" data-escape="false">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- VERIFY OTP MODAL --}}
    <div class="modal fade" id="verifyOtpModal" tabindex="-1" role="dialog" aria-labelledby="verifyOtpModalLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verifyOtpModalLabel">
                        <i class="fa fa-shield text-theme mr-1"></i> {{ __('Verify Student Pickup OTP') }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="verifyOtpForm" action="{{ route('student-pickup.verify-otp') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="verify_pickup_id">

                        {{-- 1. Select Student Dropdown --}}
                        <div class="form-group mb-3" id="pending_select_group">
                            <label for="pending_student_select" class="font-weight-bold text-dark">
                                <i class="fa fa-user-circle mr-1 text-theme"></i> {{ __('Select Student / Pending Request') }}
                            </label>
                            <select id="pending_student_select" class="form-control">
                                <option value="">-- Choose Student to Verify --</option>
                            </select>
                            <small class="form-text text-muted">
                                {{ __('Select student from pending list OR type their 6-digit OTP below.') }}
                            </small>
                        </div>

                        <div class="text-center my-3 text-muted font-weight-bold" style="font-size: 12px; position: relative;" id="divider_or">
                            <span style="background: #fff; padding: 0 12px; position: relative; z-index: 1;">
                                {{ __('— OR ENTER 6-DIGIT OTP —') }}
                            </span>
                            <hr style="position: absolute; top: 1px; left: 0; right: 0; margin: 0; z-index: 0;">
                        </div>

                        {{-- 2. OTP Input --}}
                        <div class="form-group text-center my-3">
                            <label for="verify_otp_input" class="font-weight-bold d-block text-muted" style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px;">
                                {{ __('6-Digit Verification OTP') }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="otp" 
                                   id="verify_otp_input" 
                                   class="form-control text-center mx-auto" 
                                   placeholder="------" 
                                   maxlength="6" 
                                   required 
                                   autocomplete="off"
                                   style="max-width: 220px; font-size: 28px; letter-spacing: 8px; font-weight: 700; height: 56px; border: 2px solid #5756a2; border-radius: 8px;">
                            <div id="otp_lookup_status" class="mt-2" style="font-size: 13px;"></div>
                        </div>

                        {{-- 3. Live Student Info Card --}}
                        <div id="verify_details_box" class="card border-success d-none mb-2" style="background: #f4fbf7; border-width: 2px; border-radius: 8px;">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <div>
                                        <span class="badge badge-success px-2 py-1"><i class="fa fa-check-circle"></i> {{ __('Ready for Pickup') }}</span>
                                    </div>
                                    <small class="text-muted" id="detail_created_at"></small>
                                </div>
                                <div class="row">
                                    <div class="col-12 mb-2">
                                        <div class="d-flex align-items-center">
                                            <i class="fa fa-graduation-cap text-success mr-2" style="font-size: 18px;"></i>
                                            <div>
                                                <div class="font-weight-bold text-dark" style="font-size: 16px;" id="detail_student_name">-</div>
                                                <span class="badge badge-info" id="detail_class_name">-</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <small class="text-muted d-block">{{ __('Authorized Pickup Person') }}</small>
                                        <strong class="text-dark" id="detail_pickup_person">-</strong>
                                    </div>
                                    <div class="col-sm-6 mb-2">
                                        <small class="text-muted d-block">{{ __('Parent / Guardian') }}</small>
                                        <strong class="text-dark" id="detail_parent_name">-</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-theme" id="verifySubmitBtn">
                            <i class="fa fa-check-circle mr-1"></i> {{ __('Verify & Allow Pickup') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        let pendingRequestsCache = [];

        function pickupStatusFormatter(value, row) {
            if (value == 0) {
                return '<span class="badge badge-warning">Pending</span>';
            } else if (value == 1) {
                return '<span class="badge badge-success">Verified</span>';
            } else if (value == 2) {
                return '<span class="badge badge-danger">Expired</span>';
            }
            return '-';
        }

        // Fetch and populate pending students dropdown
        function loadPendingStudents(selectedId = null) {
            let select = $('#pending_student_select');
            select.html('<option value="">{{ __("Loading pending students...") }}</option>');

            $.ajax({
                url: "{{ route('student-pickup.pending-list') }}",
                type: 'GET',
                dataType: 'json',
                success: function (res) {
                    select.empty();
                    if (!res.error && res.data && res.data.length > 0) {
                        pendingRequestsCache = res.data;
                        select.append('<option value="">-- {{ __("Choose Student to Verify") }} (' + res.data.length + ' pending) --</option>');
                        $.each(res.data, function (i, item) {
                            let option = $('<option></option>')
                                .attr('value', item.id)
                                .attr('data-otp', item.otp)
                                .attr('data-student', item.student_name)
                                .attr('data-class', item.class_name)
                                .attr('data-person', item.pickup_person_name)
                                .attr('data-parent', item.parent_name)
                                .attr('data-created', item.created_at)
                                .text(item.student_name + ' — ' + item.class_name + ' (Pickup: ' + item.pickup_person_name + ')');

                            if (selectedId && item.id == selectedId) {
                                option.prop('selected', true);
                            }
                            select.append(option);
                        });

                        if (selectedId) {
                            select.trigger('change');
                        }
                    } else {
                        pendingRequestsCache = [];
                        select.append('<option value="">{{ __("No pending pickup requests found") }}</option>');
                    }
                },
                error: function () {
                    select.html('<option value="">-- {{ __("Choose Student to Verify") }} --</option>');
                }
            });
        }

        function displayStudentDetails(data) {
            $('#verify_pickup_id').val(data.id || '');
            $('#detail_student_name').text(data.student_name || '-');
            $('#detail_class_name').text(data.class_name && data.class_name !== '-' ? data.class_name : 'No Class');
            $('#detail_pickup_person').text(data.pickup_person_name || '-');
            $('#detail_parent_name').text(data.parent_name || '-');
            $('#detail_created_at').text(data.created_at ? 'Requested: ' + data.created_at : '');
            $('#verify_details_box').removeClass('d-none');
            $('#otp_lookup_status').html('<span class="text-success"><i class="fa fa-check-circle"></i> {{ __("Student details matched") }}</span>');
        }

        function hideStudentDetails() {
            $('#verify_details_box').addClass('d-none');
            $('#otp_lookup_status').empty();
        }

        // When student is selected from dropdown
        $('#pending_student_select').on('change', function () {
            let selected = $(this).find(':selected');
            let id = selected.val();

            if (id) {
                let otp = selected.attr('data-otp') || '';
                $('#verify_otp_input').val(otp);
                displayStudentDetails({
                    id: id,
                    student_name: selected.attr('data-student'),
                    class_name: selected.attr('data-class'),
                    pickup_person_name: selected.attr('data-person'),
                    parent_name: selected.attr('data-parent'),
                    created_at: selected.attr('data-created')
                });
            } else {
                hideStudentDetails();
                $('#verify_pickup_id').val('');
                $('#verify_otp_input').val('');
            }
        });

        // Real-time lookup when typing OTP
        let otpLookupTimer = null;
        $('#verify_otp_input').on('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            let otp = this.value.trim();

            if (otp.length === 6) {
                // First check local cache
                let matched = pendingRequestsCache.find(r => r.otp === otp);
                if (matched) {
                    $('#pending_student_select').val(matched.id);
                    displayStudentDetails(matched);
                    return;
                }

                // If not in cache, do quick server check
                $('#otp_lookup_status').html('<span class="text-muted"><i class="fa fa-spinner fa-spin"></i> {{ __("Looking up student...") }}</span>');
                clearTimeout(otpLookupTimer);
                otpLookupTimer = setTimeout(function () {
                    $.ajax({
                        url: "{{ route('student-pickup.check-otp') }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            otp: otp
                        },
                        dataType: 'json',
                        success: function (res) {
                            if (!res.error && res.data) {
                                $('#pending_student_select').val(res.data.id);
                                displayStudentDetails(res.data);
                            } else {
                                hideStudentDetails();
                                $('#otp_lookup_status').html('<span class="text-danger"><i class="fa fa-times-circle"></i> ' + (res.message || '{{ __("No pending pickup found for this OTP") }}') + '</span>');
                            }
                        },
                        error: function () {
                            $('#otp_lookup_status').html('<span class="text-danger"><i class="fa fa-times-circle"></i> {{ __("Could not check OTP") }}</span>');
                        }
                    });
                }, 300);
            } else {
                if ($('#pending_student_select').val() === '') {
                    hideStudentDetails();
                }
            }
        });

        // Row action event (clicking green verify button in table)
        window.pickupActionEvents = {
            'click .verify-pickup-btn': function (e, value, row, index) {
                e.preventDefault();
                $('#verify_pickup_id').val(row.id);
                $('#verify_otp_input').val(row.otp);
                displayStudentDetails({
                    id: row.id,
                    student_name: row.student_name,
                    class_name: row.class_name,
                    pickup_person_name: row.pickup_person_name,
                    parent_name: row.parent_name,
                    created_at: row.created_at
                });
                loadPendingStudents(row.id);
                $('#verifyOtpModal').modal('show');
            }
        };

        // When opening modal from general button (top or toolbar)
        $('#topVerifyModalBtn, #toolbarVerifyModalBtn').on('click', function () {
            $('#verify_pickup_id').val('');
            $('#verify_otp_input').val('');
            hideStudentDetails();
            loadPendingStudents();
        });

        // Reset modal on close
        $('#verifyOtpModal').on('hidden.bs.modal', function () {
            $('#verify_pickup_id').val('');
            $('#verify_otp_input').val('');
            hideStudentDetails();
            $('#pending_student_select').val('');
            $('#verifySubmitBtn').prop('disabled', false).html('<i class="fa fa-check-circle mr-1"></i> {{ __("Verify & Allow Pickup") }}');
        });

        // Submit OTP verification
        $('#verifyOtpForm').on('submit', function (e) {
            e.preventDefault();
            let form = $(this);
            let submitBtn = $('#verifySubmitBtn');
            let otp = $('#verify_otp_input').val().trim();

            if (otp.length !== 6) {
                if (typeof showErrorToast !== 'undefined') {
                    showErrorToast('{{ __("Please enter a valid 6-digit OTP") }}');
                } else {
                    alert('{{ __("Please enter a valid 6-digit OTP") }}');
                }
                return false;
            }

            submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> {{ __("Verifying...") }}');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function (response) {
                    submitBtn.prop('disabled', false).html('<i class="fa fa-check-circle mr-1"></i> {{ __("Verify & Allow Pickup") }}');
                    if (response.error) {
                        if (typeof showErrorToast !== 'undefined') {
                            showErrorToast(response.message || '{{ __("Invalid or expired OTP") }}');
                        } else {
                            alert(response.message || '{{ __("Invalid or expired OTP") }}');
                        }
                    } else {
                        $('#verifyOtpModal').modal('hide');
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: '{{ __("Pickup Verified!") }}',
                                text: response.message,
                                icon: 'success',
                                confirmButtonColor: '#28a745'
                            });
                        } else if (typeof showSuccessToast !== 'undefined') {
                            showSuccessToast(response.message);
                        } else {
                            alert(response.message);
                        }
                        $('#table_list').bootstrapTable('refresh');
                    }
                },
                error: function (xhr) {
                    submitBtn.prop('disabled', false).html('<i class="fa fa-check-circle mr-1"></i> {{ __("Verify & Allow Pickup") }}');
                    let msg = '{{ __("Verification failed. Please try again.") }}';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    if (typeof showErrorToast !== 'undefined') {
                        showErrorToast(msg);
                    } else {
                        alert(msg);
                    }
                }
            });
        });
    </script>
@endsection
