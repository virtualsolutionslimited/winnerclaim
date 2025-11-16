<?php
/**
 * API Endpoint: Get Current Draw Week Winners
 * Returns all winners for the current draw week
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once 'db.php';

// Include required functions
require_once 'winner_functions.php';

try {
    // Get current draw week
    $currentDraw = getCurrentDrawWeek($pdo);
    
    if (!$currentDraw) {
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'No current draw week found',
            'data' => []
        ]);
        exit;
    }
    
    // Get all winners for current draw week
    $winners = getCurrentWeekWinners($pdo);
    
    // Format the response data
    $formattedWinners = [];
    foreach ($winners as $winner) {
        $formattedWinners[] = [
            'id' => (int)$winner['id'],
            'name' => htmlspecialchars($winner['name']),
            'phone' => maskPhoneNumber($winner['phone']),
            'draw_week' => (int)$winner['draw_week'],
            'draw_date' => $winner['draw_date'],
            'is_claimed' => (bool)$winner['is_claimed'],
            'claimed_at' => $winner['claimedAt'] ?? null,
            'created_at' => $winner['createdAt'],
            'status' => $winner['is_claimed'] ? 'claimed' : 'unclaimed'
        ];
    }
    
    // Response data
    $response = [
        'status' => 'success',
        'message' => 'Current draw week winners retrieved successfully',
        'data' => [
            'draw_week' => [
                'id' => (int)$currentDraw['id'],
                'date' => $currentDraw['date'],
                'status' => $currentDraw['status'] ?? 'active'
            ],
            'winners' => $formattedWinners,
            'statistics' => [
                'total_winners' => count($formattedWinners),
                'claimed_count' => count(array_filter($formattedWinners, fn($w) => $w['is_claimed'])),
                'unclaimed_count' => count(array_filter($formattedWinners, fn($w) => !$w['is_claimed'])),
                'claim_rate' => count($formattedWinners) > 0 ? 
                    round((count(array_filter($formattedWinners, fn($w) => $w['is_claimed'])) / count($formattedWinners)) * 100, 2) : 0
            ]
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage(),
        'data' => []
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage(),
        'data' => []
    ]);
}

/**
 * Mask phone number for privacy (show only last 4 digits)
 * @param string $phone Full phone number
 * @return string Masked phone number
 */
function maskPhoneNumber($phone) {
    if (strlen($phone) <= 4) {
        return $phone;
    }
    $visible = substr($phone, -4);
    $masked = str_repeat('*', strlen($phone) - 4);
    return $masked . $visible;
}
?>
