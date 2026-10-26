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
                        
                        {{-- Context details when verifying a specific table row --}}
                        <div id="verify_details_box" class="alert alert-info d-none mb-3" style="border-radius: 8px;">
                            <div class="d-flex align-items-center mb-1">
                                <strong class="mr-2">{{ __('Student') }}:</strong>
                                <span id="detail_student_name" class="font-weight-bold"></span>
                                <span id="detail_class_name" class="badge badge-primary ml-2"></span>
                            </div>
                            <div class="mb-1">
                                <strong>{{ __('Pickup Person') }}:</strong>
                                <span id="detail_pickup_person" class="text-dark"></span>
                            </div>
                            <div>
                                <strong>{{ __('Parent') }}:</strong>
                                <span id="detail_parent_name" class="text-dark"></span>
                            </div>
                        </div>

                        <div class="form-group text-center my-3">
                            <label for="verify_otp_input" class="font-weight-bold d-block text-muted" style="font-size: 14px; text-transform: uppercase; letter-spacing: 1px;">
                                {{ __('Enter 6-Digit Verification OTP') }} <span class="text-danger">*</span>
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
                            <small class="form-text text-muted mt-2">
                                {{ __('Ask the parent or authorized pickup person for the 6-digit OTP.') }}
                            </small>
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

        // Action events for rows
        window.pickupActionEvents = {
            'click .verify-pickup-btn': function (e, value, row, index) {
                e.preventDefault();
                $('#verify_pickup_id').val(row.id);
                $('#verify_otp_input').val(row.otp);
                $('#detail_student_name').text(row.student_name);
                $('#detail_class_name').text(row.class_name && row.class_name !== '-' ? row.class_name : '');
                $('#detail_pickup_person').text(row.pickup_person_name);
                $('#detail_parent_name').text(row.parent_name);
                $('#verify_details_box').removeClass('d-none');
                $('#verifyOtpModal').modal('show');
            }
        };

        // When opening modal from general button (not row)
        $('#topVerifyModalBtn, #toolbarVerifyModalBtn').on('click', function () {
            $('#verify_pickup_id').val('');
            $('#verify_otp_input').val('');
            $('#verify_details_box').addClass('d-none');
        });

        // Focus OTP field on modal open
        $('#verifyOtpModal').on('shown.bs.modal', function () {
            $('#verify_otp_input').focus().select();
        });

        // Reset modal on close
        $('#verifyOtpModal').on('hidden.bs.modal', function () {
            $('#verify_pickup_id').val('');
            $('#verify_otp_input').val('');
            $('#verify_details_box').addClass('d-none');
            $('#verifySubmitBtn').prop('disabled', false).html('<i class="fa fa-check-circle mr-1"></i> {{ __("Verify & Allow Pickup") }}');
        });

        // Numeric-only input for OTP
        $('#verify_otp_input').on('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
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
