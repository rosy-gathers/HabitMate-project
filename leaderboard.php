<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch top 10 users by points
$stmt = $pdo->query("SELECT u.username, p.total_points FROM users u JOIN points p ON u.id = p.user_id ORDER BY p.total_points DESC LIMIT 10");
$top_users = $stmt->fetchAll();

// Handle user search
$search_result = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_username'])) {
    $search_username = $_POST['search_username'];
    $stmt = $pdo->prepare("SELECT u.username, p.total_points FROM users u JOIN points p ON u.id = p.user_id WHERE u.username = ?");
    $stmt->execute([$search_username]);
    $search_result = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HabitMate - Leaderboard</title>
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
        <h2 class="text-2xl font-bold mb-4">Leaderboard</h2>
        <!-- Search Form -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h3 class="text-xl font-semibold mb-2">Search User</h3>
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700">Username</label>
                    <input type="text" name="search_username" class="w-full p-2 border rounded" required>
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Search</button>
            </form>
            <?php if ($search_result): ?>
                <p class="mt-4"><?php echo htmlspecialchars($search_result['username']); ?>: <?php echo $search_result['total_points']; ?> points</p>
            <?php elseif (isset($_POST['search_username'])): ?>
                <p class="mt-4 text-red-500">User not found</p>
            <?php endif; ?>
        </div>
        <!-- Top Users -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-xl font-semibold mb-2">Top 10 Users</h3>
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="text-left p-2">Rank</th>
                        <th class="text-left p-2">Username</th>
                        <th class="text-left p-2">Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_users as $index => $user): ?>
                        <tr>
                            <td class="p-2"><?php echo $index + 1; ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="p-2"><?php echo $user['total_points']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>