@extends('layouts.home_page.master')

@section('content')
<style>
    .career-section {
        padding: 60px 0;
        background-color: #f8f9fa;
        min-height: 80vh;
    }
    .career-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        padding: 40px;
    }
    .career-title {
        color: var(--secondary-color, #215679);
        font-weight: 700;
        margin-bottom: 30px;
        text-align: center;
    }
</style>

<div class="main">
    <section class="career-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="career-card">
                        <h2 class="career-title">{{ __('Join Our Team') }}</h2>
                        
                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ url('/careers') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            
                            <div class="mb-4">
                                <label for="school_id" class="form-label">{{ __('Select School') }} <span class="text-danger">*</span></label>
                                <select name="school_id" id="school_id" class="form-select form-control" required>
                                    <option value="">{{ __('Choose a school...') }}</option>
                                    @foreach($schools as $school)
                                        <option value="{{ $school->id }}" {{ old('school_id') == $school->id ? 'selected' : '' }}>
                                            {{ $school->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="name" class="form-label">{{ __('Full Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required placeholder="{{ __('Enter your full name') }}">
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label">{{ __('Email Address') }} <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required placeholder="{{ __('Enter your email') }}">
                            </div>

                            <div class="mb-4">
                                <label for="phone" class="form-label">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                                <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}" required placeholder="{{ __('Enter your phone number') }}">
                            </div>

                            <div class="mb-4">
                                <label for="resume" class="form-label">{{ __('Upload Resume (PDF, DOC, DOCX)') }} <span class="text-danger">*</span></label>
                                <input type="file" name="resume" id="resume" class="form-control" accept=".pdf,.doc,.docx" required>
                                <small class="text-muted">{{ __('Max file size: 5MB') }}</small>
                            </div>

                            <div class="text-center mt-5">
                                <button type="submit" class="commonBtn btn btn-primary px-5 py-2">{{ __('Submit Application') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
