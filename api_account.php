<?php
session_start();

require_once 'db.php';
require_once 'winner_functions.php';
require_once 'draw_functions.php';

header('Content-Type: application/json');

// Get the action from the request
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create_account':
            // Get account data from request
            $phone = $_POST['phone'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $isAccountHolder = $_POST['is_account_holder'] ?? false;
            $termsAgreement = $_POST['terms_agreement'] ?? false;
            $privacyAgreement = $_POST['privacy_agreement'] ?? false;
            
            // Validate required fields
            if (empty($phone) || empty($email) || empty($password)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Phone, email, and password are required'
                ]);
                exit;
            }
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid email format'
                ]);
                exit;
            }
            
            // Validate password length
            if (strlen($password) < 8) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Password must be at least 8 characters long'
                ]);
                exit;
            }
            
            // Store account data in session for later use in createClaim
            $_SESSION['account_data'] = [
                'phone' => $phone,
                'email' => $email,
                'password' => $password, // In production, you should hash this
                'is_account_holder' => $isAccountHolder,
                'terms_agreement' => $termsAgreement,
                'privacy_agreement' => $privacyAgreement,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Check if the phone number matches a winner
            $pdo = connectDB();
            $currentDraw = getCurrentDrawWeek($pdo);
            
            if (!$currentDraw) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No current draw week found'
                ]);
                exit;
            }
            
            // Verify winner exists
            $stmt = $pdo->prepare("
                SELECT * FROM winners 
                WHERE draw_week = ? AND phone = ? AND is_claimed = 0
            ");
            $stmt->execute([$currentDraw['id'], $phone]);
            $winner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$winner) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No unclaimed prize found for this phone number'
                ]);
                exit;
            }
            
            // Store winner info in session
            $_SESSION['winner_info'] = $winner;
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Account created successfully',
                'winner_info' => [
                    'name' => $winner['name'],
                    'phone' => $winner['phone'],
                    'draw_date' => $winner['draw_date']
                ]
            ]);
            break;
            
        case 'get_session_data':
            // For debugging - return current session data
            echo json_encode([
                'status' => 'success',
                'account_data' => $_SESSION['account_data'] ?? null,
                'winner_info' => $_SESSION['winner_info'] ?? null
            ]);
            break;
            
        default:
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action'
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
