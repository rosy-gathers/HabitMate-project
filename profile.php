<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $height = (float)$_POST['height'];
        $weight = (float)$_POST['weight'];

        $stmt = $pdo->prepare("UPDATE users SET height = ?, weight = ? WHERE id = ?");
        $stmt->execute([$height, $weight, $user_id]);

        header("Location: profile.php?success=Profile updated!");
        exit();
    } elseif (isset($_POST['upload_picture']) && isset($_FILES['profile_picture'])) {
        $file = $_FILES['profile_picture'];
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

        if (in_array(strtolower($ext), $allowed) && $file['size'] <= 2000000) { // 2MB limit
            $filename = "uploads/" . $user_id . "_" . time() . "." . $ext;
            if (move_uploaded_file($file['tmp_name'], $filename)) {
                // Delete old picture if not default
                if ($user['profile_picture'] != 'uploads/default.jpg') {
                    unlink($user['profile_picture']);
                }
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$filename, $user_id]);
                header("Location: profile.php?success=Profile picture updated!");
                exit();
            } else {
                $error = "Failed to upload picture.";
            }
        } else {
            $error = "Invalid file type or size. Use JPG/PNG under 2MB.";
        }
    } elseif (isset($_POST['delete_picture'])) {
        if ($user['profile_picture'] != 'uploads/default.jpg') {
            unlink($user['profile_picture']);
            $stmt = $pdo->prepare("UPDATE users SET profile_picture = 'uploads/default.jpg' WHERE id = ?");
            $stmt->execute([$user_id]);
            header("Location: profile.php?success=Profile picture reset to default!");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HabitMate - Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-500 p-4 text-white">
        <div class="container mx-auto flex justify-between">
            <h1 class="text-xl font-bold">HabitMate</h1>
            <div>
                <a href="dashboard.php" class="mr-4">Dashboard</a>
                <a href="profile.php" class="mr-4">Profile</a>
                <a href="leaderboard.php" class="mr-4">Leaderboard</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
    <div class="container mx-auto p-6">
        <h2 class="text-2xl font-bold mb-4">Update Profile</h2>
        <?php if (isset($_GET['success'])) echo "<p class='text-green-500'>{$_GET['success']}</p>"; ?>
        <?php if (isset($error)) echo "<p class='text-red-500'>$error</p>"; ?>
        <div class="bg-white p-6 rounded-lg shadow w-full max-w-md">
            <!-- Profile Picture -->
            <div class="mb-6 text-center">
                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="w-32 h-32 rounded-full mx-auto mb-4 object-cover">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="block text-gray-700">Upload New Picture</label>
                        <input type="file" name="profile_picture" accept=".jpg,.jpeg,.png" class="w-full p-2 border rounded">
                    </div>
                    <button type="submit" name="upload_picture" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600 mb-2">Upload Picture</button>
                </form>
                <form method="POST">
                    <button type="submit" name="delete_picture" class="w-full bg-red-500 text-white p-2 rounded hover:bg-red-600">Delete Picture</button>
                </form>
            </div>
            <!-- Height/Weight -->
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700">Height (cm)</label>
                    <input type="number" name="height" step="0.1" class="w-full p-2 border rounded" value="<?php echo $user['height'] ?? ''; ?>" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Weight (kg)</label>
                    <input type="number" name="weight" step="0.1" class="w-full p-2 border rounded" value="<?php echo $user['weight'] ?? ''; ?>" required>
                </div>
                <button type="submit" name="update_profile" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Update Profile</button>
            </form>
        </div>
    </div>
</body>
</html>