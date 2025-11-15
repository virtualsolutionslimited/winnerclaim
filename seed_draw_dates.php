<?php
/**
 * Seeder function for draw_dates table
 * Populates the table with every Sunday at 6pm from November 16, 2025 to March 22, 2026
 */

function seedDrawDates($pdo) {
    try {
        // Clear existing data (optional - comment out if you want to keep existing data)
        $pdo->exec("TRUNCATE TABLE draw_dates");
        
        // Start date: Sunday, November 16, 2025 at 6:00 PM
        $startDate = new DateTime('2025-11-16 18:00:00');
        
        // End date: Sunday, March 22, 2026 at 6:00 PM
        $endDate = new DateTime('2026-03-22 18:00:00');
        
        // Prepare the insert statement
        $stmt = $pdo->prepare("INSERT INTO draw_dates (date, createdAt) VALUES (?, NOW())");
        
        $currentDate = clone $startDate;
        $insertedCount = 0;
        
        echo "Starting to seed draw dates from " . $startDate->format('Y-m-d H:i:s') . " to " . $endDate->format('Y-m-d H:i:s') . "\n";
        
        while ($currentDate <= $endDate) {
            // Insert the current Sunday date
            $stmt->execute([$currentDate->format('Y-m-d H:i:s')]);
            $insertedCount++;
            
            echo "Inserted: " . $currentDate->format('Y-m-d H:i:s') . "\n";
            
            // Move to next Sunday (add 7 days)
            $currentDate->modify('+7 days');
        }
        
        echo "Successfully inserted $insertedCount draw dates.\n";
        return $insertedCount;
        
    } catch (PDOException $e) {
        echo "Error seeding draw dates: " . $e->getMessage() . "\n";
        return false;
    }
}

// Example usage:
/*
// Database connection
$host = '127.0.0.1';
$dbname = 'raffle';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Run the seeder
    $result = seedDrawDates($pdo);
    
    if ($result) {
        echo "Seeder completed successfully!\n";
    }
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
*/
?>
