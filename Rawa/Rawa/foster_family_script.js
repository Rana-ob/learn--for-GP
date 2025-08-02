document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('fosterFamilySignupForm');

    // Conditional Logic for "Study Stage"
    const childStudyRadios = document.querySelectorAll('input[name="child_study"]');
    const studyStageGroup = document.getElementById('study-stage-group');
    const studyStageSelect = document.getElementById('study-stage');

    function toggleStudyStage() {
        if (document.querySelector('input[name="child_study"]:checked')?.value === 'نعم') {
            studyStageGroup.style.display = 'block';
            studyStageSelect.setAttribute('required', 'true');
        } else {
            studyStageGroup.style.display = 'none';
            studyStageSelect.removeAttribute('required');
            studyStageSelect.value = ''; // Clear selected value
        }
    }

    childStudyRadios.forEach(radio => {
        radio.addEventListener('change', toggleStudyStage);
    });
    toggleStudyStage(); // Initial check on page load

    // Validation for "Official Adoption Decision"
    const officialAdoptionDecisionRadios = document.querySelectorAll('input[name="official_adoption_decision"]');
    const adoptionDecisionError = document.getElementById('adoption-decision-error');

    function checkOfficialAdoptionDecision() {
        if (document.querySelector('input[name="official_adoption_decision"]:checked')?.value === 'لا') {
            adoptionDecisionError.style.display = 'block';
            return false;
        } else {
            adoptionDecisionError.style.display = 'none';
            return true;
        }
    }

    officialAdoptionDecisionRadios.forEach(radio => {
        radio.addEventListener('change', checkOfficialAdoptionDecision);
    });

    // Validation for "Adoption Type"
    const adoptionTypeRadios = document.querySelectorAll('input[name="adoption_type"]');
    const adoptionTypeError = document.getElementById('adoption-type-error');

    function checkAdoptionType() {
        if (document.querySelector('input[name="adoption_type"]:checked')?.value === 'مؤقت') {
            adoptionTypeError.style.display = 'block';
            return false;
        } else {
            adoptionTypeError.style.display = 'none';
            return true;
        }
    }

    adoptionTypeRadios.forEach(radio => {
        radio.addEventListener('change', checkAdoptionType);
    });


    // Password Validation
    const passwordInput = document.getElementById('password');
    const passwordError = document.getElementById('password-error');

    passwordInput.addEventListener('input', function() {
        if (passwordInput.value.length < 8) {
            passwordError.textContent = 'الرمز السري يجب أن يكون 8 أحرف على الأقل.';
        } else {
            passwordError.textContent = '';
        }
    });

    // National ID Validation (simplified for 10 digits)
    const nationalIdInput = document.getElementById('national-id');
    const nationalIdError = document.getElementById('national-id-error');

    nationalIdInput.addEventListener('input', function() {
        const value = nationalIdInput.value;
        // Check if it's exactly 10 digits and contains only numbers
        if (!/^\d{10}$/.test(value)) {
            nationalIdError.textContent = 'رقم الهوية الوطنية يجب أن يكون 10 أرقام فقط.';
        } else {
            nationalIdError.textContent = '';
        }
    });

    // Form Submission Logic
    form.addEventListener('submit', function(event) {
        let isValid = true;

        // Re-check all validations before submission
        if (!checkOfficialAdoptionDecision()) {
            isValid = false;
        }
        if (!checkAdoptionType()) {
            isValid = false;
        }

        // Check password length
        if (passwordInput.value.length < 8) {
            passwordError.textContent = 'الرمز السري يجب أن يكون 8 أحرف على الأقل.';
            isValid = false;
        } else {
            passwordError.textContent = '';
        }

        // Check National ID length and format
        const nationalIdValue = nationalIdInput.value;
        if (!/^\d{10}$/.test(nationalIdValue)) {
            nationalIdError.textContent = 'رقم الهوية الوطنية يجب أن يكون 10 أرقام فقط.';
            isValid = false;
        } else {
            nationalIdError.textContent = '';
        }
        
        // If "Child Study" is "نعم", "Study Stage" must be selected
        if (document.querySelector('input[name="child_study"]:checked')?.value === 'نعم' && studyStageSelect.value === '') {
            // This case should be handled by the 'required' attribute on the select,
            // but for explicit JS validation:
            // You might want a more specific error message here, or just let the 'required' attribute handle it
            isValid = false;
            // Example of a specific error for study stage (you'd need to add a div for it)
            // alert('الرجاء اختيار المرحلة الدراسية.');
        }


        if (!isValid) {
            event.preventDefault(); // Prevent form submission
            alert('الرجاء مراجعة البيانات المدخلة وتصحيح الأخطاء.'); // Generic alert
        }
    });
});

// Terms and conditions popup functionality
const termsLink = document.querySelector('.terms-link');
const termsPopup = document.getElementById('termsPopup');
const closeTerms = document.getElementById('closeTerms');

if (termsLink && termsPopup && closeTerms) {
    termsLink.addEventListener('click', function(e) {
        e.preventDefault();
        termsPopup.style.display = 'flex';
        document.querySelector('.terms-content').scrollTop = 0;
    });
    
    closeTerms.addEventListener('click', function() {
        termsPopup.style.display = 'none';
    });
    
    // Close when clicking outside the popup content
    termsPopup.addEventListener('click', function(e) {
        if (e.target === termsPopup) {
            termsPopup.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Terms and conditions popup functionality
    const termsLink = document.querySelector('.terms-link');
    const termsPopup = document.getElementById('termsPopup');
    const closeTerms = document.getElementById('closeTerms');
    
    if (termsLink && termsPopup && closeTerms) {
        termsLink.addEventListener('click', function(e) {
            e.preventDefault();
            termsPopup.style.display = 'flex';
            document.querySelector('.terms-content').scrollTop = 0;
        });
        
        closeTerms.addEventListener('click', function() {
            termsPopup.style.display = 'none';
        });
        
        termsPopup.addEventListener('click', function(e) {
            if (e.target === termsPopup) {
                termsPopup.style.display = 'none';
            }
        });
    }
    
    // Show/hide study level based on child study status
    const studyRadios = document.querySelectorAll('input[name="is_child_studying"]');
    const studyLevelGroup = document.getElementById('child-study-level-group');
    
    studyRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            studyLevelGroup.style.display = this.value === 'نعم' ? 'block' : 'none';
        });
    });
    
    // Validate fostering decision
    const fosteringRadios = document.querySelectorAll('input[name="official_fostering_decision"]');
    const fosteringError = document.getElementById('fostering-decision-error');
    
    fosteringRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            fosteringError.style.display = this.value === 'لا' ? 'block' : 'none';
        });
    });
    
    // Form validation
    const signupForm = document.getElementById('fosterFamilySignupForm');
    
    if (signupForm) {
        signupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate national ID
            const nationalId = document.getElementById('national-id').value;
            if (nationalId.length !== 10 || !/^\d+$/.test(nationalId)) {
                document.getElementById('national-id-error').textContent = 'يجب أن يتكون رقم الهوية من 10 أرقام';
                document.getElementById('national-id-error').style.display = 'block';
                return;
            } else {
                document.getElementById('national-id-error').style.display = 'none';
            }
            
            // Validate password
            const password = document.getElementById('password').value;
            if (password.length < 8) {
                document.getElementById('password-error').textContent = 'يجب أن يكون الرمز السري مكون من 8 أحرف على الأقل';
                document.getElementById('password-error').style.display = 'block';
                return;
            } else {
                document.getElementById('password-error').style.display = 'none';
            }
            
            // Validate terms checkbox
            if (!document.querySelector('input[name="terms_and_conditions"]:checked')) {
                alert('يجب الموافقة على الشروط والأحكام');
                return;
            }
            
            // If all validations pass
            alert('تم تسجيل حساب الأسرة الكافلة بنجاح!');
            // this.submit(); // Uncomment to actually submit the form
        });
    }
});