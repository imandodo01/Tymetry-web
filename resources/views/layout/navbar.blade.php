<!-- NAVBAR -->
<nav class="topbar navbar navbar-expand-lg">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">@yield('title')</span>

        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="small text-muted">{{ auth()->user()->name }}</span>

            <form method="POST" action="{{ url('/logout') }}">
                @csrf

                <button type="submit" class="btn btn-outline-dark btn-sm px-3">
                    Logout
                </button>
            </form>
        </div>
    </div>
</nav>
