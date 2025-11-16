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
const accountForm = document.getElementById("createAccountForm");
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
let errorModal = null;

function showModal(modalId) {
  console.log("showModal called with modalId:", modalId);

  // Hide current modal if any
  if (currentModal) {
    currentModal.classList.remove("show");
  }

  // Show new modal
  currentModal = document.getElementById(modalId);
  console.log("Found modal element:", currentModal);

  if (currentModal) {
    currentModal.classList.add("show");
    document.body.style.overflow = "hidden";
    console.log("Modal shown successfully");

    // Re-initialize OTP flow if phone verification modal is shown
    if (modalId === "phoneVerificationModal") {
      // Re-add event listeners for OTP inputs
      const otpInputs = currentModal.querySelectorAll(".otp-digit");
      otpInputs.forEach((input, index) => {
        // Remove existing listeners to avoid duplicates
        input.replaceWith(input.cloneNode(true));
      });

      // Re-add event listeners
      const newOtpInputs = currentModal.querySelectorAll(".otp-digit");
      newOtpInputs.forEach((input, index) => {
        // Handle input
        input.addEventListener("input", (e) => {
          // Only allow numbers
          e.target.value = e.target.value.replace(/[^0-9]/g, "");

          // Move to next input if current input has a value
          if (e.target.value && index < newOtpInputs.length - 1) {
            newOtpInputs[index + 1].focus();
          }

          // Enable/disable verify button based on OTP completion
          const allFilled = Array.from(newOtpInputs).every(
            (input) => input.value
          );
          const verifyBtn = currentModal.querySelector("#verifyOtpBtn");
          if (verifyBtn) {
            verifyBtn.disabled = !allFilled;
          }
        });

        // Handle backspace
        input.addEventListener("keydown", (e) => {
          if (e.key === "Backspace" && !e.target.value && index > 0) {
            newOtpInputs[index - 1].focus();
          }
        });
      });

      // Add verify button event listener
      const verifyBtn = currentModal.querySelector("#verifyOtpBtn");
      if (verifyBtn) {
        verifyBtn.addEventListener("click", handleVerifyOtp);
      }
    }
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
      if (errorModal === modal) {
        errorModal = null;
      }
    }
  } else if (currentModal) {
    // Close current modal (default behavior)
    currentModal.classList.remove("show");
    currentModal = null;
    document.body.style.overflow = "";
  }
}

function showErrorModal(message) {
  // Set error message
  const errorMessageElement = document.getElementById("error-message");
  if (errorMessageElement) {
    errorMessageElement.textContent = message;
  }

  // Show error modal without hiding current modal
  errorModal = document.getElementById("errorModal");
  if (errorModal) {
    errorModal.classList.add("show");
    // Don't set currentModal so underlying modal stays active
  }
}

// Show Summary Modal with user data
function showSummaryModal() {
  // Try to get claim summary data from session storage
  const claimData = JSON.parse(
    sessionStorage.getItem("claimSummaryData") || "{}"
  );

  console.log("showSummaryModal called with claimData:", claimData);

  // Populate summary modal with data
  const summaryPhone = document.getElementById("summary-phone");
  const summaryEmail = document.getElementById("summary-email");
  const summaryAccountHolder = document.getElementById(
    "summary-account-holder"
  );
  const summaryPrize = document.getElementById("summary-prize");
  const summaryClaimId = document.getElementById("summary-claim-id");
  const summarySubmitted = document.getElementById("summary-submitted");

  if (summaryPhone) summaryPhone.textContent = claimData.phone || "N/A";
  if (summaryEmail) summaryEmail.textContent = claimData.email || "N/A";
  if (summaryAccountHolder)
    summaryAccountHolder.textContent =
      claimData.isAccountHolder === true ? "Yes" : "No";
  if (summaryPrize)
    summaryPrize.textContent = claimData.prizeName || "World Cup Experience";
  if (summaryClaimId) summaryClaimId.textContent = claimData.claimId || "N/A";
  if (summarySubmitted)
    summarySubmitted.textContent = claimData.submittedAt
      ? new Date(claimData.submittedAt).toLocaleDateString() +
        " " +
        new Date(claimData.submittedAt).toLocaleTimeString([], {
          hour: "2-digit",
          minute: "2-digit",
        })
      : "N/A";

  console.log("Summary modal elements populated, showing modal...");

  // Show the summary modal
  showModal("summaryModal");
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
}

// Handle Send OTP
async function handleSendOtp() {
  const phone = phoneInput.value.trim();
  const sendOtpBtn = document.getElementById("sendOtpBtn");
  const originalText = sendOtpBtn.innerHTML;

  // Validate phone number
  if (
    !phone ||
    (phone.length !== 9 && phone.length !== 10) ||
    !/^\d+$/.test(phone)
  ) {
    const errorMessageElement = document.getElementById("error-message");
    if (errorMessageElement) {
      errorMessageElement.textContent =
        "Please enter a valid 9-digit or 10-digit MoMo number";
    }
    showModal("errorModal");
    return;
  }

  // Disable button and show loading
  sendOtpBtn.disabled = true;
  sendOtpBtn.innerHTML = "Verify...";

  try {
    const response = await fetch("api_otp.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `action=verify&phone=${encodeURIComponent(phone)}`,
    });

    const result = await response.json();

    if (result.status === "success") {
      // Found unclaimed winner - store winner info but don't display details
      if (result.winner_info) {
        window.currentWinner = result.winner_info;

        // Remove any existing winner details display
        const phoneSection = document.querySelector(".modal-content");
        const existingDetails = phoneSection.querySelector(".winner-details");
        if (existingDetails) {
          existingDetails.remove();
        }
      }

      // Change button click handler and directly send OTP
      sendOtpBtn.onclick = handleSendOtpAfterVerification;

      // Automatically trigger OTP sending
      handleSendOtpAfterVerification();
    } else if (result.status === "not_found") {
      // Show not found message
      const errorMessageElement = document.getElementById("error-message");
      if (errorMessageElement) {
        errorMessageElement.textContent =
          "No unclaimed winnings found for this phone number";
        result.message || "No unclaimed winnings found for this phone number";
      }
      showModal("errorModal");

      // Show "no winnings found" message with helpful info
      const noWinningsMsg = `
        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffc107;">
          <h4 style="margin: 0 0 10px 0; color: #856404;"> No Unclaimed Winnings Found</h4>
          <p style="margin: 0; color: #856404;">
            This phone number doesn't have any unclaimed winnings in the current draw week.<br>
            Please check:<br>
            • The phone number matches the winning SMS<br>
            • You're checking the current draw week<br>
            • The prize hasn't been claimed yet
          </p>
        </div>
      `;

      // Show no winnings message
      const phoneSection = document.querySelector(".modal-content");
      const existingMsg = phoneSection.querySelector(".no-winnings-message");
      if (existingMsg) {
        existingMsg.remove();
      }

      const msgDiv = document.createElement("div");
      msgDiv.className = "no-winnings-message";
      msgDiv.innerHTML = noWinningsMsg;

      const phoneField = phoneInput.closest(".form-group");
      phoneField.parentNode.insertBefore(msgDiv, phoneField.nextSibling);

      // Remove message after 5 seconds
      setTimeout(() => {
        if (msgDiv.parentNode) {
          msgDiv.remove();
        }
      }, 5000);
    } else {
      // Show error message with specific details
      const errorMessage = result.message || "Failed to verify phone number";
      const errorMessageElement = document.getElementById("error-message");
      if (errorMessageElement) {
        errorMessageElement.textContent = errorMessage;
      }

      showModal("errorModal");
    }
  } catch (error) {
    const errorMessageElement = document.getElementById("error-message");
    if (errorMessageElement) {
      errorMessageElement.textContent = "Network error. Please try again.";
    }

    showModal("errorModal");
  } finally {
    // Re-enable button
    sendOtpBtn.disabled = false;
    sendOtpBtn.textContent = "Verify";
  }
}

// Handle Verify OTP
async function handleVerifyOtp() {
  // Get phone number from input field
  const phone = phoneInput.value.trim();

  // Get all OTP input fields
  const otpInputs = document.querySelectorAll(".otp-digit");
  let enteredOtp = "";

  // Combine all OTP digits
  otpInputs.forEach((input) => {
    enteredOtp += input.value || "";
  });

  // Ensure we have exactly 6 digits
  if (enteredOtp.length !== 6) {
    showErrorModal("Please enter all 6 digits of the verification code");
    return;
  }

  if (!phone) {
    showErrorModal("Phone number is required for verification");
    return;
  }

  // Disable verify button while processing
  verifyOtpBtn.disabled = true;
  verifyOtpBtn.textContent = "Verifying...";

  try {
    const response = await fetch("api_otp.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: new URLSearchParams({
        action: "verify_otp",
        phone: phone,
        otp: enteredOtp,
      }),
    });

    const result = await response.json();

    if (result.status === "success" && result.verified) {
      // Update verify button to show success
      verifyOtpBtn.textContent = "Verified";
      verifyOtpBtn.style.backgroundColor = "#4CAF50";
      verifyOtpBtn.style.color = "white";
      verifyOtpBtn.style.borderColor = "#4CAF50";
      verifyOtpBtn.disabled = true;

      // Update current user info if returned
      if (result.winner_info) {
        currentUser = result.winner_info;
      }

      // Enable continue button with brand yellow background and deep blue text
      const nextBtn = document.getElementById("nextBtn");
      if (nextBtn) {
        nextBtn.disabled = false;
        nextBtn.style.backgroundColor = "var(--primary-color)"; // Brand yellow
        nextBtn.style.color = "#170742"; // Deep blue text
      }
    } else {
      // Reset verify button
      verifyOtpBtn.disabled = false;
      verifyOtpBtn.textContent = "Verify OTP";

      // Show error message
      showErrorModal(
        result.message || "Invalid verification code. Please try again."
      );

      // Clear OTP inputs for retry
      otpInputs.forEach((input) => {
        input.value = "";
      });
      // Focus on first input
      if (otpInputs[0]) {
        otpInputs[0].focus();
      }
    }
  } catch (error) {
    console.error("Error verifying OTP:", error);

    // Reset verify button
    verifyOtpBtn.disabled = false;
    verifyOtpBtn.textContent = "Verify OTP";

    showErrorModal("Network error. Please try again.");
  }
}

// Handle Send OTP After Verification
async function handleSendOtpAfterVerification() {
  const phone = phoneInput.value.trim();
  const sendOtpBtn = document.getElementById("sendOtpBtn");

  // Disable button and show loading
  sendOtpBtn.disabled = true;
  sendOtpBtn.innerHTML = "Verify...";

  try {
    const response = await fetch("api_otp.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `action=send_otp&phone=${encodeURIComponent(phone)}`,
    });

    const result = await response.json();

    if (result.status === "success") {
      // Show OTP input section
      otpSection.style.display = "block";

      // Enable verify OTP button
      verifyOtpBtn.disabled = false;

      // Focus on OTP input
      setTimeout(() => otpInput.focus(), 500);

      // Update button to show it was sent
      sendOtpBtn.innerHTML = "✓ OTP Sent";
      sendOtpBtn.disabled = true;
      sendOtpBtn.style.backgroundColor = "#4CAF50";
      sendOtpBtn.style.color = "white";
    } else {
      // Show error message
      showModal(
        "errorModal",
        result.message || "Failed to send verification code"
      );

      // Re-enable button for retry
      sendOtpBtn.disabled = false;
      sendOtpBtn.innerHTML = "Send OTP";
    }
  } catch (error) {
    console.error("Error sending OTP:", error);
    showModal("errorModal", "Network error. Please try again.");

    // Re-enable button for retry
    sendOtpBtn.disabled = false;
    sendOtpBtn.innerHTML = "Send OTP";
  }
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

  accountForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = emailField.value.trim();
    const password = passwordField.value;
    const confirmPassword = confirmPasswordField.value;
    const isAccountHolder = accountHolderCheckbox.checked;
    const termsAgreement = termsCheckbox.checked;
    const privacyAgreement = privacyCheckbox.checked;

    // Get phone number from the hidden field or from the previous step
    const phone =
      document.getElementById("accountPhone")?.value || phoneInput.value.trim();

    console.log("Phone input value:", phoneInput.value);
    console.log(
      "AccountPhone field value:",
      document.getElementById("accountPhone")?.value
    );
    console.log("Final phone value:", phone);

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

    // Disable submit button while processing
    const submitBtn = accountForm.querySelector(".submit-btn");
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = "Creating Account...";

    console.log("Creating account with data:", {
      phone: phone,
      email: email,
      isAccountHolder: isAccountHolder,
      termsAgreement: termsAgreement,
      privacyAgreement: privacyAgreement,
    });

    // Store account data locally for later use
    const accountData = {
      phone: phone,
      email: email,
      password: password,
      isAccountHolder: isAccountHolder,
      termsAgreement: termsAgreement,
      privacyAgreement: privacyAgreement,
    };

    // Store in session storage for later use
    sessionStorage.setItem("accountData", JSON.stringify(accountData));

    console.log("Account data stored locally, showing contract modal");

    // Hide current modal and show contract modal
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

  // Age selection handlers
  const ageRadios = document.querySelectorAll('input[name="ageRange"]');
  const ageRestrictionMessage = document.getElementById(
    "ageRestrictionMessage"
  );
  const parentalConsentSection = document.getElementById(
    "parentalConsentSection"
  );

  ageRadios.forEach((radio) => {
    radio.addEventListener("change", function () {
      // Hide all conditional sections first
      ageRestrictionMessage.style.display = "none";
      parentalConsentSection.style.display = "none";

      // Reset parental consent checkbox
      const parentalCheckbox = document.getElementById("parentalCheckbox");
      if (parentalCheckbox) {
        parentalCheckbox.checked = false;
      }

      // Show appropriate section based on age selection
      if (this.value === "below18") {
        ageRestrictionMessage.style.display = "block";
        acceptButton.disabled = true;
      } else if (this.value === "18to20") {
        parentalConsentSection.style.display = "block";
        checkAllTermsAccepted(); // Re-check to validate parental consent
      } else if (this.value === "above21") {
        // Just enable validation check
        checkAllTermsAccepted();
      }
    });
  });

  // Update checkAllTermsAccepted to include age validation
  function checkAllTermsAccepted() {
    const selectedAge = document.querySelector(
      'input[name="ageRange"]:checked'
    );

    // If no age selected, disable button
    if (!selectedAge) {
      acceptButton.disabled = true;
      return false;
    }

    // If under 18, disable button
    if (selectedAge.value === "below18") {
      acceptButton.disabled = true;
      return false;
    }

    // Get all required checkboxes (terms + parental consent if applicable)
    const requiredCheckboxes = [];
    termCheckboxes.forEach((checkbox) => {
      // Only include terms checkboxes, not old parental consent
      if (
        checkbox.id !== "parentalCheckbox" ||
        (selectedAge.value === "18to20" && checkbox.id === "parentalCheckbox")
      ) {
        requiredCheckboxes.push(checkbox);
      }
    });

    // Check if all required checkboxes are checked
    const allChecked = Array.from(requiredCheckboxes).every(
      (checkbox) => checkbox.checked
    );

    // Also validate guardian details if 18-20 age range
    let guardianDetailsValid = true;
    if (selectedAge.value === "18to20") {
      const guardianName = document.getElementById("guardianName");
      const guardianPhone = document.getElementById("guardianPhone");
      const parentalCheckbox = document.getElementById("parentalCheckbox");

      if (parentalCheckbox && parentalCheckbox.checked) {
        guardianDetailsValid =
          guardianName.value.trim() !== "" && guardianPhone.value.trim() !== "";
      } else {
        guardianDetailsValid = false;
      }
    }

    acceptButton.disabled = !(allChecked && guardianDetailsValid);
    return allChecked && guardianDetailsValid;
  }

  // Add event listeners for guardian details inputs
  const guardianNameInput = document.getElementById("guardianName");
  const guardianPhoneInput = document.getElementById("guardianPhone");
  const parentalCheckbox = document.getElementById("parentalCheckbox");

  if (guardianNameInput) {
    guardianNameInput.addEventListener("input", checkAllTermsAccepted);
  }

  if (guardianPhoneInput) {
    guardianPhoneInput.addEventListener("input", checkAllTermsAccepted);
  }

  if (parentalCheckbox) {
    parentalCheckbox.addEventListener("change", checkAllTermsAccepted);
  }

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

// Global variable for captured image
let capturedImage = null;

// Initialize KYC Form
function initKycForm() {
  const kycForm = document.getElementById("kycForm");
  const ghanaCardInput = document.getElementById("ghanaCard");
  const fileInput = document.getElementById("fileInput");
  const selfieInput = document.getElementById("selfieInput");

  // New preview elements
  const selfiePreview = document.getElementById("selfiePreview");
  const selfiePreviewImage = document.getElementById("selfiePreviewImage");
  const cardPreview = document.getElementById("cardPreview");
  const cardPreviewImage = document.getElementById("cardPreviewImage");
  const retakeSelfieBtn = document.getElementById("retakeSelfieBtn");
  const retakeCardBtn = document.getElementById("retakeCardBtn");

  const submitKycBtn = document.getElementById("submitKycBtn");
  const backBtn = kycForm?.querySelector(".back-btn");

  // Initially disable submit button
  if (submitKycBtn) {
    submitKycBtn.disabled = true;
  }

  // Format Ghana Card input
  if (ghanaCardInput) {
    ghanaCardInput.addEventListener("input", (e) => {
      let value = e.target.value.replace(/\D/g, "");
      if (value.length > 9) {
        value = value.slice(0, 9) + "-" + value.slice(9, 10);
      }
      e.target.value = value;
      validateKycForm();
    });
  }

  // Handle card input change
  if (fileInput) {
    fileInput.addEventListener("change", (e) => {
      const file = e.target.files[0];
      if (file) {
        displayCardPreview(file);
      }
    });
  }

  // Handle selfie input change
  if (selfieInput) {
    selfieInput.addEventListener("change", (e) => {
      const file = e.target.files[0];
      if (file) {
        displaySelfiePreview(file);
      }
    });
  }

  // Handle retake photo buttons
  if (retakeSelfieBtn) {
    retakeSelfieBtn.addEventListener("click", () => {
      resetSelfiePreview();
    });
  }

  if (retakeCardBtn) {
    retakeCardBtn.addEventListener("click", () => {
      resetCardPreview();
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
    const ghanaCard = ghanaCardInput?.value.trim() || "";
    const hasSelfie = selfieInput?.files && selfieInput.files.length > 0;
    const hasGhanaCard = fileInput?.files && fileInput.files.length > 0;

    const isGhanaCardValid = /^\d{9}-[0-9A-Z]$/i.test(ghanaCard);
    const isValid = isGhanaCardValid && hasSelfie && hasGhanaCard;

    if (submitKycBtn) {
      submitKycBtn.disabled = !isValid;
    }

    return isValid;
  }

  // Display selfie preview
  function displaySelfiePreview(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      selfiePreviewImage.src = e.target.result;
      selfiePreview.style.display = "block";
      validateKycForm();
    };
    reader.readAsDataURL(file);
  }

  // Display card preview
  function displayCardPreview(file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      cardPreviewImage.src = e.target.result;
      cardPreview.style.display = "block";
      validateKycForm();
    };
    reader.readAsDataURL(file);
  }

  // Reset selfie preview
  function resetSelfiePreview() {
    if (selfiePreview) selfiePreview.style.display = "none";
    if (selfiePreviewImage) selfiePreviewImage.src = "";
    if (selfieInput) selfieInput.value = "";
    validateKycForm();
  }

  // Reset card preview
  function resetCardPreview() {
    if (cardPreview) cardPreview.style.display = "none";
    if (cardPreviewImage) cardPreviewImage.src = "";
    if (fileInput) fileInput.value = "";
    validateKycForm();
  }

  // Submit KYC
  async function submitKyc() {
    if (!validateKycForm()) return;

    const ghanaCard = ghanaCardInput.value.trim();
    const submitKycBtn = document.getElementById("submitKycBtn");
    const originalText = submitKycBtn.textContent;

    // Disable button while processing
    submitKycBtn.disabled = true;
    submitKycBtn.textContent = "Submitting...";

    try {
      // First, create the account with all data
      const accountData = JSON.parse(
        sessionStorage.getItem("accountData") || "{}"
      );

      // Create FormData for claim submission
      const formData = new FormData();
      formData.append("action", "create_claim");
      formData.append("ghana_card", ghanaCard);

      // Add account data
      formData.append("phone", accountData.phone);
      formData.append("email", accountData.email);
      formData.append("password", accountData.password);
      formData.append("is_account_holder", accountData.isAccountHolder);
      formData.append("terms_agreement", accountData.termsAgreement);
      formData.append("privacy_agreement", accountData.privacyAgreement);

      // Add images if captured
      if (selfieInput && selfieInput.files && selfieInput.files.length > 0) {
        formData.append("selfie_image", selfieInput.files[0]);
      }
      if (fileInput && fileInput.files && fileInput.files.length > 0) {
        formData.append("ghana_card_image", fileInput.files[0]);
      }

      const response = await fetch("api_claim.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();

      if (result.status === "success") {
        // Store claim summary data before clearing accountData
        const accountData = JSON.parse(
          sessionStorage.getItem("accountData") || "{}"
        );
        console.log("Account data from storage:", accountData);
        console.log("API result:", result);

        const claimSummaryData = {
          phone: accountData.phone,
          email: accountData.email,
          isAccountHolder: accountData.isAccountHolder,
          winnerName: result.winner_name || accountData.name || "User",
          prizeName: result.prize_name || "World Cup Experience",
          claimId: result.claim_id,
          submittedAt: new Date().toISOString(),
        };

        console.log("Storing claimSummaryData:", claimSummaryData);
        sessionStorage.setItem(
          "claimSummaryData",
          JSON.stringify(claimSummaryData)
        );

        // Clear session storage
        sessionStorage.removeItem("accountData");

        // Show success modal
        hideModal();
        const successModal = document.getElementById("successModal");
        if (successModal) {
          successModal.classList.add("show");
        }
      } else {
        // Show error message
        const errorMessage = document.getElementById("error-message");
        errorMessage.textContent = result.message || "Failed to submit KYC";
        showModal("errorModal");
      }
    } catch (error) {
      console.error("Error submitting KYC:", error);
      const errorMessage = document.getElementById("error-message");
      errorMessage.textContent = "Network error. Please try again.";
      showModal("errorModal");
    } finally {
      // Re-enable submit button
      submitKycBtn.disabled = false;
      submitKycBtn.textContent = originalText;
    }
  }
}

// Initialize Modals
function initModals() {
  // Close button handlers
  document.querySelectorAll(".close-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      const modal = e.target.closest(".modal");
      if (modal) {
        hideModal(modal.id);
        // Refresh page if closing success modal
        if (modal.id === "successModal") {
          setTimeout(() => {
            window.location.reload();
          }, 300);
        }
      } else {
        hideModal();
      }
    });
  });

  // Error modal OK button handler
  const errorModalOkBtn = document.getElementById("errorModalOkBtn");
  if (errorModalOkBtn) {
    errorModalOkBtn.addEventListener("click", () => {
      hideModal("errorModal");
    });
  }
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

// Reset My Claims Modal to phone verification state
function resetMyClaimsModal() {
  // Show phone section and hide OTP section
  myClaimsPhoneSection.style.display = "block";
  myClaimsOtpSection.style.display = "none";

  // Clear phone input
  myClaimsPhoneInput.value = "";

  // Clear OTP inputs
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

  // Hide resend button
  myClaimsResendOtp.style.display = "none";

  // Clear countdown
  if (myClaimsOtpCountdown) {
    clearInterval(myClaimsOtpCountdown);
  }
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

      row.innerHTML = `
        <td>${drawDate}</td>
        <td>
          <button class="download-btn view-claim-btn" data-claim='${JSON.stringify(
            claim
          )}'>
            View
          </button>
        </td>
      `;
      claimsTableBody.appendChild(row);
    });

    // Add event listeners to view buttons
    document.querySelectorAll(".view-claim-btn").forEach((btn) => {
      btn.addEventListener("click", function () {
        const claimData = JSON.parse(this.getAttribute("data-claim"));
        viewClaimDetail(claimData);
      });
    });
  } else {
    // Show no claims message
    const row = document.createElement("tr");
    row.innerHTML = `
      <td colspan="2" style="text-align: center; padding: 20px;">
        No claims found for this user
      </td>
    `;
    claimsTableBody.appendChild(row);
  }
}

// View Claim Detail
let currentClaimDetail = null;

function viewClaimDetail(claim) {
  console.log("viewClaimDetail called with:", claim);

  currentClaimDetail = claim;

  // Populate the detail modal
  document.getElementById("detailClaimId").textContent = claim.id || "N/A";
  document.getElementById("detailWinnerName").textContent = claim.name || "N/A";
  document.getElementById("detailPhone").textContent = claim.phone || "N/A";

  // Format dates
  const drawDate = claim.draw_date
    ? new Date(claim.draw_date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
      })
    : "N/A";

  const dateClaimed = claim.updatedAt
    ? new Date(claim.updatedAt).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
      })
    : "N/A";

  document.getElementById("detailDrawDate").textContent = drawDate;
  document.getElementById("detailDateClaimed").textContent = dateClaimed;

  // Status with green text for "Claimed"
  const statusElement = document.getElementById("detailStatus");
  const status = claim.is_claimed ? "Claimed" : "Pending";
  statusElement.textContent = status;

  // Apply green text color for claimed status
  if (claim.is_claimed) {
    statusElement.style.color = "#4CAF50";
  } else {
    statusElement.style.color = ""; // Default color for pending
  }

  // Show the modal
  console.log("Showing claimDetailModal");
  showModal("claimDetailModal");
}

// Download Claim Contract
function downloadClaimContract() {
  if (!currentClaimDetail) return;

  // Create a simple contract text
  const contractContent = `
WINNER ACCEPTANCE CONTRACT

Claim ID: ${currentClaimDetail.id}
Winner Name: ${currentClaimDetail.name}
Phone: ${currentClaimDetail.phone}
Draw Week: ${currentClaimDetail.draw_week}
Draw Date: ${
    currentClaimDetail.draw_date
      ? new Date(currentClaimDetail.draw_date).toLocaleDateString()
      : "N/A"
  }
Date Claimed: ${
    currentClaimDetail.updatedAt
      ? new Date(currentClaimDetail.updatedAt).toLocaleDateString()
      : "N/A"
  }

This contract confirms that ${
    currentClaimDetail.name
  } has successfully claimed the World Cup Experience prize for Draw Week ${
    currentClaimDetail.draw_week
  }.
The terms and conditions have been accepted and verified.

Generated on: ${new Date().toLocaleString()}
  `.trim();

  // Create a blob and download
  const blob = new Blob([contractContent], { type: "text/plain" });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `Contract_${
    currentClaimDetail.id
  }_${currentClaimDetail.name.replace(/\s+/g, "_")}.txt`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  window.URL.revokeObjectURL(url);
}

// Download Contract (legacy function for backward compatibility)
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
    // Reset My Claims modal to phone verification state
    resetMyClaimsModal();
    showModal("myClaimsPhoneModal");
  });

  // Back button handlers - only trigger for actual button clicks
  document.querySelectorAll(".back-btn").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();

      // Only proceed if this is actually a button element being clicked
      // and not a file input or other form element
      if (e.target.tagName !== "BUTTON" && !e.target.closest("button")) {
        return;
      }

      // Additional safety check: ensure we're not clicking on input elements
      if (e.target.tagName === "INPUT" || e.target.closest("input, label")) {
        return;
      }

      const currentStep = e.target.closest(".modal").id;
      if (!currentStep) return; // Safety check

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
  const otpSection = document.getElementById("otpSection");
  const otpInput = document.getElementById("otp");
  const otpTimer = document.getElementById("otpTimer");
  const resendOtp = document.getElementById("resendOtp");

  let otpCountdown;
  let otpTimeLeft = 120; // 2 minutes in seconds

  // Next button in phone verification
  document.getElementById("nextBtn").addEventListener("click", () => {
    const phone = phoneInput.value.trim();

    // Set the phone number in the hidden field
    document.getElementById("accountPhone").value = phone;

    console.log("Moving to account creation with phone:", phone);

    // Show the account creation modal
    hideModal();
    showModal("createAccountModal");
  });

  // Account creation form
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
  document.getElementById("finishBtn").addEventListener("click", () => {
    console.log(
      "finishBtn clicked, hiding success modal and showing summary..."
    );
    // Hide success modal and show summary modal
    hideModal();
    showSummaryModal();
  });

  // OK button handler for error modal
  document.querySelectorAll(".ok-btn").forEach((btn) => {
    btn.addEventListener("click", hideModal);
  });

  // Close button for claim detail modal
  const closeClaimDetailBtn = document.getElementById("closeClaimDetailBtn");
  if (closeClaimDetailBtn) {
    closeClaimDetailBtn.addEventListener("click", () => {
      hideModal("claimDetailModal");
    });
  }

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
