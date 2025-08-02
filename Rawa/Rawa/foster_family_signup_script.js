// foster_family_signup_script.js - JavaScript for Foster Family Signup Form

document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.getElementById('fosterFamilySignupForm');
    const nationalIdInput = document.getElementById('national-id');
    const passwordInput = document.getElementById('password');
    const officialFosteringDecisionRadios = document.querySelectorAll('input[name="official_fostering_decision"]');
    const isChildStudyingRadios = document.querySelectorAll('input[name="is_child_studying"]');
    const childStudyLevelGroup = document.getElementById('child-study-level-group');
    const childStudyLevelSelect = document.getElementById('child-study-level');

    const nationalIdError = document.getElementById('national-id-error');
    const passwordError = document.getElementById('password-error');
    const fosteringDecisionError = document.getElementById('fostering-decision-error');

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

    // Event listener for National ID input validation
    nationalIdInput.addEventListener('input', function() {
        if (this.value.length !== 10 || !/^\d+$/.test(this.value)) {
            showError(nationalIdError, 'رقم الهوية الوطنية يجب أن يتكون من 10 أرقام فقط.');
        } else {
            hideError(nationalIdError);
        }
    });

    // Event listener for Password input validation (basic)
    passwordInput.addEventListener('input', function() {
        if (this.value.length < 6) { // Minimum 6 characters for client-side, backend has 8
            showError(passwordError, 'الرمز السري يجب أن لا يقل عن 6 أحرف.');
        } else {
            hideError(passwordError);
        }
    });

    // Event listener for "هل الطفل المكفول يدرس حاليًا؟" radio buttons
    isChildStudyingRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'نعم') {
                childStudyLevelGroup.style.display = 'block';
                childStudyLevelSelect.setAttribute('required', 'required'); // Make select required
            } else {
                childStudyLevelGroup.style.display = 'none';
                childStudyLevelSelect.removeAttribute('required'); // Remove required
                childStudyLevelSelect.value = ''; // Reset select value
            }
        });
    });

    // Event listener for form submission
    signupForm.addEventListener('submit', async function(event) {
        event.preventDefault(); // Prevent default form submission

        // Reset all error messages
        hideError(nationalIdError);
        hideError(passwordError);
        hideError(fosteringDecisionError);

        // Client-side validation before sending data
        let isValid = true;

        if (nationalIdInput.value.length !== 10 || !/^\d+$/.test(nationalIdInput.value)) {
            showError(nationalIdError, 'رقم الهوية الوطنية يجب أن يتكون من 10 أرقام فقط.');
            isValid = false;
        }
        if (passwordInput.value.length < 6) { // Client-side check
            showError(passwordError, 'الرمز السري يجب أن لا يقل عن 6 أحرف.');
            isValid = false;
        }

        const selectedFosteringDecision = document.querySelector('input[name="official_fostering_decision"]:checked');
        if (!selectedFosteringDecision) {
            alert('الرجاء الإجابة على سؤال "هل تملك قرار كفالة رسمي؟"');
            isValid = false;
        } else if (selectedFosteringDecision.value === 'لا') {
            showError(fosteringDecisionError, 'نعتذر، لا يمكن إكمال التسجيل بدون قرار كفالة رسمي.');
            isValid = false;
        }

        const selectedIsChildStudying = document.querySelector('input[name="is_child_studying"]:checked');
        if (!selectedIsChildStudying) {
            alert('الرجاء الإجابة على سؤال "هل الطفل المكفول يدرس حاليًا؟"');
            isValid = false;
        } else if (selectedIsChildStudying.value === 'نعم' && childStudyLevelSelect.value === '') {
            alert('الرجاء اختيار المرحلة الدراسية للطفل المكفول.');
            isValid = false;
        }

        const selectedFosteringType = document.querySelector('input[name="fostering_type"]:checked');
        if (!selectedFosteringType) {
            alert('الرجاء الإجابة على سؤال "ما نوع الكفالة؟"');
            isValid = false;
        }

        const selectedChildAgeGroup = document.querySelector('input[name="child_age_group"]:checked');
        if (!selectedChildAgeGroup) {
            alert('الرجاء الإجابة على سؤال "الفئة العمرية للطفل المكفول؟"');
            isValid = false;
        }

        // Check terms and conditions checkbox
        const termsCheckbox = document.querySelector('input[name="terms_and_conditions"]');
        if (!termsCheckbox || !termsCheckbox.checked) {
            alert('الرجاء الموافقة على الشروط والأحكام.');
            isValid = false;
        }


        if (!isValid) {
            return; // Stop form submission if validation fails
        }

        // Collect form data
        const formData = {
            national_id: nationalIdInput.value,
            password: passwordInput.value,
            user_type: 'foster_family', // Explicitly set user type for backend
            foster_family_head_name: document.getElementById('head-name').value,
            has_official_fostering_decision: selectedFosteringDecision.value === 'نعم' ? 1 : 0,
            fostering_type: selectedFosteringType.value,
            child_age_group: selectedChildAgeGroup.value,
            is_child_studying: selectedIsChildStudying.value === 'نعم' ? 1 : 0,
            child_study_level: selectedIsChildStudying.value === 'نعم' ? childStudyLevelSelect.value : null
        };

        // Map Arabic ENUM values to English for backend
        const fosteringTypeMap = {
            'مؤقت': 'temporary',
            'دائم': 'permanent'
        };
        formData.fostering_type = fosteringTypeMap[formData.fostering_type] || formData.fostering_type;

        const childAgeGroupMap = {
            'أقل من 6 سنوات': 'under_6',
            '6 سنوات أو أكثر': '6_or_more'
        };
        formData.child_age_group = childAgeGroupMap[formData.child_age_group] || formData.child_age_group;

        // Send data to PHP backend
        try {
            const response = await fetch('http://localhost/Rawa/signup.php', { // Adjust URL to your Rawa folder
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (response.ok) {
                alert(result.message);
                // Redirect to Rawa.html if backend sends a redirect, otherwise fallback
                if (result.redirect) {
                    window.location.href = result.redirect; // Use the redirect path from backend (should be Rawa.html)
                } else {
                    window.location.href = 'foster_family_login.html'; // Fallback redirect
                }
            } else {
                alert('خطأ في التسجيل: ' + (result.message || 'حدث خطأ غير معروف.'));
            }
        } catch (error) {
            console.error('Error during signup:', error);
            alert('حدث خطأ في الاتصال بالخادم. الرجاء المحاولة لاحقاً.');
        }
    });
});
