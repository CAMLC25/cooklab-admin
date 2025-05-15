@extends('home')

@section('content-admin')
<div class="content-wrapper">
    <form method="POST" action="" enctype="multipart/form-data">
        @csrf

        <!-- PHẦN ẢNH, TÊN, MÔ TẢ -->
        <div class="row mb-4">
            <div class="col-md-4">
                <label for="imageUpload" class="upload-box d-block text-center">
                    <img id="mainImagePreview" src="{{ asset('admin-assets/images/cook_panel.png') }}" alt="Upload Icon" class="icon mb-2" style="max-width: 100%; height: 200px; object-fit: contain;">
                    <div>Chia sẻ món ăn của bạn!</div>
                    <input type="file" id="imageUpload" name="image" onchange="previewMainImage(event)">
                </label>
            </div>
            <div class="col-md-8">
                <h3>Tên món:</h3>
                <input type="text" name="title" class="form-control mb-3" placeholder="Món canh bí ngon nhất nhà mình">
                <textarea name="description" class="form-control" placeholder="Hãy chia sẻ cảm hứng của bạn..." rows="3"></textarea>
            </div>
        </div>

        <!-- KHẨU PHẦN + THỜI GIAN -->
        <div class="row mb-4">
            <div class="col-md-6">
                <label>Khẩu phần:</label>
                <input type="text" name="servings" class="form-control" placeholder="2 người">
            </div>
            <div class="col-md-6">
                <label>Thời gian nấu:</label>
                <input type="text" name="cook_time" class="form-control" placeholder="1 tiếng 30 phút">
            </div>
        </div>

        <!-- NGUYÊN LIỆU -->
        <h4>Nguyên liệu</h4>
        <div id="ingredients-list">
            <div class="row ingredient-group mb-2">
                <div class="col-md-10">
                    <input type="text" name="ingredients[]" class="form-control" placeholder="250g bột">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger w-100" onclick="removeIngredient(this)">Xoá</button>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-outline-primary mb-4" onclick="addIngredient()">+ Nguyên liệu</button>

        <!-- CÁC BƯỚC -->
        <h4>Các bước</h4>
        <div id="steps-list">
            <div class="step-block mb-3 border p-3 bg-light">
                <h5 class="step-title mb-2">Bước 1</h5>
                <input type="text" name="steps[]" class="form-control mb-2" placeholder="Mô tả bước...">
                <input type="file" name="step_images[]" onchange="previewStepImage(event, this)">
                <img src="" class="step-preview mt-2" style="max-width: 100px; display: none;">
                <button type="button" class="btn btn-danger mt-2" onclick="removeStep(this)">Xoá bước</button>
            </div>
        </div>
        <button type="button" class="btn btn-outline-success mb-4" onclick="addStep()">+ Bước làm</button>

        <!-- NÚT LƯU -->
        <div class="text-right">
            <button type="submit" class="btn btn-primary">Lưu công thức</button>
        </div>
    </form>
</div>

<!-- STYLE -->
<style>
    .upload-box {
        border: 2px dashed #ccc;
        background-color: #f8f8f8;
        padding: 20px;
        border-radius: 10px;
        cursor: pointer;
        position: relative;
    }

    .upload-box input[type="file"] {
        display: none;
    }

    .step-block {
        cursor: move;
    }
</style>

<!-- SCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        Sortable.create(document.getElementById('steps-list'), {
            animation: 150,
            onEnd: updateStepTitles
        });
    });

    function previewMainImage(event) {
        const reader = new FileReader();
        reader.onload = function () {
            document.getElementById('mainImagePreview').src = reader.result;
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    function addIngredient() {
        const html = `
        <div class="row ingredient-group mb-2">
            <div class="col-md-10">
                <input type="text" name="ingredients[]" class="form-control" placeholder="Nguyên liệu...">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger w-100" onclick="removeIngredient(this)">Xoá</button>
            </div>
        </div>`;
        document.getElementById('ingredients-list').insertAdjacentHTML('beforeend', html);
    }

    function removeIngredient(btn) {
        btn.closest('.ingredient-group').remove();
    }

    function addStep() {
        const html = `
        <div class="step-block mb-3 border p-3 bg-light">
            <h5 class="step-title mb-2">Bước</h5>
            <input type="text" name="steps[]" class="form-control mb-2" placeholder="Mô tả bước...">
            <input type="file" name="step_images[]" onchange="previewStepImage(event, this)">
            <img src="" class="step-preview mt-2" style="max-width: 100px; display: none;">
            <button type="button" class="btn btn-danger mt-2" onclick="removeStep(this)">Xoá bước</button>
        </div>`;
        document.getElementById('steps-list').insertAdjacentHTML('beforeend', html);
        updateStepTitles();
    }

    function removeStep(btn) {
        btn.closest('.step-block').remove();
        updateStepTitles();
    }

    function updateStepTitles() {
        document.querySelectorAll('#steps-list .step-block').forEach((el, index) => {
            el.querySelector('.step-title').textContent = `Bước ${index + 1}`;
        });
    }

    function previewStepImage(event, input) {
        const reader = new FileReader();
        reader.onload = function () {
            const img = input.nextElementSibling;
            img.src = reader.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>
@endsection
