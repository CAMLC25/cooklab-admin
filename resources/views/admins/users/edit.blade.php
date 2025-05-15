@extends('home')

@section('content-admin')
    <div class="content-wrapper">
        <h3 class="font-weight-bold">Chỉnh sửa thông tin User</h3>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('update-user', $user->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- <div class="form-group">
                <label>Tên</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="form-group">
                <label for="id_cooklab">ID CookLab</label>
                <input type="text" name="id_cooklab" value="{{ old('id_cooklab', $user->id_cooklab) }}"
                    class="form-control" required>
                <small class="form-text text-muted"><strong>Chỉ chứa bằng chữ, số, và giấu gạch chân '_'.</strong></small>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="form-group">
                <label>Trạng thái</label>
                <select name="status" class="form-control" required>
                    <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="locked" {{ old('status', $user->status) == 'locked' ? 'selected' : '' }}>Locked</option>
                </select>
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" class="form-control">
                <small class="text-muted">Để trống nếu không thay đổi mật khẩu.</small>
            </div>

            <div class="form-group">
                <label>Xác nhận mật khẩu</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>

            <div class="form-group">
                <label>Ảnh đại diện (Avatar)</label>
                <input type="file" name="avatar" class="form-control-file">
                @if ($user->avatar)
                    <div class="mt-3">
                        <img src="{{ asset($user->avatar) }}" alt="Avatar" width="100">
                    </div>
                @else
                    <p class="text-muted">Chưa có ảnh đại diện.</p>
                @endif
            </div> --}}

            <div class="form-group">
                <label>Tên</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $user->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="id_cooklab">ID CookLab</label>
                <input type="text" name="id_cooklab" value="{{ old('id_cooklab', $user->id_cooklab) }}"
                    class="form-control @error('id_cooklab') is-invalid @enderror" required>
                <small class="form-text text-muted"><strong>Chỉ chứa chữ, số và dấu gạch dưới '_'.</strong></small>
                @error('id_cooklab')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email', $user->email) }}" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>Trạng thái</label>
                <select name="status" class="form-control @error('status') is-invalid @enderror" required>
                    <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="locked" {{ old('status', $user->status) == 'locked' ? 'selected' : '' }}>Locked</option>
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                <small class="text-muted">Để trống nếu không thay đổi mật khẩu.</small>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>Xác nhận mật khẩu</label>
                <input type="password" name="password_confirmation"
                    class="form-control @error('password_confirmation') is-invalid @enderror">
                @error('password_confirmation')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label>Ảnh đại diện (Avatar)</label>
                <input type="file" name="avatar" class="form-control-file @error('avatar') is-invalid @enderror">
                @error('avatar')
                    <div class="invalid-feedback d-block">{{ $message }}</div> {{-- vì input file không dùng is-invalid được tốt --}}
                @enderror

                @if ($user->avatar)
                    <div class="mt-3">
                        <img src="{{ asset($user->avatar) }}" alt="Avatar" width="100">
                    </div>
                @else
                    <p class="text-muted">Chưa có ảnh đại diện.</p>
                @endif
            </div>


            <button type="submit" class="btn btn-primary">Cập nhật</button>
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
