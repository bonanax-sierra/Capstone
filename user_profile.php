<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: index.php");
    exit;
}

include 'includes/db.php';

$user_id = $_SESSION['user_id'];

// Profile picture upload handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    $uploadDir = 'uploads/profile_pictures/';
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if ($file['error'] === 0) {
        if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxSize) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newName = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $path = $uploadDir . $newName;

            if (move_uploaded_file($file['tmp_name'], $path)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                $stmt->execute([$newName, $user_id]);
                $success = "Profile picture updated.";
            } else {
                $error = "Upload failed.";
            }
        } else {
            $error = "Only JPG, PNG, GIF under 2MB allowed.";
        }
    } else {
        $error = "Upload error occurred.";
    }
}

// Get user data
$stmt = $pdo->prepare("SELECT username, email, profile_picture FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$profilePic = (!empty($user['profile_picture']) && file_exists("uploads/profile_pictures/" . $user['profile_picture']))
    ? "uploads/profile_pictures/" . $user['profile_picture']
    : "uploads/profile_pictures/default_user.png";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/sidebar.css" />

    <style>
        body {
            background-color: #f1f3f6;
            display: flex;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 40px;
        }

        .dashboard-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 30px;
            color: #343a40;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .profile-box {
            padding: 20px;
            border-radius: 10px;
            background-color: #ffffff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .profile-box h5 {
            margin-bottom: 10px;
            color: #0d6efd;
        }

        .profile-box p {
            margin-bottom: 5px;
            color: #495057;
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #0d6efd;
            margin-bottom: 15px;
            transition: 0.3s;
        }

        .profile-picture:hover {
            opacity: 0.8;
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="dashboard-title"><i class="bi bi-speedometer2 me-2"></i>User Dashboard</div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-check-circle me-2"></i>Completed Assessments</h5>
                        <p class="card-text fs-4">3</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-hourglass-split me-2"></i>Pending Assessments</h5>
                        <p class="card-text fs-4">1</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-bar-chart-line me-2"></i>Risk Level</h5>
                        <p class="card-text fs-4">Moderate</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Snapshot -->
        <div class="row">
            <div class="col-lg-6">
                <div class="profile-box text-center">
                    <form method="POST" enctype="multipart/form-data" id="profileForm">
                        <!-- Hidden File Input -->
                        <input type="file" name="profile_picture" id="fileInput" class="d-none" accept="image/*" onchange="document.getElementById('profileForm').submit()" />

                        <!-- Clickable Image -->
                        <label for="fileInput" style="cursor: pointer;">
                            <img src="<?= file_exists($profilePic) ? htmlspecialchars($profilePic) : 'uploads/profile_pictures/default_user.png' ?>"
                                alt="Profile Picture"
                                class="profile-picture"
                                title="Click to upload a new profile picture" />
                        </label>
                    </form>

                    <h5><i class="bi bi-person-circle me-2"></i>Profile Overview</h5>
                    <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p><strong>Role:</strong> User</p>
                </div>

            </div>

            <div class="col-lg-6">
                <div class="profile-box">
                    <h5><i class="bi bi-journal-text me-2"></i>Next Steps</h5>
                    <p>👉 <a href="take_assessment.php">Take another assessment</a></p>
                    <p>📊 <a href="my_results.php">View your results</a></p>
                    <p>👤 <a href="profile.php">Manage your profile</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header <?php echo isset($success) ? 'bg-success' : 'bg-danger'; ?>">
                    <h5 class="modal-title text-white"><?php echo isset($success) ? 'Success' : 'Error'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <p><?php echo $success ?? $error; ?></p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary w-100" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($success) || isset($error)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var feedbackModal = new bootstrap.Modal(document.getElementById('feedbackModal'));
                feedbackModal.show();
            });
        </script>
    <?php endif; ?>

</body>
</html>