<?php
/**
 * Elementor Booking Form Widget.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Widget_Booking_Form class.
 */
class Sodek_GB_Widget_Booking_Form extends \Elementor\Widget_Base {

    /**
     * Get widget name.
     *
     * @return string
     */
    public function get_name() {
        return 'sodek_gb_booking_form';
    }

    /**
     * Get widget title.
     *
     * @return string
     */
    public function get_title() {
        return __( 'Booking Form', 'glowbook' );
    }

    /**
     * Get widget icon.
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-form-horizontal';
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
        return array( 'booking', 'form', 'appointment', 'schedule', 'calendar', 'glowbook' );
    }

    /**
     * Register widget controls.
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __( 'Content', 'glowbook' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'service_id',
            array(
                'label'   => __( 'Service', 'glowbook' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => Sodek_GB_Elementor::get_services_options(),
                'default' => '',
                'description' => __( 'Leave empty to show service selector first.', 'glowbook' ),
            )
        );

        $this->add_control(
            'show_service_info',
            array(
                'label'        => __( 'Show Service Info', 'glowbook' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'glowbook' ),
                'label_off'    => __( 'No', 'glowbook' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => array(
                    'service_id!' => '',
                ),
            )
        );

        $this->add_control(
            'show_addons',
            array(
                'label'        => __( 'Show Add-ons', 'glowbook' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'glowbook' ),
                'label_off'    => __( 'No', 'glowbook' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->end_controls_section();

        // Style Section - Form Container
        $this->start_controls_section(
            'style_container',
            array(
                'label' => __( 'Form Container', 'glowbook' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            array(
                'name'     => 'container_background',
                'label'    => __( 'Background', 'glowbook' ),
                'types'    => array( 'classic', 'gradient' ),
                'selector' => '{{WRAPPER}} .sodek-gb-booking-form',
            )
        );

        $this->add_responsive_control(
            'container_padding',
            array(
                'label'      => __( 'Padding', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', 'em', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-booking-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'     => 'container_border',
                'selector' => '{{WRAPPER}} .sodek-gb-booking-form',
            )
        );

        $this->add_control(
            'container_border_radius',
            array(
                'label'      => __( 'Border Radius', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-booking-form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'container_shadow',
                'selector' => '{{WRAPPER}} .sodek-gb-booking-form',
            )
        );

        $this->end_controls_section();

        // Style Section - Calendar
        $this->start_controls_section(
            'style_calendar',
            array(
                'label' => __( 'Calendar', 'glowbook' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_control(
            'calendar_primary_color',
            array(
                'label'     => __( 'Primary Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-calendar .sodek-gb-day.available:hover' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
                    '{{WRAPPER}} .sodek-gb-calendar .sodek-gb-day.selected' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
                    '{{WRAPPER}} .sodek-gb-time-slot.selected' => 'background-color: {{VALUE}}; border-color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'calendar_available_color',
            array(
                'label'     => __( 'Available Day Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-calendar .sodek-gb-day.available' => 'background-color: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        // Style Section - Button
        $this->start_controls_section(
            'style_button',
            array(
                'label' => __( 'Button', 'glowbook' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'button_typography',
                'selector' => '{{WRAPPER}} .sodek-gb-submit-btn',
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
                    '{{WRAPPER}} .sodek-gb-submit-btn' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'button_background',
            array(
                'label'     => __( 'Background Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-submit-btn' => 'background-color: {{VALUE}};',
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
                    '{{WRAPPER}} .sodek-gb-submit-btn:hover' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'button_hover_background',
            array(
                'label'     => __( 'Background Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-submit-btn:hover' => 'background-color: {{VALUE}};',
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
                    '{{WRAPPER}} .sodek-gb-submit-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .sodek-gb-submit-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
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

        $service_id = ! empty( $settings['service_id'] ) ? absint( $settings['service_id'] ) : 0;
        $show_info  = 'yes' === $settings['show_service_info'] ? 'yes' : 'no';

        // Add wrapper class for Elementor styling
        echo '<div class="sodek-gb-elementor-widget">';

        // Use the shortcode
        echo do_shortcode( sprintf(
            '[sodek_gb_booking_form service_id="%d" show_info="%s"]',
            $service_id,
            $show_info
        ) );

        echo '</div>';
    }

    /**
     * Render widget output in the editor.
     */
    protected function content_template() {
        ?>
        <div class="sodek-gb-elementor-placeholder">
            <div class="sodek-gb-elementor-placeholder-icon">
                <i class="eicon-form-horizontal"></i>
            </div>
            <div class="sodek-gb-elementor-placeholder-title">
                <?php esc_html_e( 'GlowBook Booking Form', 'glowbook' ); ?>
            </div>
            <div class="sodek-gb-elementor-placeholder-desc">
                <# if ( settings.service_id ) { #>
                    <?php esc_html_e( 'Booking form for selected service', 'glowbook' ); ?>
                <# } else { #>
                    <?php esc_html_e( 'Service selector with booking form', 'glowbook' ); ?>
                <# } #>
            </div>
        </div>
        <?php
    }
}
