<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monochrome Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>

<body>
    <div class="wrapper d-flex">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h4>ADMIN</h4>
            </div>

            <ul class="sidebar-menu list-unstyled">
                <li>
                    <a href="#" class="active">Dashboard</a>
                </li>
                <li>
                    <a href="#">Users</a>
                </li>
                <li>
                    <a href="#">Reports</a>
                </li>
                <li>
                    <a href="#">Settings</a>
                </li>
            </ul>
        </aside>
        <!-- MAIN -->
        <main class="main-content flex-grow-1">

            <!-- NAVBAR -->
            <nav class="topbar navbar navbar-expand-lg">
                <div class="container-fluid">
                    <span class="navbar-brand mb-0 h1">Dashboard</span>

                    <div class="ms-auto d-flex align-items-center gap-3">
                        <span class="small text-muted">Administrator</span>
                        <button class="btn btn-outline-dark btn-sm">Logout</button>
                    </div>
                </div>
            </nav>
            <!-- CONTENT -->
            <div class="content-area">

                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <p>Total Users</p>
                            <h2>124</h2>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <p>Orders</p>
                            <h2>58</h2>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="dashboard-card">
                            <p>Revenue</p>
                            <h2>$2,450</h2>
                        </div>
                    </div>
                </div>
                <div class="content-card">
                    <h5 class="mb-3">Recent Activity</h5>

                    <table class="table custom-table align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#001</td>
                                <td>John Doe</td>
                                <td>
                                    <span class="badge bg-dark">Active</span>
                                </td>
                                <td>2026-05-20</td>
                            </tr>
                            <tr>
                                <td>#002</td>
                                <td>Jane Doe</td>
                                <td>
                                    <span class="badge bg-secondary">Pending</span>
                                </td>
                                <td>2026-05-19</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>

</body>

</html>
