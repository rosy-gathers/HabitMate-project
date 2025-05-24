<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['session_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = (int)$_GET['session_id'];

// Fetch active session
$stmt = $pdo->prepare("SELECT * FROM active_sessions WHERE id = ? AND user_id = ?");
$stmt->execute([$session_id, $user_id]);
$session = $stmt->fetch();

if (!$session) {
    header("Location: dashboard.php");
    exit();
}

// Handle session completion or termination
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['complete'])) {
        $points_earned = $session['duration'] * 10; // 10 points per minute

        // Move to sessions table
        $stmt = $pdo->prepare("INSERT INTO sessions (user_id, type, duration, points) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $session['type'], $session['duration'], $points_earned]);

        // Update total points
        $stmt = $pdo->prepare("UPDATE points SET total_points = total_points + ? WHERE user_id = ?");
        $stmt->execute([$points_earned, $user_id]);

        // Delete active session
        $stmt = $pdo->prepare("DELETE FROM active_sessions WHERE id = ?");
        $stmt->execute([$session_id]);

        header("Location: dashboard.php?success=Session completed! $points_earned points earned.");
        exit();
    } elseif (isset($_POST['end'])) {
        // Delete active session without awarding points
        $stmt = $pdo->prepare("DELETE FROM active_sessions WHERE id = ?");
        $stmt->execute([$session_id]);

        header("Location: dashboard.php?message=Session ended without completion.");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HabitMate - Session Timer</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/script.js"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md text-center">
        <h2 class="text-2xl font-bold mb-4"><?php echo ucfirst($session['type']); ?> Session</h2>
        <p class="text-lg mb-4">Time Remaining: <span id="timer"><?php echo $session['duration']; ?>:00</span></p>
        <form method="POST" action="">
            <button type="submit" name="complete" id="completeBtn" class="w-full bg-green-500 text-white p-2 rounded hover:bg-green-600 mb-2" disabled>Complete Session</button>
            <button type="submit" name="end" class="w-full bg-red-500 text-white p-2 rounded hover:bg-red-600">End Session</button>
        </form>
        <script>
            const duration = <?php echo $session['duration'] * 60; ?>; // Convert minutes to seconds
            startTimer(duration, <?php echo $session_id; ?>);
        </script>
    </div>
</body>
</html>