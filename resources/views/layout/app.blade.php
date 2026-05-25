<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>

    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    @stack('styles')
</head>

<body>
    <div class="wrapper d-flex">
        @include('layout.sidebar')
        <main class="main-content flex-grow-1">
            @include('layout.navbar')
            <div class="content-area">
                @yield('content')
            </div>
        </main>
    </div>
    <div id="pageLoader" class="d-none position-fixed top-0 start-0 w-100 h-100 bg-white bg-opacity-50">
        <div class="d-flex justify-content-center align-items-center h-100">
            <div class="spinner-border">
            </div>
        </div>
    </div>
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        <div id="appToast" class="toast align-items-center border-0 shadow-sm" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    Success
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm">
                <div class="modal-header border-0">
                    <h5 class="modal-title">
                        Confirm Action
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="confirmMessage">
                        Are you sure?
                    </p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-dark btn-sm" id="confirmButton">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> --}}
    <!-- jQuery -->
    <script src="{{ asset('assets/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    @if (session('success'))
    <script>
        document.addEventListener('DOMContentLoaded',function () {
            showToast(@json(session('success')));
        });
    </script>
    @endif
    @stack('scripts')

</body>

</html>
