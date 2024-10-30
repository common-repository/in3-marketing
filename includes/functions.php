<?php
/**
 * Output properly formatted numeric value
 *
 */
function in3_formatted( $numeric = 0 ) {
    if ( function_exists( 'get_woocommerce_price_format' ) ) {

        $args = apply_filters(
            'wc_price_args',
            wp_parse_args(
                [],
                array(
                    'ex_tax_label'       => false,
                    'currency'           => '',
                    'decimal_separator'  => wc_get_price_decimal_separator(),
                    'thousand_separator' => wc_get_price_thousand_separator(),
                    'decimals'           => wc_get_price_decimals(),
                    'price_format'       => get_woocommerce_price_format(),
                )
            )
        );

        $bedrag = (float)$numeric / 3;

        return apply_filters(
            'formatted_woocommerce_price',
            number_format( $bedrag, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] ),
            $bedrag,
            $args['decimals'],
            $args['decimal_separator'],
            $args['thousand_separator']
        );

    } else {
        return '0.00';
    }
}

/**
 * Check displaying condition
 *
 */
function in3_should_display( $price, $settings ) {
    return $price >= $settings['min_amount'] && $price <= $settings['max_amount'];
}

/**
 * Check whether widget must be displayed on the product page or not.
 * And actually display it if needed.
 *
 */
function in3_display_on_single_product() {
    global $product;
    $in3_settings   = get_option( 'in3_settings' );
    $price          = $product->get_price();
    $should_display = in3_should_display( $price, $in3_settings );

    if ( 'variable' !== $product->get_type() && !$should_display ) {
        return;
    }

    $bedrag_formatted = in3_formatted( $price );

    $data = [
        'href'             => 'https://www.payin3.nl/nl/?utm_source=Plug-in&utm_medium=WooCommerce&utm_campaign=Meer_info',
        'appearance'       => 'branded',
        'theme'            => $in3_settings['theme'],
        'tooltip_desc'     => $in3_settings['tooltip_desc'],
        'display_as_text'  => In3()->widget_get_display_text_tmpl( 'opt1' ),
        'bedrag_formatted' => get_woocommerce_currency_symbol() . $bedrag_formatted
    ];

    if ( empty( $in3_settings['tooltip_desc'] ) ) {
        $data['tooltip_desc'] = __( 'Betaal de eerste termijn direct via iDEAL. De tweede en derde termijn betaal je binnen 30 en 60 dagen. Zonder rente, zonder BKR-registratie! Klik voor meer informatie', 'in3' );
    }

    if ( 'variable' === $product->get_type() ) {
        in3_widget_tmpl_js( $data );
    } else {
        in3_widget_tmpl( $data );
    }
}

/**
 * Main JS template for in3 widget. Take price/total (for cart/checkout) and output
 * the final text.
 *
 */
function in3_widget_tmpl_js( $data ) {
    $final_text = $data['display_as_text'];
    $final_text = wp_kses( $final_text, array(
        'em'     => array(),
        'strong' => array(),
    ) );
    $tmpl       = '';

    $svg_in3 = '<svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 107.34 47.74">
  <defs>
    <style>
      .in3_cls-1 {
        fill: #c06;
      }

      .in3_cls-1, .in3_cls-2, .in3_cls-3 {
        stroke-width: 0px;
      }

      .in3_cls-3 {
        fill: #fff;
      }
    </style>
  </defs>
  <g>
    <path class="in3_cls-2" d="m90.32,7.72h-41.66c3.7,4.18,5.43,9.78,5.43,16.1s-1.74,12.01-5.46,16.21h41.68c11.87,0,17.02-6.71,17.02-16.19s-5.15-16.12-17.02-16.12Z"/>
    <path class="in3_cls-3" d="m0,3.54v40.66c0,1.95,1.59,3.54,3.54,3.54h24.3c18.37,0,26.33-10.28,26.33-23.92S46.2,0,27.83,0H3.54C1.59,0,0,1.59,0,3.54Z"/>
    <path class="in3_cls-1" d="m16.26,9.98v30.05h13.08c11.87,0,17.02-6.71,17.02-16.19S41.21,7.72,29.34,7.72h-10.81c-1.26,0-2.27,1.03-2.27,2.26Z"/>
    <path class="in3_cls-2" d="m27.83,44.5H6.71c-1.88,0-3.4-1.52-3.4-3.4V6.65c0-1.88,1.52-3.4,3.4-3.4h21.13c20.05,0,23.04,12.9,23.04,20.58,0,13.32-8.19,20.67-23.04,20.67ZM6.71,4.39c-1.26,0-2.26,1.01-2.26,2.26v34.45c0,1.26,1.01,2.27,2.26,2.27h21.13c14.12,0,21.91-6.94,21.91-19.54,0-16.92-13.73-19.45-21.91-19.45H6.71Z"/>
  </g>
  <g>
    <path class="in3_cls-3" d="m20.86,19.78c.46,0,.88.07,1.29.21.41.14.74.37,1.04.65.28.3.51.67.69,1.1.16.44.25.96.25,1.56,0,.53-.07,1.01-.19,1.45-.14.44-.34.83-.6,1.15s-.6.57-1.01.76c-.41.18-.88.28-1.43.28h-3.1v-7.18h3.06v.02Zm-.11,5.86c.23,0,.44-.04.67-.11.21-.07.41-.19.57-.37.16-.18.3-.39.41-.67.11-.28.16-.6.16-1.01,0-.35-.04-.69-.11-.97-.07-.28-.19-.55-.35-.74s-.37-.37-.64-.48-.58-.16-.97-.16h-1.13v4.53h1.4v-.02Z"/>
    <path class="in3_cls-3" d="m30.52,19.78v1.33h-3.79v1.54h3.49v1.22h-3.49v1.75h3.88v1.33h-5.45v-7.18h5.36v.02Z"/>
    <path class="in3_cls-3" d="m35.94,19.78l2.69,7.18h-1.65l-.55-1.59h-2.69l-.57,1.59h-1.59l2.71-7.18h1.65Zm.09,4.41l-.9-2.64h-.02l-.94,2.64h1.86Z"/>
    <path class="in3_cls-3" d="m41.19,19.78v5.86h3.5v1.33h-5.08v-7.18h1.57Z"/>
  </g>
  <circle class="in3_cls-2" cx="10.35" cy="23.38" r="3.31"/>
  <path class="in3_cls-2" d="m12.85,40.03h0c-2.78,0-5.01-2.25-5.01-5.01v-3.91c0-1.38,1.11-2.51,2.51-2.51h0c1.38,0,2.51,1.11,2.51,2.51v8.92h-.02Z"/>
  <g>
    <path class="in3_cls-3" d="m63.3,15.7c-.8,0-1.42-.19-1.88-.57-.45-.38-.68-.93-.68-1.64,0-.64.23-1.16.69-1.57.46-.41,1.08-.61,1.86-.61s1.42.19,1.86.57.67.92.67,1.61-.23,1.19-.68,1.6c-.45.41-1.07.61-1.85.61Zm-2.13,16.92v-14.13h4.26v14.13h-4.26Z"/>
    <path class="in3_cls-3" d="m69.41,32.61v-14.13h4.04l.13,2.87-.85.32c.2-.64.55-1.22,1.05-1.74s1.11-.94,1.82-1.26c.71-.32,1.45-.48,2.23-.48,1.06,0,1.96.22,2.69.65.73.43,1.28,1.08,1.65,1.94.37.86.56,1.91.56,3.15v8.67h-4.28v-8.33c0-.57-.08-1.04-.24-1.41-.16-.37-.4-.65-.73-.84s-.73-.27-1.21-.25c-.37,0-.72.06-1.04.17-.32.12-.59.28-.82.51-.23.22-.41.47-.55.76s-.2.59-.2.93v8.46h-4.26Z"/>
    <path class="in3_cls-3" d="m98.01,24.11c-.32-.72-.76-1.34-1.33-1.88-.57-.53-1.22-.95-1.94-1.25-.49-.2-1.02-.31-1.56-.38l4.78-5.13-.82-1.65h-11.44v3.8l5.97.12-3.95,4.29.98,2.63c.35-.18.7-.32,1.04-.43s.66-.19.96-.25c.3-.06.59-.09.85-.09.59,0,1.09.1,1.5.29s.74.48.96.86.33.85.33,1.4c0,.51-.13.98-.39,1.38-.26.41-.59.72-1.01.94-.42.22-.87.33-1.37.33s-.97-.08-1.42-.25c-.45-.17-.89-.44-1.3-.81-.42-.37-.82-.87-1.21-1.49l-3.25,2.1c.92,1.54,1.95,2.63,3.07,3.26,1.13.63,2.44.94,3.95.94,1.31,0,2.5-.28,3.58-.85,1.07-.57,1.92-1.33,2.55-2.3.63-.97.94-2.07.94-3.31,0-.82-.16-1.58-.48-2.3Z"/>
  </g>
</svg>';
    $svg_i   = '<svg width="4" height="7" viewBox="0 0 4 7" xmlns="http://www.w3.org/2000/svg">
<path d="M2.05453 5.26976C1.69938 6.10467 2.138 6.29687 2.75339 6.14003L2.71083 6.51828C1.58154 7.17637 -0.00273865 6.99186 0.656833 5.39431C0.899057 4.80541 1.7894 3.68144 1.52917 3.03872C1.42279 2.77426 1.066 2.77426 0.719025 2.88497L0.758305 2.5344C1.34586 2.16691 2.11509 1.96549 2.66828 2.27762C3.65682 2.8373 2.22475 4.86691 2.05453 5.26976ZM2.70592 0.125C2.2673 0.125 1.91215 0.458656 1.91215 0.870729C1.91215 1.2828 2.2673 1.61646 2.70592 1.61646C3.14455 1.61646 3.4997 1.2828 3.4997 0.870729C3.4997 0.458656 3.14291 0.125 2.70592 0.125Z" fill="#274383"/>
</svg>';

    switch ( $data['appearance'] ) :
        case 'branded' :
            $tmpl .= '<div class="in3Widget withBg withLogo ' . esc_attr( $data['theme'] ) . '">
<span class="logo">' . $svg_in3 . '</span>
<p>' . $final_text . '</p><i class="info">' . $svg_i . '</i></div>';
            break;
        case 'branded_only' :
            $tmpl .= '<div class="in3Widget noBg withLogo ' . esc_attr( $data['theme'] ) . '">
<span class="logo">' . $svg_in3 . '</span>
<p>' . $final_text . '</p><i class="info">' . $svg_i . '</i></div>';
            break;
        case 'label' :
            $tmpl .= '<div class="in3Widget withBg ' . esc_attr( $data['theme'] ) . '"><p>' . $final_text . '</p><i class="info">' . $svg_i . '</i></div>';
            break;
        case 'textual' :
            $tmpl .= '<div class="in3Widget ' . esc_attr( $data['theme'] ) . '"><p>' . $final_text . '</p><i class="info">' . $svg_i . '</i></div>';
            break;
    endswitch;

    // end display description
    $tmpl .= '<a href="' . esc_url( $data['href'] ) . '" class="in3WidgetTooltip ' . esc_attr( $data['theme'] ) . '" target="_blank">' .
        esc_html( $data['tooltip_desc'] ) . '</a>';

    echo "<template id='in3_widget_tmpl'>$tmpl</template>";
}

/**
 * Main template for in3 widget. Take price/total (for cart/checkout) and output
 * the final text.
 *
 */
function in3_widget_tmpl( $data, $shoudReturn = false ) {
    $mustache   = new Mustache_Engine( array('entity_flags' => ENT_QUOTES) );
    $final_text = $mustache->render(
        $data['display_as_text'],
        array('bedrag' => $data['bedrag_formatted'])
    );
    $final_text = wp_kses( $final_text, array(
        'em'     => array(),
        'strong' => array(),
    ) );
    $tmpl       = '';

    $svg_in3 = '<svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 107.34 47.74">
  <defs>
    <style>
      .in3_cls-1 {
        fill: #c06;
      }

      .in3_cls-1, .in3_cls-2, .in3_cls-3 {
        stroke-width: 0px;
      }

      .in3_cls-3 {
        fill: #fff;
      }
    </style>
  </defs>
  <g>
    <path class="in3_cls-2" d="m90.32,7.72h-41.66c3.7,4.18,5.43,9.78,5.43,16.1s-1.74,12.01-5.46,16.21h41.68c11.87,0,17.02-6.71,17.02-16.19s-5.15-16.12-17.02-16.12Z"/>
    <path class="in3_cls-3" d="m0,3.54v40.66c0,1.95,1.59,3.54,3.54,3.54h24.3c18.37,0,26.33-10.28,26.33-23.92S46.2,0,27.83,0H3.54C1.59,0,0,1.59,0,3.54Z"/>
    <path class="in3_cls-1" d="m16.26,9.98v30.05h13.08c11.87,0,17.02-6.71,17.02-16.19S41.21,7.72,29.34,7.72h-10.81c-1.26,0-2.27,1.03-2.27,2.26Z"/>
    <path class="in3_cls-2" d="m27.83,44.5H6.71c-1.88,0-3.4-1.52-3.4-3.4V6.65c0-1.88,1.52-3.4,3.4-3.4h21.13c20.05,0,23.04,12.9,23.04,20.58,0,13.32-8.19,20.67-23.04,20.67ZM6.71,4.39c-1.26,0-2.26,1.01-2.26,2.26v34.45c0,1.26,1.01,2.27,2.26,2.27h21.13c14.12,0,21.91-6.94,21.91-19.54,0-16.92-13.73-19.45-21.91-19.45H6.71Z"/>
  </g>
  <g>
    <path class="in3_cls-3" d="m20.86,19.78c.46,0,.88.07,1.29.21.41.14.74.37,1.04.65.28.3.51.67.69,1.1.16.44.25.96.25,1.56,0,.53-.07,1.01-.19,1.45-.14.44-.34.83-.6,1.15s-.6.57-1.01.76c-.41.18-.88.28-1.43.28h-3.1v-7.18h3.06v.02Zm-.11,5.86c.23,0,.44-.04.67-.11.21-.07.41-.19.57-.37.16-.18.3-.39.41-.67.11-.28.16-.6.16-1.01,0-.35-.04-.69-.11-.97-.07-.28-.19-.55-.35-.74s-.37-.37-.64-.48-.58-.16-.97-.16h-1.13v4.53h1.4v-.02Z"/>
    <path class="in3_cls-3" d="m30.52,19.78v1.33h-3.79v1.54h3.49v1.22h-3.49v1.75h3.88v1.33h-5.45v-7.18h5.36v.02Z"/>
    <path class="in3_cls-3" d="m35.94,19.78l2.69,7.18h-1.65l-.55-1.59h-2.69l-.57,1.59h-1.59l2.71-7.18h1.65Zm.09,4.41l-.9-2.64h-.02l-.94,2.64h1.86Z"/>
    <path class="in3_cls-3" d="m41.19,19.78v5.86h3.5v1.33h-5.08v-7.18h1.57Z"/>
  </g>
  <circle class="in3_cls-2" cx="10.35" cy="23.38" r="3.31"/>
  <path class="in3_cls-2" d="m12.85,40.03h0c-2.78,0-5.01-2.25-5.01-5.01v-3.91c0-1.38,1.11-2.51,2.51-2.51h0c1.38,0,2.51,1.11,2.51,2.51v8.92h-.02Z"/>
  <g>
    <path class="in3_cls-3" d="m63.3,15.7c-.8,0-1.42-.19-1.88-.57-.45-.38-.68-.93-.68-1.64,0-.64.23-1.16.69-1.57.46-.41,1.08-.61,1.86-.61s1.42.19,1.86.57.67.92.67,1.61-.23,1.19-.68,1.6c-.45.41-1.07.61-1.85.61Zm-2.13,16.92v-14.13h4.26v14.13h-4.26Z"/>
    <path class="in3_cls-3" d="m69.41,32.61v-14.13h4.04l.13,2.87-.85.32c.2-.64.55-1.22,1.05-1.74s1.11-.94,1.82-1.26c.71-.32,1.45-.48,2.23-.48,1.06,0,1.96.22,2.69.65.73.43,1.28,1.08,1.65,1.94.37.86.56,1.91.56,3.15v8.67h-4.28v-8.33c0-.57-.08-1.04-.24-1.41-.16-.37-.4-.65-.73-.84s-.73-.27-1.21-.25c-.37,0-.72.06-1.04.17-.32.12-.59.28-.82.51-.23.22-.41.47-.55.76s-.2.59-.2.93v8.46h-4.26Z"/>
    <path class="in3_cls-3" d="m98.01,24.11c-.32-.72-.76-1.34-1.33-1.88-.57-.53-1.22-.95-1.94-1.25-.49-.2-1.02-.31-1.56-.38l4.78-5.13-.82-1.65h-11.44v3.8l5.97.12-3.95,4.29.98,2.63c.35-.18.7-.32,1.04-.43s.66-.19.96-.25c.3-.06.59-.09.85-.09.59,0,1.09.1,1.5.29s.74.48.96.86.33.85.33,1.4c0,.51-.13.98-.39,1.38-.26.41-.59.72-1.01.94-.42.22-.87.33-1.37.33s-.97-.08-1.42-.25c-.45-.17-.89-.44-1.3-.81-.42-.37-.82-.87-1.21-1.49l-3.25,2.1c.92,1.54,1.95,2.63,3.07,3.26,1.13.63,2.44.94,3.95.94,1.31,0,2.5-.28,3.58-.85,1.07-.57,1.92-1.33,2.55-2.3.63-.97.94-2.07.94-3.31,0-.82-.16-1.58-.48-2.3Z"/>
  </g>
</svg>';
    $svg_i   = '<svg width="4" height="7" viewBox="0 0 4 7" xmlns="http://www.w3.org/2000/svg">
<path d="M2.05453 5.26976C1.69938 6.10467 2.138 6.29687 2.75339 6.14003L2.71083 6.51828C1.58154 7.17637 -0.00273865 6.99186 0.656833 5.39431C0.899057 4.80541 1.7894 3.68144 1.52917 3.03872C1.42279 2.77426 1.066 2.77426 0.719025 2.88497L0.758305 2.5344C1.34586 2.16691 2.11509 1.96549 2.66828 2.27762C3.65682 2.8373 2.22475 4.86691 2.05453 5.26976ZM2.70592 0.125C2.2673 0.125 1.91215 0.458656 1.91215 0.870729C1.91215 1.2828 2.2673 1.61646 2.70592 1.61646C3.14455 1.61646 3.4997 1.2828 3.4997 0.870729C3.4997 0.458656 3.14291 0.125 2.70592 0.125Z" fill="#274383"/>
</svg>';

    switch ( $data['appearance'] ) :
        case 'branded' :
            $tmpl .= '<div class="in3Widget withBg withLogo ' . esc_attr( $data['theme'] ) . '">
<span class="logo">' . $svg_in3 . '</span>
<p>' . $final_text . '</p><i class="info">' . $svg_i . '</i></div>';
            break;
        case 'branded_only' :
            $tmpl .= '<div class="in3Widget noBg withLogo ' . esc_attr( $data['theme'] ) . '">
<span class="logo">' . $svg_in3 . '</span>
<p>' . $final_text . '</p><i class="info">' . $svg_i . '</i></div>';
            break;
        case 'label' :
            $tmpl .= '<div class="in3Widget withBg ' . esc_attr( $data['theme'] ) . '">
<p>' . $final_text . '</p><i class="info">' . $svg_i . '</i></div>';
            break;
        case 'textual' :
            $tmpl .= '<div class="in3Widget ' . esc_attr( $data['theme'] ) . '">
<p>' . $final_text . '</p><i class="info">' . $svg_i . '</i></div>';
            break;
    endswitch;

    $href = isset( $data['href'] ) ? $data['href'] : '';

    $tmpl .= '<a href="' . esc_url( $href ) . '" class="in3WidgetTooltip ' . esc_attr( $data['theme'] ) . '" target="_blank">' .
        esc_html( $data['tooltip_desc'] ) . '</a>';

    // end display description
    if ( $shoudReturn ) {
        return $tmpl;
    } else {
        echo $tmpl;
    }
}

/**
 * Check whether widget must be displayed in the cart
 *
 */
function in3_display_in_cart() {
    $in3_settings   = get_option( 'in3_settings' );
    $price          = WC()->cart->get_total( 'edit' );
    $should_display = in3_should_display( $price, $in3_settings );

    if ( !$should_display ) {
        return;
    }

    $bedrag_formatted = in3_formatted( $price );

    $data = [
        'href'             => 'https://www.payin3.nl/nl/?utm_source=Plug-in&utm_medium=WooCommerce&utm_campaign=Meer_info',
        'appearance'       => 'branded',
        'theme'            => $in3_settings['theme'],
        'tooltip_desc'     => $in3_settings['tooltip_desc'],
        'display_as_text'  => In3()->widget_get_display_text_tmpl( 'opt1' ),
        'bedrag_formatted' => get_woocommerce_currency_symbol() . $bedrag_formatted
    ];

    if ( empty( $in3_settings['tooltip_desc'] ) ) {
        $data['tooltip_desc'] = __( 'Betaal de eerste termijn direct via iDEAL. De tweede en derde termijn betaal je binnen 30 en 60 dagen. Zonder rente, zonder BKR-registratie! Klik voor meer informatie', 'in3' );
    }

    echo '<tr class="in3Widget__row"><td colspan="2">';
    in3_widget_tmpl( $data );
    echo '</td></tr>';
}

function in3_display_on_single_product_before_block( $block_content, $block ) {
    if ( $block['blockName'] === 'woocommerce/add-to-cart-form' ) {
        global $wp_query;
        $product_id = 0;
        $product    = null;

        if ( !empty( $wp_query->queried_object->ID ) ) {
            $product_id = $wp_query->queried_object->ID;
        }

        if ( !empty( $product_id ) ) {
            $product = wc_get_product( $product_id );
        }

        if ( !$product ) {
            return;
        }

        $in3_settings   = get_option( 'in3_settings' );
        $price          = $product->get_price();
        $should_display = in3_should_display( $price, $in3_settings );

        if ( 'variable' !== $product->get_type() && !$should_display ) {
            return;
        }

        $bedrag_formatted = in3_formatted( $price );

        $data = [
            'href'             => 'https://www.payin3.nl/nl/?utm_source=Plug-in&utm_medium=WooCommerce&utm_campaign=Meer_info',
            'appearance'       => 'branded',
            'theme'            => $in3_settings['theme'],
            'tooltip_desc'     => $in3_settings['tooltip_desc'],
            'display_as_text'  => In3()->widget_get_display_text_tmpl( 'opt1' ),
            'bedrag_formatted' => get_woocommerce_currency_symbol() . $bedrag_formatted
        ];

        if ( empty( $in3_settings['tooltip_desc'] ) ) {
            $data['tooltip_desc'] = __( 'Betaal de eerste termijn direct via iDEAL. De tweede en derde termijn betaal je binnen 30 en 60 dagen. Zonder rente, zonder BKR-registratie! Klik voor meer informatie', 'in3' );
        }

        $widget = in3_widget_tmpl( $data, true );

        $content = $widget;
        $content .= $block_content;
        return $content;
    }
    return $block_content;
}
