/**
 * registration.js
 * MedLink Registration Handler
 */

document.addEventListener('DOMContentLoaded', () => {

    const registerForm =
        document.getElementById('registerForm');

    if (registerForm) {

        registerForm.addEventListener(
            'submit',
            handleRegistration
        );

        // Real-time email validation
        const emailInput =
            document.getElementById('email');

        if (emailInput) {

            emailInput.addEventListener(
                'blur',
                checkEmailAvailability
            );
        }

        // Real-time password validation
        const passwordInput =
            document.getElementById('password');

        if (passwordInput) {

            passwordInput.addEventListener(
                'input',
                checkPasswordStrength
            );
        }
    }

    // Password show/hide
    const passwordToggles =
        document.querySelectorAll('.password-toggle');

    passwordToggles.forEach(toggle => {

        toggle.addEventListener(
            'click',
            handlePasswordToggle
        );
    });
});

/**
 * HANDLE REGISTRATION
 */
async function handleRegistration(event) {

    event.preventDefault();

    // Validate form
    if (!validateRegistrationForm()) {
        return;
    }

    const form =
        event.target;

    const formData =
        new FormData(form);

    const statusElement =
        document.getElementById('registerStatus');

    // Show loading
    if (statusElement) {

        statusElement.textContent =
            'Creating your account...';

        statusElement.className =
            'form-status processing';
    }

    clearFieldErrors();

    try {

        /**
         * PATH CORRECTED
         *
         * From:
         * frontend/pages/public/register.html
         *
         * To:
         * backend/auth/register.php
         */
        const response = await fetch(
            '../../../backend/auth/register.php',
            {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            }
        );

        // Debugging
        const responseText =
            await response.text();

        console.log(
            'SERVER RESPONSE:',
            responseText
        );

        const data =
            parseJsonResponse(responseText);

        // SUCCESS
        if (data.success) {

            if (statusElement) {

                statusElement.textContent =
                    data.message ||
                    'Registration successful';

                statusElement.className =
                    'form-status success';
            }

            // Reset form
            form.reset();

            // Remove password strength box
            const strengthBox =
                document.querySelector(
                    '.password-strength'
                );

            if (strengthBox) {
                strengthBox.remove();
            }

            // Redirect
            setTimeout(() => {

                window.location.href =
                    './login.html';

            }, 2000);

        } else {

            // FAILED
            if (statusElement) {

                statusElement.textContent =
                    data.message ||
                    'Registration failed';

                statusElement.className =
                    'form-status error';
            }

            // Display field errors
            if (
                data.errors &&
                Object.keys(data.errors).length > 0
            ) {

                displayFieldErrors(
                    data.errors
                );
            }
        }

    } catch (error) {

        console.error(
            'Registration Error:',
            error
        );

        if (statusElement) {

            statusElement.textContent =
                error.message ||
                'Connection error. Please try again.';

            statusElement.className =
                'form-status error';
        }
    }
}

/**
 * PARSE JSON RESPONSE
 */
function parseJsonResponse(responseText) {

    try {

        return JSON.parse(responseText);

    } catch (error) {

        const cleanText =
            String(responseText || '')
                .replace(/<[^>]*>/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();

        throw new Error(
            cleanText ||
            'Server returned an invalid response'
        );
    }
}

/**
 * DISPLAY FIELD ERRORS
 */
function displayFieldErrors(errors) {

    clearFieldErrors();

    for (const [field, message]
        of Object.entries(errors)) {

        const errorElement =
            document.getElementById(
                field + 'Error'
            );

        if (errorElement) {

            errorElement.textContent =
                message;
        }

        const inputElement =
            document.getElementById(field);

        if (inputElement) {

            inputElement.classList.add(
                'input-error'
            );

            inputElement.addEventListener(
                'focus',
                () => {

                    inputElement.classList.remove(
                        'input-error'
                    );

                    if (errorElement) {
                        errorElement.textContent = '';
                    }
                },
                { once: true }
            );
        }
    }
}

/**
 * CLEAR ERRORS
 */
function clearFieldErrors() {

    document
        .querySelectorAll('.field-error')
        .forEach(el => {
            el.textContent = '';
        });

    document
        .querySelectorAll('.input-error')
        .forEach(el => {
            el.classList.remove('input-error');
        });
}

/**
 * CHECK EMAIL AVAILABILITY
 */
async function checkEmailAvailability(event) {

    const email =
        event.target.value.trim();

    if (!email) {
        return;
    }

    try {

        const formData =
            new FormData();

        formData.append(
            'email',
            email
        );

        /**
         * PATH CORRECTED
         */
        const response = await fetch(
            '../../../backend/auth/check-email.php',
            {
                method: 'POST',
                body: formData
            }
        );

        const data =
            parseJsonResponse(
                await response.text()
            );

        const errorElement =
            document.getElementById(
                'emailError'
            );

        if (
            data.success &&
            data.data &&
            !data.data.available
        ) {

            if (errorElement) {

                errorElement.textContent =
                    'Email already exists';
            }

            event.target.classList.add(
                'input-error'
            );

        } else {

            if (errorElement) {
                errorElement.textContent = '';
            }

            event.target.classList.remove(
                'input-error'
            );
        }

    } catch (error) {

        console.error(
            'Email Check Error:',
            error
        );

        const errorElement =
            document.getElementById(
                'emailError'
            );

        if (errorElement) {
            errorElement.textContent =
                error.message ||
                'Unable to check email right now';
        }
    }
}

/**
 * CHECK PASSWORD STRENGTH
 */
async function checkPasswordStrength(event) {

    const password =
        event.target.value;

    if (!password) {
        return;
    }

    try {

        const formData =
            new FormData();

        formData.append(
            'password',
            password
        );

        /**
         * PATH CORRECTED
         */
        const response = await fetch(
            '../../../backend/auth/validate-password.php',
            {
                method: 'POST',
                body: formData
            }
        );

        const data =
            parseJsonResponse(
                await response.text()
            );

        if (data.success) {
            displayPasswordStrength(data);
        }

    } catch (error) {

        console.error(
            'Password Validation Error:',
            error
        );
    }
}

/**
 * DISPLAY PASSWORD STRENGTH
 */
function displayPasswordStrength(data) {

    let strengthContainer =
        document.querySelector(
            '.password-strength'
        );

    if (!strengthContainer) {

        strengthContainer =
            document.createElement('div');

        strengthContainer.className =
            'password-strength';

        const passwordField =
            document.getElementById('password');

        if (
            passwordField &&
            passwordField.parentNode
        ) {

            passwordField.parentNode.insertBefore(
                strengthContainer,
                passwordField.nextSibling
            );
        }
    }

    const strength =
        data.strength || 'weak';

    strengthContainer.innerHTML = `
        <div class="strength-meter ${strength}">
            <div class="strength-text">
                Password Strength:
                <strong>${strength.toUpperCase()}</strong>
            </div>
        </div>
    `;
}

/**
 * SHOW / HIDE PASSWORD
 */
function handlePasswordToggle(event) {

    event.preventDefault();

    const button =
        event.currentTarget;

    const targetId =
        button.dataset.target;

    const input =
        document.getElementById(targetId);

    if (!input) {
        return;
    }

    if (input.type === 'password') {

        input.type = 'text';
        button.textContent = 'Hide';

    } else {

        input.type = 'password';
        button.textContent = 'Show';
    }
}

/**
 * FORM VALIDATION
 */
function validateRegistrationForm() {

    const fields = {

        first_name:
            document.getElementById('first_name'),

        last_name:
            document.getElementById('last_name'),

        email:
            document.getElementById('email'),

        password:
            document.getElementById('password'),

        phone:
            document.getElementById('phone'),

        gender:
            document.getElementById('gender')
    };

    let isValid = true;

    clearFieldErrors();

    // Required fields
    for (const [key, field]
        of Object.entries(fields)) {

        if (
            field &&
            !String(field.value).trim()
        ) {

            field.classList.add(
                'input-error'
            );

            const errorElement =
                document.getElementById(
                    key + 'Error'
                );

            if (errorElement) {

                errorElement.textContent =
                    `${formatFieldName(key)} is required`;
            }

            isValid = false;
        }
    }

    // Email validation
    const email =
        fields.email?.value.trim();

    const emailPattern =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (
        email &&
        !emailPattern.test(email)
    ) {

        fields.email.classList.add(
            'input-error'
        );

        document.getElementById(
            'emailError'
        ).textContent =
            'Invalid email address';

        isValid = false;
    }

    // Password validation
    const password =
        fields.password?.value;

    if (
        password &&
        password.length < 8
    ) {

        fields.password.classList.add(
            'input-error'
        );

        document.getElementById(
            'passwordError'
        ).textContent =
            'Password must be at least 8 characters';

        isValid = false;
    }

    return isValid;
}

/**
 * FORMAT FIELD NAME
 */
function formatFieldName(name) {

    return name
        .replace('_', ' ')
        .replace(/\b\w/g, char =>
            char.toUpperCase()
        );
}
