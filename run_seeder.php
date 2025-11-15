<?php
/**
 * Web interface to run the draw dates seeder
 */

// Include the seeder function
require_once 'seed_draw_dates.php';

// Database configuration
$host = '127.0.0.1';
$dbname = 'raffle';
$username = 'root';
$password = '';

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_seeder'])) {
    try {
        // Create database connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Run the seeder
        $result = seedDrawDates($pdo);
        
        if ($result !== false) {
            $message = "Successfully inserted $result draw dates!";
        } else {
            $error = "Failed to insert draw dates. Check console output for details.";
        }
        
    } catch (PDOException $e) {
        $error = "Database connection failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draw Dates Seeder</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #2196F3;
        }
        .warning {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }
        .success {
            background: #d4edda;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            width: 100%;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn:active {
            background-color: #004085;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Draw Dates Seeder</h1>
        
        <div class="info">
            <strong>This will insert draw dates for every Sunday at 6:00 PM:</strong><br>
            From: November 16, 2025<br>
            To: March 22, 2026
        </div>
        
        <div class="warning">
            <strong>⚠️ Warning:</strong> This will truncate (clear) the existing draw_dates table before inserting new data.
        </div>
        
        <?php if ($message): ?>
            <div class="success">
                ✅ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">
                ❌ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <button type="submit" name="run_seeder" class="btn" onclick="return confirm('Are you sure you want to run the seeder? This will clear existing data.')">
                🚀 Run Draw Dates Seeder
            </button>
        </form>
        
        <div style="margin-top: 30px;">
            <h3>What this seeder does:</h3>
            <ul>
                <li>Clears all existing data from draw_dates table</li>
                <li>Inserts Sunday dates from Nov 16, 2025 to Mar 22, 2026</li>
                <li>All dates are set to 6:00 PM (18:00:00)</li>
                <li>Total of 18 draw dates will be inserted</li>
            </ul>
        </div>
    </div>
</body>
</html>
