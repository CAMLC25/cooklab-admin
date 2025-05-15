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
            <div class="col-md-12 grid-margin" id="recipes_list">
                <div class="row">
                    <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                        <h3 class="font-weight-bold">Danh sách Công thức nấu ăn</h3>
                    </div>
                    <div class="col-12 col-xl-4 mb-4 mb-xl-0">
                        <a href="{{ route('create_recipe') }}" class="btn btn-success btn-sm float-right">Tạo công thức</a>
                    </div>
                </div>
                <!-- Thêm ô tìm kiếm ở đây và căn giữa -->
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <form action="{{ route('search_recipes') }}" method="GET" class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Tìm kiếm công thức"
                                value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <p class="card-title">Danh sách Công thức</p>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Ảnh</th>
                                        <th>Tên công thức</th>
                                        <th>Danh mục</th>
                                        <th>Người đăng</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recipes as $recipe)
                                        <tr>
                                            <td>{{ $recipe->id }}</td>
                                            <td>
                                                @if ($recipe->image)
                                                    <img src="{{ asset($recipe->image) }}" alt="Recipe Image"
                                                        style="width: 100px; height: 100px; object-fit: cover; border-radius: 0;">
                                                @else
                                                    <p>Chưa có ảnh</p>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('detail_recipes', ['id' => $recipe->id]) }}">
                                                    {{ $recipe->title }}</a>
                                            </td>
                                            <td>{{ $recipe->category->name }}</td>
                                            <td>{{ $recipe->user->name }}</td> <!-- Hiển thị tên người đăng -->

                                            <td>
                                                <select class="form-control status-selector" data-id="{{ $recipe->id }}">
                                                    <option value="pending"
                                                        {{ $recipe->status == 'pending' ? 'selected' : '' }}>Đang chờ duyệt
                                                    </option>
                                                    <option value="approved"
                                                        {{ $recipe->status == 'approved' ? 'selected' : '' }}>Đã duyệt
                                                    </option>
                                                    <option value="rejected"
                                                        {{ $recipe->status == 'rejected' ? 'selected' : '' }}>Bị từ chối
                                                    </option>
                                                </select>
                                            </td>

                                            <td>
                                                <a href="{{ route('edit_recipe', ['id' => $recipe->id]) }}"
                                                    class="btn btn-info btn-sm">Chỉnh sửa</a>
                                                <form action="{{ route('destroy_recipe', ['id' => $recipe->id]) }}"
                                                    method="POST" style="display:inline-block;"
                                                    onsubmit="return confirm('Xác nhận xóa {{ $recipe->title }} ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <div class="pagination justify-content-center" style="margin-top: 10px;">
                                {{ $recipes->appends(['companiesPage' => request('companiesPage')])->fragment('recipes_list')->links('pagination::bootstrap-4') }}
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

    <!-- Modal nhập lý do từ chối -->
    <!-- Modal nhập lý do từ chối -->
    <style>
        #rejected-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            width: 90%;
            max-width: 420px;
            transition: all 0.3s ease-in-out;
        }

        #rejected-modal h5 {
            margin-bottom: 15px;
            font-weight: bold;
            color: #dc3545;
        }

        #rejected-modal textarea {
            border-radius: 8px;
            resize: none;
        }

        #rejected-modal .btn {
            border-radius: 8px;
            padding: 6px 16px;
        }

        #rejected-modal .btn+.btn {
            margin-left: 10px;
        }
    </style>

    <div id="rejected-modal">
        <h5>Nhập lý do từ chối</h5>
        <form id="rejected-form" method="POST" action="{{ route('update_status_rejected') }}">
            @csrf
            <input type="hidden" name="recipe_id" id="modal-recipe-id">
            <textarea name="reason_rejected" class="form-control" placeholder="Lý do từ chối..." rows="3" required></textarea>
            <div class="mt-3 text-end">
                <button type="submit" class="btn btn-danger">Lưu</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Huỷ</button>
            </div>
        </form>
    </div>

    <script>
        document.querySelectorAll('.status-selector').forEach(select => {
            select.addEventListener('change', function() {
                if (this.value === 'rejected') {
                    const recipeId = this.getAttribute('data-id');
                    document.getElementById('modal-recipe-id').value = recipeId;
                    document.getElementById('rejected-modal').style.display = 'block';
                    this.value = '{{ $recipe->status }}'; // giữ trạng thái cũ trên dropdown
                } else {
                    // Gửi form ngay nếu không phải "rejected"
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('update_status_direct') }}';

                    const token = document.createElement('input');
                    token.type = 'hidden';
                    token.name = '_token';
                    token.value = '{{ csrf_token() }}';

                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'recipe_id';
                    idInput.value = this.getAttribute('data-id');

                    const statusInput = document.createElement('input');
                    statusInput.type = 'hidden';
                    statusInput.name = 'status';
                    statusInput.value = this.value;

                    form.appendChild(token);
                    form.appendChild(idInput);
                    form.appendChild(statusInput);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });

        function closeModal() {
            document.getElementById('rejected-modal').style.display = 'none';
        }
    </script>
    <style>
        .status-pending {
            background-color: #fff3cd !important;
            /* vàng nhạt */
            color: #856404;
        }

        .status-approved {
            background-color: #d4edda !important;
            /* xanh nhạt */
            color: #155724;
        }

        .status-rejected {
            background-color: #f8d7da !important;
            /* đỏ nhạt */
            color: #721c24;
        }
    </style>
    <script>
        function updateSelectColor(select) {
            select.classList.remove('status-pending', 'status-approved', 'status-rejected');
            if (select.value === 'pending') {
                select.classList.add('status-pending');
            } else if (select.value === 'approved') {
                select.classList.add('status-approved');
            } else if (select.value === 'rejected') {
                select.classList.add('status-rejected');
            }
        }

        document.querySelectorAll('.status-selector').forEach(select => {
            updateSelectColor(select); // đặt màu khi trang vừa load

            select.addEventListener('change', function() {
                updateSelectColor(this); // cập nhật màu mới
                // phần xử lý popup/từ chối giữ nguyên như trước...
            });
        });
    </script>
@endsection
