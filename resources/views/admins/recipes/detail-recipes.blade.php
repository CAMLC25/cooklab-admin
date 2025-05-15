@extends('home')

@section('content-admin')
    <div class="content-wrapper">
        <div class="col-md-12 grid-margin" id="recipes_list">
            <div class="row">
                <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                    <h3 class="font-weight-bold">Chi tiết công thức nấu ăn</h3>
                </div>
            </div>
        </div>
        <div class="container recipe-container my-4">
            <div class="row">
                <div class="col-md-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">
                            <!-- Phần đầu công thức -->
                            <div class="recipe-header">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="recipe-image-container">
                                            @if ($recipe->image)
                                                <img src="{{ asset($recipe->image) }}" alt="{{ $recipe->title }}"
                                                    class="recipe-main-image">
                                            @else
                                                <img src="{{ asset('admin-assets/images/default_recipe.jpg') }}"
                                                    alt="Ảnh mặc định" class="recipe-main-image">
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <h1 class="recipe-title">{{ $recipe->title }}</h1>

                                        <div class="recipe-subtitle mb-3">
                                            {{-- <i class="fas fa-utensils"></i> "Món ngon" cooksnap cho mọi người! --}}
                                        </div>

                                        <div class="recipe-stats mb-3">
                                            {{-- <i class="fas fa-users"></i> {{ $recipe->servings ?? '4-5' }} người đang đính nấu món này --}}
                                        </div>

                                        <div class="recipe-author d-flex align-items-center mb-3">
                                            <div class="author-avatar">
                                                <img src="{{ asset($recipe->user->avatar) }}" alt="Avatar"
                                                    class="rounded-circle">
                                            </div>
                                            <div class="author-info ms-2">
                                                <div class="author-name">{{ $recipe->user->name ?? 'NGƯỜI DÙNG' }}</div>
                                                {{-- <div class="author-location"><i class="fas fa-map-marker-alt"></i> {{ $recipe->user->location ?? 'TP.HCM' }}</div> --}}
                                            </div>
                                        </div>

                                        <div class="recipe-tags mb-3">
                                            <span class="recipe-tag">#{{ $recipe->category->name ?? 'monngon' }}</span>
                                        </div>

                                        {{-- <div class="recipe-actions">
                        <button class="btn btn-outline-warning btn-sm me-2">
                            <i class="far fa-bookmark"></i> Lưu Món
                        </button>
                        <button class="btn btn-outline-primary btn-sm me-2">
                            <i class="fas fa-plus"></i> Thêm vào bộ sưu tập
                        </button>
                        <button class="btn btn-outline-success btn-sm me-2">
                            <i class="fas fa-share"></i> Chia sẻ
                        </button>
                        <button class="btn btn-outline-dark btn-sm">
                            <i class="fas fa-print"></i> In
                        </button>
                    </div> --}}
                                    </div>
                                </div>
                            </div>

                            <!-- Nội dung chính -->
                            <div class="recipe-content mt-5">
                                <div class="row">
                                    <!-- Cột trái: Nguyên liệu -->
                                    <div class="col-md-4">
                                        <div class="ingredients-section">
                                            <h3 class="section-title">Nguyên Liệu</h3>
                                            <div class="servings-info mb-3">
                                                <i class="fas fa-user-friends"></i> {{ $recipe->servings ?? '4-5' }}
                                            </div>

                                            <ul class="ingredients-list">
                                                @foreach ($recipe->ingredients as $ingredient)
                                                    <li class="ingredient-item">
                                                        <span
                                                            class="ingredient-amount">{{ $ingredient->amount ?? '' }}</span>
                                                        <span class="ingredient-name">{{ $ingredient->name }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Cột phải: Hướng dẫn cách làm -->
                                    <div class="col-md-8">
                                        <div class="directions-section">
                                            <h3 class="section-title">Hướng dẫn cách làm</h3>
                                            <div class="cook-time mb-3">
                                                <i class="far fa-clock"></i> {{ $recipe->cook_time ?? '30' }}
                                            </div>

                                            <div class="steps-list">
                                                @foreach ($recipe->steps as $step)
                                                    <div class="step-item">
                                                        <div class="step-number">{{ $step->step_number }}</div>
                                                        <div class="step-content">
                                                            <p class="step-text">{{ $step->description }}</p>
                                                            @if ($step->image)
                                                                <div class="step-image-row">
                                                                    <div class="step-image-container">
                                                                        <img src="{{ asset($step->image) }}"
                                                                            alt="Bước {{ $step->step_number }}"
                                                                            class="step-image">
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="recipe-id-container mt-4 text-center">
                                <p><strong>ID Công thức:</strong> <span id="recipe-id">{{ $recipe->id }}</span></p>
                                <button class="btn btn-outline-primary" id="copy-button" onclick="copyRecipeId()">
                                    <i class="fas fa-copy"></i> Sao chép ID
                                </button>
                            </div>

                            <script>
                                function copyRecipeId() {
                                    var recipeId = document.getElementById('recipe-id').innerText; // Lấy ID công thức
                                    var copyButton = document.getElementById('copy-button');

                                    // Sao chép ID vào clipboard
                                    navigator.clipboard.writeText(recipeId).then(function() {
                                        // Thay đổi nội dung nút khi sao chép thành công
                                        copyButton.innerHTML = '<i class="fas fa-check-circle"></i> Đã sao chép';
                                        copyButton.disabled = true; // Tắt nút sau khi sao chép

                                        // Quay lại trạng thái ban đầu sau 2 giây
                                        setTimeout(function() {
                                            copyButton.innerHTML = '<i class="fas fa-copy"></i> Sao chép ID';
                                            copyButton.disabled = false; // Bật lại nút
                                        }, 2000);
                                    }).catch(function(error) {
                                        console.error('Lỗi sao chép: ', error);
                                    });
                                }
                            </script>

                            <!-- Thêm phần này sau phần "Nội dung chính" và trước khi kết thúc container -->
                            <div class="recipe-reactions-comments mt-5">
                                <div class="row">
                                    <div class="col-12">
                                        <!-- Phần phản ứng -->
                                        <div class="recipe-reactions mb-4">
                                            <h3 class="section-title">Phản ứng</h3>
                                            <div class="reactions-container d-flex justify-content-start gap-4">
                                                @php
                                                    $reactions = ['heart', 'mlem', 'clap'];
                                                    $reactionEmojis = ['heart' => '❤️', 'mlem' => '😊', 'clap' => '👏'];
                                                    $reactionCounts = [
                                                        'heart' => $heartCount ?? 0,
                                                        'mlem' => $mlemCount ?? 0,
                                                        'clap' => $clapCount ?? 0,
                                                    ];
                                                @endphp

                                                @foreach ($reactions as $reaction)
                                                    <div class="reaction-item">
                                                        <form action="{{ route('recipe.reaction.store', $recipe->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            <input type="hidden" name="reaction_type"
                                                                value="{{ $reaction }}">
                                                            <button type="submit"
                                                                class="btn reaction-btn {{ $userReaction == $reaction ? 'active' : '' }}">
                                                                <span
                                                                    class="reaction-emoji">{{ $reactionEmojis[$reaction] }}</span>
                                                                <span
                                                                    class="reaction-count">{{ $reactionCounts[$reaction] }}</span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <!-- Phần bình luận -->
                                        <div class="recipe-comments">
                                            <h3 class="section-title">Bình luận ({{ count($recipe->comments ?? []) }})</h3>

                                            <!-- Form thêm bình luận mới -->
                                            <div class="comment-form mb-4">
                                                <form action="{{ route('recipe.comment.store', $recipe->id) }}"
                                                    method="POST">
                                                    @csrf
                                                    <div class="input-group">
                                                        <div class="user-avatar-small me-2">
                                                            <img src="{{ asset(Auth::user()->avatar ?? 'admin-assets/images/default_avatar.jpg') }}"
                                                                class="rounded-circle" alt="Avatar">
                                                        </div>
                                                        <textarea name="content" class="form-control" placeholder="Viết bình luận của bạn..." rows="2" required></textarea>
                                                    </div>
                                                    <div class="text-end mt-2">
                                                        <button type="submit" class="btn btn-primary btn-sm">Đăng bình
                                                            luận</button>
                                                    </div>
                                                </form>
                                            </div>

                                            <!-- Danh sách bình luận -->
                                            <div class="comments-list">
                                                @if (isset($recipe->comments) && count($recipe->comments) > 0)
                                                    @foreach ($recipe->comments as $comment)
                                                        <div class="comment-item">
                                                            <div class="comment-author d-flex align-items-start">
                                                                <div class="comment-avatar me-2">
                                                                    <img src="{{ asset($comment->user->avatar ?? 'admin-assets/images/default_avatar.jpg') }}"
                                                                        class="rounded-circle" alt="Avatar">
                                                                </div>
                                                                <div class="comment-content">
                                                                    <div class="comment-header">
                                                                        <span
                                                                            class="comment-author-name">{{ $comment->user->name }}</span>
                                                                        <span
                                                                            class="comment-date">{{ $comment->created_at->diffForHumans() }}</span>
                                                                    </div>
                                                                    <div class="comment-text">
                                                                        {{ $comment->content }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="no-comments text-center py-3">
                                                        <p class="text-muted">Chưa có bình luận nào. Hãy là người đầu tiên
                                                            bình luận!</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
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
@endsection

<style>
    /* Thiết lập chung */
    .recipe-container {
        max-width: 1000px;
        margin: 0 auto;
        color: #333;
        font-family: 'Segoe UI', Roboto, sans-serif;
    }

    /* Phần header */
    .recipe-title {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: #333;
    }

    .recipe-subtitle {
        color: #666;
        font-size: 1rem;
    }

    .recipe-stats {
        color: #666;
        font-size: 0.9rem;
    }

    .recipe-image-container {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .recipe-main-image {
        width: 100%;
        height: auto;
        object-fit: cover;
    }

    .author-avatar img {
        width: 40px;
        height: 40px;
        object-fit: cover;
    }

    .author-name {
        font-weight: 600;
        font-size: 0.9rem;
    }

    .author-location {
        font-size: 0.8rem;
        color: #666;
    }

    .recipe-tag {
        display: inline-block;
        background-color: #fff8e1;
        color: #ff8a00;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 0.85rem;
        margin-right: 5px;
    }

    /* Phần nguyên liệu */
    .section-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #eee;
    }

    .servings-info,
    .cook-time {
        color: #666;
        font-size: 0.9rem;
    }

    .ingredients-list {
        list-style-type: none;
        padding-left: 0;
    }

    .ingredient-item {
        padding: 8px 0;
        border-bottom: 1px dashed #eee;
    }

    .ingredient-amount {
        color: #666;
        margin-right: 8px;
        font-weight: 500;
    }

    /* Phần các bước */
    .steps-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .step-item {
        display: flex;
        margin-bottom: 1.5rem;
    }

    .step-number {
        display: flex;
        align-items: flex-start;
        justify-content: center;
        width: 30px;
        height: 30px;
        background-color: #ff8a00;
        color: white;
        border-radius: 50%;
        margin-right: 15px;
        font-weight: bold;
        flex-shrink: 0;
    }

    .step-content {
        flex-grow: 1;
    }

    .step-text {
        margin-bottom: 10px;
        line-height: 1.6;
    }

    .step-image-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    .step-image-container {
        max-width: 200px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .step-image {
        width: 100%;
        height: auto;
        object-fit: cover;
    }

    /* Responsive */
    @media (max-width: 767px) {
        .recipe-title {
            font-size: 1.8rem;
        }

        .recipe-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .recipe-actions button {
            margin-bottom: 5px;
        }

        .step-image-container {
            max-width: 150px;
        }
    }

    /* Phần phản ứng */
    .recipe-reactions-comments {
        border-top: 1px solid #eee;
        padding-top: 2rem;
    }

    .reactions-container {
        margin-bottom: 1rem;
    }

    .reaction-item {
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .reaction-btn {
        background-color: #f5f5f5;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        transition: all 0.2s;
        border: none;
        color: #666;
        font-size: 1.2rem;
        margin-bottom: 0.5rem;
    }

    .reaction-btn:hover {
        background-color: #ff8a00;
        color: white;
        transform: scale(1.1);
    }

    .reaction-btn.active {
        background-color: #ff8a00;
        color: white;
    }

    .reaction-count {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: #fff;
        color: #333;
        border-radius: 50%;
        font-size: 0.65rem;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #ddd;
        font-weight: bold;
    }

    .reaction-label {
        font-size: 0.8rem;
        color: #666;
    }

    /* Phần bình luận */
    .comment-form {
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 8px;
    }

    .user-avatar-small img,
    .comment-avatar img {
        width: 40px;
        height: 40px;
        object-fit: cover;
    }

    .comments-list {
        margin-top: 1.5rem;
    }

    .comment-item {
        padding: 15px 0;
        border-bottom: 1px solid #eee;
    }

    .comment-item:last-child {
        border-bottom: none;
    }

    .comment-header {
        margin-bottom: 5px;
    }

    .comment-author-name {
        font-weight: 600;
        font-size: 0.9rem;
    }

    .comment-date {
        font-size: 0.8rem;
        color: #888;
        margin-left: 10px;
    }

    .comment-text {
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .no-comments {
        background-color: #f9f9f9;
        border-radius: 8px;
    }
</style>
