<?php
require_once 'db.php';
require_once 'draw_functions.php';

/**
 * Check if a phone number exists for the current draw week
 * @param PDO $pdo Database connection
 * @param string $phone The phone number to check
 * @return array Result with status and winner information if found
 */
function checkWinnerForCurrentWeek($pdo, $phone) {
    try {
        // Clean the phone number (remove spaces, dashes, parentheses)
        $cleanPhone = preg_replace('/[\s\-\(\)]/', '', $phone);
        
        // Get the current draw week
        $currentDraw = getCurrentDrawWeek($pdo);
        
        if (!$currentDraw) {
            return [
                'status' => 'error',
                'message' => 'No current draw week found',
                'found' => false
            ];
        }
        
        // Search for the phone number in the current draw week
        $stmt = $pdo->prepare("
            SELECT w.*, d.date as draw_date 
            FROM winners w 
            LEFT JOIN draw_dates d ON w.draw_week = d.id 
            WHERE w.draw_week = ? AND (
                REPLACE(REPLACE(REPLACE(w.phone, ' ', ''), '-', ''), '(', '') = ? OR
                REPLACE(REPLACE(REPLACE(w.phone, ' ', ''), '-', ''), '(', '') LIKE ? OR
                w.phone LIKE ? OR
                w.phone = ?
            )
        ");
        
        $searchPatterns = [
            $cleanPhone,                    // Exact match after cleaning
            $cleanPhone . '%',              // Starts with cleaned number
            '%' . $phone . '%',             // Contains original number
            $phone                          // Exact original match
        ];
        
        $stmt->execute([$currentDraw['id'], ...$searchPatterns]);
        $winner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($winner) {
            return [
                'status' => 'success',
                'found' => true,
                'message' => 'Winner found for current draw week!',
                'winner' => [
                    'id' => $winner['id'],
                    'name' => $winner['name'],
                    'phone' => $winner['phone'],
                    'draw_week' => $winner['draw_week'],
                    'draw_date' => $winner['draw_date'],
                    'is_claimed' => $winner['is_claimed'],
                    'created_at' => $winner['createdAt']
                ],
                'current_draw' => $currentDraw
            ];
        } else {
            return [
                'status' => 'success',
                'found' => false,
                'message' => 'Phone number not found for current draw week',
                'current_draw' => $currentDraw
            ];
        }
        
    } catch (PDOException $e) {
        error_log("Error checking winner: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage(),
            'found' => false
        ];
    }
}

/**
 * Get all winners for the current draw week
 * @param PDO $pdo Database connection
 * @return array Array of winners for current week
 */
function getCurrentWeekWinners($pdo) {
    try {
        $currentDraw = getCurrentDrawWeek($pdo);
        
        if (!$currentDraw) {
            return [];
        }
        
        $stmt = $pdo->prepare("
            SELECT w.*, d.date as draw_date 
            FROM winners w 
            LEFT JOIN draw_dates d ON w.draw_week = d.id 
            WHERE w.draw_week = ? 
            ORDER BY w.createdAt ASC
        ");
        
        $stmt->execute([$currentDraw['id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error getting current week winners: " . $e->getMessage());
        return [];
    }
}

/**
 * Search winners by phone number across all weeks (for admin use)
 * @param PDO $pdo Database connection
 * @param string $phone The phone number to search
 * @return array Search results
 */
function searchWinnerByPhone($pdo, $phone) {
    try {
        $cleanPhone = preg_replace('/[\s\-\(\)]/', '', $phone);
        
        $stmt = $pdo->prepare("
            SELECT w.*, d.date as draw_date, 
                   CASE 
                       WHEN d.date >= NOW() THEN 'upcoming'
                       WHEN d.date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'recent'
                       ELSE 'past'
                   END as week_status
            FROM winners w 
            LEFT JOIN draw_dates d ON w.draw_week = d.id 
            WHERE (
                REPLACE(REPLACE(REPLACE(w.phone, ' ', ''), '-', ''), '(', '') = ? OR
                REPLACE(REPLACE(REPLACE(w.phone, ' ', ''), '-', ''), '(', '') LIKE ? OR
                w.phone LIKE ? OR
                w.phone = ?
            )
            ORDER BY d.date DESC, w.createdAt DESC
        ");
        
        $searchPatterns = [
            $cleanPhone,
            $cleanPhone . '%',
            '%' . $phone . '%',
            $phone
        ];
        
        $stmt->execute($searchPatterns);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error searching winner: " . $e->getMessage());
        return [];
    }
}
?>
