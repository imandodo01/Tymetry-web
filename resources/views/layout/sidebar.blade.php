<aside class="sidebar">
    <div class="sidebar-header">
        <h4>ADMIN</h4>
    </div>

    <ul class="sidebar-menu list-unstyled">
        <li>
            <a href="{{ route('dashboard.index') }}" class="{{ Route::currentRouteName() == 'dashboard.index'
                ? 'active' : '' }}">Dashboard</a>
        </li>
        <li>
            <a href="{{ route('todo.index') }}" class="{{ Route::currentRouteName() == 'todo.index' ? 'active' : ''
                }}">Todo</a>
        </li>
        <li>
            <a href="{{ route('todo.archive') }}" class="{{ Route::currentRouteName() == 'todo.archive' ? 'active' : ''
                }}">Archived Todo</a>
        </li>
        <li>
            <a href="{{ route('profile.index') }}" class="{{ Route::currentRouteName() == 'profile.index' ? 'active' : ''
                }}">Profile</a>
        </li>
        <li>
            <form method="POST" action="{{ url('/logout') }}">
                @csrf
                <button type="submit" class="sidebar-link btn w-100 text-start border-0 bg-transparent px-3 py-2">
                    Logout
                </button>
            </form>
        </li>
    </ul>
</aside>
