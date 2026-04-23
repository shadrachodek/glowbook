/**
 * GlowBook Standalone Booking
 *
 * Simple booking flow: Category -> Service -> Date & Time (with add-ons) -> Details
 *
 * @package GlowBook
 * @since   2.0.0
 */

(function($) {
    'use strict';

    // Booking state
    const state = {
        step: 'category',
        category: null,
        service: null,
        addons: [],
        date: null,
        time: null,       // Display format (e.g., "9:00 AM")
        timeStart: null,  // 24h format (e.g., "09:00") for API
        timeEnd: null,    // End time in 24h format
        customer: {},
        customerType: 'new',
        skippedServiceStep: false // Track if we auto-selected single service
    };
    const CUSTOMER_PAYMENT_RULES_ENABLED = !(window.sodekGBStandalone?.customerPaymentRulesEnabled === false || window.sodekGBStandalone?.customerPaymentRulesEnabled === '0' || window.sodekGBStandalone?.customerPaymentRulesEnabled === 0);
    const ENFORCE_CUSTOMER_PAYMENT_TYPE = window.sodekGBStandalone?.enforceCustomerPaymentType === true || window.sodekGBStandalone?.enforceCustomerPaymentType === '1' || window.sodekGBStandalone?.enforceCustomerPaymentType === 1;
    const CONFIGURED_RETURNING_CUSTOMER_PAYMENT = parseFloat(window.sodekGBStandalone?.returningCustomerPaymentAmount);
    const CONFIGURED_NEW_CUSTOMER_PAYMENT = parseFloat(window.sodekGBStandalone?.newCustomerPaymentAmount);
    let customerTypeCheckTimer = null;
    let customerTypeRequestId = 0;
    const availableDatesCache = {};
    let calendarRequestId = 0;
    let addonRequestId = 0;
    let timeSlotRequestId = 0;
    let categoryTransitionTimer = null;

    // Config from wp_localize_script
    const config = window.sodekGBStandalone || {
        ajaxUrl: '/wp-admin/admin-ajax.php',
        restUrl: '/wp-json/sodek-gb/v1/',
        nonce: '',
        currency: '$'
    };

    // DOM elements
    let $container;

    function parseBusinessDate(dateString) {
        if (!dateString) {
            return new Date();
        }

        const parts = String(dateString).split('-');
        if (parts.length !== 3) {
            return new Date();
        }

        return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10), 12, 0, 0, 0);
    }

    function buildRestUrl(path, params) {
        const baseUrl = String(config.restUrl || '/wp-json/sodek-gb/v1/');
        const separator = baseUrl.includes('?') ? '&' : '?';
        const query = params instanceof URLSearchParams ? params.toString() : new URLSearchParams(params || {}).toString();

        return `${baseUrl}${path}${query ? separator + query : ''}`;
    }

    /**
     * Initialize
     */
    function init() {
        $container = $('.sodek-gb-booking');
        if (!$container.length) {
            return;
        }

        bindEvents();
        initCalendar();

        // Check for preselected service first
        const preselected = getPreselectedData();
        if (preselected && preselected.service_id) {
            handlePreselectedService(preselected.service_id);
            $container.removeClass('loading');
            return;
        }

        // Restore state from URL hash on page load
        const restored = restoreStateFromHash();

        // If no state was restored, show the default category step
        if (!restored) {
            showStep('category');
        }

        // Remove loading class to reveal the correct step
        $container.removeClass('loading');

        // Listen for browser back/forward
        $(window).on('hashchange', function() {
            restoreStateFromHash();
        });
    }

    /**
     * Update URL hash to reflect current state
     */
    function updateUrlHash() {
        let hash = state.step;

        if (state.step === 'service' && state.category) {
            hash = `service/${state.category.slug || state.category.id}`;
        } else if (state.step === 'datetime' && state.service) {
            hash = `datetime/${state.category ? (state.category.slug || state.category.id) : 0}/${state.service.slug || state.service.id}`;
        } else if (state.step === 'details' && state.service && state.date && state.timeStart) {
            hash = `details/${state.service.slug || state.service.id}/${state.date}/${state.timeStart}`;
        }

        // Use replaceState to avoid creating new history entry for each step
        // but user can still use back button to go to previous page
        const newUrl = window.location.pathname + window.location.search + '#' + hash;
        history.replaceState(null, '', newUrl);
    }

    /**
     * Restore state from URL hash
     * @returns {boolean} True if state was restored, false otherwise
     */
    function restoreStateFromHash() {
        const hash = window.location.hash.slice(1); // Remove #
        if (!hash) return false;

        const parts = hash.split('/');
        const step = parts[0];

        switch (step) {
            case 'service':
                if (parts[1]) {
                    const $categoryRow = findCategoryRow(parts[1]);
                    if ($categoryRow.length) {
                        const categoryId = parseInt($categoryRow.data('category-id'), 10);
                        // Restore category state without triggering navigation
                        state.category = {
                            id: categoryId,
                            slug: $categoryRow.data('category-slug') || parts[1],
                            name: $categoryRow.find('.sodek-gb-category-name').text()
                        };
                        $('.sodek-gb-selected-category-name').text(state.category.name);
                        filterServices(categoryId);
                        showStep('service');
                        return true;
                    }
                }
                return false;

            case 'datetime':
                if (parts[1] && parts[2]) {
                    const $categoryRow = findCategoryRow(parts[1]);
                    const $serviceRow = findServiceRow(parts[2]);

                    if ($serviceRow.length) {
                        // Restore category if provided
                        if ($categoryRow.length) {
                            const categoryId = parseInt($categoryRow.data('category-id'), 10);
                            state.category = {
                                id: categoryId,
                                slug: $categoryRow.data('category-slug') || parts[1],
                                name: $categoryRow.find('.sodek-gb-category-name').text()
                            };
                        }

                        // Restore service state
                        const serviceId = parseInt($serviceRow.data('service-id'), 10);
                        restoreServiceState($serviceRow, serviceId);
                        showStep('datetime');
                        return true;
                    }
                }
                return false;

            case 'details':
                // For details step, we need service, date, and time
                // This is harder to restore fully, so go back to datetime
                if (parts[1]) {
                    const $serviceRow = findServiceRow(parts[1]);
                    if ($serviceRow.length) {
                        // Get category from service
                        const categoryId = parseInt($serviceRow.data('category-id'), 10);
                        const $categoryRow = $(`.sodek-gb-category-row[data-category-id="${categoryId}"]`);
                        if ($categoryRow.length) {
                            state.category = {
                                id: categoryId,
                                slug: $categoryRow.data('category-slug') || '',
                                name: $categoryRow.find('.sodek-gb-category-name').text()
                            };
                        }
                        const serviceId = parseInt($serviceRow.data('service-id'), 10);
                        restoreServiceState($serviceRow, serviceId);
                        showStep('datetime');
                        return true;
                    }
                }
                return false;

            case 'category':
                showStep('category');
                return true;

            default:
                return false;
        }
    }

    /**
     * Restore service state from row
     */
    function restoreServiceState($row, serviceId) {
        const price = parseFloat($row.data('price')) || 0;
        const depositAmount = parseFloat($row.data('deposit-amount')) || price;

        state.service = {
            id: serviceId,
            slug: $row.data('service-slug') || '',
            name: $row.find('.sodek-gb-service-name').text(),
            price: price,
            duration: parseInt($row.data('duration'), 10) || 60,
            description: $row.data('description') || '',
            image: $row.find('.sodek-gb-service-thumb img').attr('src') || '',
            imageFull: $row.find('.sodek-gb-service-thumb').data('lightbox-src') || $row.find('.sodek-gb-service-thumb img').attr('src') || '',
            deposit_type: $row.data('deposit-type') || 'fixed',
            deposit_value: parseFloat($row.data('deposit-value')) || 0,
            deposit_amount: depositAmount
        };

        // Clear addons and load service-specific add-ons from the backend
        state.addons = [];
        loadAddonsForService(serviceId);

        updateAppointmentCard();
    }

    /**
     * Show step without updating hash (used during restore)
     */
    function showStep(step) {
        state.step = step;
        $('.sodek-gb-step').removeClass('active');
        $(`.sodek-gb-step[data-step="${step}"]`).addClass('active');

        if (step === 'datetime') {
            const $targetStep = $(`.sodek-gb-step[data-step="${step}"]`);
            const $backBtn = $targetStep.find('.sodek-gb-back-link');
            if (state.skippedServiceStep) {
                $backBtn.data('back', 'category').html(`
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    SELECT CATEGORY
                `);
            } else {
                $backBtn.data('back', 'service').html(`
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    SELECT APPOINTMENT
                `);
            }
        }

        if (step === 'details') {
            initializeSquarePayment();
            initDepositSlider();
        }
    }

    /**
     * Bind events
     */
    function bindEvents() {
        // Category selection - click anywhere on row or button
        $container.on('click', '.sodek-gb-category-row, .sodek-gb-category-row .sodek-gb-btn-select', function(e) {
            e.stopPropagation();
            const $row = $(this).closest('.sodek-gb-category-row');
            const categoryId = $row.data('category-id');
            selectCategory(categoryId);
        });

        // Service selection - click anywhere on row or button
        $container.on('click', '.sodek-gb-service-row, .sodek-gb-service-row .sodek-gb-btn-select', function(e) {
            if ($(e.target).closest('.sodek-gb-lightbox-trigger').length) {
                return;
            }
            e.stopPropagation();
            const $row = $(this).closest('.sodek-gb-service-row');
            const serviceId = $row.data('service-id');
            selectService(serviceId);
        });

        // Back navigation
        $container.on('click', '.sodek-gb-back-link', function() {
            goToStep($(this).data('back'));
        });

        // Remove appointment (X button)
        $container.on('click', '.sodek-gb-appointment-remove', function() {
            goToStep('service');
        });

        // Add-on toggle
        $container.on('change', '.sodek-gb-addon-row input[type="checkbox"]', function() {
            toggleAddon($(this).closest('.sodek-gb-addon-row'));
        });

        // Show all add-ons
        $container.on('click', '#sodek-gb-show-all-addons', function() {
            $('#sodek-gb-addons-list')
                .find('.sodek-gb-addon-row')
                .filter('.sodek-gb-addon-hidden')
                .removeClass('sodek-gb-addon-hidden')
                .show();
            $(this).hide();
        });

        // Calendar navigation
        $container.on('click', '.sodek-gb-calendar-prev', function() {
            navigateCalendar(-1);
        });
        $container.on('click', '.sodek-gb-calendar-next', function() {
            navigateCalendar(1);
        });

        // Date selection
        $container.on('click', '.sodek-gb-calendar-day.available', function() {
            selectDate($(this).data('date'));
        });

        // Time selection
        $container.on('click', '.sodek-gb-timeslot:not(.disabled)', function() {
            selectTime($(this).data('time'));
        });

        // Form submission
        $container.on('submit', '#sodek-gb-customer-form', function(e) {
            e.preventDefault();
            submitBooking();
        });

        // Deposit input
        $container.on('input', '#sodek-gb-deposit-input', function() {
            updateDepositFromInput($(this).val());
        });

        // Deposit input blur - validate and format
        $container.on('blur', '#sodek-gb-deposit-input', function() {
            validateDepositInput();
        });

        // Deposit quick options
        $container.on('click', '.sodek-gb-deposit-option', function() {
            const percent = $(this).data('percent');
            selectDepositOption(percent);
        });

        $container.on('input', '#sodek-gb-email, #sodek-gb-phone', function() {
            scheduleCustomerTypeCheck();
        });

        $container.on('blur change', '#sodek-gb-email, #sodek-gb-phone', function() {
            scheduleCustomerTypeCheck(true);
        });

        // Image lightbox - open
        $(document).on('click', '.sodek-gb-lightbox-trigger', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const src = $(this).data('lightbox-src');
            const title = $(this).data('lightbox-title') || '';
            openLightbox(src, title);
        });

        // Image lightbox - close
        $(document).on('click', '#sodek-gb-lightbox', function(e) {
            if ($(e.target).is('#sodek-gb-lightbox') || $(e.target).closest('.sodek-gb-lightbox-close').length) {
                closeLightbox();
            }
        });

        // Image lightbox - close on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#sodek-gb-lightbox').hasClass('active')) {
                closeLightbox();
            }
        });
    }

    /**
     * Open image lightbox
     */
    function openLightbox(src, title) {
        const $lightbox = $('#sodek-gb-lightbox');
        const $image = $('#sodek-gb-lightbox-image');
        const $caption = $('#sodek-gb-lightbox-caption');

        $image.attr('src', src).attr('alt', title);
        $caption.text(title);
        $lightbox.addClass('active');
        $('body').css('overflow', 'hidden');
    }

    /**
     * Close image lightbox
     */
    function closeLightbox() {
        const $lightbox = $('#sodek-gb-lightbox');
        $lightbox.removeClass('active');
        $('body').css('overflow', '');
    }

    /**
     * Get preselected data from JSON script
     */
    function getPreselectedData() {
        const $data = $('#sodek-gb-preselected');
        if ($data.length) {
            try {
                return JSON.parse($data.text());
            } catch (e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Handle preselected service
     */
    function handlePreselectedService(serviceId) {
        const $serviceRow = $(`.sodek-gb-service-row[data-service-id="${serviceId}"]`);
        if ($serviceRow.length) {
            const categoryId = parseInt($serviceRow.data('category-id'), 10);
            // Set category first
            state.category = {
                id: categoryId,
                slug: $serviceRow.data('category-slug') || '',
                name: $(`.sodek-gb-category-row[data-category-id="${categoryId}"] .sodek-gb-category-name`).text()
            };
            // Select the service
            selectService(serviceId);
        }
    }

    /**
     * Select category
     */
    function selectCategory(categoryId) {
        // Ensure categoryId is an integer for consistent comparison
        categoryId = parseInt(categoryId, 10);

        const $row = $(`.sodek-gb-category-row[data-category-id="${categoryId}"]`);
        setCategoryLoadingState($row);

        state.category = {
            id: categoryId,
            slug: $row.data('category-slug') || '',
            name: $row.find('.sodek-gb-category-name').text()
        };
        // Update category name display
        $('.sodek-gb-selected-category-name').text(state.category.name);

        const finishSelection = () => {
            // Filter services and get count
            const servicesInCategory = filterServices(categoryId);

            // If only one service in category, auto-select it and skip to datetime
            if (servicesInCategory.length === 1) {
                const serviceId = servicesInCategory[0].data('service-id');
                state.skippedServiceStep = true;
                clearCategoryLoadingState();
                selectService(serviceId);
                return;
            }

            state.skippedServiceStep = false;
            goToStep('service');
            clearCategoryLoadingState();
        };

        const $serviceStep = $('.sodek-gb-step-service');
        $serviceStep.addClass('is-loading');
        showStep('service');

        if (categoryTransitionTimer) {
            clearTimeout(categoryTransitionTimer);
        }

        categoryTransitionTimer = window.setTimeout(finishSelection, 220);
    }

    function setCategoryLoadingState($row) {
        $('.sodek-gb-category-row').removeClass('is-loading');
        $('.sodek-gb-step-service').addClass('is-loading');

        if ($row && $row.length) {
            $row.addClass('is-loading');
        }
    }

    function clearCategoryLoadingState() {
        $('.sodek-gb-category-row').removeClass('is-loading');
        $('.sodek-gb-step-service').removeClass('is-loading');
    }

    /**
     * Filter services by category
     * @returns {jQuery[]} Array of visible service rows
     */
    function filterServices(categoryId) {
        // Ensure categoryId is an integer for consistent comparison
        categoryId = parseInt(categoryId, 10);
        const $services = $('.sodek-gb-service-row');
        const visibleServices = [];

        $services.each(function() {
            const $row = $(this);
            const rowCategoryId = parseInt($row.data('category-id'), 10);
            if (rowCategoryId === categoryId) {
                $row.show();
                visibleServices.push($row);
            } else {
                $row.hide();
            }
        });

        return visibleServices;
    }

    /**
     * Select service
     */
    function selectService(serviceId) {
        const $row = $(`.sodek-gb-service-row[data-service-id="${serviceId}"]`);
        if (!$row.length) return;

        serviceId = parseInt(serviceId, 10);

        const price = parseFloat($row.data('price')) || 0;
        const depositAmount = parseFloat($row.data('deposit-amount')) || price;

        state.service = {
            id: serviceId,
            slug: $row.data('service-slug') || '',
            name: $row.find('.sodek-gb-service-name').text(),
            price: price,
            duration: parseInt($row.data('duration'), 10) || 60,
            description: $row.data('description') || '',
            image: $row.find('.sodek-gb-service-thumb img').attr('src') || '',
            imageFull: $row.find('.sodek-gb-service-thumb').data('lightbox-src') || $row.find('.sodek-gb-service-thumb img').attr('src') || '',
            deposit_type: $row.data('deposit-type') || 'fixed',
            deposit_value: parseFloat($row.data('deposit-value')) || 0,
            deposit_amount: depositAmount
        };

        // Clear addons
        state.addons = [];
        resetSelectedDateTime();
        loadAddonsForService(serviceId);

        // Update appointment card
        updateAppointmentCard();

        const $calendar = $('.sodek-gb-calendar');
        const calendarYear = parseInt($calendar.data('year'), 10) || new Date().getFullYear();
        const calendarMonth = parseInt($calendar.data('month'), 10) || (new Date().getMonth() + 1);
        renderCalendar(calendarYear, calendarMonth);

        goToStep('datetime');
    }

    function resetSelectedDateTime() {
        state.date = null;
        state.time = null;
        state.timeStart = null;
        state.timeEnd = null;

        $('.sodek-gb-calendar-day').removeClass('selected');
        $('.sodek-gb-date-display').text('');
        $('#sodek-gb-timeslots').html('<p class="sodek-gb-select-date-msg">Select a date to see available times.</p>');
    }

    function findCategoryRow(value) {
        const raw = String(value || '');
        const numeric = parseInt(raw, 10);
        if (!Number.isNaN(numeric) && String(numeric) === raw) {
            const $byId = $(`.sodek-gb-category-row[data-category-id="${numeric}"]`);
            if ($byId.length) {
                return $byId;
            }
        }

        return $(`.sodek-gb-category-row[data-category-slug="${raw}"]`);
    }

    function findServiceRow(value) {
        const raw = String(value || '');
        const numeric = parseInt(raw, 10);
        if (!Number.isNaN(numeric) && String(numeric) === raw) {
            const $byId = $(`.sodek-gb-service-row[data-service-id="${numeric}"]`);
            if ($byId.length) {
                return $byId;
            }
        }

        return $(`.sodek-gb-service-row[data-service-slug="${raw}"]`);
    }

    /**
     * Filter add-ons based on selected service
     */
    async function loadAddonsForService(serviceId) {
        serviceId = parseInt(serviceId, 10);
        const $addonsSection = $('.sodek-gb-addons-section');
        const $addonsList = $('#sodek-gb-addons-list');
        const requestId = ++addonRequestId;

        state.addons = [];
        $addonsList.html(renderAddonLoadingState());
        $addonsSection.show();

        try {
            const response = await fetch(config.ajaxUrl, {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'sodek_gb_get_service_addons',
                    nonce: config.bookingNonce || config.nonce || '',
                    service_id: serviceId
                })
            });

            const data = await response.json();

            if (requestId !== addonRequestId || !state.service || parseInt(state.service.id, 10) !== serviceId) {
                return;
            }

            if (!data.success) {
                throw new Error(data.data?.message || 'Failed to load add-ons.');
            }

            const html = data.data?.html || '';
            const count = parseInt(data.data?.count || 0, 10);

            if (count > 0 && html) {
                $addonsList.html(html);
                $addonsSection.show();
            } else {
                $addonsList.empty();
                $addonsSection.hide();
            }
        } catch (error) {
            if (requestId !== addonRequestId || !state.service || parseInt(state.service.id, 10) !== serviceId) {
                return;
            }

            console.error('GlowBook: Failed to load service add-ons:', error);
            $addonsList.html(renderAddonErrorState());
            $addonsSection.show();
        }
    }

    function renderAddonLoadingState() {
        return `
            <div class="sodek-gb-addon-loading" aria-live="polite" aria-busy="true">
                <div class="sodek-gb-addon-loading-head">
                    <span class="sodek-gb-addon-loading-title">Finding matching add-ons</span>
                    <span class="sodek-gb-addon-loading-copy">We’re loading only the options for this appointment.</span>
                </div>
                <div class="sodek-gb-addon-skeleton-grid">
                    ${renderAddonSkeletonCard()}
                    ${renderAddonSkeletonCard()}
                    ${renderAddonSkeletonCard()}
                    ${renderAddonSkeletonCard()}
                </div>
            </div>
        `;
    }

    function renderAddonSkeletonCard() {
        return `
            <div class="sodek-gb-addon-skeleton-card" aria-hidden="true">
                <span class="sodek-gb-addon-skeleton-check"></span>
                <span class="sodek-gb-addon-skeleton-body">
                    <span class="sodek-gb-addon-skeleton-line is-title"></span>
                    <span class="sodek-gb-addon-skeleton-line is-meta"></span>
                </span>
            </div>
        `;
    }

    function renderAddonErrorState() {
        return `
            <div class="sodek-gb-addon-feedback is-error" role="status" aria-live="polite">
                <span class="sodek-gb-addon-feedback-title">Add-ons couldn’t load right now</span>
                <span class="sodek-gb-addon-feedback-copy">Try selecting the service again, or refresh the page if the problem continues.</span>
            </div>
        `;
    }

    /**
     * Update the appointment card
     */
    function updateAppointmentCard() {
        const $card = $('.sodek-gb-appointment-card');
        const $thumb = $card.find('.sodek-gb-appointment-thumb');
        const $thumbImage = $thumb.find('img');

        // Title with staff name
        $card.find('.sodek-gb-appointment-title').text(state.service.name);

        // Price
        if ((state.service.price || 0) > 0) {
            $card.find('.sodek-gb-appointment-price').text(formatPrice(state.service.price)).show();
        } else {
            $card.find('.sodek-gb-appointment-price').text('').hide();
        }

        // Description
        $card.find('.sodek-gb-appointment-desc').text(state.service.description);

        if (state.service.image) {
            $thumbImage.attr('src', state.service.image).attr('alt', state.service.name);
            $thumb
                .attr('data-lightbox-src', state.service.imageFull || state.service.image)
                .attr('data-lightbox-title', state.service.name)
                .addClass('is-visible');
        } else {
            $thumbImage.attr('src', '').attr('alt', '');
            $thumb
                .attr('data-lightbox-src', '')
                .attr('data-lightbox-title', '')
                .removeClass('is-visible');
        }
    }

    /**
     * Toggle add-on
     */
    function toggleAddon($row) {
        const addonId = $row.data('addon-id');
        const isChecked = $row.find('input[type="checkbox"]').is(':checked');

        if (isChecked) {
            $row.addClass('is-selected');
            state.addons.push({
                id: addonId,
                name: $row.find('.sodek-gb-addon-name').text(),
                price: parseFloat($row.data('price')) || 0,
                duration: parseInt($row.data('duration'), 10) || 0
            });
        } else {
            $row.removeClass('is-selected');
            state.addons = state.addons.filter(a => a.id !== addonId);
        }

        state.time = null;
        state.timeStart = null;
        state.timeEnd = null;
        $('.sodek-gb-timeslot').removeClass('selected');

        if (state.service) {
            const $calendar = $('.sodek-gb-calendar');
            const calendarYear = parseInt($calendar.data('year'), 10) || parseBusinessDate(config.businessDate).getFullYear();
            const calendarMonth = parseInt($calendar.data('month'), 10) || (parseBusinessDate(config.businessDate).getMonth() + 1);
            renderCalendar(calendarYear, calendarMonth);

            if (state.date) {
                loadTimeSlots(state.date);
            }
        }
    }

    function getSelectedAddonIds() {
        return state.addons.map(addon => parseInt(addon.id, 10)).filter(id => Number.isFinite(id) && id > 0);
    }

    /**
     * Initialize calendar
     */
    function initCalendar() {
        const now = parseBusinessDate(config.businessDate);
        renderCalendar(now.getFullYear(), now.getMonth() + 1);
    }

    /**
     * Navigate calendar
     */
    function navigateCalendar(direction) {
        const $calendar = $('.sodek-gb-calendar');
        let month = parseInt($calendar.data('month'), 10);
        let year = parseInt($calendar.data('year'), 10);

        month += direction;
        if (month > 12) {
            month = 1;
            year++;
        } else if (month < 1) {
            month = 12;
            year--;
        }

        if (state.date) {
            const [selectedYear, selectedMonth] = String(state.date).split('-');
            if (parseInt(selectedYear, 10) !== year || parseInt(selectedMonth, 10) !== month) {
                resetSelectedDateTime();
            }
        }

        renderCalendar(year, month);
    }

    /**
     * Render calendar
     */
    async function renderCalendar(year, month) {
        const $calendar = $('.sodek-gb-calendar');
        $calendar.data('year', year).data('month', month);

        // Update header
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        $calendar.find('.sodek-gb-calendar-month').text(`${monthNames[month - 1]} ${year}`);

        let availableDates = null;
        let availabilityRequestFailed = false;
        if (state.service && state.service.id) {
            $calendar.addClass('is-loading');
            $calendar.find('.sodek-gb-calendar-days').html(renderCalendarLoadingState(year, month));
            availableDates = await fetchAvailableDates(year, month, state.service.id);
            if (availableDates === null) {
                return;
            }
            if (parseInt($calendar.data('year'), 10) !== year || parseInt($calendar.data('month'), 10) !== month) {
                return;
            }
            availabilityRequestFailed = availableDates === false;
        }

        // Build days
        const firstDay = new Date(year, month - 1, 1).getDay();
        const daysInMonth = new Date(year, month, 0).getDate();
        const today = parseBusinessDate(config.businessDate);
        today.setHours(0, 0, 0, 0);

        let html = '';

        // Empty cells
        for (let i = 0; i < firstDay; i++) {
            html += '<span class="sodek-gb-calendar-day empty"></span>';
        }

        // Days
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const dateObj = new Date(year, month - 1, day);
            const isPast = dateObj.getTime() < today.getTime();
            const hasBackendAvailability = Array.isArray(availableDates) ? availableDates.includes(dateStr) : null;
            const isUnavailable = availabilityRequestFailed ? true : (hasBackendAvailability === null ? dateObj.getDay() === 0 : !hasBackendAvailability);

            let classes = 'sodek-gb-calendar-day';
            if (isPast || isUnavailable) {
                classes += ' disabled';
            } else {
                classes += ' available';
            }

            if (state.date === dateStr) {
                classes += ' selected';
            }

            html += `<span class="${classes}" data-date="${dateStr}">${day}</span>`;
        }

        $calendar.find('.sodek-gb-calendar-days').html(html);
        $calendar.removeClass('is-loading');

        if (availabilityRequestFailed) {
            resetSelectedDateTime();
            $('#sodek-gb-timeslots').html('<p class="sodek-gb-no-slots">We couldn’t load available dates right now. Please refresh the page or try again in a moment.</p>');
            return;
        }

        if (state.date) {
            const selectedIsInMonth = state.date.startsWith(`${year}-${String(month).padStart(2, '0')}-`);
            const selectedStillAvailable = !Array.isArray(availableDates) || availableDates.includes(state.date);

            if (!selectedIsInMonth || !selectedStillAvailable) {
                resetSelectedDateTime();
            }
        }
    }

    function renderCalendarLoadingState(year, month) {
        const firstDay = new Date(year, month - 1, 1).getDay();
        const daysInMonth = new Date(year, month, 0).getDate();
        let html = '';

        for (let i = 0; i < firstDay; i++) {
            html += '<span class="sodek-gb-calendar-day empty"></span>';
        }

        for (let day = 1; day <= daysInMonth; day++) {
            html += `<span class="sodek-gb-calendar-day disabled is-loading-day" aria-hidden="true">${day}</span>`;
        }

        return html;
    }

    async function fetchAvailableDates(year, month, serviceId) {
        const addonIds = getSelectedAddonIds();
        const cacheKey = `${serviceId}:${year}:${month}:${addonIds.join(',')}`;
        const requestId = ++calendarRequestId;

        if (Object.prototype.hasOwnProperty.call(availableDatesCache, cacheKey)) {
            return availableDatesCache[cacheKey];
        }

        try {
            const params = new URLSearchParams({
                service_id: serviceId,
                year: year,
                month: month,
                addon_ids: addonIds.join(',')
            });

            const response = await fetch(buildRestUrl('availability/dates', params), {
                headers: {
                    'X-WP-Nonce': config.restNonce
                }
            });

            if (!response.ok) {
                throw new Error(`Availability dates request failed with status ${response.status}`);
            }

            const data = await response.json();
            if (requestId !== calendarRequestId) {
                return null;
            }

            const dates = Array.isArray(data.dates) ? data.dates : [];
            availableDatesCache[cacheKey] = dates;
            return dates;
        } catch (error) {
            console.error('Failed to load available dates:', error);
            return false;
        }
    }

    /**
     * Select date
     */
    function selectDate(dateStr) {
        state.date = dateStr;
        state.time = null;

        // Update calendar UI
        $('.sodek-gb-calendar-day').removeClass('selected');
        $(`.sodek-gb-calendar-day[data-date="${dateStr}"]`).addClass('selected');

        // Update date display
        const dateObj = new Date(dateStr + 'T12:00:00');
        const options = { weekday: 'long', month: 'long', day: 'numeric' };
        $('.sodek-gb-date-display').text(dateObj.toLocaleDateString('en-US', options));

        // Load time slots
        loadTimeSlots(dateStr);
    }

    /**
     * Load time slots
     */
    async function loadTimeSlots(date) {
        const $container = $('#sodek-gb-timeslots');
        const serviceId = state.service ? parseInt(state.service.id, 10) : 0;
        const requestId = ++timeSlotRequestId;

        if (!serviceId) {
            $container.html('<p class="sodek-gb-no-slots">Please select an appointment before choosing a time.</p>');
            return;
        }

        $container.html('<p class="sodek-gb-loading">Loading available times...</p>');

        try {
            const params = new URLSearchParams({
                service_id: serviceId,
                date: date,
                addon_ids: getSelectedAddonIds().join(',')
            });

            const response = await fetch(buildRestUrl('availability/slots', params), {
                headers: {
                    'X-WP-Nonce': config.restNonce
                }
            });

            if (!response.ok) {
                throw new Error(`Availability request failed with status ${response.status}`);
            }

            const data = await response.json();

            if (requestId !== timeSlotRequestId || state.date !== date || !state.service || parseInt(state.service.id, 10) !== serviceId) {
                return;
            }

            if (data.slots && data.slots.length > 0) {
                // Transform API slots to display format and pass remaining count
                const formattedSlots = data.slots.map(slot => ({
                    time: formatTime(slot.start),
                    start: slot.start,
                    end: slot.end,
                    available: true,
                    spots: data.remaining_slots // null = unlimited
                }));
                renderTimeSlots(formattedSlots);
            } else {
                // No slots available - could be fully booked or no business hours
                $container.html('<p class="sodek-gb-no-slots">No available times for this date. Please select another date.</p>');
            }
        } catch (error) {
            if (requestId !== timeSlotRequestId || state.date !== date || !state.service || parseInt(state.service.id, 10) !== serviceId) {
                return;
            }

            console.error('Failed to load slots:', error);
            state.time = null;
            state.timeStart = null;
            state.timeEnd = null;
            $container.html('<p class="sodek-gb-no-slots">We couldn’t load available times right now. Please refresh the page or try another date.</p>');
        }
    }

    /**
     * Format time from 24h (HH:mm) to 12h (h:mm AM/PM)
     */
    function formatTime(time24) {
        const [hours, minutes] = time24.split(':');
        const h = parseInt(hours, 10);
        const suffix = h >= 12 ? 'PM' : 'AM';
        const h12 = h % 12 || 12;
        return `${h12}:${minutes} ${suffix}`;
    }

    /**
     * Generate demo time slots
     */
    function generateDemoSlots() {
        const slots = [];
        // Use 24h format for start times, display format for time
        const times = [
            { display: '9:00 AM', start: '09:00' },
            { display: '9:30 AM', start: '09:30' },
            { display: '10:00 AM', start: '10:00' },
            { display: '10:30 AM', start: '10:30' },
            { display: '11:00 AM', start: '11:00' },
            { display: '11:30 AM', start: '11:30' },
            { display: '12:00 PM', start: '12:00' },
            { display: '12:30 PM', start: '12:30' },
            { display: '1:00 PM', start: '13:00' },
            { display: '1:30 PM', start: '13:30' },
            { display: '2:00 PM', start: '14:00' },
            { display: '2:30 PM', start: '14:30' },
            { display: '3:00 PM', start: '15:00' },
            { display: '3:30 PM', start: '15:30' },
            { display: '4:00 PM', start: '16:00' },
            { display: '4:30 PM', start: '16:30' },
            { display: '5:00 PM', start: '17:00' }
        ];

        times.forEach(t => {
            slots.push({
                time: t.display,
                start: t.start,
                end: '', // Will be calculated by backend
                available: Math.random() > 0.3, // Random availability
                spots: 1
            });
        });

        return slots;
    }

    /**
     * Render time slots
     */
    function renderTimeSlots(slots) {
        const $container = $('#sodek-gb-timeslots');
        let html = '<div class="sodek-gb-timeslots-grid">';

        slots.forEach(slot => {
            const isAvailable = slot.available !== false;
            // Only show spots text if there's a limit (not null/undefined) and spots > 0
            let spotsText = '';
            if (slot.spots !== null && slot.spots !== undefined && slot.spots > 0) {
                spotsText = `${slot.spots} spot${slot.spots > 1 ? 's' : ''} left`;
            }

            html += `
                <button type="button"
                    class="sodek-gb-timeslot ${isAvailable ? '' : 'disabled'}"
                    data-time="${slot.time}"
                    data-start="${slot.start || ''}"
                    data-end="${slot.end || ''}"
                    ${isAvailable ? '' : 'disabled'}>
                    <span class="sodek-gb-timeslot-time">${slot.time}</span>
                    ${spotsText ? `<span class="sodek-gb-timeslot-spots">${spotsText}</span>` : ''}
                </button>
            `;
        });

        html += '</div>';
        $container.html(html);
    }

    /**
     * Select time
     */
    function selectTime(time) {
        // Get the selected timeslot element
        const $slot = $(`.sodek-gb-timeslot[data-time="${time}"]`);

        // Store both display time and 24h time
        state.time = time; // Display format (e.g., "9:00 AM")
        state.timeStart = $slot.data('start') || time; // 24h format (e.g., "09:00")
        state.timeEnd = $slot.data('end') || '';

        // Update UI
        $('.sodek-gb-timeslot').removeClass('selected');
        $slot.addClass('selected');

        // Go to details step
        updateBookingSummary();
        goToStep('details');
    }

    /**
     * Update booking summary
     */
    function updateBookingSummary() {
        const $summary = $('.sodek-gb-booking-summary');

        // Service
        $summary.find('.sodek-gb-summary-service').html(`
            <span class="sodek-gb-summary-kicker">Appointment</span>
            <strong class="sodek-gb-summary-value is-service">${state.service.name}</strong>
            <span class="sodek-gb-summary-meta">${formatPrice(state.service.price)}</span>
        `);

        // Addons
        if (state.addons.length > 0) {
            let addonsHtml = '<span class="sodek-gb-summary-kicker">Add-ons</span>';
            state.addons.forEach(addon => {
                addonsHtml += `
                    <span class="sodek-gb-summary-addon-item">
                        <span>${addon.name}</span>
                        <strong>${formatPrice(addon.price)}</strong>
                    </span>
                `;
            });
            $summary.find('.sodek-gb-summary-addons').html(addonsHtml).show();
        } else {
            $summary.find('.sodek-gb-summary-addons').hide();
        }

        // Date & Time
        const dateObj = new Date(state.date + 'T12:00:00');
        const dateStr = dateObj.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        $summary.find('.sodek-gb-summary-datetime').html(`
            <span class="sodek-gb-summary-kicker">When</span>
            <strong class="sodek-gb-summary-value">${dateStr}</strong>
            <span class="sodek-gb-summary-meta">at ${state.time}</span>
        `);

        // Total
        const total = state.service.price + state.addons.reduce((sum, a) => sum + a.price, 0);
        $summary.find('.sodek-gb-summary-total').html(`
            <span class="sodek-gb-summary-kicker">Total</span>
            <strong class="sodek-gb-summary-total-value">${formatPrice(total)}</strong>
        `);
    }

    /**
     * Go to step
     */
    function goToStep(step) {
        state.step = step;

        if (step !== 'service') {
            clearCategoryLoadingState();
        }

        // Hide all steps
        $('.sodek-gb-step').removeClass('active');

        // Show target step
        const $targetStep = $(`.sodek-gb-step[data-step="${step}"]`);
        $targetStep.addClass('active');

        // Update back button on datetime step based on whether we skipped service step
        if (step === 'datetime') {
            const $backBtn = $targetStep.find('.sodek-gb-back-link');
            if (state.skippedServiceStep) {
                $backBtn.data('back', 'category').html(`
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    SELECT CATEGORY
                `);
            } else {
                $backBtn.data('back', 'service').html(`
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                    SELECT APPOINTMENT
                `);
            }
        }

        // Initialize Square payment form and deposit slider when entering details step
        if (step === 'details') {
            initializeSquarePayment();
            initDepositSlider();
        }

        // Update URL hash to persist state
        updateUrlHash();

        // Scroll to top
        if ($container && $container[0]) {
            $container[0].scrollIntoView({ behavior: 'smooth' });
        }
    }

    /**
     * Initialize Square payment form
     */
    function initializeSquarePayment() {
        // Check if card is already available (from debug init)
        if (window.glowbookSquareCard) {
            return;
        }

        // Check if Square SDK and our handler are available
        if (typeof window.SodekGbSquarePayment !== 'undefined') {
            // Re-initialize if not already done
            if (!window.SodekGbSquarePayment.initialized) {
                window.SodekGbSquarePayment.init();
            }
        }
    }

    /**
     * Submit booking
     */
    async function submitBooking() {
        const $form = $('#sodek-gb-customer-form');
        const $btn = $form.find('.sodek-gb-btn-book');
        const originalText = $btn.text();

        $btn.prop('disabled', true).text('Processing...');

        try {
            const formData = new FormData($form[0]);
            const total = state.service.price + state.addons.reduce((sum, a) => sum + a.price, 0);
            const customDeposit = parseFloat($('#sodek-gb-custom-deposit').val()) || 0;
            const requiredDeposit = getRequiredDeposit(total);
            const depositAmount = customDeposit > 0 ? customDeposit : requiredDeposit;

            // Tokenize card if Square payment is available
            let cardToken = '';
            let verificationToken = '';

            // Check for Square card (either from SodekGbSquarePayment or debug init)
            const squareCard = window.glowbookSquareCard || (window.SodekGbSquarePayment && window.SodekGbSquarePayment.card);
            const squarePayments = window.glowbookSquarePayments || (window.SodekGbSquarePayment && window.SodekGbSquarePayment.payments);

            if (squareCard) {
                try {
                    const tokenResult = await squareCard.tokenize();

                    if (tokenResult.status === 'OK') {
                        cardToken = tokenResult.token;

                        // Verify buyer (optional, for 3DS)
                        if (squarePayments) {
                            try {
                                // Get currency code from Square config if available
                                const currencyCode = (window.sodekGbSquare && window.sodekGbSquare.currency) || 'USD';
                                const verifyResult = await squarePayments.verifyBuyer(cardToken, {
                                    amount: depositAmount.toFixed(2),
                                    currencyCode: currencyCode,
                                    intent: 'CHARGE'
                                });
                                if (verifyResult && verifyResult.token) {
                                    verificationToken = verifyResult.token;
                                }
                            } catch (verifyErr) {
                                void verifyErr;
                            }
                        }
                    } else {
                        // Tokenization failed - show error from Square
                        const errorMsg = tokenResult.errors && tokenResult.errors[0]
                            ? tokenResult.errors[0].message
                            : 'Card validation failed';
                        throw new Error(errorMsg);
                    }
                } catch (tokenErr) {
                    console.error('GlowBook: Tokenization failed:', tokenErr);
                    throw new Error(tokenErr.message || 'Please check your card details.');
                }
            } else {
                console.error('GlowBook: No Square card available for tokenization');
                throw new Error('Payment form not ready. Please refresh and try again.');
            }

            const response = await fetch(config.ajaxUrl, {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'sodek_gb_standalone_payment',
                    nonce: config.paymentNonce || config.nonce,
                    service_id: state.service.id,
                    addon_ids: JSON.stringify(state.addons.map(a => a.id)),
                    booking_date: state.date,
                    booking_time: state.timeStart || state.time, // Use 24h format if available
                    customer_email: formData.get('email'),
                    customer_phone: formData.get('phone'),
                    customer_name: `${formData.get('first_name')} ${formData.get('last_name')}`.trim(),
                    booking_notes: formData.get('notes'),
                    card_token: cardToken,
                    verification_token: verificationToken,
                    custom_deposit: depositAmount,
                    customer_type: state.customerType === 'returning' ? 'returning' : 'new'
                })
            });

            const data = await response.json();

            if (data.success) {
                window.location.href = data.data.confirmation_url || window.location.href + '?booked=1';
            } else {
                throw new Error(data.data?.message || 'Booking failed');
            }
        } catch (error) {
            console.error('Booking error:', error);
            alert(error.message || 'Failed to complete booking. Please try again.');
            $btn.prop('disabled', false).text(originalText);
        }
    }

    /**
     * Initialize deposit input when entering details step
     */
    function initDepositSlider() {
        if (!state.service) return;

        const total = state.service.price + state.addons.reduce((sum, a) => sum + a.price, 0);
        const serviceMinimum = getServiceMinimumDeposit(total);
        const returningDeposit = getReturningCustomerDeposit(total, serviceMinimum);
        const defaultDeposit = getRequiredDeposit(total, returningDeposit, serviceMinimum);

        // Update display values
        $('.sodek-gb-service-total').text(formatPrice(total));
        $('.sodek-gb-min-deposit').text(formatPrice(returningDeposit));
        updatePaymentRuleContent(total, serviceMinimum, returningDeposit);
        updateDepositOptionAmounts(total, returningDeposit, serviceMinimum);

        // Set input constraints and value
        const $input = $('#sodek-gb-deposit-input');
        $input.attr('min', defaultDeposit);
        $input.attr('max', Math.max(total, defaultDeposit));
        $input.val(defaultDeposit.toFixed(2));

        // Update balance display
        updateDepositDisplay(defaultDeposit, total);

        // Set hidden field
        $('#sodek-gb-custom-deposit').val(defaultDeposit);

        $('.sodek-gb-deposit-option').removeClass('active');
        $('.sodek-gb-deposit-option[data-percent="' + (areCustomerPaymentRulesEnabled() && state.customerType !== 'returning' ? 'new' : 'returning') + '"]').addClass('active');

        if (shouldEnforceCustomerPaymentType()) {
            scheduleCustomerTypeCheck(true);
        }
    }

    /**
     * Update deposit from input field
     */
    function updateDepositFromInput(value) {
        if (!state.service) return;

        const total = state.service.price + state.addons.reduce((sum, a) => sum + a.price, 0);
        const serviceMinimum = getServiceMinimumDeposit(total);
        const returningDeposit = getReturningCustomerDeposit(total, serviceMinimum);
        let depositAmount = parseFloat(value) || 0;

        // Update display (don't clamp yet - do that on blur)
        updateDepositDisplay(depositAmount, total);
        $('#sodek-gb-custom-deposit').val(depositAmount);

        // Update active button based on value
        updateDepositActiveButton(depositAmount, returningDeposit, total, serviceMinimum);
    }

    /**
     * Validate and clamp deposit input on blur
     */
    function validateDepositInput() {
        if (!state.service) return;

        const total = state.service.price + state.addons.reduce((sum, a) => sum + a.price, 0);
        const serviceMinimum = getServiceMinimumDeposit(total);
        const returningDeposit = getReturningCustomerDeposit(total, serviceMinimum);
        const requiredDeposit = getRequiredDeposit(total, returningDeposit, serviceMinimum);
        const $input = $('#sodek-gb-deposit-input');
        let depositAmount = parseFloat($input.val()) || 0;

        // Clamp to valid range
        if (depositAmount < requiredDeposit) {
            depositAmount = requiredDeposit;
        } else if ((!areCustomerPaymentRulesEnabled() || state.customerType === 'returning') && depositAmount > total) {
            depositAmount = total;
        }

        depositAmount = roundCurrency(depositAmount);

        // Update input with formatted value
        $input.val(depositAmount.toFixed(2));

        // Update displays and hidden field
        updateDepositDisplay(depositAmount, total);
        $('#sodek-gb-custom-deposit').val(depositAmount);

        // Update active button
        updateDepositActiveButton(depositAmount, returningDeposit, total, serviceMinimum);
    }

    /**
     * Update which quick option button is active
     */
    function updateDepositActiveButton(depositAmount, returningDeposit, total, serviceMinimum = getServiceMinimumDeposit(total)) {
        const newCustomerDeposit = getNewCustomerDeposit(total, serviceMinimum);
        const requiredDeposit = getRequiredDeposit(total, returningDeposit, serviceMinimum);
        const halfDeposit = Math.max(requiredDeposit, roundCurrency(total * 0.5));

        $('.sodek-gb-deposit-option').removeClass('active');

        if (areCustomerPaymentRulesEnabled() && Math.abs(depositAmount - newCustomerDeposit) < 0.01 && state.customerType !== 'returning') {
            $('.sodek-gb-deposit-option[data-percent="new"]').addClass('active');
        } else if (Math.abs(depositAmount - halfDeposit) < 0.01) {
            $('.sodek-gb-deposit-option[data-percent="50"]').addClass('active');
        } else if (Math.abs(depositAmount - returningDeposit) < 0.01) {
            $('.sodek-gb-deposit-option[data-percent="returning"]').addClass('active');
        } else if (Math.abs(depositAmount - total) < 0.01) {
            $('.sodek-gb-deposit-option[data-percent="100"]').addClass('active');
        }
    }

    /**
     * Select deposit quick option
     */
    function selectDepositOption(percent) {
        if (!state.service) return;
        const $option = $(`.sodek-gb-deposit-option[data-percent="${percent}"]`);
        if ($option.hasClass('is-disabled')) return;

        const total = state.service.price + state.addons.reduce((sum, a) => sum + a.price, 0);
        const serviceMinimum = getServiceMinimumDeposit(total);
        if (percent === 'returning' || percent === 'new') {
            state.customerType = percent;
        }
        const returningDeposit = getReturningCustomerDeposit(total, serviceMinimum);
        const requiredDeposit = getRequiredDeposit(total, returningDeposit, serviceMinimum);
        let depositAmount;

        if (percent === 'returning') {
            depositAmount = returningDeposit;
        } else if (percent === 'new' && areCustomerPaymentRulesEnabled()) {
            depositAmount = getNewCustomerDeposit(total, serviceMinimum);
        } else {
            depositAmount = roundCurrency(total * (parseInt(percent, 10) / 100));
            // Ensure at least minimum deposit
            if (depositAmount < requiredDeposit) {
                depositAmount = requiredDeposit;
            }
        }

        // Update input field
        $('#sodek-gb-deposit-input').val(depositAmount.toFixed(2));

        // Update display
        updateDepositDisplay(depositAmount, total);
        $('#sodek-gb-custom-deposit').val(depositAmount);

        // Update active button
        updatePaymentRuleContent(total, serviceMinimum, returningDeposit);
        updateDepositOptionAmounts(total, returningDeposit, serviceMinimum);
        $('.sodek-gb-deposit-option').removeClass('active');
        $(`.sodek-gb-deposit-option[data-percent="${percent}"]`).addClass('active');
    }

    /**
     * Update deposit display values
     */
    function updateDepositDisplay(depositAmount, total) {
        const balance = Math.max(0, total - depositAmount);

        // Update balance display
        $('.sodek-gb-balance-display').text(formatPrice(balance));
    }

    function updateDepositOptionAmounts(total, returningDeposit = getReturningCustomerDeposit(total), serviceMinimum = getServiceMinimumDeposit(total)) {
        const newCustomerDeposit = getNewCustomerDeposit(total, serviceMinimum);
        const halfDeposit = Math.max(getRequiredDeposit(total, returningDeposit, serviceMinimum), roundCurrency(total * 0.5));

        $('.sodek-gb-deposit-option[data-percent="returning"] .sodek-gb-deposit-option-amount').text(formatPrice(returningDeposit));
        $('.sodek-gb-deposit-option[data-percent="new"] .sodek-gb-deposit-option-amount').text(formatPrice(newCustomerDeposit));
        $('.sodek-gb-deposit-option[data-percent="50"] .sodek-gb-deposit-option-amount').text(formatPrice(halfDeposit));
        $('.sodek-gb-deposit-option[data-percent="100"] .sodek-gb-deposit-option-amount').text(formatPrice(total));
    }

    function getConfiguredReturningCustomerPaymentAmount() {
        return Number.isFinite(CONFIGURED_RETURNING_CUSTOMER_PAYMENT) ? Math.max(CONFIGURED_RETURNING_CUSTOMER_PAYMENT, 0) : 50;
    }

    function getConfiguredNewCustomerPaymentAmount() {
        return Number.isFinite(CONFIGURED_NEW_CUSTOMER_PAYMENT) ? Math.max(CONFIGURED_NEW_CUSTOMER_PAYMENT, 0) : 150;
    }

    function areCustomerPaymentRulesEnabled() {
        return CUSTOMER_PAYMENT_RULES_ENABLED;
    }

    function shouldEnforceCustomerPaymentType() {
        return areCustomerPaymentRulesEnabled() && ENFORCE_CUSTOMER_PAYMENT_TYPE;
    }

    function getServiceMinimumDeposit(total) {
        if (!state.service) {
            return roundCurrency(total);
        }

        const depositType = state.service.deposit_type || 'fixed';
        const depositValue = parseFloat(state.service.deposit_value) || 0;

        if (depositType === 'percentage') {
            return roundCurrency(total * (depositValue / 100));
        }

        return Math.min(total, depositValue > 0 ? roundCurrency(depositValue) : roundCurrency(total));
    }

    function getNewCustomerDeposit(total, serviceMinimum = getServiceMinimumDeposit(total)) {
        if (!areCustomerPaymentRulesEnabled()) {
            return serviceMinimum;
        }

        void total;
        return getConfiguredNewCustomerPaymentAmount();
    }

    function getRequiredDeposit(total, returningDeposit = getReturningCustomerDeposit(total), serviceMinimum = getServiceMinimumDeposit(total)) {
        if (!areCustomerPaymentRulesEnabled()) {
            return serviceMinimum;
        }

        if (state.customerType === 'returning') {
            return returningDeposit;
        }

        return getNewCustomerDeposit(total, serviceMinimum);
    }

    function getReturningCustomerDeposit(total, serviceMinimum = getServiceMinimumDeposit(total)) {
        if (!areCustomerPaymentRulesEnabled()) {
            return serviceMinimum;
        }

        return Math.min(total, getConfiguredReturningCustomerPaymentAmount());
    }

    function updatePaymentRuleContent(total, serviceMinimum = getServiceMinimumDeposit(total), returningDeposit = getReturningCustomerDeposit(total, serviceMinimum)) {
        const $description = $('.sodek-gb-deposit-description');
        const $status = $('.sodek-gb-customer-payment-status');
        const $summaryLabel = $('.sodek-gb-deposit-row-label');
        const $returningLabel = $('.sodek-gb-deposit-option[data-percent="returning"] .sodek-gb-deposit-option-label');
        const $newButton = $('.sodek-gb-deposit-option[data-percent="new"]');
        const $returningButton = $('.sodek-gb-deposit-option[data-percent="returning"]');
        const newCustomerDeposit = getNewCustomerDeposit(total, serviceMinimum);

        if (!areCustomerPaymentRulesEnabled()) {
            $description.text('Payment is collected during booking. The required payment for this service is shown below. You can also choose 50% or pay in full now to reduce your balance at the appointment.');
            $status.text('');
            $summaryLabel.text('Minimum Payment:');
            $returningLabel.text('Minimum Payment');
            $newButton.hide();
            $returningButton.removeClass('is-disabled');
            $('.sodek-gb-min-deposit').text(formatPrice(serviceMinimum));
            return;
        }

        $description.text(`Returning customers pay ${formatPrice(getConfiguredReturningCustomerPaymentAmount())} during booking, or the full booking total if lower. New customers pay ${formatPrice(getConfiguredNewCustomerPaymentAmount())} during booking. Staff may verify customer type at the appointment.`);
        $returningLabel.text('Returning Customer');
        $newButton.show();
        $returningButton.removeClass('is-disabled');
        $newButton.removeClass('is-disabled');

        if (!shouldEnforceCustomerPaymentType()) {
            $status.text('Choose the payment option that applies to you. Staff will verify if needed when you arrive.');
            $summaryLabel.text(state.customerType === 'returning' ? 'Returning Customer Payment:' : 'New Customer Payment:');
            $('.sodek-gb-min-deposit').text(formatPrice(state.customerType === 'returning' ? returningDeposit : newCustomerDeposit));
            return;
        }

        if (state.customerType === 'returning') {
            $status.text('We detected this as a returning customer booking based on the email or phone provided.');
            $summaryLabel.text('Returning Customer Payment:');
            $('.sodek-gb-min-deposit').text(formatPrice(returningDeposit));
            $newButton.addClass('is-disabled');
        } else {
            $status.text('We detected this as a new customer booking based on the email or phone provided.');
            $summaryLabel.text('New Customer Payment:');
            $('.sodek-gb-min-deposit').text(formatPrice(newCustomerDeposit));
            $returningButton.addClass('is-disabled');
        }
    }

    function roundCurrency(amount) {
        return Math.round((amount || 0) * 100) / 100;
    }

    function scheduleCustomerTypeCheck(immediate = false) {
        if (!shouldEnforceCustomerPaymentType()) {
            return;
        }

        if (customerTypeCheckTimer) {
            clearTimeout(customerTypeCheckTimer);
        }

        const runCheck = () => {
            detectCustomerType();
        };

        if (immediate) {
            runCheck();
            return;
        }

        customerTypeCheckTimer = window.setTimeout(runCheck, 350);
    }

    function detectCustomerType() {
        if (!shouldEnforceCustomerPaymentType()) {
            return;
        }

        const email = ($('#sodek-gb-email').val() || '').trim();
        const phone = ($('#sodek-gb-phone').val() || '').trim();
        const hasValidEmail = email.length > 3 && email.indexOf('@') > 0;
        const hasPhone = phone.replace(/[^0-9]/g, '').length >= 10;

        if (!hasValidEmail && !hasPhone) {
            applyDetectedCustomerType('new');
            return;
        }

        const requestId = ++customerTypeRequestId;

        fetch(config.ajaxUrl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'sodek_gb_check_customer_status',
                nonce: config.bookingNonce || config.nonce,
                customer_email: email,
                customer_phone: phone
            })
        })
            .then((response) => response.json())
            .then((data) => {
                if (requestId !== customerTypeRequestId || !data.success) {
                    return;
                }

                applyDetectedCustomerType(data.data?.customer_type || 'new');
            })
            .catch(() => {
                // Keep the safer default if the lookup fails.
            });
    }

    function applyDetectedCustomerType(customerType) {
        if (!shouldEnforceCustomerPaymentType()) {
            return;
        }

        const nextType = customerType === 'returning' ? 'returning' : 'new';
        const previousType = state.customerType || 'new';
        state.customerType = nextType;

        if (!state.service) {
            return;
        }

        const total = state.service.price + state.addons.reduce((sum, a) => sum + a.price, 0);
        const serviceMinimum = getServiceMinimumDeposit(total);
        const previousReturningDeposit = getReturningCustomerDeposit(total, serviceMinimum);
        const previousRequired = previousType === 'returning'
            ? previousReturningDeposit
            : getNewCustomerDeposit(total, serviceMinimum);
        const nextReturningDeposit = getReturningCustomerDeposit(total, serviceMinimum);
        const nextRequired = getRequiredDeposit(total, nextReturningDeposit, serviceMinimum);
        const $input = $('#sodek-gb-deposit-input');
        const activeOption = $('.sodek-gb-deposit-option.active').data('percent');
        let depositAmount = roundCurrency(parseFloat($input.val()) || 0);

        updatePaymentRuleContent(total, serviceMinimum, nextReturningDeposit);
        updateDepositOptionAmounts(total, nextReturningDeposit, serviceMinimum);
        $input.attr('min', nextRequired);

        if (!activeOption || activeOption === 'returning' || activeOption === 'new' || Math.abs(depositAmount - previousRequired) < 0.01) {
            depositAmount = nextRequired;
        } else if (depositAmount < nextRequired) {
            depositAmount = nextRequired;
        }

        if (!areCustomerPaymentRulesEnabled() || nextType === 'returning') {
            depositAmount = Math.min(total, depositAmount);
        }

        $input.val(depositAmount.toFixed(2));
        $('#sodek-gb-custom-deposit').val(depositAmount);
        updateDepositDisplay(depositAmount, total);
        updateDepositActiveButton(depositAmount, nextReturningDeposit, total, serviceMinimum);
    }

    /**
     * Format price
     */
    function formatPrice(amount) {
        const currencySymbol = config.currencySymbol || config.currency || '$';
        return currencySymbol + (amount || 0).toFixed(2);
    }

    // Initialize on DOM ready
    $(document).ready(init);

})(jQuery);
