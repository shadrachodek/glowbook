<?php
/**
 * Customer Portal Login Template.
 *
 * @package GlowBook
 * @since   2.0.0
 *
 * @var string $nonce         Security nonce.
 * @var bool   $sms_enabled   Whether SMS is enabled.
 */

defined( 'ABSPATH' ) || exit;

// Set defaults for optional variables
$error       = isset( $error ) ? $error : '';
$success     = isset( $success ) ? $success : '';
$redirect_to = isset( $redirect_to ) ? $redirect_to : Sodek_GB_Standalone_Booking::get_portal_url();
?>

<script>
var sodekGbPortal = {
    ajaxUrl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
    smsEnabled: <?php echo $sms_enabled ? 'true' : 'false'; ?>
};
</script>

<div class="sodek-gb-portal-login">
    <div class="sodek-gb-container">
        <div class="sodek-gb-portal-login-shell">
        <div class="sodek-gb-login-card">
            <!-- Header -->
            <div class="sodek-gb-login-header">
                <span class="sodek-gb-login-kicker"><?php esc_html_e( 'Customer portal', 'glowbook' ); ?></span>
                <h1><?php esc_html_e( 'My Appointments', 'glowbook' ); ?></h1>
                <p><?php esc_html_e( 'Sign in to view and manage your appointments', 'glowbook' ); ?></p>
            </div>

            <?php if ( $error ) : ?>
                <div class="sodek-gb-alert sodek-gb-alert-error">
                    <?php echo esc_html( $error ); ?>
                </div>
            <?php endif; ?>

            <?php if ( $success ) : ?>
                <div class="sodek-gb-alert sodek-gb-alert-success">
                    <?php echo esc_html( $success ); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="sodek-gb-portal-login-form" class="sodek-gb-login-form" method="post">
                <input type="hidden" name="action" value="sodek_gb_portal_login">
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
                <?php wp_nonce_field( 'sodek_gb_portal', 'nonce' ); ?>

                <!-- Step 1: Email Input -->
                <div id="sodek-gb-login-step-1" class="sodek-gb-login-step active">
                    <div class="sodek-gb-form-group">
                        <label for="sodek-gb-login-identifier">
                            <?php echo esc_html( $sms_enabled ? __( 'Email or Phone Number', 'glowbook' ) : __( 'Email Address', 'glowbook' ) ); ?>
                        </label>
                        <input
                            type="<?php echo esc_attr( $sms_enabled ? 'text' : 'email' ); ?>"
                            id="sodek-gb-login-identifier"
                            name="identifier"
                            class="sodek-gb-input"
                            placeholder="<?php echo esc_attr( $sms_enabled ? __( 'Enter your email or phone number', 'glowbook' ) : __( 'Enter your booking email address', 'glowbook' ) ); ?>"
                            required
                        >
                        <p class="sodek-gb-form-help">
                            <?php echo esc_html( $sms_enabled ? __( 'Enter the email or phone number you used when booking.', 'glowbook' ) : __( 'Enter the email address you used when booking.', 'glowbook' ) ); ?>
                        </p>
                    </div>

                    <button type="submit" class="sodek-gb-btn sodek-gb-btn-primary sodek-gb-btn-block">
                        <?php esc_html_e( 'Continue', 'glowbook' ); ?>
                    </button>
                </div>

                <!-- Step 2: Verification Code -->
                <div id="sodek-gb-login-step-2" class="sodek-gb-login-step" style="display: none;">
                    <div class="sodek-gb-verification-sent">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                        </svg>
                        <p id="sodek-gb-verification-message">
                            <?php esc_html_e( 'We sent a verification code to your email.', 'glowbook' ); ?>
                        </p>
                    </div>

                    <div class="sodek-gb-form-group">
                        <label for="sodek-gb-verification-code">
                            <?php esc_html_e( 'Verification Code', 'glowbook' ); ?>
                        </label>
                        <div class="sodek-gb-code-inputs">
                            <input type="text" maxlength="1" class="sodek-gb-code-input" data-index="0" inputmode="numeric" pattern="[0-9]">
                            <input type="text" maxlength="1" class="sodek-gb-code-input" data-index="1" inputmode="numeric" pattern="[0-9]">
                            <input type="text" maxlength="1" class="sodek-gb-code-input" data-index="2" inputmode="numeric" pattern="[0-9]">
                            <input type="text" maxlength="1" class="sodek-gb-code-input" data-index="3" inputmode="numeric" pattern="[0-9]">
                            <input type="text" maxlength="1" class="sodek-gb-code-input" data-index="4" inputmode="numeric" pattern="[0-9]">
                            <input type="text" maxlength="1" class="sodek-gb-code-input" data-index="5" inputmode="numeric" pattern="[0-9]">
                        </div>
                        <input type="hidden" id="sodek-gb-verification-code" name="verification_code">
                    </div>

                    <button type="submit" class="sodek-gb-btn sodek-gb-btn-primary sodek-gb-btn-block" id="sodek-gb-verify-btn" disabled>
                        <?php esc_html_e( 'Verify & Sign In', 'glowbook' ); ?>
                    </button>

                    <div class="sodek-gb-resend-section">
                        <p><?php esc_html_e( "Didn't receive the code?", 'glowbook' ); ?></p>
                        <button type="button" id="sodek-gb-resend-code" class="sodek-gb-btn sodek-gb-btn-link" disabled>
                            <?php esc_html_e( 'Resend Code', 'glowbook' ); ?>
                            <span id="sodek-gb-resend-timer"></span>
                        </button>
                    </div>

                    <button type="button" id="sodek-gb-back-to-step-1" class="sodek-gb-btn sodek-gb-btn-link sodek-gb-btn-back">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        <?php echo esc_html( $sms_enabled ? __( 'Use a different email or phone', 'glowbook' ) : __( 'Use a different email', 'glowbook' ) ); ?>
                    </button>
                </div>
            </form>

            <!-- Alternative: Magic Link -->
            <div class="sodek-gb-login-divider">
                <span><?php esc_html_e( 'or', 'glowbook' ); ?></span>
            </div>

            <div class="sodek-gb-magic-link-section">
                <p><?php esc_html_e( 'Prefer a magic link? We can email you a secure link to sign in.', 'glowbook' ); ?></p>
                <button type="button" id="sodek-gb-request-magic-link" class="sodek-gb-btn sodek-gb-btn-outline sodek-gb-btn-block">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <?php esc_html_e( 'Send Magic Link', 'glowbook' ); ?>
                </button>
            </div>

            <div class="sodek-gb-login-account-note">
                <strong><?php esc_html_e( 'How accounts work', 'glowbook' ); ?></strong>
                <p><?php echo esc_html( $sms_enabled ? __( 'GlowBook supports a hybrid customer model: you can return with a magic link or verification code, and linked WordPress users can be recognized automatically.', 'glowbook' ) : __( 'GlowBook currently supports email-based portal access with magic links, and linked WordPress users can still be recognized automatically.', 'glowbook' ) ); ?></p>
            </div>

            <!-- Book New -->
            <div class="sodek-gb-login-footer">
                <p><?php esc_html_e( "Don't have an appointment yet?", 'glowbook' ); ?></p>
                <a href="<?php echo esc_url( Sodek_GB_Standalone_Booking::get_booking_url() ); ?>" class="sodek-gb-btn sodek-gb-btn-link">
                    <?php esc_html_e( 'Book Your First Appointment', 'glowbook' ); ?>
                </a>
            </div>

        <div class="sodek-gb-login-trust">
                <span><?php esc_html_e( 'Secure access for your appointments, balance payments, and profile updates.', 'glowbook' ); ?></span>
            </div>
        </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('sodek-gb-portal-login-form');
    const step1 = document.getElementById('sodek-gb-login-step-1');
    const step2 = document.getElementById('sodek-gb-login-step-2');
    const identifierInput = document.getElementById('sodek-gb-login-identifier');
    const codeInputs = document.querySelectorAll('.sodek-gb-code-input');
    const hiddenCodeInput = document.getElementById('sodek-gb-verification-code');
    const verifyBtn = document.getElementById('sodek-gb-verify-btn');
    const resendBtn = document.getElementById('sodek-gb-resend-code');
    const resendTimer = document.getElementById('sodek-gb-resend-timer');
    const backBtn = document.getElementById('sodek-gb-back-to-step-1');
    const magicLinkBtn = document.getElementById('sodek-gb-request-magic-link');
    const verificationMessage = document.getElementById('sodek-gb-verification-message');

    let currentStep = 1;
    let customerId = null;
    let resendCountdown = 0;

    // Code input handling
    codeInputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            const value = e.target.value.replace(/\D/g, '');
            e.target.value = value;

            if (value && index < codeInputs.length - 1) {
                codeInputs[index + 1].focus();
            }

            updateHiddenCode();
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !e.target.value && index > 0) {
                codeInputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            const chars = paste.split('');

            codeInputs.forEach((inp, i) => {
                inp.value = chars[i] || '';
            });

            updateHiddenCode();

            const lastIndex = Math.min(chars.length, codeInputs.length) - 1;
            if (lastIndex >= 0) {
                codeInputs[lastIndex].focus();
            }
        });
    });

    function updateHiddenCode() {
        const code = Array.from(codeInputs).map(i => i.value).join('');
        hiddenCodeInput.value = code;
        verifyBtn.disabled = code.length !== 6;
    }

    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]:not([style*="display: none"])');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="sodek-gb-spinner"></span>';

        try {
            if (currentStep === 1) {
                // Step 1: Send login request
                const identifier = identifierInput.value.trim();
                const isEmail = identifier.includes('@');

                if (!sodekGbPortal.smsEnabled && !isEmail) {
                    showError('<?php esc_attr_e( 'Please enter the email address you used when booking.', 'glowbook' ); ?>');
                    return;
                }

                const response = await fetch(sodekGbPortal.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'sodek_gb_portal_login',
                        email: isEmail ? identifier : '',
                        phone: isEmail ? '' : identifier,
                        nonce: form.querySelector('[name="nonce"]').value
                    })
                });

                const data = await response.json();

                if (data.success) {
                    if (data.data.require_verification) {
                        // SMS verification required
                        customerId = data.data.customer_id;
                        verificationMessage.textContent = data.data.message;

                        step1.style.display = 'none';
                        step2.style.display = 'block';
                        currentStep = 2;
                        codeInputs[0].focus();
                        startResendTimer();
                    } else if (data.data.magic_link_sent) {
                        // Magic link sent via email
                        showSuccess(data.data.message);
                    } else if (data.data.redirect_url) {
                        // Direct login
                        window.location.href = data.data.redirect_url;
                    }
                } else {
                    showError(data.data?.message || '<?php esc_attr_e( 'An error occurred. Please try again.', 'glowbook' ); ?>');
                }
            } else {
                // Step 2: Verify code
                const response = await fetch(sodekGbPortal.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'sodek_gb_portal_verify',
                        customer_id: customerId,
                        code: hiddenCodeInput.value,
                        nonce: form.querySelector('[name="nonce"]').value
                    })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = data.data.redirect_url || '<?php echo esc_url( Sodek_GB_Standalone_Booking::get_portal_url() ); ?>';
                } else {
                    showError(data.data.message || '<?php esc_attr_e( 'Invalid code. Please try again.', 'glowbook' ); ?>');
                    codeInputs.forEach(i => i.value = '');
                    codeInputs[0].focus();
                    updateHiddenCode();
                }
            }
        } catch (error) {
            showError('<?php esc_attr_e( 'An error occurred. Please try again.', 'glowbook' ); ?>');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    // Back button
    backBtn.addEventListener('click', function() {
        step2.style.display = 'none';
        step1.style.display = 'block';
        currentStep = 1;
        codeInputs.forEach(i => i.value = '');
        updateHiddenCode();
    });

    // Resend code - resend verification to the same customer
    resendBtn.addEventListener('click', async function() {
        if (resendCountdown > 0) return;

        resendBtn.disabled = true;

        try {
            const identifier = identifierInput.value.trim();
            const isEmail = identifier.includes('@');

            if (!sodekGbPortal.smsEnabled && !isEmail) {
                showError('<?php esc_attr_e( 'Please enter the email address you used when booking.', 'glowbook' ); ?>');
                return;
            }

            const response = await fetch(sodekGbPortal.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sodek_gb_portal_login',
                    email: isEmail ? identifier : '',
                    phone: isEmail ? '' : identifier,
                    nonce: form.querySelector('[name="nonce"]').value
                })
            });

            const data = await response.json();

            if (data.success) {
                showSuccess('<?php esc_attr_e( 'Code resent successfully!', 'glowbook' ); ?>');
                startResendTimer();
            } else {
                showError(data.data?.message || '<?php esc_attr_e( 'Failed to resend code.', 'glowbook' ); ?>');
            }
        } catch (error) {
            showError('<?php esc_attr_e( 'Failed to resend code.', 'glowbook' ); ?>');
        }
    });

    // Magic link - uses the same login handler, which sends magic link for email
    magicLinkBtn.addEventListener('click', async function() {
        const identifier = identifierInput.value.trim();

        if (!identifier) {
            identifierInput.focus();
            showError('<?php esc_attr_e( 'Please enter your email first.', 'glowbook' ); ?>');
            return;
        }

        if (!identifier.includes('@')) {
            showError('<?php esc_attr_e( 'Magic link can only be sent to email addresses.', 'glowbook' ); ?>');
            return;
        }

        magicLinkBtn.disabled = true;
        const originalText = magicLinkBtn.innerHTML;
        magicLinkBtn.innerHTML = '<span class="sodek-gb-spinner"></span>';

        try {
            const response = await fetch(sodekGbPortal.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'sodek_gb_portal_login',
                    email: identifier,
                    phone: '',
                    nonce: form.querySelector('[name="nonce"]').value
                })
            });

            const data = await response.json();

            if (data.success) {
                showSuccess(data.data?.message || '<?php esc_attr_e( 'Magic link sent! Check your email.', 'glowbook' ); ?>');
            } else {
                showError(data.data?.message || '<?php esc_attr_e( 'Failed to send magic link.', 'glowbook' ); ?>');
            }
        } catch (error) {
            showError('<?php esc_attr_e( 'Failed to send magic link.', 'glowbook' ); ?>');
        } finally {
            magicLinkBtn.disabled = false;
            magicLinkBtn.innerHTML = originalText;
        }
    });

    function startResendTimer() {
        resendCountdown = 60;
        resendBtn.disabled = true;

        const interval = setInterval(function() {
            resendCountdown--;
            resendTimer.textContent = ' (' + resendCountdown + 's)';

            if (resendCountdown <= 0) {
                clearInterval(interval);
                resendTimer.textContent = '';
                resendBtn.disabled = false;
            }
        }, 1000);
    }

    function showError(message) {
        const existingAlert = document.querySelector('.sodek-gb-alert');
        if (existingAlert) existingAlert.remove();

        const alert = document.createElement('div');
        alert.className = 'sodek-gb-alert sodek-gb-alert-error';
        alert.textContent = message;

        const header = document.querySelector('.sodek-gb-login-header');
        header.after(alert);

        setTimeout(() => alert.remove(), 5000);
    }

    function showSuccess(message) {
        const existingAlert = document.querySelector('.sodek-gb-alert');
        if (existingAlert) existingAlert.remove();

        const alert = document.createElement('div');
        alert.className = 'sodek-gb-alert sodek-gb-alert-success';
        alert.textContent = message;

        const header = document.querySelector('.sodek-gb-login-header');
        header.after(alert);

        setTimeout(() => alert.remove(), 5000);
    }
});
</script>
