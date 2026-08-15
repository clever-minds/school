@extends('layouts.master')

@section('title')
    {{ __('Teacher Interviews') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Manage Teacher Interviews') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('Applications List') }}
                        </h4>
                        
                        <div class="table-responsive">
                            <table class="table" id="table_list">
                                <thead>
                                    <tr>
                                        <th>{{ __('No.') }}</th>
                                        <th>{{ __('School') }}</th>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Email') }}</th>
                                        <th>{{ __('Phone') }}</th>
                                        <th>{{ __('Applied On') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($applications as $key => $application)
                                        <tr>
                                            <td>{{ $applications->firstItem() + $key }}</td>
                                            <td>{{ $application->school->name ?? '-' }}</td>
                                            <td>{{ $application->name }}</td>
                                            <td>{{ $application->email }}</td>
                                            <td>{{ $application->phone }}</td>
                                            <td>{{ $application->created_at->format('d M, Y') }}</td>
                                            <td>
                                                @if($application->status == 'Pending')
                                                    <span class="badge badge-warning">{{ $application->status }}</span>
                                                @elseif($application->status == 'Rejected')
                                                    <span class="badge badge-danger">{{ $application->status }}</span>
                                                @elseif($application->status == 'Hired')
                                                    <span class="badge badge-success">{{ $application->status }}</span>
                                                @else
                                                    <span class="badge badge-info">{{ $application->status }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('teacher-interviews.show', $application->id) }}" class="btn btn-sm btn-info btn-rounded btn-icon" title="{{ __('View Details') }}">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                @if(Auth::user()->can('teacher-interview-edit') || Auth::user()->hasRole('Super Admin') || Auth::user()->hasRole('School Admin'))
                                                    <button type="button" class="btn btn-sm btn-warning btn-rounded btn-icon assign-btn" data-id="{{ $application->id }}" data-school="{{ $application->school_id }}" title="{{ __('Assign Interviewer') }}">
                                                        <i class="fa fa-user-plus"></i>
                                                    </button>
                                                @endif
                                                @if($application->resume_path)
                                                    <a href="{{ asset('storage/' . $application->resume_path) }}" target="_blank" class="btn btn-sm btn-primary btn-rounded btn-icon" title="{{ __('Download Resume') }}">
                                                        <i class="fa fa-download"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $applications->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Interviewer Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Assign Interviewer') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="assignForm" method="POST" action="">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>{{ __('Select Interviewer') }} <span class="text-danger">*</span></label>
                            <select name="interviewer_id" id="interviewer_id" class="form-control" required>
                                <option value="">{{ __('Select Interviewer') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary theme-btn">{{ __('Assign') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        $('.assign-btn').click(function() {
            var id = $(this).data('id');
            var school_id = $(this).data('school');
            
            var formAction = "{{ url('teacher-interviews') }}/" + id + "/assign";
            $('#assignForm').attr('action', formAction);
            
            // Fetch staff
            var fetchUrl = "{{ url('get-staff-by-school') }}/" + (school_id ? school_id : 0);
            $.get(fetchUrl, function(data) {
                var options = '<option value="">{{ __("Select Interviewer") }}</option>';
                data.forEach(function(staff) {
                    options += '<option value="'+staff.id+'">'+staff.first_name+' '+staff.last_name+'</option>';
                });
                $('#interviewer_id').html(options);
                $('#assignModal').modal('show');
            }).fail(function() {
                alert('{{ __("Failed to fetch staff members.") }}');
            });
        });
    });
</script>
@endsection
