import {
  dummyWinners,
  findWinnerByPhone,
  updateWinnerData,
} from "./dummyData.js";

// DOM Elements
const claimBtn = document.querySelector(".claim-cta");
const myClaimsBtn = document.querySelector(".my-claims-btn");
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
const myClaimsPhoneForm = document.getElementById("myClaimsPhoneForm");
const myClaimsPhoneInput = document.getElementById("myClaimsPhone");
const claimsTableBody = document.getElementById("claimsTableBody");
const claimsUserName = document.getElementById("claimsUserName");
// My Claims OTP elements
const myClaimsOtpSection = document.getElementById("myClaimsOtpSection");
const myClaimsVerifyOtpBtn = document.getElementById("myClaimsVerifyOtpBtn");
const myClaimsResendOtp = document.getElementById("myClaimsResendOtp");
const myClaimsOtpTimer = document.getElementById("myClaimsOtpTimer");
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
let myClaimsOtpCountdown;
let otpCode = "";
let myClaimsOtpCode = "";
let countdownInterval;
let file = null;

function startCountdown() {
  // Use the claim window date from PHP, or fallback to 5 days from now
  const now = new Date();
  let targetDate;

  console.log("Current time:", now);
  console.log("Window.claimWindowDate:", window.claimWindowDate);

  if (window.claimWindowDate) {
    // Use the claim window date from PHP
    targetDate = new Date(window.claimWindowDate);
    console.log("Using PHP date:", targetDate);
  } else {
    // Fallback: 5 days from now
    targetDate = new Date(now);
    targetDate.setDate(now.getDate() + 5);
    console.log("Using fallback date:", targetDate);
  }

  console.log("Time remaining (ms):", targetDate - now);
  console.log("Is expired:", targetDate < now);

  // Set the initial countdown values
  updateCountdown(targetDate);

  // Update the countdown every second
  clearInterval(countdownInterval);
  countdownInterval = setInterval(() => updateCountdown(targetDate), 1000);
}

function updateCountdown(targetDate) {
  const now = new Date();
  let timeRemaining = targetDate - now;

  // If countdown is over, show "X days ago" in red
  if (timeRemaining < 0) {
    clearInterval(countdownInterval);

    // Calculate how long ago it was
    const timeAgo = Math.abs(timeRemaining);
    const totalSeconds = Math.floor(timeAgo / 1000);
    const days = Math.floor(totalSeconds / (3600 * 24));
    const hours = Math.floor((totalSeconds % (3600 * 24)) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);

    // Create "ago" text
    let agoText = "";
    if (days > 0) {
      agoText = days === 1 ? "Due 1 day ago" : `Due ${days} days ago`;
    } else if (hours > 0) {
      agoText = hours === 1 ? "Due 1 hour ago" : `Due ${hours} hours ago`;
    } else if (minutes > 0) {
      agoText =
        minutes === 1 ? "Due 1 minute ago" : `Due ${minutes} minutes ago`;
    } else {
      agoText = "Due just now";
    }

    // Update the banner display with red text
    if (countdownDisplay) {
      countdownDisplay.textContent = agoText;
      countdownDisplay.style.color = "#ff4444"; // Red color
    }

    // Hide claim button when claim window is expired
    if (claimBtn) {
      claimBtn.style.display = "none";
    }

    // Update the numeric countdown display to show "00"
    document
      .querySelectorAll(".countdown-value")
      .forEach((el) => (el.textContent = "00"));
    return;
  }

  // Calculate time units for active countdown
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
    countdownDisplay.style.color = ""; // Reset to default color
  }

  // Show claim button when claim window is active
  if (claimBtn) {
    claimBtn.style.display = "";
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

function hideModal(modalId) {
  if (modalId) {
    // Close specific modal
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove("show");
      if (currentModal === modal) {
        currentModal = null;
        document.body.style.overflow = "";
      }
    }
  } else if (currentModal) {
    // Close current modal (default behavior)
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

  // Update verify button to show success
  verifyOtpBtn.textContent = "✓ Verified";
  verifyOtpBtn.style.backgroundColor = "#4CAF50";
  verifyOtpBtn.style.color = "white";
  verifyOtpBtn.style.borderColor = "#4CAF50";
  verifyOtpBtn.disabled = true;

  // Enable continue button with brand yellow background and deep blue text
  const nextBtn = document.getElementById("nextBtn");
  nextBtn.disabled = false;
  nextBtn.style.backgroundColor = "var(--primary-color)"; // Brand yellow
  nextBtn.style.color = "#170742"; // Deep blue text

  // Don't advance to next step - let user click continue
}

// Handle Resend OTP
function handleResendOtp() {
  handleSendOtp();
  resendOtp.style.display = "none";
}
// Initialize Account Form
function initAccountForm() {
  if (!accountForm) {
    console.log("Account form not found!");
    return;
  }

  // Get form elements
  const emailField = document.getElementById("email");
  const passwordField = document.getElementById("password");
  const confirmPasswordField = document.getElementById("confirmPassword");
  const accountHolderCheckbox = document.getElementById("accountHolder");
  const termsCheckbox = document.getElementById("termsAgreement");
  const privacyCheckbox = document.getElementById("privacyAgreement");
  const submitBtn = accountForm.querySelector(".submit-btn");

  console.log("Form elements found:", {
    emailField: !!emailField,
    passwordField: !!passwordField,
    confirmPasswordField: !!confirmPasswordField,
    accountHolderCheckbox: !!accountHolderCheckbox,
    termsCheckbox: !!termsCheckbox,
    privacyCheckbox: !!privacyCheckbox,
    submitBtn: !!submitBtn,
  });

  // Function to check if all conditions are met
  function updateSubmitButton() {
    // Always keep button enabled with brand colors
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.style.backgroundColor = "var(--primary-color)"; // Brand yellow
      submitBtn.style.color = "#170742"; // Deep blue text

      // Add hover effect
      submitBtn.addEventListener("mouseenter", () => {
        submitBtn.style.backgroundColor = "var(--primary-color)"; // Brand yellow
        submitBtn.style.color = "#170742"; // Deep blue text
      });

      submitBtn.addEventListener("mouseleave", () => {
        submitBtn.style.backgroundColor = "var(--primary-color)"; // Brand yellow
        submitBtn.style.color = "#170742"; // Deep blue text
      });
    }
  }

  // Password matching validation
  function validatePasswordMatch() {
    const password = passwordField.value;
    const confirmPassword = confirmPasswordField.value;
    const passwordError = document.getElementById("passwordError");

    if (confirmPassword && password !== confirmPassword) {
      passwordError.style.display = "block";
      return false;
    } else {
      passwordError.style.display = "none";
      return true;
    }
  }

  // Add event listeners for all form fields
  if (emailField) emailField.addEventListener("input", updateSubmitButton);
  if (passwordField)
    passwordField.addEventListener("input", () => {
      validatePasswordMatch();
      updateSubmitButton();
    });
  if (confirmPasswordField)
    confirmPasswordField.addEventListener("input", () => {
      validatePasswordMatch();
      updateSubmitButton();
    });
  if (accountHolderCheckbox)
    accountHolderCheckbox.addEventListener("change", updateSubmitButton);
  if (termsCheckbox)
    termsCheckbox.addEventListener("change", updateSubmitButton);
  if (privacyCheckbox)
    privacyCheckbox.addEventListener("change", updateSubmitButton);

  // Add toggle functionality for confirm password
  // This will be set up globally below

  // Initialize button state
  updateSubmitButton();

  accountForm.addEventListener("submit", (e) => {
    e.preventDefault();

    const email = emailField.value.trim();
    const password = passwordField.value;
    const confirmPassword = confirmPasswordField.value;
    const isAccountHolder = accountHolderCheckbox.checked;
    const termsAgreement = termsCheckbox.checked;
    const privacyAgreement = privacyCheckbox.checked;

    // Validate form
    if (email && !email.includes("@")) {
      const errorMessage = document.getElementById("error-message");
      errorMessage.textContent = "Please enter a valid email address";
      showModal("errorModal");
      return;
    }

    if (password.length < 8) {
      const errorMessage = document.getElementById("error-message");
      errorMessage.textContent = "Password must be at least 8 characters long";
      showModal("errorModal");
      return;
    }

    if (!validatePasswordMatch()) {
      const errorMessage = document.getElementById("error-message");
      errorMessage.textContent = "Passwords do not match";
      showModal("errorModal");
      return;
    }

    if (!isAccountHolder) {
      const errorMessage = document.getElementById("error-message");
      errorMessage.textContent =
        "You must confirm you are the MoMo account holder";
      showModal("errorModal");
      return;
    }

    if (!termsAgreement) {
      const errorMessage = document.getElementById("error-message");
      errorMessage.textContent = "You must agree to the Terms & Conditions";
      showModal("errorModal");
      return;
    }

    if (!privacyAgreement) {
      const errorMessage = document.getElementById("error-message");
      errorMessage.textContent =
        "You must agree to the Privacy and Data Statement";
      showModal("errorModal");
      return;
    }

    // In a real app, you would create the account here
    console.log("Creating account:", {
      email,
      password,
      isAccountHolder,
      termsAgreement,
      privacyAgreement,
    });

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
      e.stopPropagation();
      const fileInput = document.getElementById("fileInput");
      if (fileInput) {
        fileInput.click();
      }
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
      // Check if this was a camera capture
      if (
        capturedImage &&
        capturedImage.name &&
        capturedImage.name.startsWith("capture_")
      ) {
        resetImagePreview();
        startCamera(); // Restart camera for retake
      } else {
        resetImagePreview();
      }
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
    if (!ghanaCardInput || !submitKycBtn) return true;

    const isGhanaCardValid = ghanaCardInput.validity.valid;
    const hasImage =
      (previewImage && previewImage.src && previewImage.src !== "") ||
      capturedImage;

    // Always enable submit button
    submitKycBtn.disabled = false;
    return true;
  }

  // Display image preview
  function displayImagePreview(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      previewImage.src = e.target.result;
      uploadPreview.style.display = "block";

      // Show retake button only for camera captures
      const previewActions = document.getElementById("previewActions");
      if (
        previewActions &&
        file.type === "image/jpeg" &&
        capturedImage &&
        capturedImage.name.startsWith("capture_")
      ) {
        previewActions.style.display = "block";
      }

      validateKycForm();
    };
    reader.readAsDataURL(file);
  }

  // Reset image preview
  function resetImagePreview() {
    const uploadPreview = document.getElementById("uploadPreview");
    const previewImage = document.getElementById("previewImage");
    const fileInput = document.getElementById("fileInput");
    const previewActions = document.getElementById("previewActions");

    if (uploadPreview) uploadPreview.style.display = "none";
    if (previewImage) previewImage.src = "";
    if (fileInput) fileInput.value = "";
    if (previewActions) previewActions.style.display = "none";

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

    // Show success modal
    hideModal();
    const successModal = document.getElementById("successModal");
    if (successModal) {
      successModal.classList.add("show");
    }
  }

  // Clean up camera on page unload
  window.addEventListener("beforeunload", () => {
    if (stream) {
      stopCamera();
    }
  });
}

// Initialize Modals
function initModals() {
  // Close button handlers
  document.querySelectorAll(".close-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const modal = e.target.closest(".modal");
      if (modal) {
        hideModal(modal.id);
      } else {
        hideModal();
      }
    });
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

// Initialize My Claims Phone Form
function initMyClaimsPhoneForm() {
  myClaimsPhoneForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const phoneNumber = myClaimsPhoneInput.value.trim();

    // Validate phone number (basic validation)
    if (!phoneNumber || phoneNumber.length < 10) {
      showError("Please enter a valid phone number");
      return;
    }

    try {
      // Show loading state
      const submitBtn = myClaimsPhoneForm.querySelector(
        'button[type="submit"]'
      );
      const originalText = submitBtn.textContent;
      submitBtn.textContent = "Checking...";
      submitBtn.disabled = true;

      console.log("Checking claims for phone:", phoneNumber);

      // Call API to check claims by phone number
      const formData = new FormData();
      formData.append("action", "check_claims_by_phone");
      formData.append("phone", phoneNumber);

      const response = await fetch(".", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();
      console.log("API response:", result);

      // Restore button state
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;

      if (result.status === "error") {
        console.log("API returned error:", result.message);
        showError(result.message);
        return;
      }

      if (result.status === "success" && result.total_claims === 0) {
        console.log("No claims found, showing no claims modal");
        // Hide phone verification modal and show no claims modal
        hideModal("myClaimsPhoneModal");
        showModal("noClaimsModal");
        return;
      }

      if (result.status === "success" && result.total_claims > 0) {
        console.log("Claims found, proceeding with OTP");
        console.log("OTP sent:", result.otp_sent);
        console.log("OTP code (for testing):", result.otp_code);

        // Store claims data for later use
        window.currentUserClaims = result.claims;
        window.currentUserPhone = phoneNumber;

        // Set current user (for compatibility with existing code)
        currentUser = {
          phone: phoneNumber,
          name: result.claims[0]?.name || "User",
        };

        // Use the OTP code sent via SMS (for testing, we have it in the response)
        if (result.otp_sent && result.otp_code) {
          myClaimsOtpCode = result.otp_code;
          console.log(`Using OTP sent to ${phoneNumber}: ${myClaimsOtpCode}`);
        } else {
          // Fallback: generate OTP locally if SMS failed
          myClaimsOtpCode = Math.floor(
            100000 + Math.random() * 900000
          ).toString();
          console.log(
            `SMS failed, using local OTP for ${phoneNumber}: ${myClaimsOtpCode}`
          );
        }

        // Hide phone section and show OTP section
        document.getElementById("myClaimsPhoneSection").style.display = "none";
        myClaimsOtpSection.style.display = "block";

        // Start OTP timer
        startMyClaimsOtpCountdown();

        // Focus first OTP input
        const firstOtpInput = myClaimsOtpSection.querySelector(".otp-digit");
        if (firstOtpInput) {
          firstOtpInput.focus();
        }
      }
    } catch (error) {
      console.error("Error checking claims:", error);

      // Restore button state
      const submitBtn = myClaimsPhoneForm.querySelector(
        'button[type="submit"]'
      );
      submitBtn.textContent = originalText || "Verify";
      submitBtn.disabled = false;

      showError("Network error. Please try again.");
    }
  });

  // Add event listeners for My Claims OTP input fields
  const myClaimsOtpInputs = myClaimsOtpSection.querySelectorAll(".otp-digit");
  myClaimsOtpInputs.forEach((input, index) => {
    input.addEventListener("input", (e) => {
      if (e.target.value.length === 1) {
        if (index < myClaimsOtpInputs.length - 1) {
          myClaimsOtpInputs[index + 1].focus();
        }
      }
      checkMyClaimsOtpCompletion();
    });

    input.addEventListener("keydown", (e) => {
      if (e.key === "Backspace" && e.target.value === "" && index > 0) {
        myClaimsOtpInputs[index - 1].focus();
      }
    });
  });

  // Handle My Claims OTP verification
  myClaimsVerifyOtpBtn.addEventListener("click", handleMyClaimsVerifyOtp);

  // Handle My Claims Resend OTP
  myClaimsResendOtp.addEventListener("click", handleMyClaimsResendOtp);
}

// Start My Claims OTP Countdown
function startMyClaimsOtpCountdown() {
  let seconds = 120; // 2 minutes

  myClaimsOtpCountdown = setInterval(() => {
    seconds--;
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    if (myClaimsOtpTimer) {
      myClaimsOtpTimer.textContent = `${minutes}:${remainingSeconds
        .toString()
        .padStart(2, "0")} `;
    }

    if (seconds <= 0) {
      clearInterval(myClaimsOtpCountdown);
      if (myClaimsResendOtp) {
        myClaimsResendOtp.style.display = "inline";
      }
    }
  }, 1000);
}

// Check My Claims OTP Completion
function checkMyClaimsOtpCompletion() {
  const myClaimsOtpInputs = myClaimsOtpSection.querySelectorAll(".otp-digit");
  const allFilled = Array.from(myClaimsOtpInputs).every(
    (input) => input.value.length === 1
  );

  if (myClaimsVerifyOtpBtn) {
    myClaimsVerifyOtpBtn.disabled = !allFilled;
  }
}

// Handle My Claims OTP Verification
function handleMyClaimsVerifyOtp() {
  console.log("handleMyClaimsVerifyOtp called");

  // Get all OTP input fields and combine them
  const myClaimsOtpInputs = myClaimsOtpSection.querySelectorAll(".otp-digit");
  let enteredOtp = "";

  myClaimsOtpInputs.forEach((input) => {
    enteredOtp += input.value || "0"; // Use '0' if empty to ensure we get 6 digits
  });

  // Ensure we have exactly 6 digits (pad with zeros if needed)
  enteredOtp = enteredOtp.padEnd(6, "0").substring(0, 6);

  console.log("Verifying OTP code:", enteredOtp);
  console.log("For phone:", window.currentUserPhone);

  // Update verify button to show loading
  myClaimsVerifyOtpBtn.textContent = "Verifying...";
  myClaimsVerifyOtpBtn.disabled = true;

  // Call API to verify OTP
  verifyClaimsOtp(window.currentUserPhone, enteredOtp);
}

// Verify OTP via API
async function verifyClaimsOtp(phone, code) {
  try {
    const formData = new FormData();
    formData.append("action", "verify_claims_otp");
    formData.append("phone", phone);
    formData.append("code", code);

    const response = await fetch(".", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();
    console.log("OTP verification response:", result);

    if (result.verified) {
      // Success - OTP verified
      myClaimsVerifyOtpBtn.textContent = "✓ Verified";
      myClaimsVerifyOtpBtn.style.backgroundColor = "#4CAF50";
      myClaimsVerifyOtpBtn.style.color = "white";
      myClaimsVerifyOtpBtn.style.borderColor = "#4CAF50";

      // Clear countdown
      clearInterval(myClaimsOtpCountdown);

      // Hide phone verification modal
      console.log("Hiding myClaimsPhoneModal");
      hideModal("myClaimsPhoneModal");

      // Show claims list using the verified claims data
      if (result.claims && result.claims.length > 0) {
        console.log("Showing claims list modal");
        // Create a winner object with the claims data
        const winnerData = {
          name: result.winner?.name || result.claims[0]?.name || "User",
          phone: phone,
          claims: result.claims,
        };
        showClaimsList(winnerData);
        showModal("myClaimsListModal");
      } else {
        console.log("Showing no claims modal");
        // This shouldn't happen since we check for claims earlier, but just in case
        showModal("noClaimsModal");
      }
    } else {
      // Failed verification
      myClaimsVerifyOtpBtn.textContent = "Verify Code";
      myClaimsVerifyOtpBtn.style.backgroundColor = "";
      myClaimsVerifyOtpBtn.style.color = "";
      myClaimsVerifyOtpBtn.style.borderColor = "";
      myClaimsVerifyOtpBtn.disabled = false;

      // Show error message
      showError(result.message || "Invalid verification code");

      // Clear OTP inputs for retry
      const myClaimsOtpInputs =
        myClaimsOtpSection.querySelectorAll(".otp-digit");
      myClaimsOtpInputs.forEach((input) => {
        input.value = "";
      });
      myClaimsOtpInputs[0].focus();
    }
  } catch (error) {
    console.error("Error verifying OTP:", error);

    // Restore button state
    myClaimsVerifyOtpBtn.textContent = "Verify Code";
    myClaimsVerifyOtpBtn.style.backgroundColor = "";
    myClaimsVerifyOtpBtn.style.color = "";
    myClaimsVerifyOtpBtn.style.borderColor = "";
    myClaimsVerifyOtpBtn.disabled = false;

    showError("Network error. Please try again.");
  }
}

// Handle My Claims Resend OTP
async function handleMyClaimsResendOtp() {
  if (!currentUser) return;

  try {
    // Show loading state
    const resendBtn = document.getElementById("myClaimsResendOtp");
    const originalText = resendBtn.textContent;
    resendBtn.textContent = "Sending...";
    resendBtn.disabled = true;

    // Call API to resend OTP
    const formData = new FormData();
    formData.append("action", "check_claims_by_phone");
    formData.append("phone", currentUser.phone);

    const response = await fetch(".", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();
    console.log("Resend OTP response:", result);

    // Restore button state
    resendBtn.textContent = originalText;
    resendBtn.disabled = false;

    if (result.status === "success" && result.otp_sent) {
      myClaimsOtpCode = result.otp_code;
      console.log(`New OTP sent to ${currentUser.phone}: ${myClaimsOtpCode}`);

      // Show success message
      showError("New verification code sent to your phone");
    } else {
      // Fallback: generate locally
      myClaimsOtpCode = Math.floor(100000 + Math.random() * 900000).toString();
      console.log(`Failed to send SMS, using local OTP: ${myClaimsOtpCode}`);
      showError("Failed to send SMS. Using test code: " + myClaimsOtpCode);
    }

    // Reset OTP inputs
    const myClaimsOtpInputs = myClaimsOtpSection.querySelectorAll(".otp-digit");
    myClaimsOtpInputs.forEach((input) => {
      input.value = "";
    });

    // Reset verify button
    myClaimsVerifyOtpBtn.textContent = "Verify Code";
    myClaimsVerifyOtpBtn.style.backgroundColor = "";
    myClaimsVerifyOtpBtn.style.color = "";
    myClaimsVerifyOtpBtn.style.borderColor = "";
    myClaimsVerifyOtpBtn.disabled = true;

    // Hide resend button and restart countdown
    myClaimsResendOtp.style.display = "none";
    startMyClaimsOtpCountdown();

    // Focus first OTP input
    const firstOtpInput = myClaimsOtpSection.querySelector(".otp-digit");
    if (firstOtpInput) {
      firstOtpInput.focus();
    }
  } catch (error) {
    console.error("Error resending OTP:", error);

    // Restore button state
    const resendBtn = document.getElementById("myClaimsResendOtp");
    resendBtn.textContent = "Resend Code";
    resendBtn.disabled = false;

    showError("Network error. Please try again.");
  }
}
function showClaimsList(winner) {
  // Update user name
  claimsUserName.textContent = `${winner.name}'s Claims`;

  // Clear existing claims
  claimsTableBody.innerHTML = "";

  // Check if winner has any claims
  if (winner.claims && winner.claims.length > 0) {
    winner.claims.forEach((claim) => {
      const row = document.createElement("tr");

      // Format the draw date
      const drawDate = claim.draw_date
        ? new Date(claim.draw_date).toLocaleDateString()
        : "N/A";

      // Create prize name based on the data available
      const prizeName = `World Cup Experience - Draw ${
        claim.draw_week || "N/A"
      }`;

      row.innerHTML = `
        <td>${prizeName}</td>
        <td>${drawDate}</td>
        <td>
          <button class="download-btn" onclick="downloadContract('${claim.id}', '${winner.name}', '${prizeName}')">
            Download
          </button>
        </td>
      `;
      claimsTableBody.appendChild(row);
    });
  } else {
    // Show no claims message
    const row = document.createElement("tr");
    row.innerHTML = `
      <td colspan="3" style="text-align: center; padding: 20px;">
        No claims found for this user
      </td>
    `;
    claimsTableBody.appendChild(row);
  }
}

// Download Contract
function downloadContract(claimId, winnerName, prizeName) {
  // Create a simple contract text
  const contractContent = `
WINNER ACCEPTANCE CONTRACT

Claim ID: ${claimId}
Winner Name: ${winnerName}
Prize: ${prizeName}
Date: ${new Date().toLocaleDateString()}

This contract confirms that ${winnerName} has successfully claimed the ${prizeName} prize.
The terms and conditions have been accepted and verified.

Generated on: ${new Date().toLocaleString()}
  `.trim();

  // Create a blob and download
  const blob = new Blob([contractContent], { type: "text/plain" });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `Contract_${claimId}_${winnerName.replace(/\s+/g, "_")}.txt`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  window.URL.revokeObjectURL(url);
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

  // Initialize My Claims phone form
  if (myClaimsPhoneForm) {
    initMyClaimsPhoneForm();
  }

  // Claim button click handler
  claimBtn.addEventListener("click", () => {
    showModal("phoneVerificationModal");
  });

  // My Claims button handler
  myClaimsBtn.addEventListener("click", () => {
    showModal("myClaimsPhoneModal");
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
    // Get all OTP input fields and combine them
    const otpInputs = document.querySelectorAll(".otp-digit");
    let otp = "";
    otpInputs.forEach((input) => {
      otp += input.value || "";
    });

    // In a real app, you would verify the OTP here
    // For demo purposes, we'll accept any 6-digit code
    if (otp.length === 6 && /^\d+$/.test(otp)) {
      clearInterval(otpCountdown);

      // Update verify button to show success
      verifyOtpBtn.textContent = "✓ Verified";
      verifyOtpBtn.style.backgroundColor = "#4CAF50";
      verifyOtpBtn.style.color = "white";
      verifyOtpBtn.style.borderColor = "#4CAF50";
      verifyOtpBtn.disabled = true;

      // Enable continue button with brand yellow background and deep blue text
      const nextBtn = document.getElementById("nextBtn");
      nextBtn.disabled = false;
      nextBtn.style.backgroundColor = "var(--primary-color)"; // Brand yellow
      nextBtn.style.color = "#170742"; // Deep blue text
    }
  });

  // Next button in phone verification
  document.getElementById("nextBtn").addEventListener("click", () => {
    const phone = document.getElementById("phone").value;
    const winner = findWinnerByPhone(phone);

    if (winner) {
      // Pre-fill the account creation form
      document.getElementById("accountPhone").value = winner.phone;

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

  // Toggle confirm password visibility
  document
    .getElementById("toggleConfirmPassword")
    .addEventListener("click", () => {
      const confirmPasswordInput = document.getElementById("confirmPassword");
      const icon = document.getElementById("toggleConfirmPassword");

      if (confirmPasswordInput.type === "password") {
        confirmPasswordInput.type = "text";
        icon.textContent = "👁️";
      } else {
        confirmPasswordInput.type = "password";
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
