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

// Fetch total points
$stmt = $pdo->prepare("SELECT total_points FROM points WHERE user_id = ?");
$stmt->execute([$user_id]);
$points = $stmt->fetch()['total_points'];

// Fetch recent sessions
$stmt = $pdo->prepare("SELECT * FROM sessions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$sessions = $stmt->fetchAll();

// Handle session creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_session'])) {
    $type = $_POST['type'];
    $duration = (int)$_POST['duration'];

    // Store in active_sessions
    $stmt = $pdo->prepare("INSERT INTO active_sessions (user_id, type, duration) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $type, $duration]);

    $session_id = $pdo->lastInsertId();
    header("Location: timer.php?session_id=$session_id");
    exit();
}

// Handle BMI calculation
$bmi = null;
$bmi_category = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['calculate_bmi'])) {
    $height = (float)$_POST['height'];
    $weight = (float)$_POST['weight'];

    // Update user profile
    $stmt = $pdo->prepare("UPDATE users SET height = ?, weight = ? WHERE id = ?");
    $stmt->execute([$height, $weight, $user_id]);

    // Calculate BMI
    $bmi = $weight / (($height / 100) ** 2);
    if ($bmi < 18.5) {
        $bmi_category = "Underweight";
    } elseif ($bmi < 25) {
        $bmi_category = "Normal weight";
    } elseif ($bmi < 30) {
        $bmi_category = "Overweight";
    } else {
        $bmi_category = "Obese";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HabitMate - Dashboard</title>
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
        <h2 class="text-2xl font-bold mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Points Summary -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-semibold mb-2">Total Points</h3>
                <p class="text-3xl"><?php echo $points; ?></p>
            </div>
            <!-- BMI Calculator -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-semibold mb-2">BMI Calculator</h3>
                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="block text-gray-700">Height (cm)</label>
                        <input type="number" name="height" step="0.1" class="w-full p-2 border rounded" value="<?php echo $user['height'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700">Weight (kg)</label>
                        <input type="number" name="weight" step="0.1" class="w-full p-2 border rounded" value="<?php echo $user['weight'] ?? ''; ?>" required>
                    </div>
                    <button type="submit" name="calculate_bmi" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Calculate BMI</button>
                </form>
                <?php if ($bmi): ?>
                    <p class="mt-4">Your BMI: <?php echo round($bmi, 1); ?> (<?php echo $bmi_category; ?>)</p>
                <?php endif; ?>
            </div>
            <!-- Create Session -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-xl font-semibold mb-2">Create Session</h3>
                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="block text-gray-700">Type</label>
                        <select name="type" class="w-full p-2 border rounded" required>
                            <option value="study">Study</option>
                            <option value="exercise">Exercise</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700">Duration (minutes)</label>
                        <input type="number" name="duration" class="w-full p-2 border rounded" required>
                    </div>
                    <button type="submit" name="create_session" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">Start Session</button>
                </form>
            </div>
        </div>
        <!-- Recent Sessions -->
        <div class="bg-white p-6 rounded-lg shadow mt-6">
            <h3 class="text-xl font-semibold mb-2">Recent Sessions</h3>
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="text-left p-2">Type</th>
                        <th class="text-left p-2">Duration</th>
                        <th class="text-left p-2">Points</th>
                        <th class="text-left p-2">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session): ?>
                        <tr>
                            <td class="p-2"><?php echo ucfirst($session['type']); ?></td>
                            <td class="p-2"><?php echo $session['duration']; ?> min</td>
                            <td class="p-2"><?php echo $session['points']; ?></td>
                            <td class="p-2"><?php echo $session['created_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>