<nav class="topbar navbar" aria-label="Top bar">
    <div class="topbar-heading">
        <span class="topbar-eyebrow">WORKSPACE</span>
        <h1 class="topbar-title">@yield('title', 'Dashboard')</h1>
    </div>

    <div class="topbar-actions">
        <a href="{{ route('todo.create') }}" class="btn btn-dark btn-sm topbar-create">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
            <span>New task</span>
        </a>

        <div class="topbar-user" aria-label="Signed in as {{ auth()->user()->name }}">
            <span class="topbar-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            <span class="topbar-user-copy">
                <strong>{{ auth()->user()->name }}</strong>
                <small>Personal account</small>
            </span>
        </div>

        <form method="POST" action="{{ route('auth.logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm topbar-logout">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                <span>Log out</span>
            </button>
        </form>
    </div>
</nav>
