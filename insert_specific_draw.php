<?php
require_once 'db.php';

/**
 * One-time function to insert Sunday, November 2nd, 2025 at 6 PM draw date
 */
function insertSpecificDrawDate() {
    global $pdo;
    
    try {
        // Set the specific date: Sunday, November 2nd, 2025 at 6:00 PM
        $drawDateTime = '2025-11-02 18:00:00';
        
        // Check if this draw date already exists
        $checkStmt = $pdo->prepare("SELECT id FROM draw_dates WHERE date = ?");
        $checkStmt->execute([$drawDateTime]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "<strong>⚠️ Draw date already exists!</strong><br>";
            echo "Draw ID: " . $existing['id'] . "<br>";
            echo "Date: " . date('F j, Y \a\t g:i A', strtotime($drawDateTime));
            echo "</div>";
            return $existing['id'];
        }
        
        // Insert the new draw date
        $stmt = $pdo->prepare("
            INSERT INTO draw_dates (date, createdAt) 
            VALUES (?, NOW())
        ");
        
        $result = $stmt->execute([$drawDateTime]);
        
        if ($result) {
            $drawId = $pdo->lastInsertId();
            
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "<strong>✅ Draw date inserted successfully!</strong><br>";
            echo "Draw ID: " . $drawId . "<br>";
            echo "Date: " . date('F j, Y \a\t g:i A', strtotime($drawDateTime)) . "<br>";
            echo "Day: " . date('l', strtotime($drawDateTime));
            echo "</div>";
            
            return $drawId;
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "<strong>❌ Failed to insert draw date!</strong>";
            echo "</div>";
            return false;
        }
        
    } catch (PDOException $e) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
        echo "<strong>❌ Database Error:</strong> " . $e->getMessage();
        echo "</div>";
        return false;
    }
}

// Execute the function
$insertedId = insertSpecificDrawDate();

// Show current draw dates for reference
echo "<h3>📅 Current Draw Dates in Database:</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr><th>ID</th><th>Date & Time</th><th>Day</th><th>Formatted</th></tr>";

try {
    $stmt = $pdo->query("SELECT * FROM draw_dates ORDER BY date ASC");
    $draws = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($draws as $draw) {
        echo "<tr>";
        echo "<td>" . $draw['id'] . "</td>";
        echo "<td>" . $draw['date'] . "</td>";
        echo "<td>" . date('l', strtotime($draw['date'])) . "</td>";
        echo "<td>" . date('F j, Y \a\t g:i A', strtotime($draw['date'])) . "</td>";
        echo "</tr>";
    }
    
    if (empty($draws)) {
        echo "<tr><td colspan='4' style='text-align: center; color: #666;'>No draw dates found</td></tr>";
    }
    
} catch (PDOException $e) {
    echo "<tr><td colspan='4' style='color: red;'>Error fetching draw dates: " . $e->getMessage() . "</td></tr>";
}

echo "</table>";

// Add a note about the date
echo "<div style='background: #e2e3e5; color: #383d41; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
echo "<strong>ℹ️ Note:</strong><br>";
echo "This function inserts a draw date for <strong>Sunday, November 2nd, 2025 at 6:00 PM</strong>.<br>";
echo "This is a one-time function. After running, you can delete this file.";
echo "</div>";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Specific Draw Date</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1, h3 {
            color: #333;
        }
        table {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        th {
            background: #667eea;
            color: white;
        }
        td, th {
            padding: 12px;
            text-align: left;
        }
    </style>
</head>
<body>
    <h1>🎯 Insert Specific Draw Date</h1>
    <p>This script inserts Sunday, November 2nd, 2025 at 6:00 PM as a draw date.</p>
</body>
</html>
