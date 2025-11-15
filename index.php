<?php
require_once 'db.php';
require_once 'draw_functions.php';
require_once 'winner_functions.php';
require_once 'sms_functions.php';

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'check_claims_by_phone') {
            $phone = $_POST['phone'] ?? '';
            
            if (empty($phone)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Phone number is required'
                ]);
                exit;
            }
            
            $claimsResult = getClaimsByPhone($pdo, $phone);
            
            if ($claimsResult['status'] === 'error') {
                echo json_encode([
                    'status' => 'error',
                    'message' => $claimsResult['message']
                ]);
                exit;
            }
            
            // Always return success (even with 0 claims) to match the test_claims.php behavior
            $response = [
                'status' => 'success',
                'message' => $claimsResult['total_claims'] > 0 ? 'Claims found' : 'No claims found',
                'phone_searched' => $claimsResult['phone_searched'],
                'total_claims' => $claimsResult['total_claims'],
                'claims' => $claimsResult['claims']
            ];
            
            // If claims are found, send OTP for verification
            if ($claimsResult['total_claims'] > 0) {
                $otpResult = sendOTP($phone, 'verification');
                
                if ($otpResult['status'] === 'success') {
                    $response['otp_sent'] = true;
                    $response['otp_message'] = 'Verification code sent to your phone';
                    // In production, don't include the actual code
                    $response['otp_code'] = $otpResult['code']; // For testing only
                } else {
                    $response['otp_sent'] = false;
                    $response['otp_message'] = 'Failed to send verification code';
                }
            }
            
            echo json_encode($response);
            exit;
        }
        
        if ($_POST['action'] === 'verify_claims_otp') {
            $phone = $_POST['phone'] ?? '';
            $code = $_POST['code'] ?? '';
            
            if (empty($phone) || empty($code)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Phone number and verification code are required'
                ]);
                exit;
            }
            
            // For claims verification, we need to check OTP across all draw weeks
            // Let's create a custom verification for claims
            try {
                $cleanPhone = normalizePhoneNumber($phone);
                
                // Check OTP in winners table across all draw weeks
                $stmt = $pdo->prepare("
                    SELECT * FROM winners 
                    WHERE otp = ? AND is_claimed = 1 AND (
                        phone = ? OR
                        phone = ? OR
                        REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', '') = ? OR
                        REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', '') LIKE ? OR
                        phone LIKE ? OR
                        -- Also check with leading zero
                        REPLACE(REPLACE(REPLACE(CONCAT('0', phone), ' ', ''), '-', ''), '(', '') = ? OR
                        CONCAT('0', phone) = ?
                    )
                ");
                
                $searchPatterns = [
                    $code,                                  // OTP code
                    $cleanPhone,                            // Cleaned phone
                    $phone,                                 // Original phone
                    $cleanPhone,                            // Cleaned without spaces/dashes
                    $cleanPhone . '%',                      // Starts with
                    '%' . $cleanPhone . '%',                // Contains
                    $cleanPhone,                            // With leading zero
                    $phone                                  // Original with leading zero
                ];
                
                $stmt->execute($searchPatterns);
                $winner = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($winner) {
                    // Clear OTP after successful verification
                    $clearStmt = $pdo->prepare("UPDATE winners SET otp = NULL WHERE id = ?");
                    $clearStmt->execute([$winner['id']]);
                    
                    // Get claims for this verified user
                    $claimsResult = getClaimsByPhone($pdo, $phone);
                    
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Code verified successfully',
                        'verified' => true,
                        'winner' => [
                            'id' => $winner['id'],
                            'name' => $winner['name'],
                            'phone' => $winner['phone']
                        ],
                        'claims' => $claimsResult['claims'],
                        'total_claims' => $claimsResult['total_claims']
                    ]);
                    exit;
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Invalid or expired verification code',
                        'verified' => false
                    ]);
                    exit;
                }
                
            } catch (PDOException $e) {
                error_log("Error verifying claims OTP: " . $e->getMessage());
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Verification failed: ' . $e->getMessage(),
                    'verified' => false
                ]);
                exit;
            }
        }
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Get current draw week
$currentDraw = getCurrentDrawWeek($pdo);
$claimWindowDate = null;
$claimWindowText = 'Claim window: ';

if ($currentDraw) {
    // Calculate claim window: 5 days from draw date
    $drawDate = new DateTime($currentDraw['date']);
    $claimWindowDate = clone $drawDate;
    $claimWindowDate->add(new DateInterval('P5D')); // Add 5 days
    
    $claimWindowText .= '5 days from ' . $drawDate->format('F j, Y');
    
    // Debug output (comment this out in production)
    error_log("DEBUG: Draw Date: " . $drawDate->format('Y-m-d H:i:s'));
    error_log("DEBUG: Claim Window: " . $claimWindowDate->format('Y-m-d H:i:s'));
    error_log("DEBUG: Current Time: " . date('Y-m-d H:i:s'));
    error_log("DEBUG: Is Expired: " . ($claimWindowDate < new DateTime() ? 'YES' : 'NO'));
} else {
    $claimWindowText .= 'No current draw available';
    error_log("DEBUG: No current draw found");
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Claim Your World Cup Experience Prize</title>
    <link rel="stylesheet" href="./styles.css" />
  </head>
  <body>
    <!-- Confetti Animation -->
    <div class="confetti-container">
      <div class="confetti"></div>
      <div class="confetti"></div>
      <div class="confetti"></div>
      <div class="confetti"></div>
      <div class="confetti"></div>
      <div class="confetti"></div>
      <div class="confetti"></div>
      <div class="confetti"></div>
      <div class="confetti"></div>
      <div class="confetti"></div>
    </div>
    
    <div class="page-wrapper">
      <!-- Countdown Banner -->
      <div class="banner">
        <div class="banner-content">
          <span class="banner-icon">⏳</span>
          <div class="banner-text">
            <div class="banner-title">
              <?php echo htmlspecialchars($claimWindowText); ?>
            </div>
            <div class="countdown-display">
              Time remaining: <span id="countdown-display">4d 23h 59m</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Countdown Timer -->
      <div class="countdown">
        <div class="countdown-item">
          <span class="countdown-value">00</span>
          <span class="countdown-label">Days</span>
        </div>
        <div class="countdown-item">
          <span class="countdown-value">00</span>
          <span class="countdown-label">Hours</span>
        </div>
        <div class="countdown-item">
          <span class="countdown-value">00</span>
          <span class="countdown-label">Minutes</span>
        </div>
        <div class="countdown-item">
          <span class="countdown-value">00</span>
          <span class="countdown-label">Seconds</span>
        </div>
      </div>

      <h1 class="winner-animation winner-title">Claim Your World Cup Experience Prize</h1>
      <p>
        Congratulations! You've won a fully sponsored Ghana World Cup
        Experience. Complete the steps below to claim your prize.
      </p>
      <div class="button-container">
        <button class="claim-cta">Start Claim</button>
        <button class="my-claims-btn">My Claims</button>
      </div>
      
      <!-- Steps Overview -->
      <div class="steps-container">
        <div class="step">
          <div class="step-number">1</div>
          <div class="step-content">
            <h3>Verify MoMo Account</h3>
            <p>Enter your MoMo number to receive a verification code</p>
          </div>
        </div>
        <div class="step">
          <div class="step-number">2</div>
          <div class="step-content">
            <h3>Create Account</h3>
            <p>Set up your account with email and password</p>
          </div>
        </div>
        <div class="step">
          <div class="step-number">3</div>
          <div class="step-content">
            <h3>Accept Contract</h3>
            <p>Review and accept the winner terms and conditions</p>
          </div>
        </div>
        <div class="step">
          <div class="step-number">4</div>
          <div class="step-content">
            <h3>KYC Verification</h3>
            <p>Submit your Ghana Card and photo for verification</p>
          </div>
        </div>
        <div class="step">
          <div class="step-number">5</div>
          <div class="step-content">
            <h3>Download Your Contract</h3>
            <p>Get your finalized winner contract for your records</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Step 1: Phone Verification Modal -->
    <div class="modal" id="phoneVerificationModal">
      <div class="modal-content">
        <span class="close-btn">&times;</span>
        <div class="step-indicator">Step 1 of 4: Verify Your Phone</div>
        <h2>Verify Your Mobile Money Account</h2>
        <p>Enter your MoMo account number to receive a verification code</p>
        <p class="text-muted">The phone number must match the one that received the winning SMS.</p>

        <form id="phoneVerificationForm" class="verification-form">
          <div class="form-group">
            <label for="phone">MoMo Account Number</label>
            <div class="input-with-button">
              <div class="input-prefix">+233</div>
              <input
                type="tel"
                id="phone"
                name="phone"
                placeholder="e.g. 244123456"
                pattern="[0-9]{9}"
                inputmode="numeric"
                required
              />
              <button type="button" id="sendOtpBtn" class="otp-btn">
                Send OTP
              </button>
            </div>
            <small class="form-hint">Enter your 9-digit MoMo number without the leading 0</small>
          </div>

          <div id="otpSection" class="form-group" style="display: none">
            <label for="otp">Enter OTP Code</label>
            <div class="otp-layout">
              <div class="otp-input-container">
                <input type="text" maxlength="1" class="otp-digit" pattern="[0-9]" inputmode="numeric" />
                <input type="text" maxlength="1" class="otp-digit" pattern="[0-9]" inputmode="numeric" />
                <input type="text" maxlength="1" class="otp-digit" pattern="[0-9]" inputmode="numeric" />
                <input type="text" maxlength="1" class="otp-digit" pattern="[0-9]" inputmode="numeric" />
                <input type="text" maxlength="1" class="otp-digit" pattern="[0-9]" inputmode="numeric" />
                <input type="text" maxlength="1" class="otp-digit" pattern="[0-9]" inputmode="numeric" />
                <input type="hidden" id="otp" name="otp" />
              </div>
              <div class="otp-actions">
                <div class="button-row">
                  <button type="button" id="verifyOtpBtn" class="otp-btn" disabled>
                    Verify OTP
                  </button>
                  <button type="button" id="resendOtp" class="resend-btn">
                    Resend OTP
                  </button>
                </div>
                <div class="otp-timer">
                  <span id="otpTimer">02:00 </span> remaining
                </div>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" id="nextBtn" class="submit-btn" disabled>
              Continue
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Step 2: Create Account Modal -->
    <div class="modal" id="createAccountModal">
      <div class="modal-content">
        <span class="close-btn">&times;</span>
        <div class="step-indicator">Step 2 of 4: Create Account</div>
        <h2>Create Your Account</h2>
        <p>Set up your account with a secure password</p>
        <p>Secure access for the MoMo account holder</p>

        <form id="createAccountForm" class="account-form">
          <div class="form-group">
            <label for="accountPhone">Phone Number</label>
            <input type="tel" id="accountPhone" readonly />
          </div>

          <div class="form-group">
            <label for="email"
              >Email Address
              <span class="optional">(optional but recommended)</span></label
            >
            <input
              type="email"
              id="email"
              name="email"
              placeholder="youremail@example.com"
            />
          </div>

          <div class="form-group">
            <label for="password">Create Password</label>
            <div class="password-field">
              <input
                type="password"
                id="password"
                name="password"
                placeholder="Minimum 8 characters"
                minlength="8"
                required
              />
              <span class="password-toggle" id="togglePassword">👁️</span>
            </div>
            <div class="password-strength">
              <div class="strength-bar"></div>
              <span class="strength-text">Password strength</span>
            </div>
          </div>

          <div class="form-group">
            <label for="confirmPassword">Confirm Password</label>
            <div class="password-field">
              <input
                type="password"
                id="confirmPassword"
                name="confirmPassword"
                placeholder="Re-enter your password"
                minlength="8"
                required
              />
              <span class="password-toggle" id="toggleConfirmPassword">👁️</span>
            </div>
            <div class="password-error" id="passwordError" style="color: #e53935; font-size: 0.875rem; margin-top: 5px; display: none;">
              Passwords do not match
            </div>
          </div>

          <div class="form-checkbox">
            <input
              type="checkbox"
              id="accountHolder"
              name="accountHolder"
              required
            />
            <label for="accountHolder"
              >I am the MoMo account holder for this number</label
            >
          </div>

          <div class="form-checkbox">
            <input
              type="checkbox"
              id="termsAgreement"
              name="termsAgreement"
              required
            />
            <label for="termsAgreement">
              I have read and agree to the <a href="#" onclick="window.open('#', '_blank'); return false;">Terms & Conditions</a>.
            </label>
          </div>

          <div class="form-checkbox">
            <input
              type="checkbox"
              id="privacyAgreement"
              name="privacyAgreement"
              required
            />
            <label for="privacyAgreement">
              I have read and agree to the <a href="#" onclick="window.open('#', '_blank'); return false;">Privacy and Data Statement</a>.
            </label>
          </div>

          <div class="form-actions">
            <button type="button" class="back-btn">Back</button>
            <button type="submit" class="submit-btn">
              Create Account & Continue
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Step 3: Contract Acceptance Modal -->
    <div class="modal" id="contractModal">
      <div class="modal-content contract-modal">
        <span class="close-btn">&times;</span>
        <div class="step-indicator">
          Step 3 of 4: Winner Acceptance Contract
        </div>
        <h2>Winner Acceptance Contract</h2>
        <p class="text-muted">Please read and accept all terms to proceed</p>
        
        <div class="contract-container">
          <form id="contractForm" class="contract-form">
            <div class="contract-scroll">
              <div class="contract-intro">
                <p><strong>By ticking each box below, I confirm and agree that:</strong></p>
              </div>

              <div class="contract-item">
                <input type="checkbox" id="term1" name="terms" class="term-checkbox" required />
                <label for="term1">
                  I am the verified MoMo account holder linked to the winning entry, and the information I provide is true and accurate.
                </label>
              </div>

              <div class="contract-item">
                <input type="checkbox" id="term2" name="terms" class="term-checkbox" required />
                <label for="term2">
                  I understand my prize is a fully sponsored Ghana World Cup Experience, which includes subsidized return flights (Accra–Mexico–Accra), accommodation of up to 3 weeks, match tickets for Ghana's group-stage games, and full visa fee payment with document coaching.
                </label>
              </div>

              <div class="contract-item">
                <input type="checkbox" id="term3" name="terms" class="term-checkbox" required />
                <label for="term3">
                  I acknowledge that visa approval is granted solely at the discretion of the host embassy. If my visa is refused, I will not receive an alternative prize or cash equivalent.
                </label>
              </div>

              <div class="contract-item">
                <input type="checkbox" id="termsAgreement" name="termsAgreement" class="term-checkbox" required />
                <label for="termsAgreement">
                  I have read and agree to the <a href="#" target="_blank" style="color: #fcd115 !important;">Terms & Conditions</a>.
                </label>
              </div>

              <div class="contract-item">
                <input type="checkbox" id="privacyAgreement" name="privacyAgreement" class="term-checkbox" required />
                <label for="privacyAgreement">
                  I have read and agree to the <a href="#" target="_blank" style="color: #fcd115 !important;">Privacy and Data Statement</a>.
                </label>
              </div>

              <div class="form-group" id="parentalConsent" style="display: none;">
                <input type="checkbox" id="parentalCheckbox" name="parentalConsent" class="term-checkbox">
                <label for="parentalCheckbox">
                  I confirm I have parental or guardian consent to enter into this contract.
                </label>
              </div>
            </div>

            <div class="form-actions">
              <button type="button" class="back-btn">Back</button>
              <button type="submit" id="acceptContractBtn" class="submit-btn" disabled>
                Accept and Activate My Winner Contract
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
      </div>
    </div>

    <!-- Step 4: KYC Verification Modal -->
    <div class="modal" id="kycModal">
      <div class="modal-content">
        <span class="close-btn">&times;</span>
        <div class="step-indicator">Step 4 of 4: KYC Verification</div>
        <h2>Complete Your KYC Verification</h2>
        <p class="subtitle">Please provide your Ghana Card details and a clear selfie for verification</p>
        
        <form id="kycForm" class="kyc-form">
          <div class="form-row">
            <div class="form-group full-width">
              <label for="ghanaCard">Ghana Card Number</label>
              <div class="input-with-prefix">
                <span class="input-prefix">GHA-</span>
                <input 
                  type="text" 
                  id="ghanaCard" 
                  name="ghanaCard" 
                  placeholder="123456789-0" 
                  pattern="\d{9}-[0-9A-Z]"
                  required
                  class="form-control"
                >
              </div>
              <small class="form-hint">Format: 123456789-0 (9 digits, hyphen, 1 letter)</small>
            </div>
          </div>
          
          <div class="form-group">
            <label>Upload Your Photo</label>
            
            <div class="upload-options">
              <div class="upload-option" id="cameraOption" style="">
                <div class="upload-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                    <circle cx="12" cy="13" r="4"></circle>
                  </svg>
                </div>
                <span>Take Selfie</span>
                <input type="file" id="cameraInput" accept="image/*" capture="environment" style="display: none;">
              </div>
              
              <div class="upload-option" id="fileOption">
                <div class="upload-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                  </svg>
                </div>
                <span>Upload Ghana Card Photo</span>
                <input type="file" id="fileInput" accept="image/*" style="display: none;">
              </div>
            </div>
            
            <div class="upload-preview" id="uploadPreview" style="display: none;">
              <div class="preview-container">
                <img id="previewImage" alt="Preview" class="preview-img">
                <div class="preview-actions" id="previewActions" style="display: none;">
                  <button type="button" class="btn retake-btn" id="retakeBtn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="1 4 1 10 7 10"></polyline>
                      <polyline points="23 20 23 14 17 14"></polyline>
                      <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
                    </svg>
                    Retake
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" class="btn btn-outline back-btn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
              </svg>
              Back
            </button>
            <button type="submit" id="submitKycBtn" class="btn btn-primary submit-btn">
              Submit Verification
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-1">
                <path d="5 12h14"></path>
                <path d="12 5l7 7-7 7"></path>
              </svg>
            </button>
          </div>
        </form>
      </div>
    </div>
    
    <!-- Camera Modal -->
    <div id="cameraModal" class="camera-modal">
      <div class="camera-container">
        <video id="cameraVideo" autoplay playsinline></video>
        <div class="camera-controls">
          <button id="switchCameraBtn" class="camera-btn secondary" title="Switch Camera">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
              <circle cx="12" cy="13" r="4"></circle>
              <path d="M17 8h.01"></path>
            </svg>
          </button>
          <button id="captureBtn" class="camera-btn" title="Take Photo">
            <div class="camera-shutter"></div>
          </button>
          <button id="closeCameraBtn" class="camera-btn secondary" title="Close Camera">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <canvas id="cameraCanvas" style="display: none;"></canvas>
      </div>
    </div>

    <!-- Success Modal -->
    <div class="modal" id="successModal">
      <div class="modal-content success-modal">
        <div class="success-icon">🎉</div>
        <h2>Your Winner Contract is Activated!</h2>
        <p>
          Thank you for completing the verification process. Your World Cup
          Experience prize is now being processed.
        </p>

        <div class="next-steps">
          <p><strong>What's next?</strong></p>
          <ul>
            <li>
              You'll receive an email/SMS confirmation with your contract
              details
            </li>
            <li>
              A Visa agent will contact you within 48 hours to start your visa
              application
            </li>
            <!-- <li>Keep an eye on your email for important updates</li> -->
          </ul>
        </div>

        <div class="success-actions">
          <button id="downloadContract" class="secondary-btn">
            📄 Download Contract (PDF)
          </button>
          <button id="finishBtn" class="primary-btn">Finish</button>
        </div>
      </div>
    </div>

    <!-- Error Modal -->
    <div class="modal" id="errorModal">
      <div class="modal-content">
        <span class="close-btn">&times;</span>
        <div class="error-icon">⚠️</div>
        <h2>Sorry</h2>
        <p id="error-message"></p>
        <div class="modal-actions">
          <button class="ok-btn" onclick="hideModal()">OK</button>
        </div>
      </div>
    </div>

    <!-- My Claims Phone Verification Modal -->
    <div class="modal" id="myClaimsPhoneModal">
      <div class="modal-content">
        <span class="close-btn">&times;</span>
        
        <!-- Phone Verification Section -->
        <div id="myClaimsPhoneSection">
          <div class="modal-header">
            <h2>Verify Your Phone Number</h2>
            <p>Enter your phone number to view your claims</p>
          </div>
          
          <form id="myClaimsPhoneForm" class="verification-form">
            <div class="form-group">
              <label for="myClaimsPhone">Phone Number</label>
              <div class="input-with-button">
                <input type="tel" id="myClaimsPhone" placeholder="Enter your phone number" required />
                <button type="submit" class="verify-btn">Verify</button>
              </div>
            </div>
          </form>
        </div>
        
        <!-- OTP Section (Initially Hidden) -->
        <div id="myClaimsOtpSection" style="display: none;">
          <div class="modal-header">
            <h2>Enter Verification Code</h2>
            <p>We've sent a 6-digit code to your phone</p>
          </div>
          
          <div class="otp-container">
            <input type="text" class="otp-digit" maxlength="1" />
            <input type="text" class="otp-digit" maxlength="1" />
            <input type="text" class="otp-digit" maxlength="1" />
            <input type="text" class="otp-digit" maxlength="1" />
            <input type="text" class="otp-digit" maxlength="1" />
            <input type="text" class="otp-digit" maxlength="1" />
          </div>
          
          <div class="otp-timer" id="myClaimsOtpTimer">2:00</div>
          
          <div class="form-actions">
            <button type="button" id="myClaimsVerifyOtpBtn" class="btn btn-primary" disabled>Verify Code</button>
            <button type="button" id="myClaimsResendOtp" class="resend-btn" style="display: none;">Resend Code</button>
          </div>
        </div>
      </div>
    </div>

    <!-- No Claims Found Modal -->
    <div class="modal" id="noClaimsModal">
      <div class="modal-content" style="max-width: 450px; text-align: center;">
        <span class="close-btn">&times;</span>
        <div class="modal-icon" style="font-size: 4rem; margin-bottom: 1.5rem; color: #fcd115;">
          📋
        </div>
        <h2 style="color: #fcd115; margin-bottom: 1rem; font-size: 1.8rem;">No Claims Found</h2>
        <p style="margin-bottom: 2rem; color: rgba(255,255,255,0.8); font-size: 1.1rem; line-height: 1.6;">This phone number doesn't have any registered claims. Please check the number and try again.</p>
        <button class="btn btn-primary" onclick="this.closest('.modal').classList.remove('show')" style="background: linear-gradient(135deg, #fcd115 0%, #f4b400 100%); color: #170742; padding: 12px 32px; font-weight: 600; border-radius: 8px;">Got it</button>
      </div>
    </div>

    <!-- My Claims List Modal -->
    <div class="modal" id="myClaimsListModal">
      <div class="modal-content claims-modal">
        <span class="close-btn">&times;</span>
        <div class="modal-header">
          <h2 id="claimsUserName">Your Claims</h2>
          <p class="text-muted">View and download your claim contracts</p>
        </div>
        
        <div class="claims-table-container">
          <table class="claims-table">
            <thead>
              <tr>
                <th>Draw Week Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="claimsTableBody">
              <!-- Claims will be populated here -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Claim Detail Modal -->
    <div class="modal claim-detail-modal" id="claimDetailModal">
      <div class="modal-content" style="max-width: 900px; width: 95%;">
        <span class="close-btn">&times;</span>
        <div class="modal-header">
          <h2>Claim Details</h2>
          <p class="text-muted">View your claim information</p>
        </div>
        
        <div class="claim-detail-content">
          <div class="detail-grid">
            <div class="detail-item">
              <label>Claim ID:</label>
              <span id="detailClaimId">-</span>
            </div>
            <div class="detail-item">
              <label>Winner Name:</label>
              <span id="detailWinnerName">-</span>
            </div>
            <div class="detail-item">
              <label>Phone Number:</label>
              <span id="detailPhone">-</span>
            </div>
            <div class="detail-item">
              <label>Draw Date:</label>
              <span id="detailDrawDate">-</span>
            </div>
            <div class="detail-item">
              <label>Date Claimed:</label>
              <span id="detailDateClaimed">-</span>
            </div>
            <div class="detail-item">
              <label>Status:</label>
              <span id="detailStatus">-</span>
            </div>
          </div>
          
          <div class="detail-actions">
            <button class="btn btn-secondary" onclick="this.closest('.modal').classList.remove('show')">
              Close
            </button>
          </div>
        </div>
      </div>
    </div>

    <script>
        // Pass claim window date from PHP to JavaScript
        <?php if ($claimWindowDate): ?>
        window.claimWindowDate = '<?php echo $claimWindowDate->format('Y-m-d H:i:s'); ?>';
        console.log('PHP claim window date:', '<?php echo $claimWindowDate->format('Y-m-d H:i:s'); ?>');
        <?php else: ?>
        window.claimWindowDate = null;
        console.log('PHP claim window date: null');
        <?php endif; ?>
    </script>
    <script type="module" src="script.js"></script>
  </body>
</html>
