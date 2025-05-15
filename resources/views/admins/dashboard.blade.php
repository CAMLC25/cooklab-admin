@extends('home')

@section('content-admin')
<div class="content-wrapper">
    <div class="row">
        <div class="col-md-12 grid-margin mb-4">
            @guest
            @else
                <h3 class="font-weight-bold">Xin chào, {{ Auth::user()->name }} 👋</h3>
                <h6 class="font-weight-normal mb-0 text-muted">Chúc bạn một ngày làm việc hiệu quả!</h6>
            @endguest
        </div>
    </div>

    <div class="row">
        <!-- Card Thống kê -->
        <div class="col-md-6 grid-margin stretch-card">
            <div class="card card-tale text-white shadow">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <h4 class="font-weight-light mb-3">Tổng số User</h4>
                        <h2 class="font-weight-bold">{{ $totalUsers }}</h2>
                    </div>
                    {{-- <small>+10% so với tháng trước</small> --}}
                </div>
            </div>
        </div>

        <div class="col-md-6 grid-margin stretch-card">
            <div class="card card-dark-blue text-white shadow">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <h4 class="font-weight-light mb-3">Tổng số Công thức</h4>
                        <h2 class="font-weight-bold">{{ $totalRecipes }}</h2>
                    </div>
                    {{-- <small>+22% so với tháng trước</small> --}}
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Biểu đồ -->
        <div class="col-12 grid-margin stretch-card">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title mb-4">Tình trạng công thức</h4>
                    <canvas id="recipe-status-chart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="d-sm-flex justify-content-center justify-content-sm-between">
        <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">
            Copyright © 2025 <a href="#" target="_blank"></a>. All rights reserved.
        </span>
        <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">
            CAM <i class="ti-heart text-danger ml-1"></i>
        </span>
    </div>
</footer>

<!-- JS Chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var ctx = document.getElementById('recipe-status-chart').getContext('2d');
        var recipeStatusChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Đã duyệt', 'Chờ xác nhận', 'Từ chối'],
                datasets: [{
                    data: [{{ $approvedRecipes }}, {{ $pendingRecipes }}, {{ $rejectedRecipes }}],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                    borderColor: ['#ffffff', '#ffffff', '#ffffff'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed;
                                return `${label}: ${value} công thức`;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endsection
