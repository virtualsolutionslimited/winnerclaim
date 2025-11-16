<?php
session_start();

// Database connection
$host = 'localhost';
$dbname = 'raffle';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

header('Content-Type: application/json');

// Helper function to normalize phone number
function normalizePhoneNumber($phone) {
    // Remove all non-digit characters
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    
    // Remove leading 233 if present (Ghana country code)
    if (strlen($cleanPhone) === 12 && substr($cleanPhone, 0, 3) === '233') {
        $cleanPhone = substr($cleanPhone, 3);
    }
    
    // Remove leading 0 if present
    if (strlen($cleanPhone) === 10 && substr($cleanPhone, 0, 1) === '0') {
        $cleanPhone = substr($cleanPhone, 1);
    }
    
    return $cleanPhone;
}

// Helper function to get current draw week
function getCurrentDrawWeek($pdo) {
    $stmt = $pdo->prepare("
        SELECT *, 
        CASE 
            WHEN date > NOW() THEN 'upcoming'
            WHEN date <= NOW() AND date >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'active'
            ELSE 'expired'
        END as status
        FROM draw_dates 
        WHERE date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY date ASC 
        LIMIT 1
    ");
    $stmt->execute();
    $draw = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($draw) {
        $drawDate = new DateTime($draw['date']);
        $draw['formatted'] = $drawDate->format('F j, Y \a\t g:i A');
        return $draw;
    }
    
    return null;
}

// Get the action from the request
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create_account':
            // Get account data from request
            $phone = $_POST['phone'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
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
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Check if the phone number matches any unclaimed winner
            $stmt = $pdo->prepare("
                SELECT * FROM winners 
                WHERE phone = ? AND is_claimed = 0
            ");
            $stmt->execute([$phone]);
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
                    'phone' => $winner['phone']
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
