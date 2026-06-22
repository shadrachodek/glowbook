/**
 * GlowBook Square Web Payments SDK Integration
 *
 * Handles card tokenization for PCI-compliant payments.
 *
 * @package GlowBook
 * @since   1.1.0
 */

(function($) {
    'use strict';

    var SodekGbSquarePayment = {
        payments: null,
        card: null,
        cashAppPay: null,
        cashAppPaymentRequest: null,
        currentPaymentMethod: 'card',
        initialized: false,
        initializing: false,
        isProcessing: false,
        cashAppToken: null,

        /**
         * Initialize Square Web Payments SDK.
         */
        init: function() {
            // Check if we're in standalone mode and have config
            if (typeof sodekGbSquare === 'undefined') {
                return;
            }

            // Check if Square SDK is loaded
            if (typeof Square === 'undefined') {
                console.error('GlowBook Square: Square SDK not loaded. Check if SDK URL is accessible.');
                this.showError('Payment system failed to load. Please refresh the page.');
                return;
            }

            // Check if card container exists
            if ($('#sodek-gb-square-card-container').length === 0) {
                return;
            }

            // Check if card was already initialized by debug script
            if (window.glowbookSquareCard) {
                this.card = window.glowbookSquareCard;
                this.payments = window.glowbookSquarePayments;
                this.initialized = true;
                // Clear any error messages
                this.clearError();
                return;
            }

            if (this.initializing) {
                return;
            }

            this.initializePayments();
        },

        /**
         * Initialize Square Payments.
         */
        initializePayments: async function() {
            var self = this;
            this.initializing = true;
            try {
                if (!sodekGbSquare.applicationId || !sodekGbSquare.locationId) {
                    console.error('GlowBook Square: Missing applicationId or locationId');
                    this.showError('Payment gateway not configured. Please contact support.');
                    this.initializing = false;
                    return;
                }

                try {
                    this.payments = Square.payments(
                        sodekGbSquare.applicationId,
                        sodekGbSquare.locationId
                    );
                } catch (paymentsError) {
                    console.error('GlowBook Square: Square.payments() failed:', paymentsError);
                    this.showError(sodekGbSquare.strings.paymentError);
                    this.initializing = false;
                    return;
                }

                await this.initializeCard();

                // Initialize Cash App Pay if enabled
                if (sodekGbSquare.cashAppPayEnabled && $('#sodek-gb-cashapp-container').length > 0) {
                    await this.initializeCashApp();
                }

                this.bindPaymentTabs();
                this.initialized = true;
                this.initializing = false;

            } catch (error) {
                console.error('GlowBook Square: Failed to initialize:', error);
                this.showError(sodekGbSquare.strings.paymentError);
                this.initializing = false;
            }
        },

        /**
         * Bind payment method tab events.
         */
        bindPaymentTabs: function() {
            var self = this;
            var $tabs = $('.sodek-gb-payment-tab');

            if ($tabs.length === 0) {
                return;
            }

            $tabs.on('click', function(e) {
                e.preventDefault();
                var method = $(this).data('method');
                self.switchPaymentMethod(method);
            });
        },

        /**
         * Switch payment method.
         *
         * @param {string} method Payment method ('card' or 'cashapp').
         */
        switchPaymentMethod: function(method) {
            this.currentPaymentMethod = method;
            $('#sodek_gb_payment_method_type').val(method);

            // Update tabs
            $('.sodek-gb-payment-tab').removeClass('active').attr('aria-selected', 'false');
            $('.sodek-gb-payment-tab[data-method="' + method + '"]').addClass('active').attr('aria-selected', 'true');

            // Show/hide payment forms
            $('.sodek-gb-payment-method-card').toggle(method === 'card');
            $('.sodek-gb-payment-method-cashapp').toggle(method === 'cashapp');

            // Clear any errors
            this.clearError();

            // Clear Cash App token when switching to card to prevent stale token issues
            if (method === 'card') {
                this.cashAppToken = null;
                $('#sodek_gb_card_token').val('');
            }

            // Update Cash App payment request amount if switching to Cash App
            if (method === 'cashapp' && this.cashAppPay) {
                this.updateCashAppAmount();
            }
        },

        /**
         * Update Cash App payment amount.
         */
        updateCashAppAmount: function() {
            var amount = this.getCurrentPaymentAmount();
            if (this.cashAppPaymentRequest && amount > 0) {
                try {
                    this.cashAppPaymentRequest.update({
                        total: {
                            amount: amount.toFixed(2),
                            label: 'Booking Deposit'
                        }
                    });
                } catch (err) {
                    console.warn('GlowBook Square: Could not update Cash App amount:', err);
                }
            }
        },

        /**
         * Get current payment amount from the form.
         *
         * @returns {number} Amount in dollars.
         */
        getCurrentPaymentAmount: function() {
            var $customDeposit = $('#sodek-gb-custom-deposit');
            if ($customDeposit.length > 0) {
                return parseFloat($customDeposit.val()) || 0;
            }
            // Fallback: try deposit input
            var $depositInput = $('#sodek-gb-deposit-input');
            if ($depositInput.length > 0) {
                return parseFloat($depositInput.val()) || 0;
            }
            return 0;
        },

        /**
         * Initialize Cash App Pay.
         */
        initializeCashApp: async function() {
            var self = this;
            var $container = $('#sodek-gb-cashapp-container');

            if ($container.length === 0) {
                return;
            }

            try {
                var initialAmount = this.getCurrentPaymentAmount() || 1.00;

                // Create payment request
                this.cashAppPaymentRequest = this.payments.paymentRequest({
                    countryCode: sodekGbSquare.countryCode || 'US',
                    currencyCode: sodekGbSquare.currency || 'USD',
                    total: {
                        amount: initialAmount.toFixed(2),
                        label: 'Booking Deposit'
                    }
                });

                // Initialize Cash App Pay
                this.cashAppPay = await this.payments.cashAppPay(this.cashAppPaymentRequest, {
                    redirectURL: window.location.href.split('?')[0],
                    referenceId: 'glowbook-' + Date.now()
                });

                // Attach to container
                await this.cashAppPay.attach('#sodek-gb-cashapp-container');

                // Remove loading state
                $container.find('.sodek-gb-cashapp-loading').hide();

                // Listen for tokenization
                this.cashAppPay.addEventListener('ontokenization', function(event) {
                    var tokenResult = event.detail.tokenResult;

                    if (tokenResult.status === 'OK') {
                        self.cashAppToken = tokenResult.token;
                        $('#sodek_gb_card_token').val(tokenResult.token);

                        // Trigger form submission or notify that payment is ready
                        $(document).trigger('sodek_gb_cashapp_tokenized', [tokenResult.token]);
                    } else {
                        // Clear any stale token on failure
                        self.cashAppToken = null;
                        $('#sodek_gb_card_token').val('');
                        var errorMessage = self.parseCashAppError(tokenResult);
                        self.showError(errorMessage);
                    }
                });

                // Store reference
                window.glowbookCashAppPay = this.cashAppPay;

            } catch (error) {
                console.error('GlowBook Square: Failed to initialize Cash App Pay:', error);
                $container.find('.sodek-gb-cashapp-loading').html(
                    '<span style="color: #d92d20;">Cash App Pay is not available. Please use card payment.</span>'
                );
            }
        },

        /**
         * Parse Cash App error to user-friendly message.
         *
         * @param {Object} result Token result.
         * @returns {string} Error message.
         */
        parseCashAppError: function(result) {
            if (!result.errors || result.errors.length === 0) {
                return 'Cash App payment failed. Please try again or use card payment.';
            }

            var error = result.errors[0];
            return error.message || 'Cash App payment failed. Please try again.';
        },

        /**
         * Initialize the Card payment method.
         */
        initializeCard: async function() {
            var self = this;
            var $container = $('#sodek-gb-square-card-container');

            if ($container.length === 0) {
                return;
            }

            // Add loading indicator
            $container.addClass('loading');

            try {
                // Card options for styling
                // Note: Square SDK doesn't support CSS variables, must use static hex colors
                var primaryColor = '#2271b1';

                // Try to get primary color from CSS variable if available
                if (typeof getComputedStyle !== 'undefined') {
                    var rootStyle = getComputedStyle(document.documentElement);
                    var cssVarColor = rootStyle.getPropertyValue('--sodek-gb-color-primary').trim();
                    if (cssVarColor && cssVarColor.match(/^#[0-9A-Fa-f]{6}$/)) {
                        primaryColor = cssVarColor;
                    }
                }

                var cardOptions = {
                    style: {
                        '.input-container': {
                            borderColor: '#d0d5dd',
                            borderRadius: '8px'
                        },
                        '.input-container.is-focus': {
                            borderColor: primaryColor
                        },
                        '.input-container.is-error': {
                            borderColor: '#d92d20'
                        },
                        'input': {
                            backgroundColor: '#ffffff',
                            color: '#101828',
                            fontFamily: 'inherit',
                            fontSize: '16px'
                        },
                        'input::placeholder': {
                            color: '#98a2b3'
                        },
                        '.message-text': {
                            color: '#d92d20'
                        },
                        '.message-icon': {
                            color: '#d92d20'
                        }
                    }
                };

                try {
                    this.card = await this.payments.card(cardOptions);
                } catch (cardError) {
                    console.error('GlowBook Square: payments.card() failed:', cardError);
                    throw new Error('Card creation failed: ' + cardError.message);
                }

                try {
                    await this.card.attach('#sodek-gb-square-card-container');
                } catch (attachError) {
                    console.error('GlowBook Square: card.attach() failed:', attachError);
                    throw new Error('Card attach failed: ' + attachError.message);
                }

                $container.removeClass('loading');

                // Clear any previous error messages on success
                this.clearError();

                // Store reference for potential reuse
                window.glowbookSquareCard = this.card;
                window.glowbookSquarePayments = this.payments;

                // Add event listeners
                this.card.addEventListener('focusClassAdded', function(event) {
                    $container.addClass('is-focused');
                });

                this.card.addEventListener('focusClassRemoved', function(event) {
                    $container.removeClass('is-focused');
                });

                this.card.addEventListener('errorClassAdded', function(event) {
                    $container.addClass('has-error');
                });

                this.card.addEventListener('errorClassRemoved', function(event) {
                    $container.removeClass('has-error');
                });

            } catch (error) {
                console.error('GlowBook: Failed to initialize card:', error);
                $container.removeClass('loading');
                this.showError(sodekGbSquare.strings.paymentError);
            }
        },

        /**
         * Tokenize the payment method.
         *
         * @returns {Promise<string|null>} Token or null on failure.
         */
        tokenize: async function() {
            var self = this;

            // If Cash App Pay is selected and we already have a token, return it
            if (this.currentPaymentMethod === 'cashapp') {
                if (this.cashAppToken) {
                    return this.cashAppToken;
                }
                // Cash App Pay requires user interaction - show message
                this.showError('Please click the Cash App Pay button to complete payment.');
                return null;
            }

            // Card payment flow
            if (!this.card) {
                this.showError(sodekGbSquare.strings.paymentError);
                return null;
            }

            if (this.isProcessing) {
                return null;
            }

            this.isProcessing = true;
            this.clearError();

            try {
                var tokenResult = await this.card.tokenize();

                if (tokenResult.status === 'OK') {
                    $('#sodek_gb_card_token').val(tokenResult.token);
                    this.isProcessing = false;
                    return tokenResult.token;
                }

                // Handle errors
                var errorMessage = this.parseTokenizeError(tokenResult);
                this.showError(errorMessage);
                this.isProcessing = false;
                return null;

            } catch (error) {
                console.error('GlowBook: Tokenize error:', error);
                this.showError(sodekGbSquare.strings.paymentError);
                this.isProcessing = false;
                return null;
            }
        },

        /**
         * Check if Cash App Pay token is ready.
         *
         * @returns {boolean}
         */
        hasCashAppToken: function() {
            return this.currentPaymentMethod === 'cashapp' && this.cashAppToken !== null;
        },

        /**
         * Get current payment method type.
         *
         * @returns {string} 'card' or 'cashapp'.
         */
        getPaymentMethodType: function() {
            return this.currentPaymentMethod;
        },

        /**
         * Verify buyer (for 3DS/SCA if needed).
         *
         * @param {string} token Card token.
         * @param {number} amount Payment amount in dollars.
         * @returns {Promise<string|null>} Verification token or null.
         */
        verifyBuyer: async function(token, amount) {
            var self = this;

            if (!this.payments || !token) {
                return null;
            }

            try {
                var verificationDetails = {
                    amount: String(amount),
                    billingContact: {},
                    currencyCode: sodekGbSquare.currency || 'USD',
                    intent: 'CHARGE'
                };

                var verificationResults = await this.payments.verifyBuyer(token, verificationDetails);

                if (verificationResults && verificationResults.token) {
                    $('#sodek_gb_verification_token').val(verificationResults.token);
                    return verificationResults.token;
                }

                return null;

            } catch (error) {
                console.error('GlowBook: Verification error:', error);
                // Verification is optional for many regions, so we continue
                return null;
            }
        },

        /**
         * Parse tokenize error to user-friendly message.
         *
         * @param {Object} result Tokenize result.
         * @returns {string} Error message.
         */
        parseTokenizeError: function(result) {
            if (!result.errors || result.errors.length === 0) {
                return sodekGbSquare.strings.paymentError;
            }

            var error = result.errors[0];

            // Map common error codes to messages
            switch (error.code) {
                case 'CARD_TOKEN_EXPIRED':
                    return sodekGbSquare.strings.paymentError;
                case 'CARD_DECLINED':
                    return sodekGbSquare.strings.cardDeclined;
                case 'CVV_FAILURE':
                case 'INVALID_CVV':
                    return sodekGbSquare.strings.invalidCard;
                case 'INVALID_CARD':
                case 'INVALID_EXPIRATION':
                    return sodekGbSquare.strings.invalidCard;
                default:
                    return error.message || sodekGbSquare.strings.paymentError;
            }
        },

        /**
         * Show error message.
         *
         * @param {string} message Error message.
         */
        showError: function(message) {
            var $errorContainer = $('#sodek-gb-payment-errors');

            if ($errorContainer.length === 0) {
                $errorContainer = $('<div id="sodek-gb-payment-errors" class="sodek-gb-payment-errors" role="alert" aria-live="polite"></div>');
                $('#sodek-gb-square-card-container').after($errorContainer);
            }

            $errorContainer.html('<span class="sodek-gb-error-icon">&#9888;</span> ' + message).show();
            $errorContainer.attr('aria-hidden', 'false');

            // Scroll to error
            $('html, body').animate({
                scrollTop: $errorContainer.offset().top - 100
            }, 300);
        },

        /**
         * Clear error message.
         */
        clearError: function() {
            var $errorContainer = $('#sodek-gb-payment-errors');
            $errorContainer.hide().empty().attr('aria-hidden', 'true');
        },

        /**
         * Check if card form is valid.
         *
         * @returns {boolean}
         */
        isValid: function() {
            return this.initialized && this.card !== null;
        },

        /**
         * Reset the payment forms.
         */
        reset: function() {
            if (this.card) {
                this.card.clear();
            }
            this.clearError();
            $('#sodek_gb_card_token').val('');
            $('#sodek_gb_verification_token').val('');
            this.cashAppToken = null;
            this.isProcessing = false;
        },

        /**
         * Destroy and cleanup.
         */
        destroy: function() {
            if (this.card) {
                this.card.destroy();
                this.card = null;
            }
            if (this.cashAppPay) {
                this.cashAppPay.destroy();
                this.cashAppPay = null;
            }
            this.cashAppPaymentRequest = null;
            this.cashAppToken = null;
            this.payments = null;
            this.initialized = false;
        }
    };

    // Export to global scope
    window.SodekGbSquarePayment = SodekGbSquarePayment;

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Small delay to ensure Square SDK is fully loaded
        setTimeout(function() {
            SodekGbSquarePayment.init();
        }, 100);
    });

    // Reinitialize on page fragments loaded (for AJAX page loads)
    $(document).on('sodek_gb_payment_form_ready', function() {
        if (!SodekGbSquarePayment.initialized) {
            SodekGbSquarePayment.init();
        }
    });

})(jQuery);
