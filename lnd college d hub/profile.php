<?php
/**
 * User Profile Management Page
 */
require __DIR__ . '/config.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/encryption.php';
require __DIR__ . '/includes/college_features.php';

requireLogin();

$user = getCurrentUser();
$db = getDB();
$profile = getUserProfile($user['id']);
$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken()) {
        $errors[] = 'Invalid security token.';
    } else {
        $action = $_POST['_action'] ?? '';

        if ($action === 'update_profile') {
            $phone = trim($_POST['phone'] ?? '');
            $gender = $_POST['gender'] ?? null;
            $city = trim($_POST['city'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            $date_of_birth = $_POST['date_of_birth'] ?? null;

            $update_data = [];
            if ($phone !== '') {
                $update_data['phone'] = $phone;
            }
            if ($gender) {
                $update_data['gender'] = $gender;
            }
            if ($city !== '') {
                $update_data['city'] = $city;
            }
            if ($bio !== '') {
                $update_data['bio'] = $bio;
            }
            if ($date_of_birth) {
                $update_data['date_of_birth'] = $date_of_birth;
            }

            if (!empty($update_data)) {
                updateUserProfile($user['id'], $update_data);
                $profile = getUserProfile($user['id']);
                $message = 'Profile updated successfully.';
            }
        }

        if ($action === 'upload_picture') {
            if (!isset($_FILES['profile_picture'])) {
                $errors[] = 'No file selected.';
            } else {
                $file = $_FILES['profile_picture'];
                $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $max_size = 5 * 1024 * 1024; // 5MB

                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    $errors[] = 'File upload error.';
                } elseif (!is_uploaded_file($file['tmp_name'])) {
                    $errors[] = 'Invalid upload request.';
                } elseif ($file['size'] > $max_size) {
                    $errors[] = 'File size exceeds 5MB limit.';
                } else {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed_extensions, true)) {
                        $errors[] = 'Invalid file extension. Only JPG, PNG, GIF, WebP allowed.';
                    }

                    $detected_mime = null;
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    if ($finfo) {
                        $detected_mime = finfo_file($finfo, $file['tmp_name']);
                        finfo_close($finfo);
                    }

                    if (!$detected_mime || !in_array($detected_mime, $allowed_mimes, true)) {
                        $errors[] = 'Invalid file type. Only JPG, PNG, GIF, WebP allowed.';
                    }
                }

                if (empty($errors)) {
                    $upload_dir = UPLOADS_PATH . '/profiles';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
                    $filepath = $upload_dir . '/' . $filename;

                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        chmod($filepath, 0644);
                        updateProfilePicture($user['id'], $filename);
                        $profile = getUserProfile($user['id']);
                        $message = 'Profile picture updated successfully.';
                    } else {
                        $errors[] = 'Failed to save file.';
                    }
                }
            }
        }
    }
}

$csrf_token = generateCsrfToken();
$profile_pic_url = getUserProfilePictureUrl($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
    <div class="wrapper">
        <aside class="sidebar">
            <div class="sidebar-header"><h2><?php echo APP_NAME; ?></h2></div>
            <nav class="sidebar-nav">
                <a href="/index.php" class="nav-item"><span class="icon">🏠</span> Home</a>
                <a href="profile.php" class="nav-item active"><span class="icon">👤</span> My Profile</a>
                <a href="/logout.php" class="nav-item"><span class="icon">🚪</span> Logout</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-title"><h1>My Profile</h1></div>
                <div class="header-user"><a href="/logout.php" class="btn-logout">Logout</a></div>
            </header>

            <div class="content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>

                <div class="card">
                    <h2>Profile Picture</h2>
                    <div style="display:grid;grid-template-columns:200px 1fr;gap:2rem;align-items:start;">
                        <div style="text-align:center;">
                            <img src="<?php echo htmlspecialchars($profile_pic_url, ENT_QUOTES, 'UTF-8'); ?>" alt="Profile" style="width:180px;height:180px;border-radius:50%;border:3px solid #ddd;object-fit:cover;">
                            <p style="margin-top:0.5rem;color:#666;font-size:0.9rem;"><?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="profile_picture">Upload Profile Picture</label>
                                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" required>
                                <small style="color:#666;">Max 5MB. Allowed: JPG, PNG, GIF, WebP</small>
                            </div>
                            <input type="hidden" name="_action" value="upload_picture">
                            <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="btn btn-primary">Upload Picture</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <h2>Profile Information</h2>
                    <form method="POST" class="form">
                        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>

                        <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($profile['city'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">Select</option>
                                    <option value="male" <?php echo ($profile['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($profile['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($profile['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($profile['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($profile['bio'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <input type="hidden" name="_action" value="update_profile">
                        <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-primary">Save Profile</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <style>
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
