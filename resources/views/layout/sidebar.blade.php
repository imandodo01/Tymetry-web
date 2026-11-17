<aside class="sidebar" aria-label="Main navigation">
    <a class="sidebar-brand" href="{{ route('dashboard.index') }}" aria-label="Tymetry home">
        <span class="sidebar-brand-mark" aria-hidden="true">T</span>
        <span class="sidebar-brand-copy">
            <strong>Tymetry</strong>
            <small>Personal workspace</small>
        </span>
    </a>

    <nav class="sidebar-navigation" aria-label="Workspace">
        <div class="sidebar-section-label">Workspace</div>
        <ul class="sidebar-menu list-unstyled">
            <li>
                <a href="{{ route('dashboard.index') }}"
                    class="sidebar-link {{ request()->routeIs('dashboard.*') ? 'active' : '' }}"
                    @if (request()->routeIs('dashboard.*')) aria-current="page" @endif>
                    <i class="bi bi-grid-1x2" aria-hidden="true"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('todo.index') }}"
                    class="sidebar-link {{ request()->routeIs('todo.index', 'todo.create', 'todo.edit') ? 'active' : '' }}"
                    @if (request()->routeIs('todo.index', 'todo.create', 'todo.edit')) aria-current="page" @endif>
                    <i class="bi bi-check2-square" aria-hidden="true"></i>
                    <span>My Tasks</span>
                </a>
            </li>
            <li>
                <a href="{{ route('todo.archive') }}"
                    class="sidebar-link {{ request()->routeIs('todo.archive') ? 'active' : '' }}"
                    @if (request()->routeIs('todo.archive')) aria-current="page" @endif>
                    <i class="bi bi-archive" aria-hidden="true"></i>
                    <span>Archive</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-section-label sidebar-account-label">Account</div>
        <ul class="sidebar-menu list-unstyled">
            <li>
                <a href="{{ route('profile.index') }}"
                    class="sidebar-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                    @if (request()->routeIs('profile.*')) aria-current="page" @endif>
                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                    <span>Profile</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <span class="sidebar-footer-indicator" aria-hidden="true"></span>
        <span>Personal workspace</span>
    </div>
</aside>
