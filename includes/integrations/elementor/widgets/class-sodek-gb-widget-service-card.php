<?php
/**
 * Elementor Service Card Widget.
 *
 * @package GlowBook
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sodek_GB_Widget_Service_Card class.
 */
class Sodek_GB_Widget_Service_Card extends \Elementor\Widget_Base {

    /**
     * Get widget name.
     *
     * @return string
     */
    public function get_name() {
        return 'sodek_gb_service_card';
    }

    /**
     * Get widget title.
     *
     * @return string
     */
    public function get_title() {
        return __( 'Service Card', 'glowbook' );
    }

    /**
     * Get widget icon.
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-info-box';
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
        return array( 'service', 'card', 'single', 'glowbook', 'beauty', 'salon', 'booking' );
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
            )
        );

        $this->add_control(
            'layout',
            array(
                'label'   => __( 'Layout', 'glowbook' ),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'vertical'   => __( 'Vertical', 'glowbook' ),
                    'horizontal' => __( 'Horizontal', 'glowbook' ),
                ),
                'default' => 'vertical',
            )
        );

        $this->add_control(
            'show_image',
            array(
                'label'        => __( 'Show Image', 'glowbook' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'glowbook' ),
                'label_off'    => __( 'No', 'glowbook' ),
                'return_value' => 'yes',
                'default'      => 'yes',
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
            'show_description',
            array(
                'label'        => __( 'Show Description', 'glowbook' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'glowbook' ),
                'label_off'    => __( 'No', 'glowbook' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'description_length',
            array(
                'label'     => __( 'Description Length', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::NUMBER,
                'min'       => 10,
                'max'       => 500,
                'default'   => 100,
                'condition' => array(
                    'show_description' => 'yes',
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

        $this->add_control(
            'link_entire_card',
            array(
                'label'        => __( 'Link Entire Card', 'glowbook' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'glowbook' ),
                'label_off'    => __( 'No', 'glowbook' ),
                'return_value' => 'yes',
                'default'      => 'no',
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
            )
        );

        $this->end_controls_section();

        // Style Section - Card
        $this->start_controls_section(
            'style_card',
            array(
                'label' => __( 'Card', 'glowbook' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
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
                    '{{WRAPPER}} .sodek-gb-service-card-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} 0 0;',
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

        $this->add_control(
            'card_hover_animation',
            array(
                'label' => __( 'Hover Animation', 'glowbook' ),
                'type'  => \Elementor\Controls_Manager::HOVER_ANIMATION,
            )
        );

        $this->end_controls_section();

        // Style Section - Image
        $this->start_controls_section(
            'style_image',
            array(
                'label'     => __( 'Image', 'glowbook' ),
                'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_image' => 'yes',
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
                    'size' => 250,
                    'unit' => 'px',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-service-card-image' => 'height: {{SIZE}}{{UNIT}};',
                ),
                'condition'  => array(
                    'layout' => 'vertical',
                ),
            )
        );

        $this->add_responsive_control(
            'image_width',
            array(
                'label'      => __( 'Width', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array( 'px', '%' ),
                'range'      => array(
                    'px' => array(
                        'min' => 100,
                        'max' => 500,
                    ),
                    '%'  => array(
                        'min' => 20,
                        'max' => 60,
                    ),
                ),
                'default'    => array(
                    'size' => 40,
                    'unit' => '%',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-service-card--horizontal .sodek-gb-service-card-image' => 'width: {{SIZE}}{{UNIT}}; flex: 0 0 {{SIZE}}{{UNIT}};',
                ),
                'condition'  => array(
                    'layout' => 'horizontal',
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Css_Filter::get_type(),
            array(
                'name'     => 'image_filters',
                'selector' => '{{WRAPPER}} .sodek-gb-service-card-image img',
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
                'selector' => '{{WRAPPER}} .sodek-gb-service-card-title',
            )
        );

        $this->add_control(
            'title_color',
            array(
                'label'     => __( 'Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-service-card-title' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_responsive_control(
            'title_spacing',
            array(
                'label'      => __( 'Spacing', 'glowbook' ),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array( 'px' ),
                'range'      => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 50,
                    ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .sodek-gb-service-card-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_control(
            'meta_heading',
            array(
                'label'     => __( 'Price & Duration', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'meta_typography',
                'selector' => '{{WRAPPER}} .sodek-gb-service-card-meta',
            )
        );

        $this->add_control(
            'price_color',
            array(
                'label'     => __( 'Price Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-service-card-price' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'duration_color',
            array(
                'label'     => __( 'Duration Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-service-card-duration' => 'color: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'description_heading',
            array(
                'label'     => __( 'Description', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name'     => 'description_typography',
                'selector' => '{{WRAPPER}} .sodek-gb-service-card-description',
            )
        );

        $this->add_control(
            'description_color',
            array(
                'label'     => __( 'Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-service-card-description' => 'color: {{VALUE}};',
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
                'selector' => '{{WRAPPER}} .sodek-gb-service-card-button',
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
                ),
            )
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name'     => 'button_border',
                'selector' => '{{WRAPPER}} .sodek-gb-service-card-button',
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
                ),
            )
        );

        $this->add_control(
            'button_hover_border_color',
            array(
                'label'     => __( 'Border Color', 'glowbook' ),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .sodek-gb-service-card-button:hover' => 'border-color: {{VALUE}};',
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
                ),
            )
        );

        $this->add_control(
            'button_full_width',
            array(
                'label'        => __( 'Full Width', 'glowbook' ),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'glowbook' ),
                'label_off'    => __( 'No', 'glowbook' ),
                'return_value' => 'yes',
                'selectors'    => array(
                    '{{WRAPPER}} .sodek-gb-service-card-button' => 'width: 100%; text-align: center;',
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

        if ( ! $service_id ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<p>' . esc_html__( 'Please select a service.', 'glowbook' ) . '</p>';
            }
            return;
        }

        $service = get_post( $service_id );

        if ( ! $service || 'sodek_gb_service' !== $service->post_type ) {
            if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
                echo '<p>' . esc_html__( 'Service not found.', 'glowbook' ) . '</p>';
            }
            return;
        }

        $layout           = sanitize_text_field( $settings['layout'] );
        $show_image       = 'yes' === $settings['show_image'];
        $show_price       = 'yes' === $settings['show_price'];
        $show_duration    = 'yes' === $settings['show_duration'];
        $show_description = 'yes' === $settings['show_description'];
        $show_button      = 'yes' === $settings['show_book_button'];
        $link_card        = 'yes' === $settings['link_entire_card'];
        $button_text      = ! empty( $settings['button_text'] ) ? $settings['button_text'] : __( 'Book Now', 'glowbook' );
        $desc_length      = absint( $settings['description_length'] );
        $hover_animation  = ! empty( $settings['card_hover_animation'] ) ? 'elementor-animation-' . $settings['card_hover_animation'] : '';

        $price       = get_post_meta( $service_id, '_sodek_gb_price', true );
        $duration    = get_post_meta( $service_id, '_sodek_gb_duration', true );
        $description = wp_trim_words( $service->post_content, $desc_length, '...' );

        // Get booking URL - use configured page or fallback to service single page
        $booking_page = ! empty( $settings['booking_page_url']['url'] ) ? $settings['booking_page_url']['url'] : '';
        if ( $booking_page ) {
            $booking_url = add_query_arg( 'service', $service_id, $booking_page );
        } else {
            $booking_url = get_permalink( $service_id );
        }

        $card_classes = array(
            'sodek-gb-service-card',
            'sodek-gb-service-card--' . $layout,
        );

        if ( $hover_animation ) {
            $card_classes[] = $hover_animation;
        }

        ?>
        <div class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>">
            <?php if ( $link_card ) : ?>
                <a href="<?php echo esc_url( $booking_url ); ?>" class="sodek-gb-service-card-link"></a>
            <?php endif; ?>

            <?php if ( $show_image && has_post_thumbnail( $service_id ) ) : ?>
                <div class="sodek-gb-service-card-image">
                    <?php echo get_the_post_thumbnail( $service_id, 'medium_large' ); ?>
                </div>
            <?php endif; ?>

            <div class="sodek-gb-service-card-content">
                <h3 class="sodek-gb-service-card-title"><?php echo esc_html( $service->post_title ); ?></h3>

                <?php if ( $show_price || $show_duration ) : ?>
                    <div class="sodek-gb-service-card-meta">
                        <?php if ( $show_price && $price ) : ?>
                            <span class="sodek-gb-service-card-price">
                                <?php echo wc_price( $price ); ?>
                            </span>
                        <?php endif; ?>

                        <?php if ( $show_duration && $duration ) : ?>
                            <span class="sodek-gb-service-card-duration">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
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

                <?php if ( $show_description && $description ) : ?>
                    <div class="sodek-gb-service-card-description">
                        <?php echo esc_html( $description ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( $show_button && ! $link_card ) : ?>
                    <a href="<?php echo esc_url( $booking_url ); ?>" class="sodek-gb-service-card-button">
                        <?php echo esc_html( $button_text ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render widget output in the editor.
     */
    protected function content_template() {
        ?>
        <div class="sodek-gb-elementor-placeholder">
            <div class="sodek-gb-elementor-placeholder-icon">
                <i class="eicon-info-box"></i>
            </div>
            <div class="sodek-gb-elementor-placeholder-title">
                <?php esc_html_e( 'GlowBook Service Card', 'glowbook' ); ?>
            </div>
            <div class="sodek-gb-elementor-placeholder-desc">
                <# if ( settings.service_id ) { #>
                    <?php esc_html_e( 'Displaying selected service', 'glowbook' ); ?>
                    <br>
                    <small><?php esc_html_e( 'Layout:', 'glowbook' ); ?> {{ settings.layout }}</small>
                <# } else { #>
                    <?php esc_html_e( 'Please select a service', 'glowbook' ); ?>
                <# } #>
            </div>
        </div>
        <?php
    }
}
