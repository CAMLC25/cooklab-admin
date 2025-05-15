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
            <div class="col-md-12 grid-margin" id="categories_list">
                <div class="row">
                    <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                        <h3 class="font-weight-bold">Danh sách danh mục</h3>
                    </div>
                    <div class="col-12 col-xl-4 mb-4 mb-xl-0">
                        <a href="{{ route('create-categories') }}" class="btn btn-success btn-sm float-right">Tạo danh mục mới</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <p class="card-title">Danh sách danh mục</p>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tên Danh Mục</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($categories as $category)
                                        <tr>
                                            <td>{{ $category->id }}</td>
                                            <td>{{ $category->name }}</td>
                                            <td>
                                                <a href="{{ route('edit-categories', $category->id) }}" class="btn btn-warning">Chỉnh sửa</a>
                                                <form action="{{ route('destroy-categories', $category->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Phân trang -->
                            <div class="pagination justify-content-center" style="margin-top: 10px;">
                                {{-- {{ $users->links() }} --}}
                                {{ $categories->appends(['companiesPage' => request('companiesPage')])->fragment('categories_list')->links() }}
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
            if (window.location.hash && window.location.hash === "#categories_list") {
                document.getElementById("categories_list").scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    </script>
@endsection
