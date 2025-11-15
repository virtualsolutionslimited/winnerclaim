<?php
/**
 * Functions to get current and next draw week information
 */

/**
 * Get the current draw week (next upcoming Sunday at 6pm)
 * @param PDO $pdo Database connection
 * @return array|null Draw information or null if no draws found
 */
function getCurrentDrawWeek($pdo) {
    try {
        $now = new DateTime();
        
        // Get the next draw date from today onwards
        $stmt = $pdo->prepare("
            SELECT * FROM draw_dates 
            WHERE date >= ? 
            ORDER BY date ASC 
            LIMIT 1
        ");
        $stmt->execute([$now->format('Y-m-d H:i:s')]);
        
        $draw = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($draw) {
            $drawDate = new DateTime($draw['date']);
            return formatDrawInfo($draw, $drawDate, $now);
        }
        
        return null;
        
    } catch (PDOException $e) {
        error_log("Error getting current draw week: " . $e->getMessage());
        return null;
    }
}

/**
 * Get the next draw week (the one after the current draw)
 * @param PDO $pdo Database connection
 * @return array|null Draw information or null if no draws found
 */
function getNextDrawWeek($pdo) {
    try {
        $now = new DateTime();
        
        // Get the current draw first
        $currentDraw = getCurrentDrawWeek($pdo);
        
        if ($currentDraw) {
            // Get the draw after the current one
            $stmt = $pdo->prepare("
                SELECT * FROM draw_dates 
                WHERE date > ? 
                ORDER BY date ASC 
                LIMIT 1
            ");
            $stmt->execute([$currentDraw['datetime']]);
        } else {
            // If no current draw, get the next one from today
            $stmt = $pdo->prepare("
                SELECT * FROM draw_dates 
                WHERE date >= ? 
                ORDER BY date ASC 
                LIMIT 2
            ");
            $stmt->execute([$now->format('Y-m-d H:i:s')]);
            $draws = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Return the second draw if there are at least 2
            if (count($draws) >= 2) {
                $draw = $draws[1];
                $drawDate = new DateTime($draw['date']);
                return formatDrawInfo($draw, $drawDate, $now);
            }
            return null;
        }
        
        $draw = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($draw) {
            $drawDate = new DateTime($draw['date']);
            return formatDrawInfo($draw, $drawDate, $now);
        }
        
        return null;
        
    } catch (PDOException $e) {
        error_log("Error getting next draw week: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all upcoming draws (current and future)
 * @param PDO $pdo Database connection
 * @param int $limit Maximum number of draws to return
 * @return array Array of draw information
 */
function getUpcomingDraws($pdo, $limit = 5) {
    try {
        $now = new DateTime();
        
        // Debug: Log current time for troubleshooting
        error_log("Current time for getUpcomingDraws: " . $now->format('Y-m-d H:i:s'));
        
        // Get draws from today onwards (including today)
        $stmt = $pdo->prepare("
            SELECT * FROM draw_dates 
            WHERE date >= ? 
            ORDER BY date ASC 
            LIMIT ?
        ");
        $stmt->execute([$now->format('Y-m-d H:i:s'), $limit]);
        
        $draws = [];
        while ($draw = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $drawDate = new DateTime($draw['date']);
            $draws[] = formatDrawInfo($draw, $drawDate, $now);
        }
        
        // Debug: Log how many draws were found
        error_log("Found " . count($draws) . " upcoming draws");
        
        return $draws;
        
    } catch (PDOException $e) {
        error_log("Error getting upcoming draws: " . $e->getMessage());
        return [];
    }
}

/**
 * Get the most recent past draw
 * @param PDO $pdo Database connection
 * @return array|null Draw information or null if no draws found
 */
function getLatestPastDraw($pdo) {
    try {
        $now = new DateTime();
        
        $stmt = $pdo->prepare("
            SELECT * FROM draw_dates 
            WHERE date < ? 
            ORDER BY date DESC 
            LIMIT 1
        ");
        $stmt->execute([$now->format('Y-m-d H:i:s')]);
        
        $draw = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($draw) {
            $drawDate = new DateTime($draw['date']);
            return formatDrawInfo($draw, $drawDate, $now);
        }
        
        return null;
        
    } catch (PDOException $e) {
        error_log("Error getting latest past draw: " . $e->getMessage());
        return null;
    }
}

/**
 * Helper function to format draw information
 * @param array $draw Raw draw data from database
 * @param DateTime $drawDate DateTime object for the draw
 * @param DateTime $now Current DateTime
 * @return array Formatted draw information
 */
function formatDrawInfo($draw, $drawDate, $now) {
    $diff = $now->diff($drawDate);
    
    return [
        'id' => $draw['id'],
        'date' => $drawDate->format('Y-m-d'),
        'time' => $drawDate->format('H:i'),
        'datetime' => $drawDate->format('Y-m-d H:i:s'),
        'formatted' => $drawDate->format('F j, Y \a\t g:i A'),
        'day_name' => $drawDate->format('l'),
        'short_date' => $drawDate->format('M j, Y'),
        'days_until' => $diff->days,
        'hours_until' => $diff->h,
        'minutes_until' => $diff->i,
        'is_today' => $now->format('Y-m-d') === $drawDate->format('Y-m-d'),
        'is_past' => $drawDate < $now,
        'status' => getDrawStatus($drawDate, $now)
    ];
}

/**
 * Get the status of a draw
 * @param DateTime $drawDate Draw date
 * @param DateTime $now Current date
 * @return string Status description
 */
function getDrawStatus($drawDate, $now) {
    if ($drawDate < $now) {
        return 'completed';
    } elseif ($drawDate->format('Y-m-d') === $now->format('Y-m-d')) {
        if ($drawDate->format('H:i') <= $now->format('H:i')) {
            return 'completed';
        } else {
            return 'today';
        }
    } elseif ($drawDate->diff($now)->days === 1) {
        return 'tomorrow';
    } else {
        return 'upcoming';
    }
}

/**
 * Get human-readable time until draw
 * @param array $draw Draw information
 * @return string Human readable time
 */
function getTimeUntil($draw) {
    if ($draw['is_past']) {
        return 'Draw completed';
    }
    
    if ($draw['days_until'] === 0) {
        if ($draw['hours_until'] === 0) {
            return $draw['minutes_until'] . ' minutes';
        } else {
            return $draw['hours_until'] . ' hours, ' . $draw['minutes_until'] . ' minutes';
        }
    } elseif ($draw['days_until'] === 1) {
        return 'Tomorrow at ' . date('g:i A', strtotime($draw['time']));
    } else {
        return $draw['days_until'] . ' days';
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
    
    // Get current draw week
    $currentDraw = getCurrentDrawWeek($pdo);
    if ($currentDraw) {
        echo "Current Draw: " . $currentDraw['formatted'] . "\n";
        echo "Time until: " . getTimeUntil($currentDraw) . "\n";
        echo "Status: " . $currentDraw['status'] . "\n";
    }
    
    // Get next draw week
    $nextDraw = getNextDrawWeek($pdo);
    if ($nextDraw) {
        echo "Next Draw: " . $nextDraw['formatted'] . "\n";
        echo "Time until: " . getTimeUntil($nextDraw) . "\n";
    }
    
    // Get upcoming draws
    $upcoming = getUpcomingDraws($pdo, 3);
    foreach ($upcoming as $draw) {
        echo "- " . $draw['formatted'] . " (" . getTimeUntil($draw) . ")\n";
    }
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
*/
?>
