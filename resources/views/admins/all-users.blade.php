@extends('home')

@section('content-admin')
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-12 grid-margin" id="users_list">
                <div class="row">
                    <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                        <h3 class="font-weight-bold">Account Users</h3>
                    </div>
                    <div class="col-12 col-xl-4 mb-4 mb-xl-0">
                        <a href="{{ route('users-create') }}" class="btn btn-success btn-sm float-right">Tạo User</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card" >
                <div class="card">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    <div class="card-body">
                        <p class="card-title"> Danh sách Users</p>
                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Ảnh</th>
                                                <th>ID CookLab</th>
                                                <th>Tên</th>
                                                <th>Email</th>
                                                <th>Trạng Thái</th>
                                                {{-- <th>Role</th>
                                                <th>Created at</th>
                                                <th>Updated at</th> --}}
                                                <th>Hành động</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($users as $user)
                                                <tr>
                                                    <td>{{ $user->id }}</td>
                                                    <!-- Hiển thị Avatar -->
                                                    <td>
                                                        @if ($user->avatar)
                                                            <img src="{{ asset($user->avatar) }}" alt="Avatar"
                                                                width="50" height="50">
                                                        @else
                                                            <img src="{{ asset('default-avatar.jpg') }}"
                                                                alt="Default Avatar" width="50" height="50">
                                                        @endif
                                                    </td>
                                                    <td>{{ '@' . $user->id_cooklab }}</td>
                                                    <td>{{ $user->name }}</td>
                                                    <td>{{ $user->email }}</td>

                                                    <!-- Hiển thị Trạng thái -->
                                                    <td>
                                                        @if ($user->status == 'active')
                                                            <span class="badge badge-success">Hoạt động</span>
                                                        @else
                                                            <span class="badge badge-danger">Chặn</span>
                                                        @endif
                                                    </td>

                                                    {{-- <td>{{ $user->role }}</td>
                                                    <td>{{ $user->created_at }}</td>
                                                    <td>{{ $user->updated_at }}</td> --}}

                                                    <td>
                                                        <!-- Edit Button -->
                                                        <a href="{{ route('edit-user', $user->id) }}"
                                                            class="btn btn-primary btn-sm">Chỉnh sửa</a>

                                                        <!-- Delete Form -->
                                                        <form action="{{ route('destroy-user', $user->id) }}" method="POST" style="display:inline;"
                                                            onsubmit="return confirm('Xác nhận xóa {{$user->name}} ?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                class="btn btn-danger btn-sm">Xóa</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                    <!-- Pagination -->
                                    <div class="pagination justify-content-center" style="margin-top: 10px;">
                                        {{-- {{ $users->links() }} --}}
                                        {{ $users->appends(['companiesPage' => request('companiesPage')])->fragment('users_list')->links() }}
                                    </div>
                                </div>
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
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (window.location.hash && window.location.hash === "#users_list") {
                document.getElementById("users_list").scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    </script>
    <!-- partial -->
@endsection
