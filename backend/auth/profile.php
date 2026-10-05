<?php
/**
 * Admin Profile & Password Management
 * Book Banko
 */
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

requireLogin();

$pageTitle = 'Admin Profile';
$pageSubtitle = 'Manage your account settings, name, email, and password';
$db = Database::getConnection();
$adminId = $_SESSION['admin_user']['id'];

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf)) {
        flash('error', 'Security token expired. Please try again.');
    } elseif ($action === 'update_info') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($fullName) || empty($email)) {
            flash('error', 'Name and email are required fields.');
        } else {
            try {
                $stmt = $db->prepare("UPDATE admins SET full_name = :name, email = :email WHERE id = :id");
                $stmt->execute([':name' => $fullName, ':email' => $email, ':id' => $adminId]);
                $_SESSION['admin_user']['full_name'] = $fullName;
                $_SESSION['admin_user']['email'] = $email;
                flash('success', 'Profile updated successfully.');
            } catch (PDOException $e) {
                flash('error', 'Update error: ' . $e->getMessage());
            }
        }
    } elseif ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (empty($currentPass) || empty($newPass)) {
            flash('error', 'Please fill in all password fields.');
        } elseif ($newPass !== $confirmPass) {
            flash('error', 'New password and confirmation do not match.');
        } elseif (strlen($newPass) < 6) {
            flash('error', 'New password must be at least 6 characters long.');
        } else {
            $stmt = $db->prepare("SELECT password_hash FROM admins WHERE id = :id");
            $stmt->execute([':id' => $adminId]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($currentPass, $admin['password_hash'])) {
                $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                $update = $db->prepare("UPDATE admins SET password_hash = :hash WHERE id = :id");
                $update->execute([':hash' => $newHash, ':id' => $adminId]);
                flash('success', 'Password changed successfully.');
            } else {
                flash('error', 'Current password is incorrect.');
            }
        }
    }
    header('Location: ' . url('auth/profile.php'));
    exit;
}

// Fetch current details
$stmt = $db->prepare("SELECT * FROM admins WHERE id = :id");
$stmt->execute([':id' => $adminId]);
$admin = $stmt->fetch();

include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <!-- Account Information -->
    <div class="col-lg-6 mb-4">
        <div class="bb-card">
            <div class="bb-card-header">
                <h5><i class="bi bi-person-fill text-primary me-2"></i>Personal Details</h5>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <input type="hidden" name="action" value="update_info">

                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control bg-light" id="username" value="<?= htmlspecialchars($admin['username']) ?>" disabled>
                    <small class="text-muted">Username cannot be changed.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="full_name">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($admin['full_name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
                </div>

                <button type="submit" class="btn btn-bb-primary">
                    <i class="bi bi-save me-1"></i>Save Changes
                </button>
            </form>
        </div>
    </div>

    <!-- Change Password -->
    <div class="col-lg-6 mb-4">
        <div class="bb-card">
            <div class="bb-card-header">
                <h5><i class="bi bi-shield-lock-fill text-primary me-2"></i>Change Password</h5>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                <input type="hidden" name="action" value="change_password">

                <div class="mb-3">
                    <label class="form-label" for="current_password">Current Password</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" placeholder="At least 6 characters" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn btn-bb-primary">
                    <i class="bi bi-key-fill me-1"></i>Update Password
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
