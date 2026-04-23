/**
 * GlowBook Public Scripts
 */

(function($) {
    'use strict';

    var SodekGb = {
        selectedDate: null,
        selectedTime: null,
        currentMonth: new Date().getMonth(),
        currentYear: new Date().getFullYear(),
        availableDates: [],
        serviceId: null,
        basePrice: 0,
        baseDuration: 0,
        baseDeposit: 0,

        init: function() {
            // Reset any stuck loading states on page load
            this.resetButtonState();

            this.bindEvents();
            this.initProductPageForm();
            this.initShortcodeForm();
            this.initProductGallery();
            this.initPoliciesAccordion();
        },

        resetButtonState: function() {
            // Remove loading class from buttons (in case page was reloaded after failed submission)
            $('.sodek-gb-submit-btn, .sodek-gb-book-button').removeClass('sodek-gb-loading sodek-gb-success');
            $('.sodek-gb-btn-loading').hide();
            $('.sodek-gb-btn-content, .sodek-gb-btn-text, .sodek-gb-btn-price').show();
        },

        initPoliciesAccordion: function() {
            var $toggle = $('.sodek-gb-policies-toggle');
            if ($toggle.length === 0) return;

            $toggle.on('click', function() {
                var $btn = $(this);
                var $content = $('#sodek-gb-policies-content');
                var isExpanded = $btn.attr('aria-expanded') === 'true';

                // Toggle state
                $btn.attr('aria-expanded', !isExpanded);
                $content.attr('aria-hidden', isExpanded);
            });
        },

        bindEvents: function() {
            var self = this;

            // Service selector
            $(document).on('change', '.sodek-gb-service-select', this.onServiceSelect.bind(this));

            // Calendar navigation - use mousedown as fallback for themes that block click
            $(document).on('click.sodekgb mousedown.sodekgb', '.sodek-gb-calendar-prev', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (e.type === 'mousedown' && e.which !== 1) return; // Only left click
                if (e.type === 'mousedown') {
                    // Prevent double firing with click
                    $(this).data('mousedown-fired', true);
                    setTimeout(function() { $(e.target).data('mousedown-fired', false); }, 100);
                }
                if (e.type === 'click' && $(this).data('mousedown-fired')) return;
                self.prevMonth(e);
            });

            $(document).on('click.sodekgb mousedown.sodekgb', '.sodek-gb-calendar-next', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (e.type === 'mousedown' && e.which !== 1) return;
                if (e.type === 'mousedown') {
                    $(this).data('mousedown-fired', true);
                    setTimeout(function() { $(e.target).data('mousedown-fired', false); }, 100);
                }
                if (e.type === 'click' && $(this).data('mousedown-fired')) return;
                self.nextMonth(e);
            });

            // Day selection - use mousedown as fallback for themes that block click
            $(document).on('click.sodekgb mousedown.sodekgb', '.sodek-gb-calendar-day.sodek-gb-day-available', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (e.type === 'mousedown' && e.which !== 1) return;
                if (e.type === 'mousedown') {
                    $(this).data('mousedown-fired', true);
                    setTimeout(function() { $(e.target).data('mousedown-fired', false); }, 100);
                }
                if (e.type === 'click' && $(this).data('mousedown-fired')) return;
                self.onDaySelect(e);
            });

            // Time slot selection - use mousedown as fallback
            $(document).on('click.sodekgb mousedown.sodekgb', '.sodek-gb-time-slot', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (e.type === 'mousedown' && e.which !== 1) return;
                if (e.type === 'mousedown') {
                    $(this).data('mousedown-fired', true);
                    setTimeout(function() { $(e.target).data('mousedown-fired', false); }, 100);
                }
                if (e.type === 'click' && $(this).data('mousedown-fired')) return;
                self.onTimeSelect(e);
            });

            // Form submission validation - both product page and shortcode forms
            $(document).on('submit', 'form.cart, .sodek-gb-booking-form-inner', this.validateBookingForm.bind(this));

            // Keyboard navigation for time slots
            $(document).on('keydown', '.sodek-gb-time-slot', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    self.onTimeSelect(e);
                }
            });

            // Keyboard navigation for calendar days
            $(document).on('keydown', '.sodek-gb-calendar-day.sodek-gb-day-available', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    self.onDaySelect(e);
                }
            });

            // Add-on selection
            $(document).on('change', 'input[name="sodek_gb_addon_ids[]"]', function() {
                self.updateAddonsTotal();
                self.updateRunningTotal();
            });

            // Step navigation - Continue to date
            $(document).on('click', '.sodek-gb-continue-to-date, .sodek-gb-skip-addons', function(e) {
                e.preventDefault();
                self.goToStep('date');
            });

            // Step navigation - Back to add-ons
            $(document).on('click', '.sodek-gb-back-to-addons', function(e) {
                e.preventDefault();
                self.goToStep('addons');
            });

            // Step navigation - Back to date
            $(document).on('click', '.sodek-gb-back-to-date', function(e) {
                e.preventDefault();
                self.goToStep('date');
            });

            // Step navigation - Back to time
            $(document).on('click', '.sodek-gb-back-to-time', function(e) {
                e.preventDefault();
                self.goToStep('time');
            });

            // Deposit amount input - validate and update display
            $(document).on('input change', '#sodek_gb_custom_deposit', function() {
                var $input = $(this);
                // Use dynamically calculated minimum deposit (based on total with add-ons)
                var min = parseFloat($input.data('calculated-min-deposit')) || parseFloat($input.data('min-deposit')) || parseFloat($input.attr('min')) || 0;
                var max = self.getCurrentTotal();
                var value = parseFloat($input.val());

                // Validate minimum
                if (isNaN(value) || value < min) {
                    $('.sodek-gb-deposit-error').show();
                    // Still update display but show error
                    value = value || min;
                } else {
                    $('.sodek-gb-deposit-error').hide();
                }

                // Cap at max
                if (value > max) {
                    value = max;
                    $input.val(value.toFixed(2));
                }

                self.updateDepositDisplay(value);
                self.updateButtonPrice(value);
            });

            // On blur, ensure value is properly formatted and within range
            $(document).on('blur', '#sodek_gb_custom_deposit', function() {
                var $input = $(this);
                var min = parseFloat($input.data('calculated-min-deposit')) || parseFloat($input.data('min-deposit')) || parseFloat($input.attr('min')) || 0;
                var max = self.getCurrentTotal();
                var value = parseFloat($input.val());

                // Clamp value within range
                if (isNaN(value) || value < min) {
                    value = min;
                    $('.sodek-gb-deposit-error').hide();
                }
                if (value > max) value = max;

                $input.val(value.toFixed(2));
                self.updateDepositDisplay(value);
                self.updateButtonPrice(value);
            });
        },

        goToStep: function(stepName) {
            var $form = $('.sodek-gb-booking-flow');
            var hasAddons = $form.data('has-addons') === 'yes';

            // Hide all sections
            $('.sodek-gb-booking-section').removeClass('sodek-gb-section-active').hide();

            // Show target section
            $('[data-section="' + stepName + '"]').addClass('sodek-gb-section-active').show();

            // Update step indicators
            $('.sodek-gb-step').removeClass('sodek-gb-step-active sodek-gb-step-completed');

            // Step flow with addons: 1=Extras, 2=Date, 3=Time, 4=Confirm
            // Step flow without addons: 1=Date, 2=Time, 3=Confirm
            if (hasAddons) {
                if (stepName === 'addons') {
                    $('[data-step="1"]').addClass('sodek-gb-step-active');
                } else if (stepName === 'date') {
                    $('[data-step="1"]').addClass('sodek-gb-step-completed');
                    $('[data-step="2"]').addClass('sodek-gb-step-active');
                } else if (stepName === 'time') {
                    $('[data-step="1"]').addClass('sodek-gb-step-completed');
                    $('[data-step="2"]').addClass('sodek-gb-step-completed');
                    $('[data-step="3"]').addClass('sodek-gb-step-active');
                } else if (stepName === 'confirm') {
                    $('[data-step="1"]').addClass('sodek-gb-step-completed');
                    $('[data-step="2"]').addClass('sodek-gb-step-completed');
                    $('[data-step="3"]').addClass('sodek-gb-step-completed');
                    $('[data-step="4"]').addClass('sodek-gb-step-active');
                }
            } else {
                if (stepName === 'date') {
                    $('[data-step="1"]').addClass('sodek-gb-step-active');
                } else if (stepName === 'time') {
                    $('[data-step="1"]').addClass('sodek-gb-step-completed');
                    $('[data-step="2"]').addClass('sodek-gb-step-active');
                } else if (stepName === 'confirm') {
                    $('[data-step="1"]').addClass('sodek-gb-step-completed');
                    $('[data-step="2"]').addClass('sodek-gb-step-completed');
                    $('[data-step="3"]').addClass('sodek-gb-step-active');
                }
            }

            // Scroll to top of form
            $('html, body').animate({
                scrollTop: $form.offset().top - 100
            }, 300);
        },

        getCurrentTotal: function() {
            var basePrice = parseFloat($('.sodek-gb-summary-total-value, .sodek-gb-running-total-value').first().data('base-price')) || 0;
            var addonsTotal = 0;

            $('input[name="sodek_gb_addon_ids[]"]:checked').each(function() {
                addonsTotal += parseFloat($(this).data('price')) || 0;
            });

            return basePrice + addonsTotal;
        },

        updateRunningTotal: function() {
            var total = this.getCurrentTotal();
            var formattedTotal = this.formatPrice(total);
            var $form = $('.sodek-gb-booking-flow');
            var isAddonOnly = $form.data('addon-only') === 'yes';
            var hasAddons = $form.data('has-addons') === 'yes';
            var selectedAddonsCount = $('input[name="sodek_gb_addon_ids[]"]:checked').length;

            // For addon-only services with no addons selected, show prompt message
            if (isAddonOnly && hasAddons && selectedAddonsCount === 0) {
                var selectAddonText = (typeof sodekGbPublic !== 'undefined' && sodekGbPublic.strings.selectAddonsFirst)
                    ? sodekGbPublic.strings.selectAddonsFirst
                    : 'Select an addon';
                $('.sodek-gb-running-total-value').html('<span class="sodek-gb-select-addon-prompt">' + selectAddonText + '</span>');
                $('.sodek-gb-summary-total-value').html('<span class="sodek-gb-select-addon-prompt">' + selectAddonText + '</span>');
            } else {
                $('.sodek-gb-running-total-value').html(formattedTotal);
                $('.sodek-gb-summary-total-value').html(formattedTotal);
            }

            // Calculate minimum deposit on the TOTAL (base + all addons)
            var $input = $('#sodek_gb_custom_deposit');
            var depositType = $input.data('deposit-type') || 'percentage';
            var depositValue = parseFloat($input.data('deposit-value')) || 0;

            // Calculate minimum deposit based on total
            var newMinDeposit = 0;
            if (depositValue > 0) {
                if (depositType === 'percentage') {
                    newMinDeposit = Math.round(total * (depositValue / 100) * 100) / 100;
                } else {
                    // Fixed amount - use as minimum or total if less
                    newMinDeposit = Math.min(depositValue, total);
                }
            }

            // If no deposit configured (depositValue is 0), customer pays in full
            if (depositValue === 0 || newMinDeposit === 0) {
                newMinDeposit = total;
            }

            // Store new minimum deposit for reference
            $input.data('calculated-min-deposit', newMinDeposit);

            // Update deposit input constraints
            $input.attr('min', newMinDeposit).attr('max', total);

            // Update minimum/maximum display in hint
            $('.sodek-gb-min-deposit-display').html(this.formatPrice(newMinDeposit));
            $('.sodek-gb-max-deposit-display').html(this.formatPrice(total));

            // Show/hide deposit card only for add-on only products (base price = 0)
            var $form = $('.sodek-gb-booking-flow');
            var isAddonOnly = $form.data('addon-only') === 'yes';
            var $depositCard = $('.sodek-gb-deposit-card');

            if (isAddonOnly) {
                // For add-on only products: show deposit card only when add-ons are selected
                if (total > 0) {
                    $depositCard.slideDown(200);
                    $input.val(newMinDeposit.toFixed(2));
                } else {
                    $depositCard.slideUp(200);
                }
            } else {
                // For regular products: always update the deposit value
                $input.val(newMinDeposit.toFixed(2));
            }

            // Recalculate deposit - ensure it's within new valid range
            var currentDeposit = parseFloat($input.val()) || 0;

            // If current deposit is below new minimum, adjust to minimum
            if (currentDeposit < newMinDeposit) {
                $input.val(newMinDeposit.toFixed(2));
                this.updateDepositDisplay(newMinDeposit);
                this.updateButtonPrice(newMinDeposit);
                $('.sodek-gb-deposit-error').hide();
            }
            // If current deposit is above new total, cap at total
            else if (currentDeposit > total) {
                $input.val(total.toFixed(2));
                this.updateDepositDisplay(total);
                this.updateButtonPrice(total);
            }
            // Otherwise keep current value
            else {
                this.updateDepositDisplay(currentDeposit);
                this.updateButtonPrice(currentDeposit);
            }
        },

        updateButtonPrice: function(amount) {
            var formatted = this.formatPrice(amount);
            $('.sodek-gb-btn-price').html(formatted);
        },

        formatPrice: function(amount) {
            // Use WooCommerce formatting from localized data
            if (typeof sodekGbPublic !== 'undefined' && sodekGbPublic.currency) {
                var c = sodekGbPublic.currency;
                var formatted = parseFloat(amount).toFixed(c.decimals || 2);

                // Add thousand separator
                if (c.thousand) {
                    var parts = formatted.split('.');
                    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, c.thousand);
                    formatted = parts.join(c.separator || '.');
                }

                // Position the currency symbol
                if (c.position === 'right') {
                    return formatted + c.symbol;
                } else if (c.position === 'right_space') {
                    return formatted + ' ' + c.symbol;
                } else if (c.position === 'left_space') {
                    return c.symbol + ' ' + formatted;
                }
                return c.symbol + formatted;
            }
            // Fallback
            return '$' + parseFloat(amount).toFixed(2);
        },

        initProductPageForm: function() {
            var $form = $('.sodek-gb-product-booking-form');
            if ($form.length === 0) return;

            this.serviceId = $form.data('service-id');
            this.initCalendar($form.find('#sodek-gb-calendar-inline'));

            // Initialize running total display (for addon-only services)
            this.updateRunningTotal();

            // Apply layout body classes for browsers without :has() support
            var layout = $form.data('layout');
            if (layout && layout !== 'default') {
                $('body').addClass('sodek-gb-' + layout + '-layout');
            }
        },

        initShortcodeForm: function() {
            var $form = $('.sodek-gb-booking-form');
            if ($form.length === 0) return;

            var serviceId = $form.data('service-id');
            if (serviceId) {
                this.serviceId = serviceId;
                this.initCalendar($form.find('.sodek-gb-calendar'));
            }
        },

        onServiceSelect: function(e) {
            var serviceId = $(e.target).val();
            if (!serviceId) {
                $('.sodek-gb-service-details').removeClass('visible');
                return;
            }

            this.serviceId = serviceId;
            var $container = $(e.target).closest('.sodek-gb-booking-form, .sodek-gb-service-selector');

            // Show loading
            $container.find('.sodek-gb-service-details').addClass('visible');
            $container.find('.sodek-gb-calendar').html('<div class="sodek-gb-loading">' + sodekGbPublic.strings.loading + '</div>');

            // Load service details
            this.loadServiceDetails(serviceId, $container);
        },

        loadServiceDetails: function(serviceId, $container) {
            var self = this;

            wp.apiFetch({
                path: '/sodek-gb/v1/services/' + serviceId
            }).then(function(service) {
                self.updateServiceInfo($container, service);
                self.initCalendar($container.find('.sodek-gb-calendar'));
            }).catch(function(error) {
                $container.find('.sodek-gb-calendar').html('<div class="sodek-gb-error">' + sodekGbPublic.strings.error + '</div>');
            });
        },

        updateServiceInfo: function($container, service) {
            var $info = $container.find('.sodek-gb-service-info-dynamic');
            if ($info.length === 0) return;

            $info.find('.sodek-gb-service-title').text(service.title);
            $info.find('.sodek-gb-service-duration').text(service.duration + ' ' + sodekGbPublic.strings.minutes);
            $info.find('.sodek-gb-service-deposit').html(sodekGbPublic.strings.deposit + ': ' + this.formatPrice(service.deposit_amount));
            $info.find('.sodek-gb-service-balance').html(sodekGbPublic.strings.balanceDue + ': ' + this.formatPrice(service.price - service.deposit_amount));
        },

        initCalendar: function($calendar) {
            if ($calendar.length === 0) return;

            this.selectedDate = null;
            this.selectedTime = null;
            this.currentMonth = new Date().getMonth();
            this.currentYear = new Date().getFullYear();

            this.loadAvailableDates().then(function() {
                this.renderCalendar($calendar);
            }.bind(this));
        },

        loadAvailableDates: function() {
            var self = this;

            return wp.apiFetch({
                path: '/sodek-gb/v1/availability/dates?service_id=' + this.serviceId +
                    '&year=' + this.currentYear +
                    '&month=' + (this.currentMonth + 1)
            }).then(function(response) {
                self.availableDates = response.dates || [];
            }).catch(function() {
                self.availableDates = [];
            });
        },

        renderCalendar: function($calendar) {
            var today = new Date();
            var firstDay = new Date(this.currentYear, this.currentMonth, 1);
            var lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
            var startDay = firstDay.getDay();
            var daysInMonth = lastDay.getDate();

            var monthYearStr = sodekGbPublic.strings.monthNames[this.currentMonth] + ' ' + this.currentYear;

            var html = '<div class="sodek-gb-calendar-header">';
            html += '<button type="button" class="sodek-gb-calendar-prev" aria-label="' + (sodekGbPublic.strings.prevMonth || 'Previous month') + '">';
            html += '<span aria-hidden="true">&larr;</span>';
            html += '</button>';
            html += '<span class="sodek-gb-calendar-title" aria-live="polite" aria-atomic="true">' + monthYearStr + '</span>';
            html += '<button type="button" class="sodek-gb-calendar-next" aria-label="' + (sodekGbPublic.strings.nextMonth || 'Next month') + '">';
            html += '<span aria-hidden="true">&rarr;</span>';
            html += '</button>';
            html += '</div>';

            html += '<div class="sodek-gb-calendar-grid">';

            // Day headers
            for (var i = 0; i < 7; i++) {
                var dayIndex = (i + sodekGbPublic.startOfWeek) % 7;
                html += '<div class="sodek-gb-calendar-day-header">' + sodekGbPublic.strings.dayNames[dayIndex] + '</div>';
            }

            // Adjust start day based on week start
            var adjustedStartDay = (startDay - sodekGbPublic.startOfWeek + 7) % 7;

            // Empty cells before first day
            for (var i = 0; i < adjustedStartDay; i++) {
                html += '<div class="sodek-gb-calendar-day sodek-gb-day-empty"></div>';
            }

            // Days
            for (var day = 1; day <= daysInMonth; day++) {
                var dateStr = this.formatDate(this.currentYear, this.currentMonth + 1, day);
                var isAvailable = this.availableDates.indexOf(dateStr) !== -1;
                var isToday = today.getFullYear() === this.currentYear &&
                              today.getMonth() === this.currentMonth &&
                              today.getDate() === day;
                var isPast = new Date(this.currentYear, this.currentMonth, day) < new Date(today.getFullYear(), today.getMonth(), today.getDate());
                var isSelected = this.selectedDate === dateStr;

                var classes = ['sodek-gb-calendar-day'];
                if (isAvailable && !isPast) classes.push('sodek-gb-day-available');
                if (isPast || !isAvailable) classes.push('sodek-gb-day-disabled');
                if (isToday) classes.push('sodek-gb-day-today');
                if (isSelected) classes.push('sodek-gb-day-selected');

                var ariaLabel = this.formatDisplayDate(dateStr);
                var tabIndex = (isAvailable && !isPast) ? '0' : '-1';
                var ariaDisabled = (isPast || !isAvailable) ? 'true' : 'false';

                html += '<div class="' + classes.join(' ') + '" data-date="' + dateStr + '" ';
                html += 'role="button" tabindex="' + tabIndex + '" ';
                html += 'aria-label="' + ariaLabel + '" aria-disabled="' + ariaDisabled + '" ';
                html += 'aria-pressed="' + (isSelected ? 'true' : 'false') + '">';
                html += day;
                html += '</div>';
            }

            html += '</div>';

            $calendar.html(html);

            // Disable prev button if current month
            if (this.currentYear === today.getFullYear() && this.currentMonth === today.getMonth()) {
                $calendar.find('.sodek-gb-calendar-prev').prop('disabled', true);
            }
        },

        prevMonth: function(e) {
            e.preventDefault();
            var today = new Date();

            if (this.currentMonth === 0) {
                this.currentMonth = 11;
                this.currentYear--;
            } else {
                this.currentMonth--;
            }

            // Don't go before current month
            if (this.currentYear < today.getFullYear() ||
                (this.currentYear === today.getFullYear() && this.currentMonth < today.getMonth())) {
                this.currentMonth = today.getMonth();
                this.currentYear = today.getFullYear();
                return;
            }

            var $calendar = $(e.target).closest('.sodek-gb-calendar, #sodek-gb-calendar-inline');
            this.loadAvailableDates().then(function() {
                this.renderCalendar($calendar);
            }.bind(this));
        },

        nextMonth: function(e) {
            e.preventDefault();

            if (this.currentMonth === 11) {
                this.currentMonth = 0;
                this.currentYear++;
            } else {
                this.currentMonth++;
            }

            var $calendar = $(e.target).closest('.sodek-gb-calendar, #sodek-gb-calendar-inline');
            this.loadAvailableDates().then(function() {
                this.renderCalendar($calendar);
            }.bind(this));
        },

        onDaySelect: function(e) {
            var $day = $(e.target);
            if ($day.hasClass('sodek-gb-day-disabled')) return;

            var date = $day.data('date');
            this.selectedDate = date;

            // Update UI
            $day.closest('.sodek-gb-calendar-grid').find('.sodek-gb-day-selected').removeClass('sodek-gb-day-selected');
            $day.addClass('sodek-gb-day-selected');

            // Update hidden field
            $('#sodek_gb_booking_date').val(date);

            // Update the date display in time step
            var formattedDate = this.formatDisplayDate(date);
            $('.sodek-gb-selected-date-display').text(formattedDate);

            // Go to time step and load slots
            this.goToStep('time');
            this.loadTimeSlots(date);
        },

        loadTimeSlots: function(date) {
            var self = this;
            var $slots = $('#sodek-gb-time-slots');

            // Show loading in time slots
            $slots.html('<div class="sodek-gb-loading" aria-live="polite"><span class="sodek-gb-loading-spinner"></span> ' + sodekGbPublic.strings.loading + '</div>');

            // Reset selected time when date changes
            this.selectedTime = null;
            $('#sodek_gb_booking_time').val('');

            // Disable book button until time is selected
            $('.sodek-gb-book-button').prop('disabled', true).attr('aria-disabled', 'true');

            wp.apiFetch({
                path: '/sodek-gb/v1/availability/slots?service_id=' + this.serviceId + '&date=' + date
            }).then(function(response) {
                self.renderTimeSlots(response.slots);
            }).catch(function() {
                $slots.html('<div class="sodek-gb-error" role="alert">' + sodekGbPublic.strings.error + '</div>');
            });
        },

        renderTimeSlots: function(slots) {
            var $slots = $('#sodek-gb-time-slots');

            if (!slots || slots.length === 0) {
                $slots.html('<div class="sodek-gb-no-slots" role="status">' + sodekGbPublic.strings.noSlots + '</div>');
                return;
            }

            var html = '';
            for (var i = 0; i < slots.length; i++) {
                var slot = slots[i];
                var formattedTime = this.formatTime(slot.start);
                html += '<div class="sodek-gb-time-slot" data-time="' + slot.start + '" ';
                html += 'role="option" aria-selected="false" tabindex="0" ';
                html += 'aria-label="' + formattedTime + '">';
                html += formattedTime;
                html += '</div>';
            }

            $slots.html(html);
        },

        onTimeSelect: function(e) {
            var $slot = $(e.target).closest('.sodek-gb-time-slot');
            var time = $slot.data('time');

            this.selectedTime = time;

            // Update UI and accessibility attributes
            $slot.closest('.sodek-gb-time-slots').find('.sodek-gb-time-slot')
                .removeClass('selected')
                .attr('aria-selected', 'false');
            $slot.addClass('selected').attr('aria-selected', 'true');

            // Update hidden field
            $('#sodek_gb_booking_time').val(time);

            // Show summary
            this.showSummary();
        },

        showSummary: function() {
            var $bookButton = $('.sodek-gb-book-button');
            var isStepFlow = $('.sodek-gb-booking-flow').length > 0;

            var formattedDate = this.formatDisplayDate(this.selectedDate);
            var formattedTime = this.formatTime(this.selectedTime);

            // Update date/time in summary
            $('.sodek-gb-summary-date').text(formattedDate);
            $('.sodek-gb-summary-time').text(formattedTime);

            if (isStepFlow) {
                // Step-based flow: go to confirmation step
                this.goToStep('confirm');

                // Update confirmation section - this recalculates deposit based on total
                this.updateAddonsTotal();
                this.updateRunningTotal();
            } else {
                // Legacy flow: show summary inline
                this.updateAddonsTotal();
            }

            // Enable the book button
            $bookButton.prop('disabled', false).attr('aria-disabled', 'false');
        },

        updateAddonsTotal: function() {
            var self = this;
            var $addons = $('input[name="sodek_gb_addon_ids[]"]:checked');
            var $summary = $('.sodek-gb-booking-summary');
            var $addonsTotal = $('.sodek-gb-addons-total');
            var $addonsSummary = $('.sodek-gb-addons-summary');

            var totalPrice = 0;
            var totalDuration = 0;
            var selectedAddons = [];

            $addons.each(function() {
                var $input = $(this);
                var price = parseFloat($input.data('price')) || 0;
                var duration = parseInt($input.data('duration'), 10) || 0;
                // Support both legacy and new template structures
                var name = $input.closest('.sodek-gb-addon-item, .sodek-gb-addon-card').find('.sodek-gb-addon-name, .sodek-gb-addon-card-name').text();

                totalPrice += price;
                totalDuration += duration;
                selectedAddons.push({
                    name: name,
                    price: price,
                    duration: duration
                });
            });

            // Update addons total display (legacy)
            if (totalPrice > 0) {
                $addonsTotal.show();
                $addonsTotal.find('.sodek-gb-addons-total-price').html('+ ' + this.formatPrice(totalPrice));
                if (totalDuration > 0) {
                    $addonsTotal.find('.sodek-gb-addons-total-duration').text('(+ ' + totalDuration + ' min)').show();
                } else {
                    $addonsTotal.find('.sodek-gb-addons-total-duration').hide();
                }
            } else {
                $addonsTotal.hide();
            }

            // Update new addons summary (step flow)
            if ($addonsSummary.length) {
                if (selectedAddons.length > 0) {
                    $addonsSummary.show();
                    var countText = selectedAddons.length + ' extra' + (selectedAddons.length > 1 ? 's' : '') + ' selected';
                    $addonsSummary.find('.sodek-gb-addons-summary-count').text(countText);
                    $addonsSummary.find('.sodek-gb-addons-summary-total').html('+' + this.formatPrice(totalPrice));
                } else {
                    $addonsSummary.hide();
                }
            }

            // Update confirmation section addons list
            var $confirmAddons = $('.sodek-gb-confirmation-addons');
            var $confirmAddonsList = $confirmAddons.find('.sodek-gb-summary-addons-list');
            var $pricingAddons = $('.sodek-gb-pricing-addons');

            if (selectedAddons.length > 0) {
                $confirmAddons.show();
                $confirmAddonsList.empty();
                selectedAddons.forEach(function(addon) {
                    var durationText = addon.duration > 0 ? ' (+' + addon.duration + ' min)' : '';
                    $confirmAddonsList.append('<li>' + addon.name + ' - ' + self.formatPrice(addon.price) + durationText + '</li>');
                });

                $pricingAddons.show().find('.sodek-gb-summary-addons-price-value').html(this.formatPrice(totalPrice));
            } else {
                $confirmAddons.hide();
                $pricingAddons.hide();
            }

            // Update summary if visible (legacy)
            if ($summary.is(':visible')) {
                this.updateSummaryWithAddons(selectedAddons, totalPrice, totalDuration);
            }
        },

        updateDepositDisplay: function(depositAmount) {
            var $input = $('#sodek_gb_custom_deposit');
            var max = this.getCurrentTotal(); // Use current total including add-ons
            var min = parseFloat($input.data('min-deposit') || $input.attr('min'));
            var balance = max - depositAmount;

            // Update display
            $('.sodek-gb-chosen-deposit').html(this.formatPrice(depositAmount));
            $('.sodek-gb-remaining-balance').html(this.formatPrice(balance));

            // Update quick option button states
            this.updateDepositButtonStates(depositAmount, min, max);

            // Visual feedback - color the balance based on amount
            if (balance <= 0) {
                $('.sodek-gb-remaining-balance').css('color', 'var(--sodek-gb-color-success, #00a32a)');
                $('.sodek-gb-deposit-remaining span').text(sodekGbPublic.strings.paidInFull || 'Paid in Full!');
            } else {
                $('.sodek-gb-remaining-balance').css('color', '');
                $('.sodek-gb-deposit-remaining span').text(sodekGbPublic.strings.balanceAtAppointment || 'Balance at Appointment:');
            }
        },

        updateDepositButtonStates: function(depositAmount, min, max) {
            $('.sodek-gb-deposit-option').removeClass('active');

            if (Math.abs(depositAmount - min) < 0.01) {
                $('.sodek-gb-deposit-option[data-amount="min"]').addClass('active');
            } else if (Math.abs(depositAmount - max) < 0.01) {
                $('.sodek-gb-deposit-option[data-amount="full"]').addClass('active');
            } else if (Math.abs(depositAmount - (max * 0.5)) < 0.01 && depositAmount >= min) {
                $('.sodek-gb-deposit-option[data-amount="50"]').addClass('active');
            } else if (Math.abs(depositAmount - (max * 0.75)) < 0.01 && depositAmount >= min) {
                $('.sodek-gb-deposit-option[data-amount="75"]').addClass('active');
            }
        },

        updateDepositRange: function(minDeposit, totalPrice) {
            var $input = $('#sodek_gb_custom_deposit');
            var currentValue = parseFloat($input.val());

            // Update input range
            $input.attr('min', minDeposit);
            $input.attr('max', totalPrice);
            $input.data('min-deposit', minDeposit);
            $input.data('base-price', totalPrice);

            // Ensure current value is within new range
            if (currentValue < minDeposit) {
                currentValue = minDeposit;
            } else if (currentValue > totalPrice) {
                currentValue = totalPrice;
            }

            $input.val(currentValue.toFixed(2));

            // Update range info text
            var rangeText = (sodekGbPublic.strings.depositRangeFormat || 'Min: %1$s — Full: %2$s')
                .replace('%1$s', this.formatPrice(minDeposit))
                .replace('%2$s', this.formatPrice(totalPrice));
            $('.sodek-gb-deposit-range-info').html(rangeText);

            // Update display
            this.updateDepositDisplay(currentValue);
        },

        updateSummaryWithAddons: function(addons, addonsPrice, addonsDuration) {
            var $summary = $('.sodek-gb-booking-summary');
            var $addonsList = $summary.find('.sodek-gb-summary-addons-list');
            var $addonsSection = $summary.find('.sodek-gb-summary-addons');
            var $addonsPriceRow = $summary.find('.sodek-gb-summary-addons-price');
            var $input = $('#sodek_gb_custom_deposit');

            // Get base values from the input data attributes
            var baseDeposit = parseFloat($input.data('min-deposit') || $input.attr('min')) || 0;
            var basePrice = parseFloat($input.data('base-price') || $input.attr('max')) || 0;
            var depositType = $input.data('deposit-type') || 'percentage';
            var depositValue = parseFloat($input.data('deposit-value')) || 50;

            // Get base duration from the summary (first value shown)
            var $durationEl = $summary.find('.sodek-gb-summary-duration-value');
            var baseDuration = parseInt($durationEl.data('base-duration') || $durationEl.text(), 10) || 0;

            // Store base duration if not already stored
            if (!$durationEl.data('base-duration')) {
                $durationEl.data('base-duration', baseDuration);
            }

            // Calculate totals
            var totalPrice = basePrice + addonsPrice;
            var totalDuration = baseDuration + addonsDuration;

            // Calculate minimum deposit including add-ons
            // Must match PHP logic in Sodek_GB_Addon::calculate_addons_deposit()
            var minDeposit;
            var addonsDeposit = 0;
            if (depositType === 'percentage') {
                // For percentage deposits, apply same percentage to add-on price
                addonsDeposit = addonsPrice * (depositValue / 100);
            } else {
                // For fixed deposits, include full add-on price
                addonsDeposit = addonsPrice;
            }
            minDeposit = Math.round((baseDeposit + addonsDeposit) * 100) / 100;

            // Update addons list in summary
            if (addons.length > 0) {
                $addonsSection.show();
                $addonsPriceRow.show();

                var listHtml = '';
                addons.forEach(function(addon) {
                    listHtml += '<li>' + addon.name;
                    if (addon.duration > 0) {
                        listHtml += ' <small>(+' + addon.duration + ' min)</small>';
                    }
                    listHtml += ' - ' + sodekGbPublic.currency.symbol + addon.price.toFixed(2) + '</li>';
                });
                $addonsList.html(listHtml);

                $addonsPriceRow.find('.sodek-gb-summary-addons-price-value').html(this.formatPrice(addonsPrice));
            } else {
                $addonsSection.hide();
                $addonsPriceRow.hide();
            }

            // Update duration
            $durationEl.text(totalDuration);

            // Update total price display
            $summary.find('.sodek-gb-summary-total-value').html(this.formatPrice(totalPrice));

            // Update the deposit input range
            this.updateDepositRange(minDeposit, totalPrice);
        },

        validateBookingForm: function(e) {
            var self = this;
            var $form = $(e.target);

            // Check for product page booking form
            var $bookingForm = $form.find('.sodek-gb-product-booking-form');

            // Check for shortcode booking form
            var isShortcodeForm = $form.hasClass('sodek-gb-booking-form-inner');

            if ($bookingForm.length === 0 && !isShortcodeForm) {
                return true;
            }

            var date = $('#sodek_gb_booking_date').val();
            var time = $('#sodek_gb_booking_time').val();

            if (!date || !time) {
                e.preventDefault();
                self.showValidationError(sodekGbPublic.strings.selectDate + '\n' + sodekGbPublic.strings.selectTime);
                return false;
            }

            // Validate addon-only services must have at least one addon selected
            var $flowForm = $('.sodek-gb-booking-flow');
            var isAddonOnly = $flowForm.data('addon-only') === 'yes';
            var hasAddons = $flowForm.data('has-addons') === 'yes';

            if (isAddonOnly && hasAddons) {
                var selectedAddonsCount = $('input[name="sodek_gb_addon_ids[]"]:checked').length;
                if (selectedAddonsCount === 0) {
                    e.preventDefault();
                    var errorMsg = (typeof sodekGbPublic !== 'undefined' && sodekGbPublic.strings.selectAddonRequired)
                        ? sodekGbPublic.strings.selectAddonRequired
                        : 'Please select at least one add-on for this service.';
                    self.showValidationError(errorMsg);
                    return false;
                }
            }

            // Validate deposit amount
            var $depositInput = $('#sodek_gb_custom_deposit');
            if ($depositInput.length > 0) {
                var depositValue = parseFloat($depositInput.val()) || 0;
                var minDeposit = parseFloat($depositInput.data('calculated-min-deposit')) || parseFloat($depositInput.data('min-deposit')) || 0;
                var totalPrice = this.getCurrentTotal();

                if (depositValue < minDeposit) {
                    e.preventDefault();
                    var minDepositMsg = (typeof sodekGbPublic !== 'undefined' && sodekGbPublic.strings.minDepositRequired)
                        ? sodekGbPublic.strings.minDepositRequired.replace('%s', this.formatPrice(minDeposit))
                        : 'Minimum deposit of ' + this.formatPrice(minDeposit) + ' is required.';
                    self.showValidationError(minDepositMsg);
                    return false;
                }

                if (depositValue > totalPrice) {
                    // Silently cap at total price
                    $depositInput.val(totalPrice.toFixed(2));
                }
            }

            // All validations passed - use AJAX submission to avoid WooCommerce AJAX interference
            e.preventDefault();
            this.showButtonLoading($form);
            this.submitBookingViaAjax($form);

            return false;
        },

        submitBookingViaAjax: function($form) {
            var self = this;

            // Check if standalone payment mode
            if (sodekGbPublic.paymentMode === 'standalone' && typeof window.SodekGbSquarePayment !== 'undefined') {
                self.submitStandalonePayment($form);
                return;
            }

            // WooCommerce mode - original flow
            var formData = new FormData($form[0]);
            formData.append('action', 'sodek_gb_add_to_cart');

            // Get product ID from the add-to-cart button value
            var productId = $form.find('button[name="add-to-cart"]').val();
            if (productId) {
                formData.append('product_id', productId);
            }

            $.ajax({
                url: sodekGbPublic.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.showButtonSuccess($form);
                        // Redirect to checkout
                        window.location.href = response.data.checkout_url || sodekGbPublic.checkoutUrl || '/checkout/';
                    } else {
                        self.hideButtonLoading($form);
                        self.showValidationError(response.data.message || 'An error occurred. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    self.hideButtonLoading($form);
                    self.showValidationError('Connection error. Please try again.');
                    console.error('Booking submission error:', error);
                }
            });
        },

        /**
         * Submit booking with standalone Square payment.
         */
        submitStandalonePayment: async function($form) {
            var self = this;

            // Update button to show payment processing
            var $button = $form.find('.sodek-gb-submit-btn, .sodek-gb-book-button');
            $button.find('.sodek-gb-loading-text').text(sodekGbPublic.strings.paymentProcessing || 'Processing payment...');

            try {
                // Tokenize card with Square SDK
                var tokenResult = await window.SodekGbSquarePayment.tokenize();

                if (!tokenResult || !tokenResult.token) {
                    throw new Error(tokenResult.error || sodekGbPublic.strings.cardError || 'Card tokenization failed');
                }

                // Build form data with payment token
                var formData = new FormData($form[0]);
                formData.append('action', 'sodek_gb_standalone_payment');
                formData.append('nonce', sodekGbPublic.paymentNonce);
                formData.append('card_token', tokenResult.token);

                // Get product ID
                var productId = $form.find('button[name="add-to-cart"]').val();
                if (productId) {
                    formData.append('product_id', productId);
                }

                // Submit payment
                $.ajax({
                    url: sodekGbPublic.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            self.showButtonSuccess($form);
                            $button.find('.sodek-gb-loading-text').text(sodekGbPublic.strings.paymentSuccess || 'Payment successful!');

                            // Redirect to confirmation page
                            if (response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            } else if (response.data.booking_id) {
                                // Fallback: redirect to My Account bookings
                                window.location.href = sodekGbPublic.myAccountUrl || '/my-account/bookings/';
                            }
                        } else {
                            self.hideButtonLoading($form);
                            self.showValidationError(response.data.message || sodekGbPublic.strings.paymentError || 'Payment failed.');
                        }
                    },
                    error: function(xhr, status, error) {
                        self.hideButtonLoading($form);
                        self.showValidationError('Connection error. Please try again.');
                        console.error('Payment submission error:', error);
                    }
                });

            } catch (error) {
                self.hideButtonLoading($form);
                self.showValidationError(error.message || sodekGbPublic.strings.cardError || 'Card error. Please try again.');
                console.error('Square tokenization error:', error);
            }
        },

        showValidationError: function(message) {
            // Show user-friendly error notification instead of alert
            var $errorNotice = $('.sodek-gb-validation-error');

            // Create error notice if doesn't exist
            if ($errorNotice.length === 0) {
                $errorNotice = $('<div class="sodek-gb-validation-error" role="alert" aria-live="polite"></div>');
                $('.sodek-gb-submit-wrapper').before($errorNotice);
            }

            $errorNotice.html('<span class="sodek-gb-error-icon">&#9888;</span> ' + message.replace(/\n/g, '<br>'));
            $errorNotice.addClass('sodek-gb-error-visible');

            // Scroll to error
            $('html, body').animate({
                scrollTop: $errorNotice.offset().top - 100
            }, 300);

            // Auto-hide after 5 seconds
            setTimeout(function() {
                $errorNotice.removeClass('sodek-gb-error-visible');
            }, 5000);
        },

        showButtonLoading: function($form) {
            var $button = $form.find('.sodek-gb-submit-btn, .sodek-gb-book-button');
            if ($button.length) {
                $button.addClass('sodek-gb-loading');
                $button.prop('disabled', true);

                // Explicitly hide/show elements to avoid CSS specificity issues
                $button.find('.sodek-gb-btn-content, .sodek-gb-btn-text, .sodek-gb-btn-price').hide();
                $button.find('.sodek-gb-btn-loading').show();

                // Update aria attributes for accessibility
                $button.attr('aria-busy', 'true');
                $button.attr('aria-label', sodekGbPublic.strings.processing || 'Processing your booking...');
            }
        },

        hideButtonLoading: function($form) {
            var $button = $form.find('.sodek-gb-submit-btn, .sodek-gb-book-button');
            if ($button.length) {
                $button.removeClass('sodek-gb-loading');
                $button.prop('disabled', false);

                // Explicitly restore element visibility
                $button.find('.sodek-gb-btn-content, .sodek-gb-btn-text, .sodek-gb-btn-price').show();
                $button.find('.sodek-gb-btn-loading').hide();

                $button.attr('aria-busy', 'false');
                $button.removeAttr('aria-label');
            }
        },

        showButtonSuccess: function($form) {
            var $button = $form.find('.sodek-gb-submit-btn, .sodek-gb-book-button');
            if ($button.length) {
                $button.removeClass('sodek-gb-loading').addClass('sodek-gb-success');
                var $loadingText = $button.find('.sodek-gb-loading-text');
                if ($loadingText.length) {
                    $loadingText.text(sodekGbPublic.strings.success || 'Redirecting...');
                }
            }
        },

        // Utility functions
        formatDate: function(year, month, day) {
            return year + '-' +
                String(month).padStart(2, '0') + '-' +
                String(day).padStart(2, '0');
        },

        formatDisplayDate: function(dateStr) {
            var date = new Date(dateStr + 'T00:00:00');
            return date.toLocaleDateString(undefined, {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        formatTime: function(timeStr) {
            var parts = timeStr.split(':');
            var hours = parseInt(parts[0], 10);
            var minutes = parts[1];

            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;

            return hours + ':' + minutes + ' ' + ampm;
        },

        formatPrice: function(price) {
            var symbol = sodekGbPublic.currency.symbol;
            var formatted = parseFloat(price).toFixed(sodekGbPublic.currency.decimals);

            if (sodekGbPublic.currency.position === 'left') {
                return symbol + formatted;
            } else if (sodekGbPublic.currency.position === 'left_space') {
                return symbol + ' ' + formatted;
            } else if (sodekGbPublic.currency.position === 'right_space') {
                return formatted + ' ' + symbol;
            } else {
                return formatted + symbol;
            }
        },

        /**
         * Initialize custom product gallery for bookable services.
         */
        initProductGallery: function() {
            var $gallery = $('.sodek-gb-product-gallery');
            if ($gallery.length === 0) return;

            var self = this;

            // Thumbnail click handler
            $gallery.on('click', '.sodek-gb-gallery-thumb', function(e) {
                e.preventDefault();
                var $thumb = $(this);
                var $mainImg = $gallery.find('.sodek-gb-main-img');
                var $mainLink = $gallery.find('.sodek-gb-gallery-zoom');

                // Get new image URLs
                var newSrc = $thumb.data('image');
                var newFull = $thumb.data('full');

                // Skip if already active
                if ($thumb.hasClass('sodek-gb-thumb-active')) {
                    return;
                }

                // Update active state
                $gallery.find('.sodek-gb-gallery-thumb').removeClass('sodek-gb-thumb-active');
                $thumb.addClass('sodek-gb-thumb-active');

                // Animate image swap
                $mainImg.addClass('sodek-gb-img-loading');

                // Preload new image
                var img = new Image();
                img.onload = function() {
                    $mainImg.attr('src', newSrc);
                    $mainLink.attr('href', newFull);
                    $mainImg.removeClass('sodek-gb-img-loading').addClass('sodek-gb-img-loaded');

                    // Remove loaded class after animation
                    setTimeout(function() {
                        $mainImg.removeClass('sodek-gb-img-loaded');
                    }, 300);
                };
                img.src = newSrc;
            });

            // Keyboard navigation for accessibility
            $gallery.on('keydown', '.sodek-gb-gallery-thumb', function(e) {
                var $thumbs = $gallery.find('.sodek-gb-gallery-thumb');
                var currentIndex = $thumbs.index(this);
                var newIndex;

                switch (e.keyCode) {
                    case 37: // Left arrow
                    case 38: // Up arrow
                        e.preventDefault();
                        newIndex = currentIndex > 0 ? currentIndex - 1 : $thumbs.length - 1;
                        $thumbs.eq(newIndex).focus().click();
                        break;
                    case 39: // Right arrow
                    case 40: // Down arrow
                        e.preventDefault();
                        newIndex = currentIndex < $thumbs.length - 1 ? currentIndex + 1 : 0;
                        $thumbs.eq(newIndex).focus().click();
                        break;
                }
            });

            // Touch swipe support for main image
            var touchStartX = 0;
            var touchEndX = 0;

            $gallery.find('.sodek-gb-gallery-main').on('touchstart', function(e) {
                touchStartX = e.originalEvent.changedTouches[0].screenX;
            });

            $gallery.find('.sodek-gb-gallery-main').on('touchend', function(e) {
                touchEndX = e.originalEvent.changedTouches[0].screenX;
                self.handleGallerySwipe($gallery, touchStartX, touchEndX);
            });
        },

        /**
         * Handle swipe gesture for gallery navigation.
         */
        handleGallerySwipe: function($gallery, startX, endX) {
            var $thumbs = $gallery.find('.sodek-gb-gallery-thumb');
            var $active = $gallery.find('.sodek-gb-thumb-active');
            var currentIndex = $thumbs.index($active);
            var diff = startX - endX;

            // Minimum swipe distance
            if (Math.abs(diff) < 50) return;

            var newIndex;
            if (diff > 0) {
                // Swipe left - next image
                newIndex = currentIndex < $thumbs.length - 1 ? currentIndex + 1 : 0;
            } else {
                // Swipe right - previous image
                newIndex = currentIndex > 0 ? currentIndex - 1 : $thumbs.length - 1;
            }

            $thumbs.eq(newIndex).click();
        }
    };

    // Initialize on document ready
    $(function() {
        SodekGb.init();
    });

})(jQuery);
