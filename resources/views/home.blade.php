@extends('layouts.app')

@section('content')
    <div class="container-scroller">
        {{-- <header class="py-4">
            @yield('content-login')
        </header> --}}
        {{-- @auth('admin') --}}
        <!-- partial:partials/_navbar.html -->
        <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
                <a class="navbar-brand brand-logo mr-5" href="/"><img
                        src="{{ asset('admin-assets/images/cook_panel.png') }}" class="mr-2" alt="logo" /> CookLab</a>
                <a class="navbar-brand brand-logo-mini" href="/"><img
                        src="{{ asset('admin-assets/images/cook_panel.png') }}" alt="logo" /></a>
            </div>
            <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
                <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
                    <span class="icon-menu"></span>
                </button>

                {{-- <ul class="navbar-nav navbar-nav-right">
                    @guest
                        <li class="nav-item nav-profile dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" id="profileDropdown">
                                {{ __('More') }}
                            </a>
                            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
                                <a class="dropdown-item" href="{{ route('login') }}">
                                    {{ __('Login') }}
                                </a>
                                <a class="dropdown-item" href="{{ route('register') }}">
                                    {{ __('Register') }}
                                </a>
                            </div>
                        </li>
                    @else
                        <li class="nav-item nav-profile dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" id="profileDropdown">
                                {{ Auth::user()->name }}
                                <img src="{{ asset('admin-assets/images/cook.png') }}" alt="profile" />
                            </a>
                            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
                                <a class="dropdown-item">
                                    <i class="ti-email text-primary"></i>
                                    {{ Auth::user()->email }}
                                </a>
                                <a class="dropdown-item" href="">
                                    <i class="icon-grid text-primary"></i>
                                    Dashboard
                                </a>
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="ti-power-off text-primary"></i>
                                    Log out
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        @endauth
                    </li>
                </ul> --}}

                <ul class="navbar-nav navbar-nav-right">
                    @guest
                        <li class="nav-item nav-profile dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" id="profileDropdown">
                                {{ __('More') }}
                            </a>
                            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
                                <a class="dropdown-item" href="{{ route('login') }}">
                                    {{ __('Login') }}
                                </a>
                                <a class="dropdown-item" href="{{ route('register') }}">
                                    {{ __('Register') }}
                                </a>
                            </div>
                        </li>
                    @else
                        <li class="nav-item nav-profile dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown" id="profileDropdown">
                                {{ Auth::user()->name }}
                                <img src="{{ asset('admin-assets/images/cook.png') }}" alt="profile" />
                            </a>
                            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
                                <a class="dropdown-item">
                                    <i class="ti-email text-primary"></i>
                                    {{ Auth::user()->email }}
                                </a>
                                <a class="dropdown-item" href="{{ route('home') }}">
                                    <i class="icon-grid text-primary"></i>
                                    Dashboard
                                </a>
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="ti-power-off text-primary"></i>
                                    Log out
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </li>
                    @endguest
                </ul>


                <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button"
                    data-toggle="offcanvas">
                    <span class="icon-menu"></span>
                </button>
            </div>
        </nav>

        <!-- partial -->
        <div class="container-fluid page-body-wrapper">
            <!-- chỉnh màu -->
            {{-- <div class="theme-setting-wrapper">
                <div id="settings-trigger"><i class="ti-settings"></i></div>
                <div id="theme-settings" class="settings-panel">
                    <i class="settings-close ti-close"></i>
                    <p class="settings-heading">SIDEBAR SKINS</p>
                    <div class="sidebar-bg-options selected" id="sidebar-light-theme">
                        <div class="img-ss rounded-circle bg-light border mr-3"></div>Light
                    </div>
                    <div class="sidebar-bg-options" id="sidebar-dark-theme">
                        <div class="img-ss rounded-circle bg-dark border mr-3"></div>Dark
                    </div>
                    <p class="settings-heading mt-2">HEADER SKINS</p>
                    <div class="color-tiles mx-0 px-4">
                        <div class="tiles success"></div>
                        <div class="tiles warning"></div>
                        <div class="tiles danger"></div>
                        <div class="tiles info"></div>
                        <div class="tiles dark"></div>
                        <div class="tiles default"></div>
                    </div>
                </div>
            </div> --}}
            <div id="right-sidebar" class="settings-panel">
            </div>
            <nav class="sidebar sidebar-offcanvas" id="sidebar">
                {{-- @auth('admin') --}}
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('home') }}">
                            <i class="icon-grid menu-icon"></i>
                            <span class="menu-title">Trang chủ</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admins.all-admins') }}">
                            <i class="icon-head menu-icon"></i>
                            <span class="menu-title">Admins </span>
                            <!-- <i class="menu-arrow"></i> -->
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admins.all-users') }}">
                            <i class="icon-head menu-icon"></i>
                            <span class="menu-title">Users</span>
                            <!-- <i class="menu-arrow"></i> -->
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admins.all-recipes') }}">
                            <i class="icon-paper menu-icon"></i>
                            <span class="menu-title">Công thức món ăn</span>
                            <!-- <i class="menu-arrow"></i> -->
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admins.all-categories') }}">
                            <i class="ti-home menu-icon"></i>
                            <span class="menu-title">Danh mục</span>
                            <!-- <i class="menu-arrow"></i> -->
                        </a>
                    </li>
                    {{-- <li class="nav-item">
                        <a class="nav-link" href="" aria-expanded="false" aria-controls="auth">
                            <i class="ti-clipboard menu-icon"></i>
                            <span class="menu-title">Jobs</span>
                            <!-- <i class="menu-arrow"></i> -->
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="">
                            <i class="ti-files menu-icon"></i>
                            <span class="menu-title">Applications</span>
                        </a>
                    </li> --}}
                </ul>
                {{-- @endauth --}}
            </nav>
            <!-- partial -->
            <div class="main-panel">
                <main class="py-4">
                    @yield('content-admin')
                </main>
            </div>
        </div>
    </div>
@endsection
