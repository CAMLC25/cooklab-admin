@extends('home')

@section('content-admin')
    <div class="content-wrapper">
        <h3 class="font-weight-bold">Tạo User</h3>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('users-store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>Tên</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            {{-- <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div> --}}
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email') }}">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label>Ảnh đại diện (Avatar)</label>
                <input type="file" name="avatar" class="form-control-file @error('avatar') is-invalid @enderror">
                @error('avatar')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Xác nhận mật khẩu</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Tạo</button>
            <a href="{{ route('admins.all-users') }}" class="btn btn-secondary">Quay lại</a>
        </form>
    </div>
     <!-- content-wrapper ends -->
     <footer class="footer">
        <div class="d-sm-flex justify-content-center justify-content-sm-between">
            <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Copyright © 2025 <a href="#"
                    target="_blank"></a>. All rights reserved.</span>
            <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">CAM <i
                    class="ti-heart text-danger ml-1"></i></span>
        </div>
    </footer>
@endsection
