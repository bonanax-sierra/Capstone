<?php include 'includes/db.php'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Registration - Adolescent Risk System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f1f3f6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .register-card {
            height: 100%;
            max-height: 650px;
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border-radius: 12px;
            background-color: #ffffff;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        .btn-lg {
            width: 100%;
        }

        .form-label {
            font-weight: 500;
        }
    </style>
</head>

<body>

    <div class="register-card">
        <h3 class="text-center mb-4">Admin Registration</h3>

        <form method="POST" action="includes/register_admin.php">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Admin Token</label>
                <input type="text" name="admin_token" class="form-control" required>
            </div>
            <button type="submit" name="register_admin" class="btn btn-dark btn-lg">Register Admin</button>
            <a href="admin_login.php" class="btn btn-outline-secondary btn-lg mt-2 w-100">← Back to Login</a>
        </form>
    </div>

    <!-- ❌ Duplicate Error Modal -->
    <div class="modal fade" id="duplicateModal" tabindex="-1" aria-labelledby="duplicateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="duplicateModalLabel">Registration Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    The username or email is already taken. Please choose a different one.
                </div>
            </div>
        </div>
    </div>

    <!-- 🔒 Invalid Token Modal -->
    <div class="modal fade" id="tokenModal" tabindex="-1" aria-labelledby="tokenModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-warning">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="tokenModalLabel">Invalid Token</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    The admin token you entered is incorrect.
                </div>
            </div>
        </div>
    </div>


    <?php if (isset($_GET['error'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                <?php if ($_GET['error'] === 'duplicate'): ?>
                    var dupModal = new bootstrap.Modal(document.getElementById('duplicateModal'));
                    dupModal.show();
                <?php elseif ($_GET['error'] === 'token'): ?>
                    var tokenModal = new bootstrap.Modal(document.getElementById('tokenModal'));
                    tokenModal.show();
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>