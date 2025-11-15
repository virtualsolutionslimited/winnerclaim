<?php
require_once 'db.php';
require_once 'sms_functions.php';
require_once 'draw_functions.php';

/**
 * Normalize phone number by removing leading zero and cleaning formatting
 * @param string $phone The phone number to normalize
 * @return string The normalized phone number
 */
function normalizePhoneNumber($phone) {
    // Remove spaces, dashes, parentheses
    $cleanPhone = preg_replace('/[\s\-\(\)]/', '', $phone);
    
    // Remove leading zero if present (Ghana numbers often stored without 0 prefix)
    if (strlen($cleanPhone) > 9 && substr($cleanPhone, 0, 1) === '0') {
        return substr($cleanPhone, 1);
    }
    
    return $cleanPhone;
}

/**
 * Check if a phone number exists for the current draw week
 * @param PDO $pdo Database connection
 * @param string $phone The phone number to check
 * @return array Result with status and winner information if found
 */
function checkWinnerForCurrentWeek($pdo, $phone) {
    try {
        // Normalize the phone number (remove leading zero and formatting)
        $cleanPhone = normalizePhoneNumber($phone);
        $originalPhone = normalizePhoneNumber($phone); // Keep original for comparison
        
        // Get the current draw week
        $currentDraw = getCurrentDrawWeek($pdo);
        
        if (!$currentDraw) {
            return [
                'status' => 'error',
                'message' => 'No current draw week found',
                'found' => false
            ];
        }
        
        // Search for the phone number in the current draw week with flexible matching
        $stmt = $pdo->prepare("
            SELECT w.*, d.date as draw_date 
            FROM winners w 
            LEFT JOIN draw_dates d ON w.draw_week = d.id 
            WHERE w.draw_week = ? AND (
                REPLACE(REPLACE(REPLACE(w.phone, ' ', ''), '-', ''), '(', '') = ? OR
                REPLACE(REPLACE(REPLACE(w.phone, ' ', ''), '-', ''), '(', '') LIKE ? OR
                w.phone LIKE ? OR
                w.phone = ? OR
                -- Also check with leading zero (database might have it without zero)
                REPLACE(REPLACE(REPLACE(CONCAT('0', w.phone), ' ', ''), '-', ''), '(', '') = ? OR
                CONCAT('0', w.phone) = ?
            )
        ");
        
        $searchPatterns = [
            $cleanPhone,                    // Exact match without zero
            $cleanPhone . '%',              // Starts with
            '%' . $cleanPhone . '%',        // Contains
            $cleanPhone,                    // Direct match
            $cleanPhone,                    // Match with zero added to database value
            $cleanPhone                     // Direct match with zero
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
        $cleanPhone = normalizePhoneNumber($phone);
        
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
                w.phone = ? OR
                -- Also check with leading zero (database might have it without zero)
                REPLACE(REPLACE(REPLACE(CONCAT('0', w.phone), ' ', ''), '-', ''), '(', '') = ? OR
                CONCAT('0', w.phone) = ?
            )
            ORDER BY d.date DESC, w.createdAt DESC
        ");
        
        $searchPatterns = [
            $cleanPhone,                    // Exact match without zero
            $cleanPhone . '%',              // Starts with
            '%' . $cleanPhone . '%',        // Contains
            $cleanPhone,                    // Direct match
            $cleanPhone,                    // Match with zero added to database value
            $cleanPhone                     // Direct match with zero
        ];
        
        $stmt->execute($searchPatterns);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error searching winner: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all claims by phone number (claimed winners only)
 * @param PDO $pdo Database connection
 * @param string $phone The phone number to search
 * @return array Array of claimed winners
 */
function getClaimsByPhone($pdo, $phone) {
    try {
        // Normalize the phone number for matching
        $cleanPhone = normalizePhoneNumber($phone);
        
        $stmt = $pdo->prepare("
            SELECT w.*, d.date as draw_date,
                   CASE 
                       WHEN d.date >= NOW() THEN 'upcoming'
                       WHEN d.date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'recent'
                       ELSE 'past'
                   END as week_status
            FROM winners w 
            LEFT JOIN draw_dates d ON w.draw_week = d.id 
            WHERE w.is_claimed = 1 AND (
                REPLACE(REPLACE(REPLACE(w.phone, ' ', ''), '-', ''), '(', '') = ? OR
                REPLACE(REPLACE(REPLACE(w.phone, ' ', ''), '-', ''), '(', '') LIKE ? OR
                w.phone LIKE ? OR
                w.phone = ? OR
                -- Also check with leading zero (database might have it without zero)
                REPLACE(REPLACE(REPLACE(CONCAT('0', w.phone), ' ', ''), '-', ''), '(', '') = ? OR
                CONCAT('0', w.phone) = ?
            )
            ORDER BY d.date DESC, w.updatedAt DESC
        ");
        
        $searchPatterns = [
            $cleanPhone,                    // Exact match without zero
            $cleanPhone . '%',              // Starts with
            '%' . $cleanPhone . '%',        // Contains
            $cleanPhone,                    // Direct match
            $cleanPhone,                    // Match with zero added to database value
            $cleanPhone                     // Direct match with zero
        ];
        
        $stmt->execute($searchPatterns);
        $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'status' => 'success',
            'phone_searched' => $phone,
            'total_claims' => count($claims),
            'claims' => $claims
        ];
        
    } catch (PDOException $e) {
        error_log("Error getting claims by phone: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage(),
            'phone_searched' => $phone,
            'total_claims' => 0,
            'claims' => []
        ];
    }
}

/**
 * Create/update a claim with additional details (for current draw week)
 * @param PDO $pdo Database connection
 * @param array $claimData Array containing claim details
 * @return array Result of the claim operation
 */
function createClaim($pdo, $claimData) {
    try {
        // Get current draw week
        $currentDraw = getCurrentDrawWeek($pdo);
        
        if (!$currentDraw) {
            return [
                'status' => 'error',
                'message' => 'No current draw week found',
                'claimed' => false
            ];
        }
        
        return createClaimForDrawWeek($pdo, $claimData, $currentDraw['id']);
        
    } catch (PDOException $e) {
        error_log("Error creating claim: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage(),
            'claimed' => false
        ];
    }
}

/**
 * Create/update a claim for a specific draw week
 * @param PDO $pdo Database connection
 * @param array $claimData Array containing claim details
 * @param int $drawWeekId Specific draw week ID
 * @return array Result of the claim operation
 */
function createClaimForDrawWeek($pdo, $claimData, $drawWeekId) {
    try {
        // Normalize phone number for matching
        $phone = $claimData['phone'];
        $cleanPhone = normalizePhoneNumber($phone);
        
        // First check if winner exists for specified draw week with flexible matching
        $stmt = $pdo->prepare("
            SELECT id, name, phone, is_claimed 
            FROM winners 
            WHERE draw_week = ? AND (
                REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', '') = ? OR
                REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', '') LIKE ? OR
                phone LIKE ? OR
                phone = ? OR
                -- Also check with leading zero (database might have it without zero)
                REPLACE(REPLACE(REPLACE(CONCAT('0', phone), ' ', ''), '-', ''), '(', '') = ? OR
                CONCAT('0', phone) = ?
            )
        ");
        
        $searchPatterns = [
            $cleanPhone,                    // Exact match without zero
            $cleanPhone . '%',              // Starts with
            '%' . $cleanPhone . '%',        // Contains
            $cleanPhone,                    // Direct match
            $cleanPhone,                    // Match with zero added to database value
            $cleanPhone                     // Direct match with zero
        ];
        
        $stmt->execute([$drawWeekId, ...$searchPatterns]);
        $winner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$winner) {
            return [
                'status' => 'error',
                'message' => 'Winner not found for draw week ID ' . $drawWeekId . ' with phone number: ' . $phone,
                'claimed' => false,
                'draw_week_id' => $drawWeekId
            ];
        }
        
        // Check if already claimed
        if ($winner['is_claimed'] == 1) {
            return [
                'status' => 'error',
                'message' => 'Prize already claimed for this winner',
                'claimed' => false,
                'winner_id' => $winner['id'],
                'draw_week_id' => $drawWeekId
            ];
        }
        
        // Update the winner record with claim details
        $stmt = $pdo->prepare("
            UPDATE winners SET 
                email = ?,
                password = ?,
                photo = ?,
                ghanacard_number = ?,
                ghanacard_photo = ?,
                is_claimed = 1,
                updatedAt = NOW()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $claimData['email'] ?? null,
            $claimData['password'] ?? null,
            $claimData['photo'] ?? null,
            $claimData['ghanacard_number'] ?? null,
            $claimData['ghanacard_photo'] ?? null,
            $winner['id']
        ]);
        
        if ($result) {
            // Get draw details for response
            $drawStmt = $pdo->prepare("SELECT * FROM draw_dates WHERE id = ?");
            $drawStmt->execute([$drawWeekId]);
            $drawDetails = $drawStmt->fetch(PDO::FETCH_ASSOC);
            
            // Send success SMS notification
            $drawDate = new DateTime($drawDetails['date']);
            $formattedDate = $drawDate->format('F j, Y \a\t g:i A');
            
            $successMessage = "🎉 CONGRATULATIONS! Your prize claim has been successfully processed for " . 
                             $formattedDate . ". Your winner ID is " . $winner['id'] . 
                             ". We will contact you soon for prize collection details. Thank you!";
            
            $smsResult = sendSMS($winner['phone'], $successMessage);
            
            return [
                'status' => 'success',
                'message' => 'Claim created successfully',
                'claimed' => true,
                'winner_id' => $winner['id'],
                'draw_week_id' => $drawWeekId,
                'draw_details' => $drawDetails,
                'sms_sent' => $smsResult['status'] === 'success',
                'sms_result' => $smsResult
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to update claim details',
                'claimed' => false,
                'winner_id' => $winner['id'],
                'draw_week_id' => $drawWeekId
            ];
        }
        
    } catch (PDOException $e) {
        error_log("Error creating claim for draw week: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage(),
            'claimed' => false,
            'draw_week_id' => $drawWeekId
        ];
    }
}

/**
 * Clean and normalize phone number for comparison
 * @param string $phone Phone number to clean
 * @return string Cleaned phone number
 */
function cleanPhoneNumber($phone) {
    // Remove all non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Remove leading 0 if present (for Ghana numbers)
    if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
        $phone = substr($phone, 1);
    }
    
    // Remove +233 prefix if present
    if (strlen($phone) > 9 && substr($phone, 0, 3) === '233') {
        $phone = substr($phone, 3);
    }
    
    return $phone;
}

/**
 * Get unclaimed winners for current draw week by phone number
 * @param PDO $pdo Database connection
 * @param string $phone Phone number to check
 * @return array Result with unclaimed winner info for the phone
 */
function getUnclaimedWinnerByPhoneForCurrentDraw($pdo, $phone) {
    try {
        require_once 'draw_functions.php';
        
        // Get current draw week
        $currentDraw = getCurrentDrawWeek($pdo);
        
        if (!$currentDraw) {
            return [
                'status' => 'error',
                'message' => 'No current draw week found',
                'unclaimed_winner' => null
            ];
        }
        
        // Normalize phone number for comparison
        $cleanPhone = cleanPhoneNumber($phone);
        
        // Get unclaimed winner for this phone in current draw week
        $stmt = $pdo->prepare("
            SELECT w.*, d.date as draw_date
            FROM winners w 
            LEFT JOIN draw_dates d ON w.draw_week = d.id 
            WHERE w.draw_week = ? AND w.is_claimed = 0 AND (
                w.phone = ? OR w.phone = ? OR w.phone = ?
            )
            ORDER BY w.createdAt ASC
        ");
        
        // Try different phone formats: original, with +233, with 0 prefix
        $phoneVariants = [
            $cleanPhone,
            '+233' . $cleanPhone,
            '0' . $cleanPhone
        ];
        
        $stmt->execute([$currentDraw['id'], ...$phoneVariants]);
        $winner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($winner) {
            // Format the result
            return [
                'status' => 'success',
                'message' => 'Found unclaimed winner for this phone',
                'current_draw' => $currentDraw,
                'unclaimed_winner' => [
                    'id' => $winner['id'],
                    'name' => $winner['name'],
                    'phone' => $winner['phone'],
                    'draw_week' => $winner['draw_week'],
                    'draw_date' => $winner['draw_date'],
                    'created_at' => $winner['createdAt'],
                    'days_since_win' => calculateDaysSince($winner['createdAt'])
                ]
            ];
        } else {
            return [
                'status' => 'not_found',
                'message' => 'No unclaimed winnings found for this phone number in current draw week',
                'current_draw' => $currentDraw,
                'unclaimed_winner' => null
            ];
        }
        
    } catch (PDOException $e) {
        error_log("Error getting unclaimed winner by phone: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage(),
            'unclaimed_winner' => null
        ];
    }
}

/**
 * Get unclaimed winners for current draw week
 * @param PDO $pdo Database connection
 * @return array Result with unclaimed winners and stats
 */
function getUnclaimedWinnersForCurrentDraw($pdo) {
    try {
        // Get current draw week
        $currentDraw = getCurrentDrawWeek($pdo);
        
        if (!$currentDraw) {
            return [
                'status' => 'error',
                'message' => 'No current draw week found',
                'unclaimed_winners' => []
            ];
        }
        
        // Get all winners for current draw week who haven't claimed yet
        $stmt = $pdo->prepare("
            SELECT w.*, d.date as draw_date
            FROM winners w 
            LEFT JOIN draw_dates d ON w.draw_week = d.id 
            WHERE w.draw_week = ? AND w.is_claimed = 0
            ORDER BY w.createdAt ASC
        ");
        
        $stmt->execute([$currentDraw['id']]);
        $winners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the results
        $unclaimedWinners = [];
        foreach ($winners as $winner) {
            $unclaimedWinners[] = [
                'id' => $winner['id'],
                'name' => $winner['name'],
                'phone' => $winner['phone'],
                'draw_week' => $winner['draw_week'],
                'draw_date' => $winner['draw_date'],
                'created_at' => $winner['createdAt'],
                'days_since_win' => calculateDaysSince($winner['createdAt'])
            ];
        }
        
        return [
            'status' => 'success',
            'message' => 'Found ' . count($unclaimedWinners) . ' unclaimed winners',
            'current_draw' => $currentDraw,
            'unclaimed_winners' => $unclaimedWinners,
            'total_unclaimed' => count($unclaimedWinners)
        ];
        
    } catch (PDOException $e) {
        return [
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage(),
            'unclaimed_winners' => []
        ];
    }
}

/**
 * Calculate days since a given date
 * @param string $date The date to calculate from
 * @return int Number of days since the date
 */
function calculateDaysSince($date) {
    try {
        $created = new DateTime($date);
        $now = new DateTime();
        $diff = $now->diff($created);
        return $diff->days;
    } catch (Exception $e) {
        return 0;
    }
}
?>
