<?php
/**
 * Elementor Services Grid Widget.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Widget_Services_Grid class.
 */
class Sodek_GB_Widget_Services_Grid extends \Elementor\Widget_Base {

    /**
     * Get widget name.
     *
     * @return string
     */
    public function get_name() {
        return 'sodek_gb_services_grid';
    }

    /**
     * Get widget title.
     *
     * @return string
     */
    public function get_title() {
        return __( 'Services Grid', 'glowbook' );
    }

    /**
     * Get widget icon.
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    /**
     * Get widget categories.
     *
     * @return array
     */
    public function get_categories() {
        return array( Sodek_GB_Elementor::CATEGORY_SLUG );
    }

    /**
     * Get widget keywords.
     *
     * @return array
     */
    public function get_keywords() {
        return array( 'services', 'grid', 'catalog', 'list', 'glowbook', 'beauty', 'salon' );
    }

    /**
     * Register widget controls.
     */
    protected function register_controls() {
        // Layout Section
        $this->start_controls_section(
            'layout_section',
            array(
                'label' => __( 'Layout', 'glowbook' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'layout_style',
            array(
                'label'   => __( 'Layout Style', 'glowbook' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'grid'   => __( 'Grid (Cards)', 'glowbook' ),
                    'list-1' => __( 'Style 1 (Clean List)', 'glowbook' ),
                    'list-2' => __( 'Style 2 (Icon Cards)', 'glowbook' ),
                    'list-3' => __( 'Style 3 (Appointment Cards)', 'glowbook' ),
                ),
                'default' => 'grid',
            )
        );

        $this->add_control(
            'columns',
            array(
                'label'     => __( 'Columns', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::SELECT,
                'options'   => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ),
                'default'   => '3',
                'condition' => array(
                    'layout_style' => array( 'grid', 'list-2', 'list-3' ),
                ),
            )
        );

        $this->end_controls_section();

        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __( 'Content', 'glowbook' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'category_source',
            array(
                'label'       => __( 'Category Source', 'glowbook' ),
                'type'        => \Elementor\Controls_Manager::SELECT,
                'options'     => array(
                    'service'     => __( 'GlowBook Service Categories', 'glowbook' ),
                    'woocommerce' => __( 'WooCommerce Product Categories', 'glowbook' ),
                ),
                'default'     => 'woocommerce',
                'description' => __( 'Choose which category system to filter by.', 'glowbook' ),
            )
        );

        $this->add_control(
            'category',
            array(
                'label'       => __( 'Service Category', 'glowbook' ),
                'type'        => \Elementor\Controls_Manager::SELECT,
                'options'     => Sodek_GB_Elementor::get_categories_options(),
                'default'     => '',
                'description' => __( 'Filter services by GlowBook service category.', 'glowbook' ),
                'condition'   => array(
                    'category_source' => 'service',
                ),
            )
        );

        $this->add_control(
            'wc_category',
            array(
                'label'       => __( 'Product Category', 'glowbook' ),
                'type'        => \Elementor\Controls_Manager::SELECT,
                'options'     => Sodek_GB_Elementor::get_wc_categories_options(),
                'default'     => '',
                'description' => __( 'Filter services by WooCommerce product category.', 'glowbook' ),
                'condition'   => array(
                    'category_source' => 'woocommerce',
                ),
            )
        );

        $this->add_control(
            'limit',
            array(
                'label'   => __( 'Number of Services', 'glowbook' ),
                'type'    => \Elementor\Controls_Manager::NUMBER,
                'min'     => 1,
                'max'     => 50,
                'default' => 6,
            )
        );

        $this->add_control(
            'orderby',
            array(
                'label'   => __( 'Order By', 'glowbook' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'date'       => __( 'Date', 'glowbook' ),
                    'title'      => __( 'Title', 'glowbook' ),
                    'menu_order' => __( 'Menu Order', 'glowbook' ),
                    'rand'       => __( 'Random', 'glowbook' ),
                ),
                'default' => 'menu_order',
            )
        );

        $this->add_control(
            'order',
            array(
                'label'   => __( 'Order', 'glowbook' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'ASC'  => __( 'Ascending', 'glowbook' ),
                    'DESC' => __( 'Descending', 'glowbook' ),
                ),
                'default' => 'ASC',
            )
        );

        // Grid-specific content options
        $this->add_control(
            'grid_content_heading',
            array(
                'label'     => __( 'Display Options', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => array(
                    'layout_style' => 'grid',
                ),
            )
        );

        $this->add_control(
            'show_image',
            array(
                'label'        => __( 'Show Image/Icon', 'glowbook' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'glowbook' ),
                'label_off'    => __( 'No', 'glowbook' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => array(
                    'layout_style' => array( 'grid', 'list-2' ),
                ),
            )
        );

        $this->add_control(
            'show_price',
            array(
                'label'        => __( 'Show Price', 'glowbook' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'glowbook' ),
                'label_off'    => __( 'No', 'glowbook' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_duration',
            array(
                'label'        => __( 'Show Duration', 'glowbook' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'glowbook' ),
                'label_off'    => __( 'No', 'glowbook' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'show_excerpt',
            array(
                'label'        => __( 'Show Description', 'glowbook' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'glowbook' ),
                'label_off'    => __( 'No', 'glowbook' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => array(
                    'layout_style' => array( 'grid', 'list-2' ),
                ),
            )
        );

        $this->add_control(
            'excerpt_length',
            array(
                'label'     => __( 'Description Length (words)', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::NUMBER,
                'min'       => 5,
                'max'       => 100,
                'default'   => 15,
                'condition' => array(
                    'layout_style'  => 'list-2',
                    'show_excerpt'  => 'yes',
                ),
            )
        );

        $this->add_control(
            'show_book_button',
            array(
                'label'        => __( 'Show Book Button', 'glowbook' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'glowbook' ),
                'label_off'    => __( 'No', 'glowbook' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'button_text',
            array(
                'label'     => __( 'Button Text', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::TEXT,
                'default'   => __( 'Book Now', 'glowbook' ),
                'condition' => array(
                    'show_book_button' => 'yes',
                ),
            )
        );

        // Appointment Card Style Options
        $this->add_control(
            'appointment_card_heading',
            array(
                'label'     => __( 'Appointment Card Options', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => array(
                    'layout_style' => 'list-3',
                ),
            )
        );

        $this->add_control(
            'card_label_text',
            array(
                'label'     => __( 'Card Label', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::TEXT,
                'default'   => __( 'APPOINTMENT', 'glowbook' ),
                'condition' => array(
                    'layout_style' => 'list-3',
                ),
            )
        );

        $this->add_control(
            'price_prefix',
            array(
                'label'     => __( 'Price Prefix', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::TEXT,
                'default'   => __( 'From', 'glowbook' ),
                'condition' => array(
                    'layout_style' => 'list-3',
                    'show_price'   => 'yes',
                ),
            )
        );

        $this->add_control(
            'show_button_icon',
            array(
                'label'        => __( 'Show Button Icon', 'glowbook' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'glowbook' ),
                'label_off'    => __( 'No', 'glowbook' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => array(
                    'layout_style'     => 'list-3',
                    'show_book_button' => 'yes',
                ),
            )
        );

        $this->add_control(
            'button_icon',
            array(
                'label'     => __( 'Button Icon', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::SELECT,
                'options'   => array(
                    'lock'     => __( 'Lock', 'glowbook' ),
                    'calendar' => __( 'Calendar', 'glowbook' ),
                    'arrow'    => __( 'Arrow Right', 'glowbook' ),
                    'check'    => __( 'Checkmark', 'glowbook' ),
                ),
                'default'   => 'lock',
                'condition' => array(
                    'layout_style'     => 'list-3',
                    'show_book_button' => 'yes',
                    'show_button_icon' => 'yes',
                ),
            )
        );

        $this->add_control(
            'booking_page_url',
            array(
                'label'       => __( 'Booking Page URL', 'glowbook' ),
                'type'        => \Elementor\Controls_Manager::URL,
                'placeholder' => __( 'https://yoursite.com/booking/', 'glowbook' ),
                'description' => __( 'Select the page with the [sodek_gb_booking_form] shortcode. Leave empty to link to service single page.', 'glowbook' ),
                'default'     => array(
                    'url' => '',
                ),
                'condition'   => array(
                    'show_book_button' => 'yes',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Grid
        $this->start_controls_section(
            'style_grid',
            array(
                'label'     => __( 'Grid', 'glowbook' ),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'layout_style' => 'grid',
                ),
            )
        );

        $this->add_responsive_control(
            'grid_gap',
            array(
                'label'      => __( 'Gap', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array( 'px', 'em' ),
                'range'      => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 100,
                    ),
                ),
                'default'    => array(
                    'size' => 30,
                    'unit' => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-services-grid' => 'gap: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - List (Style 1)
        $this->start_controls_section(
            'style_list',
            array(
                'label'     => __( 'List', 'glowbook' ),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'layout_style' => 'list-1',
                ),
            )
        );

        $this->add_control(
            'list_divider_color',
            array(
                'label'     => __( 'Divider Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#e5e5e5',
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-services-list .sodek-gb-service-list-item' => 'border-bottom-color: {{VALUE}};',
                ),
            )
        );

        $this->add_responsive_control(
            'list_item_padding',
            array(
                'label'      => __( 'Item Padding', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em' ),
                'default'    => array(
                    'top'    => 20,
                    'right'  => 0,
                    'bottom' => 20,
                    'left'   => 0,
                    'unit'   => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-services-list .sodek-gb-service-list-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'list_duration_color',
            array(
                'label'     => __( 'Duration Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ff6b6b',
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-services-list .sodek-gb-service-list-duration' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Icon Cards (Style 2)
        $this->start_controls_section(
            'style_icon_cards',
            array(
                'label'     => __( 'Icon Cards', 'glowbook' ),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'layout_style' => 'list-2',
                ),
            )
        );

        $this->add_responsive_control(
            'icon_cards_gap',
            array(
                'label'      => __( 'Gap', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array( 'px', 'em' ),
                'range'      => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 60,
                    ),
                ),
                'default'    => array(
                    'size' => 20,
                    'unit' => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-services-list--style-2' => 'gap: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'icon_cards_border_color',
            array(
                'label'     => __( 'Border Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#e5e5e5',
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-services-list--style-2 .sodek-gb-icon-card' => 'border-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'icon_cards_border_radius',
            array(
                'label'      => __( 'Border Radius', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 30,
                    ),
                ),
                'default'    => array(
                    'size' => 8,
                    'unit' => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-services-list--style-2 .sodek-gb-icon-card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'icon_cards_padding',
            array(
                'label'      => __( 'Card Padding', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em' ),
                'default'    => array(
                    'top'    => 30,
                    'right'  => 20,
                    'bottom' => 30,
                    'left'   => 20,
                    'unit'   => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-services-list--style-2 .sodek-gb-icon-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'icon_color',
            array(
                'label'     => __( 'Icon/Image Color Filter', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#e91e63',
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-services-list--style-2 .sodek-gb-icon-card-icon svg' => 'stroke: {{VALUE}};',
                    '{{WRAPPER}} .sodek-gb-services-list--style-2 .sodek-gb-icon-card-icon svg path' => 'stroke: {{VALUE}};',
                ),
            )
        );

        $this->add_responsive_control(
            'icon_size',
            array(
                'label'      => __( 'Icon Size', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min' => 40,
                        'max' => 150,
                    ),
                ),
                'default'    => array(
                    'size' => 80,
                    'unit' => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-services-list--style-2 .sodek-gb-icon-card-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .sodek-gb-services-list--style-2 .sodek-gb-icon-card-icon img' => 'max-width: {{SIZE}}{{UNIT}}; max-height: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Appointment Cards (Style 3)
        $this->start_controls_section(
            'style_appointment_cards',
            array(
                'label'     => __( 'Appointment Cards', 'glowbook' ),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'layout_style' => 'list-3',
                ),
            )
        );

        $this->add_responsive_control(
            'appt_cards_gap',
            array(
                'label'      => __( 'Gap', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array( 'px', 'em' ),
                'range'      => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 60,
                    ),
                ),
                'default'    => array(
                    'size' => 20,
                    'unit' => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-services-list--style-3' => 'gap: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'appt_card_bg_color',
            array(
                'label'     => __( 'Card Background', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-appt-card' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'appt_card_border_color',
            array(
                'label'     => __( 'Border Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#e5e5e5',
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-appt-card' => 'border-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'appt_card_border_radius',
            array(
                'label'      => __( 'Border Radius', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 30,
                    ),
                ),
                'default'    => array(
                    'size' => 12,
                    'unit' => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-appt-card' => 'border-radius: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'appt_card_padding',
            array(
                'label'      => __( 'Card Padding', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em' ),
                'default'    => array(
                    'top'    => 24,
                    'right'  => 24,
                    'bottom' => 24,
                    'left'   => 24,
                    'unit'   => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-appt-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'appt_label_heading',
            array(
                'label'     => __( 'Label', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );

        $this->add_control(
            'appt_label_color',
            array(
                'label'     => __( 'Label Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#888888',
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-appt-card-label' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'appt_divider_heading',
            array(
                'label'     => __( 'Divider', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );

        $this->add_control(
            'appt_divider_color',
            array(
                'label'     => __( 'Divider Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#e5e5e5',
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-appt-card-divider' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'appt_price_heading',
            array(
                'label'     => __( 'Price', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );

        $this->add_control(
            'appt_price_color',
            array(
                'label'     => __( 'Price Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#C4A35A',
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-appt-card-price' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'appt_button_heading',
            array(
                'label'     => __( 'Button', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );

        $this->add_control(
            'appt_button_bg_color',
            array(
                'label'     => __( 'Button Background', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#C4A35A',
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-appt-card-button' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'appt_button_text_color',
            array(
                'label'     => __( 'Button Text Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-appt-card-button' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .sodek-gb-appt-card-button svg' => 'stroke: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'appt_button_hover_bg_color',
            array(
                'label'     => __( 'Button Hover Background', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'default'   => '#A88A45',
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-appt-card-button:hover' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Card (Grid only)
        $this->start_controls_section(
            'style_card',
            array(
                'label'     => __( 'Card', 'glowbook' ),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'layout_style' => 'grid',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            array(
                'name'     => 'card_background',
                'label'    => __( 'Background', 'glowbook' ),
                'types'    => array( 'classic', 'gradient' ),
                'selector' => '{{WRAPPER}} .sodek-gb-service-card',
            )
        );

        $this->add_responsive_control(
            'card_padding',
            array(
                'label'      => __( 'Padding', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-service-card-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'     => 'card_border',
                'selector' => '{{WRAPPER}} .sodek-gb-service-card',
            )
        );

        $this->add_control(
            'card_border_radius',
            array(
                'label'      => __( 'Border Radius', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-service-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'card_shadow',
                'selector' => '{{WRAPPER}} .sodek-gb-service-card',
            )
        );

        $this->end_controls_section();

        // Style Section - Image (Grid only)
        $this->start_controls_section(
            'style_image',
            array(
                'label'     => __( 'Image', 'glowbook' ),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'conditions' => array(
                    'relation' => 'and',
                    'terms'    => array(
                        array(
                            'name'     => 'layout_style',
                            'operator' => '===',
                            'value'    => 'grid',
                        ),
                        array(
                            'name'     => 'show_image',
                            'operator' => '===',
                            'value'    => 'yes',
                        ),
                    ),
                ),
            )
        );

        $this->add_responsive_control(
            'image_height',
            array(
                'label'      => __( 'Height', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array( 'px', 'vh' ),
                'range'      => array(
                    'px' => array(
                        'min' => 100,
                        'max' => 500,
                    ),
                ),
                'default'    => array(
                    'size' => 200,
                    'unit' => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-service-card-image' => 'height: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'image_border_radius',
            array(
                'label'      => __( 'Border Radius', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-service-card-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Typography
        $this->start_controls_section(
            'style_typography',
            array(
                'label' => __( 'Typography', 'glowbook' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'title_heading',
            array(
                'label' => __( 'Title', 'glowbook' ),
                'type'  => \Elementor\Controls_Manager::HEADING,
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'title_typography',
                'selector' => '{{WRAPPER}} .sodek-gb-service-card-title, {{WRAPPER}} .sodek-gb-service-list-title',
            )
        );

        $this->add_control(
            'title_color',
            array(
                'label'     => __( 'Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-service-card-title' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .sodek-gb-service-list-title' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'price_heading',
            array(
                'label'     => __( 'Price', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'price_typography',
                'selector' => '{{WRAPPER}} .sodek-gb-service-card-price, {{WRAPPER}} .sodek-gb-service-list-price',
            )
        );

        $this->add_control(
            'price_color',
            array(
                'label'     => __( 'Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-service-card-price' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .sodek-gb-service-list-price' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'excerpt_heading',
            array(
                'label'     => __( 'Description', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => array(
                    'layout_style' => 'grid',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'      => 'excerpt_typography',
                'selector'  => '{{WRAPPER}} .sodek-gb-service-card-excerpt',
                'condition' => array(
                    'layout_style' => 'grid',
                ),
            )
        );

        $this->add_control(
            'excerpt_color',
            array(
                'label'     => __( 'Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-service-card-excerpt' => 'color: {{VALUE}};',
                ),
                'condition' => array(
                    'layout_style' => 'grid',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Button
        $this->start_controls_section(
            'style_button',
            array(
                'label'     => __( 'Button', 'glowbook' ),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_book_button' => 'yes',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'button_typography',
                'selector' => '{{WRAPPER}} .sodek-gb-service-card-button, {{WRAPPER}} .sodek-gb-service-list-button',
            )
        );

        $this->start_controls_tabs( 'button_tabs' );

        $this->start_controls_tab(
            'button_normal',
            array(
                'label' => __( 'Normal', 'glowbook' ),
            )
        );

        $this->add_control(
            'button_color',
            array(
                'label'     => __( 'Text Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-service-card-button' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .sodek-gb-service-list-button' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'button_background',
            array(
                'label'     => __( 'Background Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-service-card-button' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .sodek-gb-service-list-button' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'button_hover',
            array(
                'label' => __( 'Hover', 'glowbook' ),
            )
        );

        $this->add_control(
            'button_hover_color',
            array(
                'label'     => __( 'Text Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-service-card-button:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .sodek-gb-service-list-button:hover' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'button_hover_background',
            array(
                'label'     => __( 'Background Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-service-card-button:hover' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .sodek-gb-service-list-button:hover' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'button_padding',
            array(
                'label'      => __( 'Padding', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em' ),
                'separator'  => 'before',
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-service-card-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .sodek-gb-service-list-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'button_border_radius',
            array(
                'label'      => __( 'Border Radius', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array( 'px', '%' ),
                'range'      => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 50,
                    ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-service-card-button' => 'border-radius: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .sodek-gb-service-list-button' => 'border-radius: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output.
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        $args = array(
            'post_type'      => 'sodek_gb_service',
            'posts_per_page' => absint( $settings['limit'] ),
            'orderby'        => sanitize_text_field( $settings['orderby'] ),
            'order'          => sanitize_text_field( $settings['order'] ),
            'post_status'    => 'publish',
        );

        $category_source = ! empty( $settings['category_source'] ) ? $settings['category_source'] : 'woocommerce';

        // Filter by GlowBook Service Category
        if ( 'service' === $category_source && ! empty( $settings['category'] ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'sodek_gb_service_cat',
                    'field'    => 'term_id',
                    'terms'    => absint( $settings['category'] ),
                ),
            );
        }

        // Filter by WooCommerce Product Category
        if ( 'woocommerce' === $category_source && ! empty( $settings['wc_category'] ) ) {
            $service_ids = $this->get_service_ids_by_wc_category( absint( $settings['wc_category'] ) );
            if ( ! empty( $service_ids ) ) {
                $args['post__in'] = $service_ids;
            } else {
                // No services found for this WC category
                $args['post__in'] = array( 0 );
            }
        }

        $services = new WP_Query( $args );

        if ( ! $services->have_posts() ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<p>' . esc_html__( 'No services found.', 'glowbook' ) . '</p>';
            }
            return;
        }

        $layout_style = $settings['layout_style'];

        // Route to appropriate render method
        switch ( $layout_style ) {
            case 'list-1':
                $this->render_list_style_1( $services, $settings );
                break;
            case 'list-2':
                $this->render_list_style_2( $services, $settings );
                break;
            case 'list-3':
                $this->render_list_style_3( $services, $settings );
                break;
            case 'grid':
            default:
                $this->render_grid_style( $services, $settings );
                break;
        }

        wp_reset_postdata();
    }

    /**
     * Get the booking URL for a service.
     *
     * @param int   $service_id Service ID.
     * @param array $settings   Widget settings.
     * @return string
     */
    protected function get_booking_url( $service_id, $settings ) {
        $booking_page = ! empty( $settings['booking_page_url']['url'] ) ? $settings['booking_page_url']['url'] : '';

        if ( $booking_page ) {
            // Use the configured booking page with service parameter
            return add_query_arg( 'service', $service_id, $booking_page );
        }

        // Try to get the GlowBook booking page
        $booking_page_id = get_option( 'sodek_gb_booking_page_id' );
        if ( $booking_page_id && get_post_status( $booking_page_id ) === 'publish' ) {
            return add_query_arg( 'service', $service_id, get_permalink( $booking_page_id ) );
        }

        // Fallback to service single page
        return get_permalink( $service_id );
    }

    /**
     * Get service IDs that are linked to WooCommerce products in a specific category.
     *
     * @param int $category_id WooCommerce product category ID.
     * @return array Array of service IDs.
     */
    protected function get_service_ids_by_wc_category( $category_id ) {
        // Get WooCommerce products in this category that are booking products
        $products = wc_get_products( array(
            'limit'    => -1,
            'status'   => 'publish',
            'category' => array( get_term( $category_id, 'product_cat' )->slug ),
            'meta_key' => '_sodek_gb_is_booking_product',
            'meta_value' => 'yes',
        ) );

        $service_ids = array();

        foreach ( $products as $product ) {
            $service_id = $product->get_meta( '_sodek_gb_service_id' );
            if ( $service_id ) {
                $service_ids[] = absint( $service_id );
            }
        }

        return array_unique( $service_ids );
    }

    /**
     * Render Grid Style layout.
     *
     * @param WP_Query $services Services query.
     * @param array    $settings Widget settings.
     */
    protected function render_grid_style( $services, $settings ) {
        $columns       = absint( $settings['columns'] );
        $show_image    = 'yes' === $settings['show_image'];
        $show_price    = 'yes' === $settings['show_price'];
        $show_duration = 'yes' === $settings['show_duration'];
        $show_excerpt  = 'yes' === $settings['show_excerpt'];
        $show_button   = 'yes' === $settings['show_book_button'];
        $button_text   = ! empty( $settings['button_text'] ) ? $settings['button_text'] : __( 'Book Now', 'glowbook' );

        ?>
        <div class="sodek-gb-services-grid sodek-gb-columns-<?php echo esc_attr( $columns ); ?>">
            <?php
            while ( $services->have_posts() ) :
                $services->the_post();
                $service_id  = get_the_ID();
                $price       = get_post_meta( $service_id, '_sodek_gb_price', true );
                $duration    = get_post_meta( $service_id, '_sodek_gb_duration', true );
                $booking_url = $this->get_booking_url( $service_id, $settings );
                ?>
                <div class="sodek-gb-service-card">
                    <?php if ( $show_image && has_post_thumbnail() ) : ?>
                        <div class="sodek-gb-service-card-image">
                            <?php the_post_thumbnail( 'medium_large' ); ?>
                        </div>
                    <?php endif; ?>

                    <div class="sodek-gb-service-card-content">
                        <h3 class="sodek-gb-service-card-title"><?php the_title(); ?></h3>

                        <?php if ( $show_price || $show_duration ) : ?>
                            <div class="sodek-gb-service-card-meta">
                                <?php if ( $show_price && $price ) : ?>
                                    <span class="sodek-gb-service-card-price">
                                        <?php echo wc_price( $price ); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ( $show_duration && $duration ) : ?>
                                    <span class="sodek-gb-service-card-duration">
                                        <?php
                                        printf(
                                            /* translators: %d: duration in minutes */
                                            esc_html__( '%d min', 'glowbook' ),
                                            absint( $duration )
                                        );
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $show_excerpt && has_excerpt() ) : ?>
                            <div class="sodek-gb-service-card-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $show_button ) : ?>
                            <a href="<?php echo esc_url( $booking_url ); ?>" class="sodek-gb-service-card-button">
                                <?php echo esc_html( $button_text ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php
    }

    /**
     * Render List Style 1 - Clean List layout.
     *
     * @param WP_Query $services Services query.
     * @param array    $settings Widget settings.
     */
    protected function render_list_style_1( $services, $settings ) {
        $show_price    = 'yes' === $settings['show_price'];
        $show_duration = 'yes' === $settings['show_duration'];
        $show_button   = 'yes' === $settings['show_book_button'];
        $button_text   = ! empty( $settings['button_text'] ) ? $settings['button_text'] : __( 'Appointment', 'glowbook' );

        ?>
        <div class="sodek-gb-services-list sodek-gb-services-list--style-1">
            <?php
            while ( $services->have_posts() ) :
                $services->the_post();
                $service_id  = get_the_ID();
                $price       = get_post_meta( $service_id, '_sodek_gb_price', true );
                $duration    = get_post_meta( $service_id, '_sodek_gb_duration', true );
                $booking_url = $this->get_booking_url( $service_id, $settings );
                ?>
                <div class="sodek-gb-service-list-item">
                    <div class="sodek-gb-service-list-info">
                        <h3 class="sodek-gb-service-list-title"><?php the_title(); ?></h3>
                    </div>

                    <div class="sodek-gb-service-list-actions">
                        <?php if ( $show_price || $show_duration ) : ?>
                            <div class="sodek-gb-service-list-meta">
                                <?php if ( $show_price && $price ) : ?>
                                    <span class="sodek-gb-service-list-price">
                                        <?php echo wc_price( $price ); ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ( $show_duration && $duration ) : ?>
                                    <span class="sodek-gb-service-list-duration">
                                        <?php
                                        printf(
                                            /* translators: %d: duration in minutes */
                                            esc_html__( '-%d Minute', 'glowbook' ),
                                            absint( $duration )
                                        );
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $show_button ) : ?>
                            <a href="<?php echo esc_url( $booking_url ); ?>" class="sodek-gb-service-list-button">
                                <?php echo esc_html( $button_text ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php
    }

    /**
     * Render List Style 2 - Icon Cards layout.
     *
     * @param WP_Query $services Services query.
     * @param array    $settings Widget settings.
     */
    protected function render_list_style_2( $services, $settings ) {
        $columns        = absint( $settings['columns'] );
        $show_image     = 'yes' === $settings['show_image'];
        $show_excerpt   = 'yes' === $settings['show_excerpt'];
        $excerpt_length = ! empty( $settings['excerpt_length'] ) ? absint( $settings['excerpt_length'] ) : 15;

        ?>
        <div class="sodek-gb-services-list sodek-gb-services-list--style-2 sodek-gb-columns-<?php echo esc_attr( $columns ); ?>">
            <?php
            while ( $services->have_posts() ) :
                $services->the_post();
                $service_id  = get_the_ID();
                $booking_url = $this->get_booking_url( $service_id, $settings );
                $description = wp_trim_words( get_the_excerpt(), $excerpt_length, '...' );
                ?>
                <a href="<?php echo esc_url( $booking_url ); ?>" class="sodek-gb-icon-card">
                    <?php if ( $show_image ) : ?>
                        <div class="sodek-gb-icon-card-icon">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <?php the_post_thumbnail( 'thumbnail' ); ?>
                            <?php else : ?>
                                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <h3 class="sodek-gb-icon-card-title"><?php the_title(); ?></h3>

                    <?php if ( $show_excerpt && $description ) : ?>
                        <p class="sodek-gb-icon-card-desc"><?php echo esc_html( $description ); ?></p>
                    <?php endif; ?>
                </a>
            <?php endwhile; ?>
        </div>
        <?php
    }

    /**
     * Render List Style 3 - Appointment Cards layout.
     *
     * @param WP_Query $services Services query.
     * @param array    $settings Widget settings.
     */
    protected function render_list_style_3( $services, $settings ) {
        $columns          = absint( $settings['columns'] );
        $show_price       = 'yes' === $settings['show_price'];
        $show_button      = 'yes' === $settings['show_book_button'];
        $button_text      = ! empty( $settings['button_text'] ) ? $settings['button_text'] : __( 'Book Now', 'glowbook' );
        $card_label       = ! empty( $settings['card_label_text'] ) ? $settings['card_label_text'] : __( 'APPOINTMENT', 'glowbook' );
        $price_prefix     = ! empty( $settings['price_prefix'] ) ? $settings['price_prefix'] : __( 'From', 'glowbook' );
        $show_button_icon = 'yes' === ( $settings['show_button_icon'] ?? 'yes' );
        $button_icon      = ! empty( $settings['button_icon'] ) ? $settings['button_icon'] : 'lock';

        ?>
        <div class="sodek-gb-services-list sodek-gb-services-list--style-3 sodek-gb-columns-<?php echo esc_attr( $columns ); ?>">
            <?php
            while ( $services->have_posts() ) :
                $services->the_post();
                $service_id  = get_the_ID();
                $price       = get_post_meta( $service_id, '_sodek_gb_price', true );
                $booking_url = $this->get_booking_url( $service_id, $settings );
                ?>
                <div class="sodek-gb-appt-card">
                    <div class="sodek-gb-appt-card-content">
                        <span class="sodek-gb-appt-card-label"><?php echo esc_html( $card_label ); ?></span>
                        <h3 class="sodek-gb-appt-card-title"><?php the_title(); ?></h3>
                        <div class="sodek-gb-appt-card-divider"></div>
                        <?php if ( $show_price && $price ) : ?>
                            <span class="sodek-gb-appt-card-price">
                                <?php echo esc_html( $price_prefix ); ?> <?php echo wc_price( $price ); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ( $show_button ) : ?>
                        <a href="<?php echo esc_url( $booking_url ); ?>" class="sodek-gb-appt-card-button">
                            <?php echo esc_html( $button_text ); ?>
                            <?php if ( $show_button_icon ) : ?>
                                <?php echo $this->get_button_icon_svg( $button_icon ); ?>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
        <?php
    }

    /**
     * Get SVG icon for button.
     *
     * @param string $icon Icon name.
     * @return string SVG markup.
     */
    protected function get_button_icon_svg( $icon ) {
        $icons = array(
            'lock'     => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>',
            'calendar' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
            'arrow'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>',
            'check'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
        );

        return $icons[ $icon ] ?? $icons['lock'];
    }

    /**
     * Render widget output in the editor.
     */
    protected function content_template() {
        ?>
        <div class="sodek-gb-elementor-placeholder">
            <div class="sodek-gb-elementor-placeholder-icon">
                <# if ( settings.layout_style === 'list-1' ) { #>
                    <i class="eicon-editor-list-ul"></i>
                <# } else if ( settings.layout_style === 'list-2' ) { #>
                    <i class="eicon-posts-grid"></i>
                <# } else if ( settings.layout_style === 'list-3' ) { #>
                    <i class="eicon-posts-justified"></i>
                <# } else { #>
                    <i class="eicon-gallery-grid"></i>
                <# } #>
            </div>
            <div class="sodek-gb-elementor-placeholder-title">
                <?php esc_html_e( 'GlowBook Services', 'glowbook' ); ?>
            </div>
            <div class="sodek-gb-elementor-placeholder-desc">
                <# if ( settings.layout_style === 'list-1' ) { #>
                    <?php esc_html_e( 'Style 1: Clean List Layout', 'glowbook' ); ?>
                <# } else if ( settings.layout_style === 'list-2' ) { #>
                    <?php esc_html_e( 'Style 2: Icon Cards Layout', 'glowbook' ); ?>
                    <br>
                    <small><?php esc_html_e( 'Columns:', 'glowbook' ); ?> {{ settings.columns }}</small>
                <# } else if ( settings.layout_style === 'list-3' ) { #>
                    <?php esc_html_e( 'Style 3: Appointment Cards Layout', 'glowbook' ); ?>
                    <br>
                    <small><?php esc_html_e( 'Columns:', 'glowbook' ); ?> {{ settings.columns }}</small>
                <# } else { #>
                    <?php esc_html_e( 'Grid Layout', 'glowbook' ); ?>
                    <br>
                    <small><?php esc_html_e( 'Columns:', 'glowbook' ); ?> {{ settings.columns }}</small>
                <# } #>
                <br>
                <small><?php esc_html_e( 'Limit:', 'glowbook' ); ?> {{ settings.limit }}</small>
            </div>
        </div>
        <?php
    }
}
