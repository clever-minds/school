@extends('layouts.master')

@section('title')
    {{ __('Teacher Interview Categories') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('Manage Teacher Interview Categories') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('Create New Category') }}
                        </h4>
                        
                        <form action="{{ route('teacher-interview-categories.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('Category Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="{{ __('Category Name') }}" required>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('Description') }}</label>
                                    <input type="text" name="description" class="form-control" placeholder="{{ __('Description') }}">
                                </div>
                                <div class="form-group col-sm-12 col-md-2">
                                    <label>{{ __('Status') }}</label>
                                    <select name="status" class="form-control" required>
                                        <option value="1">{{ __('Active') }}</option>
                                        <option value="0">{{ __('Inactive') }}</option>
                                    </select>
                                </div>
                                <div class="col-sm-12 col-md-2 d-flex align-items-center mt-3">
                                    <button type="submit" class="btn btn-primary theme-btn btn-block">{{ __('Submit') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('Categories List') }}
                        </h4>
                        
                        <form action="{{ route('teacher-interview-categories.index') }}" method="GET" class="mb-3">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control" placeholder="{{ __('Search...') }}" value="{{ request()->search }}">
                                        <div class="input-group-append">
                                            <button class="btn btn-sm btn-primary" type="submit">{{ __('Search') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table" id="table_list">
                                <thead>
                                    <tr>
                                        <th>{{ __('No.') }}</th>
                                        <th>{{ __('Category Name') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categories as $key => $category)
                                        <tr>
                                            <td>{{ ($categories->firstItem() ?? 1) + $key }}</td>
                                            <td>{{ $category->name }}</td>
                                            <td>{{ $category->description ?? '-' }}</td>
                                            <td>
                                                @if($category->status == 1)
                                                    <span class="badge badge-success">{{ __('Active') }}</span>
                                                @else
                                                    <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info btn-rounded btn-icon" data-toggle="modal" data-target="#editModal{{ $category->id }}" title="{{ __('Edit') }}">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                
                                                <form action="{{ route('teacher-interview-categories.destroy', $category->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('{{ __('Are you sure you want to delete this category?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger btn-rounded btn-icon" title="{{ __('Delete') }}">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>

                                                <!-- Edit Modal -->
                                                <div class="modal fade" id="editModal{{ $category->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">{{ __('Edit Category') }}</h5>
                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <form action="{{ route('teacher-interview-categories.update', $category->id) }}" method="POST">
                                                                @csrf
                                                                @method('PUT')
                                                                <div class="modal-body">
                                                                    <div class="form-group">
                                                                        <label>{{ __('Category Name') }} <span class="text-danger">*</span></label>
                                                                        <input type="text" name="name" class="form-control" value="{{ $category->name }}" required>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>{{ __('Description') }}</label>
                                                                        <input type="text" name="description" class="form-control" value="{{ $category->description }}">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>{{ __('Status') }}</label>
                                                                        <select name="status" class="form-control" required>
                                                                            <option value="1" {{ $category->status == 1 ? 'selected' : '' }}>{{ __('Active') }}</option>
                                                                            <option value="0" {{ $category->status == 0 ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                                                                    <button type="submit" class="btn btn-primary theme-btn">{{ __('Update') }}</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $categories->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
