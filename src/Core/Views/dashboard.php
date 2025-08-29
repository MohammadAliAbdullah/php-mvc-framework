<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        .flex-1{
            flex: 1;
        }
        .flex-4{
            flex: 4;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark border-end text-white flex-1" id="sidebar-wrapper">
            <div class="sidebar-heading text-center py-4">
                <h4>My Dashboard</h4>
            </div>
            <div class="list-group list-group-flush">
                <a href="#" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fas fa-users"></i> Clients
                </a>
                <a href="#" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="/auth/logout" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Page Content -->
        <div id="page-content-wrapper " class="flex-4">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </nav>
            <div class="container-fluid mt-4">
                <div class="d-flex justify-content-between mb-3">
                    <h3>Clients</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                        <i class="fas fa-plus"></i> Add Client
                    </button>
                </div>
                <table id="clientsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client Name</th>
                        <th>Scopes</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <!-- Dynamic rows will be loaded here using AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<!-- Add Client Modal -->
<div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addClientModalLabel">Add Client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addClientForm">
                    <div class="mb-3">
                        <label for="clientName" class="form-label">Client Name</label>
                        <input type="text" class="form-control" id="clientName" name="clientName" required>
                    </div>
                    <div class="mb-3">
                        <label for="clientScopes" class="form-label">Scopes</label>
                        <select class="form-select" id="clientScopes" name="scopes[]" multiple required>
                            <option value="read">Customer</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Client</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function () {
        // Initialize DataTable
        const clientsTable = $('#clientsTable').DataTable({
            ajax: '/api/clients', // Replace with your API endpoint
            columns: [
                { data: 'id' },
                { data: 'name' },
                { data: 'scopes', render: data => data.join(', ') },
                {
                    data: null,
                    render: () => '<button class="btn btn-danger btn-sm">Delete</button>'
                }
            ]
        });

        // Handle Add Client Form Submission
        $('#addClientForm').on('submit', function (e) {
            e.preventDefault();

            const formData = $(this).serialize();

            $.ajax({
                url: '/auth/register/client', // Replace with your API endpoint
                method: 'POST',
                data: formData,
                success: function (response) {
                    $('#addClientModal').modal('hide');
                    clientsTable.ajax.reload(); // Reload the table
                    alert('Client created successfully!');
                },
                error: function (xhr) {
                    alert('Error: ' + xhr.responseText);
                }
            });
        });

        // Sidebar toggle
        $('#menu-toggle').on('click', function () {
            $('#wrapper').toggleClass('toggled');
        });
    });
</script>
</body>
</html>
