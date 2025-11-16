<?php
session_start();

require_once 'winner_functions.php';
require_once 'draw_functions.php';

header('Content-Type: application/json');

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

// Get the action from the request
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create_claim':
            // Get claim data from request
            $phone = $_POST['phone'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $isAccountHolder = $_POST['is_account_holder'] ?? false;
            $termsAgreement = $_POST['terms_agreement'] ?? false;
            $privacyAgreement = $_POST['privacy_agreement'] ?? false;
            $ghanaCard = $_POST['ghana_card'] ?? '';
            $selfieImage = $_FILES['selfie_image'] ?? null;
            $ghanaCardImage = $_FILES['ghana_card_image'] ?? null;
            
            // Validate required fields
            if (empty($phone) || empty($email) || empty($password) || empty($ghanaCard)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'All required fields must be provided'
                ]);
                exit;
            }
            
            // Validate at least one image is uploaded
            if ((!$selfieImage || $selfieImage['error'] !== UPLOAD_ERR_OK) && 
                (!$ghanaCardImage || $ghanaCardImage['error'] !== UPLOAD_ERR_OK)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Please upload at least one photo (selfie or Ghana Card)'
                ]);
                exit;
            }
            
            // First, verify this phone number has an unclaimed prize
            $stmt = $pdo->prepare("
                SELECT * FROM winners 
                WHERE phone = ? AND is_claimed = 0
                ORDER BY createdAt DESC
                LIMIT 1
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
            
            // Prepare claim data
            $claimData = [
                'phone' => $phone,
                'email' => $email,
                'password' => $password, // In production, this should be hashed
                'ghana_card' => $ghanaCard,
                'selfie_image' => $selfieImage,
                'is_account_holder' => $isAccountHolder,
                'terms_agreement' => $termsAgreement,
                'privacy_agreement' => $privacyAgreement,
                'winner_id' => $winner['id']
            ];
            
            // Handle selfie upload if provided
            if ($selfieImage && $selfieImage['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/photos/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = uniqid() . '_' . basename($selfieImage['name']);
                $uploadFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($selfieImage['tmp_name'], $uploadFile)) {
                    $claimData['selfie_path'] = $uploadFile;
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Failed to upload selfie image'
                    ]);
                    exit;
                }
            }
            
            // Handle Ghana card upload if provided
            if ($ghanaCardImage && $ghanaCardImage['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/ghanacards/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = uniqid() . '_' . basename($ghanaCardImage['name']);
                $uploadFile = $uploadDir . $fileName;
                
                if (move_uploaded_file($ghanaCardImage['tmp_name'], $uploadFile)) {
                    $claimData['ghana_card_path'] = $uploadFile;
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Failed to upload Ghana Card image'
                    ]);
                    exit;
                }
            }
            
            // Create the claim using the winner_functions.php
            $result = createClaim($pdo, $claimData);
            
            if ($result['status'] === 'success') {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Account created and claim submitted successfully',
                    'claim_id' => $result['claim_id'] ?? null,
                    'contract_url' => $result['contract_url'] ?? null
                ]);
            } else {
                echo json_encode($result);
            }
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
