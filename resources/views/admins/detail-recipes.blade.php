@extends('home')

@section('content-admin')
<div class="content-wrapper">
    <h1 class="my-4">Công thức nấu ăn</h1>
    <div class="recipe">
        <!-- Ảnh công thức -->
        <div class="row mb-4">
            <div class="col-md-4">
                <img src="" alt="Image" class="img-fluid">
            </div>
            <div class="col-md-8">
                <h3></h3>
                <p><strong>Mô tả:</strong> </p>
            </div>
        </div>

        <!-- Khẩu phần và thời gian -->
        <div class="row mb-4">
            <div class="col-md-6">
                <strong>Khẩu phần:</strong>
            </div>
            <div class="col-md-6">
                <strong>Thời gian nấu:</strong>
            </div>
        </div>

        <!-- Nguyên liệu -->
        <h4>Nguyên liệu</h4>
        <ul>
        </ul>

        <!-- Các bước -->
        <h4>Các bước</h4>

    </div>
</div>
@endsection

<!-- STYLE -->
<style>
    .recipe img {
        max-width: 100%;
        height: auto;
    }
    .step-block {
        background-color: #f8f8f8;
        border-radius: 10px;
        padding: 15px;
    }
</style>
