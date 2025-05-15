@extends('home')

@section('content-admin')
    <div class="content-wrapper">
        <div class="row justify-content-center">
            <div class="col-md-6 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Chỉnh sửa Admin</h4>

                        <form method="POST" action="{{ route('admin-update', $admin->id) }}">
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label for="name">Tên</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="{{ $admin->name }}" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Địa chỉ email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="{{ $admin->email }}" required>
                            </div>

                            <div class="form-group">
                                <label for="password">Mật khẩu mới (nếu muốn đổi)</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>

                            <div class="form-group">
                                <label for="password_confirmation">Xác nhận mật khẩu</label>
                                <input type="password" class="form-control" id="password_confirmation"
                                    name="password_confirmation">
                            </div>

                            <button type="submit" class="btn btn-primary">Cập nhật</button>
                            <a href="{{ route('admins.all-admins') }}" class="btn btn-secondary">Hủy</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
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
