@extends('home')

@section('content-admin')
    <!-- partial -->
    <div class="content-wrapper">
        <div class="row">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            <div class="col-md-12 grid-margin">
                <div class="row">
                    <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                        <h3 class="font-weight-bold">Account Admins</h3>
                    </div>
                    <div class="col-12 col-xl-4 mb-4 mb-xl-0">
                        <a href="{{ route('create-admin') }}" class="btn btn-success btn-sm float-right">Tạo Admin</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <p class="card-title">Danh sách Admin</p>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tên</th>
                                        <th>Email</th>
                                        <th>Quyền</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($admins as $admin)
                                        <tr>
                                            <td>{{ $admin->id }}</td>
                                            <td>{{ $admin->name }}</td>
                                            <td>{{ $admin->email }}</td>
                                            <td>{{ $admin->role }}</td>
                                            <td>
                                                <!-- Chuyển thành User -->
                                                {{-- <form action="" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-warning btn-sm">Chuyển thành User</button>
                                                </form> --}}

                                                <!-- Chỉnh sửa Admin -->
                                                <a href="{{ route('admin-edit', $admin->id) }}"
                                                    class="btn btn-info btn-sm">Chỉnh sửa</a>

                                                <!-- Xóa Admin -->
                                                <form action="{{ route('destroy-admin', $admin->id) }}" method="POST"
                                                    style="display:inline-block;"
                                                    onsubmit="return confirm('Xác nhận xóa {{ $admin->name }}?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                                </form>
                                            </td>

                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Phân trang -->
                            <div class="d-flex justify-content-center">
                                {{ $admins->links() }} <!-- Hiển thị phân trang -->
                            </div>
                        </div>
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
