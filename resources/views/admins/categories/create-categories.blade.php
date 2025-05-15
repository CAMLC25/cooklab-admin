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
                        <h3 class="font-weight-bold">Tạo Danh Mục Mới</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <p class="card-title">Tạo Danh Mục Mới</p>
                        <div class="container">
                            <form action="{{ route('store-categories') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="name">Tên Danh Mục</label>
                                    <input type="text" name="name" id="name" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-success mt-3">Lưu</button>
                            </form>
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
