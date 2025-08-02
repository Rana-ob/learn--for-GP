// common_login_script.js - JavaScript for Login Forms (Guardian and potentially Foster Family)

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('guardianLoginForm'); // Assuming this is the guardian login form
    const nationalIdInput = document.getElementById('national-id');
    const passwordInput = document.getElementById('password');
    const nationalIdError = document.getElementById('national-id-error');
    const passwordError = document.getElementById('password-error');

    // MFA elements (initially hidden, for future implementation)
    const mfaPrompt = document.getElementById('mfaPrompt');
    const mfaForm = document.getElementById('mfaForm');
    const otpCodeInput = document.getElementById('otp-code');
    const otpError = document.getElementById('otp-error');
    const resendOtpButton = document.getElementById('resendOtpButton');
    const countdownSpan = document.getElementById('countdown');

    let countdownInterval;

    // Function to show error message
    function showError(element, message) {
        element.textContent = message;
        element.style.display = 'block';
    }

    // Function to hide error message
    function hideError(element) {
        element.textContent = '';
        element.style.display = 'none';
    }

    // Basic client-side validation for National ID
    nationalIdInput.addEventListener('input', function() {
        if (this.value.length !== 10 || !/^\d+$/.test(this.value)) {
            showError(nationalIdError, 'رقم الهوية الوطنية يجب أن يتكون من 10 أرقام فقط.');
        } else {
            hideError(nationalIdError);
        }
    });

    // Basic client-side validation for Password
    passwordInput.addEventListener('input', function() {
        if (this.value.length < 6) {
            showError(passwordError, 'الرمز السري يجب أن لا يقل عن 6 أحرف.');
        } else {
            hideError(passwordError);
        }
    });

    // Handle login form submission
    if (loginForm) {
        loginForm.addEventListener('submit', async function(event) {
            event.preventDefault(); // Prevent default form submission

            // Reset error messages
            hideError(nationalIdError);
            hideError(passwordError);

            // Client-side validation
            let isValid = true;
            if (nationalIdInput.value.length !== 10 || !/^\d+$/.test(nationalIdInput.value)) {
                showError(nationalIdError, 'الرجاء إدخال رقم هوية وطنية صحيح مكون من 10 أرقام.');
                isValid = false;
            }
            if (passwordInput.value.length < 6) {
                showError(passwordError, 'الرمز السري يجب أن لا يقل عن 6 أحرف.');
                isValid = false;
            }

            if (!isValid) {
                return; // Stop if client-side validation fails
            }

            const formData = {
                national_id: nationalIdInput.value,
                password: passwordInput.value
            };

            try {
                // المسار هنا معدّل بالفعل ليتطابق مع هيكل مجلداتك (مجلد Rawa)
                const response = await fetch('http://localhost/Rawa/signin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (response.ok) {
                    // Login successful
                    alert(result.message);
                    // Redirect based on user type or a predefined path from backend
                    if (result.redirect) {
                        window.location.href = result.redirect; // e.g., info_guardian.php
                    } else {
                        // Fallback redirect if backend doesn't provide one
                        window.location.href = 'info_guardian.php'; // Default for guardian
                    }
                } else {
                    // Login failed (e.g., wrong credentials, account locked)
                    alert('خطأ في تسجيل الدخول: ' + (result.message || 'حدث خطأ غير معروف.'));
                    console.error('Login failed:', result.message);
                }
            } catch (error) {
                console.error('Error during login:', error);
                alert('حدث خطأ في الاتصال بالخادم. الرجاء المحاولة لاحقاً.');
            }
        });
    }

    // --- MFA Logic (Placeholder - Backend not yet implemented for MFA) ---
    // This part of the code is for the MFA UI and basic countdown.
    // The actual MFA verification backend (sending OTP, verifying OTP) is NOT part of signin.php yet.

    function startCountdown(duration) {
        let timer = duration;
        resendOtpButton.disabled = true; // Disable resend button during countdown
        countdownSpan.textContent = timer;

        countdownInterval = setInterval(() => {
            timer--;
            countdownSpan.textContent = timer;

            if (timer <= 0) {
                clearInterval(countdownInterval);
                resendOtpButton.disabled = false; // Enable resend button
                countdownSpan.textContent = '0';
            }
        }, 1000);
    }

    if (resendOtpButton) {
        resendOtpButton.addEventListener('click', function() {
            // In a real application, you would send a request to your backend
            // to resend the OTP here.
            alert('تم طلب إعادة إرسال الرمز (هذه وظيفة وهمية حالياً).');
            startCountdown(60); // Restart countdown
        });
    }

    if (mfaForm) {
        mfaForm.addEventListener('submit', function(event) {
            event.preventDefault();
            hideError(otpError);

            if (otpCodeInput.value.length !== 6 || !/^\d+$/.test(otpCodeInput.value)) {
                showError(otpError, 'الرجاء إدخال رمز تحقق مكون من 6 أرقام.');
                return;
            }

            // In a real application, you would send the OTP to your backend for verification here.
            // Example:
            // const otpData = {
            //     national_id: nationalIdInput.value, // Or from session/cookie
            //     otp_code: otpCodeInput.value
            // };
            // const response = await fetch('http://localhost/rawa_backend_php/verify_otp.php', { ... });
            // ... handle response ...

            alert('تم التحقق من الرمز (هذه وظيفة وهمية حالياً).');
            // If OTP is valid, redirect to dashboard
            // window.location.href = 'info_guardian.php';
        });
    }
});
