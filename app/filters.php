<?php

namespace App;

/**
 * Add <body> classes
 */
add_filter('body_class', function (array $classes) {
  /** Add page slug if it doesn't exist */
  if (is_single() || is_page() && !is_front_page()) {
    if (!in_array(basename(get_permalink()), $classes)) {
      $classes[] = basename(get_permalink());
    }
  }

  /** Add class if sidebar is active */
  if (display_sidebar()) {
    $classes[] = 'sidebar-primary';
  }

  /** Clean up class names for custom templates */
  $classes = array_map(function ($class) {
    return preg_replace(['/-blade(-php)?$/', '/^page-template-views/'], '', $class);
  }, $classes);

  return array_filter($classes);
});

/**
 * Add "… Continued" to the excerpt
 */
add_filter('excerpt_more', function () {
  return ' &hellip; <a href="' . get_permalink() . '">' . __('Continued', 'sage') . '</a>';
});

/**
 * Template Hierarchy should search for .blade.php files
 */
collect([
  'index', '404', 'archive', 'author', 'category', 'tag', 'taxonomy', 'date', 'home',
  'frontpage', 'page', 'paged', 'search', 'single', 'singular', 'attachment', 'embed',
])->map(function ($type) {
  add_filter("{$type}_template_hierarchy", __NAMESPACE__ . '\\filter_templates');
});

/**
 * Render page using Blade
 */
add_filter('template_include', function ($template) {
  collect(['get_header', 'wp_head'])->each(function ($tag) {
    ob_start();
    do_action($tag);
    $output = ob_get_clean();
    remove_all_actions($tag);
    add_action($tag, function () use ($output) {
      echo $output;
    });
  });
  $data = collect(get_body_class())->reduce(function ($data, $class) use ($template) {
    return apply_filters("sage/template/{$class}/data", $data, $template);
  }, []);
  if ($template) {
    echo template($template, $data);
    return get_stylesheet_directory() . '/index.php';
  }
  return $template;
}, PHP_INT_MAX);

/**
 * Render comments.blade.php
 */
add_filter('comments_template', function ($comments_template) {
  $comments_template = str_replace(
    [get_stylesheet_directory(), get_template_directory()],
    '',
    $comments_template
  );

  $data = collect(get_body_class())->reduce(function ($data, $class) use ($comments_template) {
    return apply_filters("sage/template/{$class}/data", $data, $comments_template);
  }, []);

  $theme_template = locate_template(["views/{$comments_template}", $comments_template]);

  if ($theme_template) {
    echo template($theme_template, $data);
    return get_stylesheet_directory() . '/index.php';
  }

  return $comments_template;
}, 100);

/// Custom stuff

/**
 * Use Lozad (lazy loading) for attachments/featured images
 */
add_filter('wp_get_attachment_image_attributes', function ($attr, $attachment) {
  // Bail on admin
  if (is_admin()) {
    return $attr;
  }

  //Only run lazy load in first laod
  if (isset($_SERVER['HTTP_X_BARBA'])) {
    return $attr;
  }

  //TODO
  return $attr;
  //if(isset($attr['data-no-lazyload'])) return;

  $attr['data-src'] = $attr['src'];
  $attr['data-srcset'] = $attr['srcset'];
  $attr['class'] .= ' lozad';
  unset($attr['src']);
  unset($attr['srcset']);

  return $attr;
}, 10, 2);

// =================================================
//
// Store chooser
//
// =================================================

/**
 * Get store chooser data from cookie
 */
function get_sc_data() {
  if (class_exists('\WC_OrderByLocation')) {
    if (isset($_COOKIE[\WC_OrderByLocation::$location_var_name])) {
      $cookie_data_raw = $_COOKIE[\WC_OrderByLocation::$location_var_name];
      try {
        $cookie_data = \json_decode(stripslashes($cookie_data_raw), true);
        return $cookie_data;

      } catch (Exception $e) {

      }
    }
  }
  return null;
}

/**
 * Helpers for store chooser
 */
function sc_method_is($method) {
  $sc_data = get_sc_data();
  return (isset($sc_data['method']) && strcasecmp($sc_data['method'], $method) === 0);
}

function sc_location_is($location) {
  $sc_data = get_sc_data();
  return (isset($sc_data['location']) && strcasecmp($sc_data['location'], $location) === 0);
}

function sc_postcode_is($test) {
  $sc_data = get_sc_data();
  return (isset($sc_data['postcode']) && strcasecmp($sc_data['postcode'], $test) === 0);
}

function sc_suburb_is($test) {
  $sc_data = get_sc_data();
  return (isset($sc_data['suburb']) && strcasecmp($sc_data['suburb'], $test) === 0);
}

/**
 * Inject the location from our own cookie
 */
add_filter('wc_obl/location', function ($location) {
  $sc_data = get_sc_data();
  if ($sc_data && isset($sc_data['location'])) {
    return $sc_data['location'];
  }

  return $location;
}, 10, 1);

/**
 * For a given postcode, get the location term slug
 * Defaults to west-hobart
 * Checks list of 'devonport' postcodes from ACF
 */
function postcode_to_location($postcode) {
  $default_location = 'west-hobart';
  $test_location = 'devonport';
  $postcodes = get_field('postcodes_devonport', 'options');
  /*if (empty($postcodes)) {
  return $default_location;
  }*/

  $return_location = $default_location;

  foreach (explode("\n", $postcodes) as $testPostcode) {
    //if (strcasecmp($postcode, $testPostcode) === 0) {
    if (trim($postcode) == trim($testPostcode)) {
      //return $test_location;
      $return_location = $test_location;
      break;
    }
  }
  return $return_location;
}

/**
 * Ajax postcode search
 */
add_action('wp_ajax_postcode_search', __NAMESPACE__ . '\\ajax_postcode_search');
add_action('wp_ajax_nopriv_postcode_search', __NAMESPACE__ . '\\ajax_postcode_search');
function ajax_postcode_search() {
  $q = isset($_POST['q']) ? $_POST['q'] : null;
  if (!$q) {
    wp_send_json_error(array('message' => 'Missing query'), 400);
    wp_die();
  }
  $resp = wp_remote_get('https://auspost.com.au/api/postcode/search.txt?key=63fa7c3657ea97f3809aacaa42142bae&q=' . $q . '&limit=10', array(

  ));
  $rawSuburbs = explode("\n", trim($resp['body']));
  $suburbs = [];
  foreach ($rawSuburbs as $rawSuburb) {
    $parts = explode("|", $rawSuburb);
    if (count($parts) < 4) {
      continue;
    }

    $suburbs[] = [
      'name' => $parts[2],
      'suburb' => $parts[2],
      'postcode' => $parts[1],
      'state' => $parts[3],
      'location' => postcode_to_location($parts[1]),
    ];
  }

  wp_send_json($suburbs);
  //wp_send_json(array(['name' => 'Hobart'], ['name' => 'West Hobart'], ['name' => 'Kingston']));
  wp_die();
}

/**
 * Remove scripts/styles from order-by-location
 */
add_filter('wc_obl/enqueue_frontend', '__return_false');

// =================================================
//
// Woocommerce specific customisations
//
// =================================================

/**
 * Add "Quantity" label to qty input
 */
add_action('woocommerce_before_quantity_input_field', function () {
  if (is_product()) {

    echo '<label class="qty-label">' . esc_html__('Quantity', 'woocommerce') . '</label>';
  }
}, 10);

/**
 * Hide product category count
 */
add_filter('woocommerce_subcategory_count_html', function () {
  return null;
});

/**
 * Remove add to cart from loop
 */
remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);

/**
 * Remove price from loop
 */
// @hooked woocommerce_template_loop_rating - 5
// @hooked woocommerce_template_loop_price - 10
remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating');
remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price');

/**
 * Show subcategores and products separately
 * Hide categories from loop when set to both (keep reading, see next)
 */
remove_filter('woocommerce_product_loop_start', 'woocommerce_maybe_show_product_subcategories');

/**
 * Add subcategories before products
 */
add_action('woocommerce_before_shop_loop', function () {
  $parent_id = is_product_category() ? get_queried_object_id() : 0;
  $product_categories = woocommerce_get_product_subcategories($parent_id);
  if (empty($product_categories)) {
    return;
  }

  echo '<div class="product-grid category-grid">';
  woocommerce_product_loop_start();
  echo woocommerce_maybe_show_product_subcategories();
  woocommerce_product_loop_end();
  echo '</div>';

  //For some reason when total is 1 it's not an integer
  $GLOBALS['woocommerce_loop']['total'] = +($GLOBALS['woocommerce_loop']['total']);
}, 5);

/**
 * Hide empty subcategories
 */
add_filter('woocommerce_product_subcategories_args', function ($args) {
  $args['hide_empty'] = 1;
  return $args;
}, 10, 1);
add_filter('woocommerce_product_subcategories_hide_empty', function () {
  return true;
}, 10);

/**
 * Wrap the loop
 */
add_action('woocommerce_before_shop_loop', function () {
  echo '<div class="product-grid">';
}, 99);
add_action('woocommerce_after_shop_loop', function () {
  echo '</div>';
}, 1);

/**
 * Wrap the result count + filter
 * Relevant hooks:
 * 'woocommerce_before_shop_loop' -> 'woocommerce_result_count', 20
 * 'woocommerce_before_shop_loop' -> 'woocommerce_catalog_ordering', 30
 */
add_action('woocommerce_before_shop_loop', function () {
  echo '<div class="result-filter_wrap flex justify-between py-3">';
}, 19);
add_action('woocommerce_before_shop_loop', function () {
  echo '</div>';
}, 31);
//TODO maybe this as well?
//add_action('woocommerce_no_products_found', 'wc_no_products_found');

//Remove
/**
 * Remove breadcrumbs from hook on category (they're in template manually - see archive-product)
 * Remove breadcrumns from hook on single product (they're in template manually - see single-product)
 */
add_action('woocommerce_before_main_content', function () {
  if (is_archive() || is_product()) {
    remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0);
  }
}, 0);

/**
 * Wrap product gallery and summary in grid container
 * see content-single-product for hook locations
 */
add_action('woocommerce_before_single_product_summary', function () {
  echo '<div class="product-wrap">';
}, 1);
add_action('woocommerce_after_single_product_summary', function () {
  echo '</div>';
}, 1);

/**
 * Wrap everything after summary in container
 */
add_action('woocommerce_after_single_product_summary', function () {
  echo '<div class="container after-summary-wrap mt-8">';
}, 5);
add_action('woocommerce_after_single_product_summary', function () {
  echo '</div>';
}, 999);

/**
 * Make gallery thumbs 'large' rather than full size for carousel
 * see wc_get_gallery_image_html()
 */
add_filter(
  'woocommerce_gallery_image_html_attachment_image_params',
  function ($params, $attachment_id, $image_size, $main_image) {
    $image_size = apply_filters('woocommerce_gallery_image_size', 'wc_single_large');
    $large_src = wp_get_attachment_image_src($attachment_id, $image_size);
    $params['data-large_image'] = esc_url($large_src[0]);
    $params['data-large_image_width'] = esc_attr($large_src[1]);
    $params['data-large_image_height'] = esc_attr($large_src[2]);
    return $params;
  }, 10, 4
);

/**
 * Make gallery thumbs actual AR, not square?
 */
add_filter('woocommerce_gallery_image_size', function ($size) {return 'wc_single_large';});
add_filter('woocommerce_gallery_thumbnail_size', function ($size) {
  //return 'medium';
  return 'wc_single_lqip';
}, 10, 1);

/**
 * Move product price after summary
 */
//* @hooked woocommerce_template_single_price - 10
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
add_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 25);

/**
 * Remove variable product price range
 */
add_filter('woocommerce_variable_sale_price_html', __NAMESPACE__ . '\\variable_product_price_html', 10, 2);
add_filter('woocommerce_variable_price_html', __NAMESPACE__ . '\\variable_product_price_html', 10, 2);

function variable_product_price_html($v_price, $v_product) {

// Product Price
  $prod_prices = array($v_product->get_variation_price('min', true), $v_product->get_variation_price('max', true));
  $prod_price = $prod_prices[0] !== $prod_prices[1] ? sprintf(__('From: %1$s', 'woocommerce'), wc_price($prod_prices[0])) : wc_price($prod_prices[0]);

// Regular Price
  $regular_prices = array($v_product->get_variation_regular_price('min', true), $v_product->get_variation_regular_price('max', true));
  sort($regular_prices);
  $regular_price = $regular_prices[0] !== $regular_prices[1] ? sprintf(__('From: %1$s', 'woocommerce'), wc_price($regular_prices[0])) : wc_price($regular_prices[0]);

  if ($prod_price !== $regular_price) {
    $prod_price = '<del>' . $regular_price . $v_product->get_price_suffix() . '</del> <ins>' .
    $prod_price . $v_product->get_price_suffix() . '</ins>';
  }
  return $prod_price;
}

/**
 * Hide SKU
 */
add_filter('wc_product_sku_enabled', '__return_false');

/**
 * Wrap main image on single product to match thumbnails
 */
add_filter('woocommerce_single_product_image_thumbnail_html', function ($html, $post_thumbnail_id) {
  $html = '<div class="woocommerce-product-gallery__slide">' . $html . '</div>';
  return $html;
}, 10, 2);

/**
 * Disable swatches inline style
 */
add_filter('rtwpvs_disable_inline_style', '__return_true');

/**
 * Replace the swatch option DIV with a goddamn BUTTON
 */
add_filter('rtwpvs_variable_term', function ($data, $type, $options, $args, $saved_attribute) {
  $product = $args['product'];
  $attribute = $args['attribute'];
  $data = '';

  if (!empty($options)) {
    if ($product && taxonomy_exists($attribute)) {
      $terms = wc_get_product_terms($product->get_id(), $attribute, array('fields' => 'all'));
      $name = uniqid(wc_variation_attribute_name($attribute));
      foreach ($terms as $term) {
        if (in_array($term->slug, $options)) {
          $selected_class = (sanitize_title($args['selected']) == $term->slug) ? 'selected' : '';
          $tooltip = trim(apply_filters('rtwpvs_variable_item_tooltip', $term->name, $term, $args));

          $tooltip_html_attr = !empty($tooltip) ? sprintf('data-rtwpvs-tooltip="%s"', esc_attr($tooltip)) : '';

          if (wp_is_mobile()) {
            $tooltip_html_attr .= !empty($tooltip) ? ' tabindex="2"' : '';
          }

          $data .= sprintf('<button type="button" %1$s class="rtwpvs-term rtwpvs-%2$s-term %2$s-variable-term-%3$s %4$s" data-term="%3$s">', $tooltip_html_attr, esc_attr($type), esc_attr($term->slug), esc_attr($selected_class));

          switch ($type):
        case 'color':
          $color = sanitize_hex_color(get_term_meta($term->term_id, 'product_attribute_color', true));
          $data .= sprintf('<span class="rtwpvs-term-span rtwpvs-term-span-%s" style="background-color:%s;"></span>', esc_attr($type), esc_attr($color));
          break;

        case 'image':
          $attachment_id = absint(get_term_meta($term->term_id, 'product_attribute_image', true));
          $image_size = rtwpvs()->get_option('attribute_image_size');
          $image_url = wp_get_attachment_image_url($attachment_id, apply_filters('rtwpvs_product_attribute_image_size', $image_size));
          $data .= sprintf('<img alt="%s" src="%s" />', esc_attr($term->name), esc_url($image_url));
          break;

        case 'button':
          $data .= sprintf('<span class="rtwpvs-term-span rtwpvs-term-span-%s">%s</span>', esc_attr($type), esc_html($term->name));
          break;

        case 'radio':
          $id = uniqid($term->slug);
          $data .= sprintf('<input name="%1$s" id="%2$s" class="rtwpvs-radio-button-term" %3$s  type="radio" value="%4$s" data-term="%4$s" /><label for="%2$s">%5$s</label>', $name, $id, checked(sanitize_title($args['selected']), $term->slug, false), esc_attr($term->slug), esc_html($term->name));
          break;

        default:
          $data .= apply_filters('rtwpvs_variable_default_item_content', '', $term, $args, $saved_attribute);
          break;
          endswitch;
          $data .= '</button>';
        }
      }
    }
  }
  return $data;
}, 10, 5);

/**
 * MAke clear variation a button instead of a link
 * Actuall remove this
 */
add_filter('woocommerce_reset_variations_link', function ($html) {

  //Original HTML
  //'<a class="reset_variations" href="#">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>'
  //return '<button class="reset_variations btn-sm mt-4 btn" >' . esc_html__('Clear selection', 'woocommerce') . '</button>';

  //Remove clear selection
  return '';
});

/**
 * Remove meta from single product
 */
//* @hooked woocommerce_template_single_meta - 40
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);

/**
 * Remove tabs, but keep description
 * wrap description
 */
remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
add_action('woocommerce_after_single_product_summary', function () {
  if (empty(get_the_content())) {
    return;
  }

  echo '<div class="bg-gray-100 px-4 py-6 product-description">';
  wc_get_template('single-product/tabs/description.php');
  echo '</div>';
}, 10);

/**
 * Set meta and note on order
 */
add_action('woocommerce_new_order', function ($order_id, $order) {
  $sc_data = get_sc_data();
  if (!empty($sc_data)) {
    if (isset($sc_data['method'])) {
      update_post_meta($order_id, '_order_sc_method', $sc_data['method']);
      $note = __(sprintf("Method: %s", $sc_data['method']));
      // Add the note
      $order->add_order_note($note);
    }

    if (!empty($sc_data['suburb'])) {
      update_post_meta($order_id, '_order_sc_suburb', $sc_data['suburb']);
    }
    if (!empty($sc_data['postcode'])) {
      update_post_meta($order_id, '_order_sc_postcode', $sc_data['postcode']);
    }

  }
}, 10, 2);

/**
 * Hide addons if it's a hamper and devonport
 */

add_action('woocommerce_before_add_to_cart_button', function () {
  global $post;
  if (is_product() && has_term('hampers', 'product_cat', $post) && sc_location_is('devonport')) {
    remove_action('woocommerce_before_add_to_cart_button', array($GLOBALS['Product_Addon_Display'], 'display'), 10);
  }

}, 9);

//=================
//
// Checkout
//
// Here be monsters
//
//=================

/**
 * If pickup, tell woocommerce
 */
add_filter('woocommerce_cart_needs_shipping_address', function ($needs_shipping_address) {
  $sc_data = get_sc_data();
  if ($sc_data && isset($sc_data['method']) && $sc_data['method'] === 'pickup') {
    return false;
  }

  return $needs_shipping_address;
});
add_filter('woocommerce_cart_needs_shipping', function ($needs_shipping) {
  $sc_data = get_sc_data();
  if ($sc_data && isset($sc_data['method']) && $sc_data['method'] === 'pickup') {
    return false;
  }
  return $needs_shipping;
});

/**
 * Turn off the checkout-wc form
 */
//add_filter('cfw_replace_form', '__return_true');

/**
 * Inject our own form
 * (This is largely copied from the original anyway: checkout-wc/templates/copify/content.php)
 * The only change here is changing all the tab ids
 * this is to prevent checkout-wc from validating tabs before change
 */
add_action('cfw_checkout_form', function ($checkout) {
  $woo = \WooCommerce::instance(); // WooCommerce Instance
  $checkout = WC()->checkout(); // Checkout Object
  $cart = WC()->cart; // Cart Object
  $customer = WC()->customer; // Customer Object

  ?>
<form <?php cfw_form_attributes();?>>
			<!-- Order Review -->
            <?php do_action('cfw_checkout_before_order_review_container');?>

            <div id="order_review" class="col-lg-7 cfw-rp" role="main">
                <?php do_action('cfw_checkout_before_order_review');?>

                <!-- Customer Info Panel -->
                <div id="hsh-cfw-customer-info" class="cfw-panel" <?php cfw_customer_info_tab_style_attribute();?>>
                    <?php do_action('cfw_checkout_customer_info_tab');?>
                </div>

                <!-- Shipping Method Panel -->
                <div id="hsh-cfw-shipping-method" class="cfw-panel" <?php cfw_shipping_method_tab_style_attribute();?>>
                    <?php do_action('cfw_checkout_shipping_method_tab');?>
                </div>

                <!-- Payment Method Panel -->
                <div id="hsh-cfw-payment-method" class="cfw-panel">
                    <?php do_action('cfw_checkout_payment_method_tab');?>
                </div>

                <?php do_action('cfw_checkout_after_order_review');?>
            </div>

            <?php do_action('cfw_checkout_after_order_review_container');?>

            <!-- Cart Summary -->
            <div id="cfw-cart-summary" class="col-lg-5" role="complementary">
                <?php do_action('cfw_checkout_cart_summary');?>
            </div>

			<?php do_action('cfw_checkout_after_cart_summary_container');?>
		</form>
<?php
});

/**
 * Because we have renamed all the tabs, so too we change the breadcrumbs
 */
/*remove_action('cfw_checkout_before_order_review', 'cfw_breadcrumb_navigation', 10);
add_action('cfw_checkout_before_order_review', function () {
//copied from cwf_breadcrumb_navigation

$show_customer_info_tab = apply_filters('cfw_show_customer_information_tab', true);
$show_shipping_method_tab = WC()->cart->needs_shipping() && apply_filters('cfw_show_shipping_tab', true) === true;

do_action('cfw_before_breadcrumb_navigation');
?>
<ul id="cfw-breadcrumb" class="etabs">
<li>
<a href="<?php echo wc_get_cart_url(); ?>">
<?php echo apply_filters('cfw_breadcrumb_cart_label', cfw_esc_html__('Cart', 'woocommerce')); ?>
</a>
</li>
<?php if ($show_customer_info_tab): ?>
<li class="tab" id="default-tab">
<a href="#hsh-cfw-customer-info" class="cfw-small">
<?php echo apply_filters('cfw_breadcrumb_customer_info_label', esc_html__('Customer information', 'checkout-wc')); ?>
</a>
</li>
<?php endif;?>
<?php if ($show_shipping_method_tab): ?>
<li class="tab">
<a href="#hsh-cfw-shipping-method" class="cfw-small">
<?php echo apply_filters('cfw_breadcrumb_shipping_label', esc_html__('Shipping method', 'checkout-wc')); ?>
</a>
</li>
<?php endif;?>
<li class="tab" <?php echo (!$show_customer_info_tab && !$show_shipping_method_tab) ? 'id="default-tab"' : ''; ?>>
<a href="#hsh-cfw-payment-method" class="cfw-small">
<?php echo apply_filters('cfw_breadcrumb_payment_label', esc_html__('Payment method', 'checkout-wc')); ?>
</a>
</li>
</ul>
<?php
do_action('cfw_after_breadcrumb_navigation');
}, 10);*/

/**
 * And finally, change all the buttons
 */

/*add_filter('cfw_continue_to_shipping_button', function ($html) {
return str_replace('#cfw-shipping-method', '#hsh-cfw-shipping-method', $html);
});
add_filter('cfw_continue_to_payment_button', function ($html) {
return str_replace('#cfw-payment-method', '#hsh-cfw-payment-method', $html);
});
add_filter('cfw_return_to_customer_information_link', function ($html) {
return str_replace('#cfw-customer-info', '#hsh-cfw-customer-info', $html);
});
add_filter('cfw_return_to_shipping_method_link', function ($html) {
return str_replace('#cfw-shipping-method', '#hsh-cfw-shipping-method', $html);
});*/

/**
 * Add some scripts/styles
 * For some reason it doesn't work here: 'cfw_load_template_assets'
 */
add_action('cfw_wp_head', function () {
  wp_enqueue_style('sage/checkout.css', asset_path('styles/checkout.css'), false, null);
});
add_action('wp_footer', function () {
  if (!is_checkout()) {
    return;
  }

  if (class_exists('\WC_OrderByLocation')) {
    $data = array('cookie_name' => \WC_OrderByLocation::$location_var_name);
    echo "<script>\n/* <![CDATA[ */\n";
    echo 'var custom_checkout_data = ' . json_encode($data);
    echo "\n/* ]]> */\n</script>";
    wp_enqueue_script('sage/checkout.js', asset_path('scripts/checkout.js'), array(), null, false);
    wp_print_scripts('sage/checkout.js');
    //wp_localize_script('sage/checkout.js', 'custom_checkout_data', array('cookie_name' => \WC_OrderByLocation::$location_var_name));
  }
  //WARNING HAX
  // This forces checkout-wc not to validate shipping fields on customer info tab
  echo '<script>(function(w){
    try {
    w.cfwEventData.settings.needs_shipping_address = false;
    } catch(e) {}
  })(window);</script>';
}, 99, 4);

/**
 * Add deliver/pickup method/location/suburb before user information
 * @hooked cfw_checkout_customer_info_tab
 */
add_action('cfw_checkout_customer_info_tab', function () {
  $sc_data = get_sc_data();
  if (empty($sc_data)) {
    return;
  }

  echo '<div class="sc-data-display">';

  if ($sc_data['method'] === 'pickup') {
    $term = get_term_by('slug', $sc_data['location'], 'location');
    echo 'Pickup from <span class="location">' . $term->name . '</span>';
  } else {
    $suburb = implode(" ", array_map(function ($word) {
      return ucfirst(strtolower($word));
    }, explode(" ", $sc_data['suburb'])));
    echo 'Delivery to <span class="suburb">' . $suburb . '</span>';
  }

  echo '</div>';
}, 5);

/**
 * Remove cfw_customer_info_address from info tab
 * This is actually shipping info, so we've moved it to another tab
 * @hooked add_action('cfw_checkout_customer_info_tab', 'cfw_customer_info_address', 40); template-hooks.php
 */
remove_action('cfw_checkout_customer_info_tab', 'cfw_customer_info_address', 40);

/**
 * Remove billing address from payment tab
 * @hooked add_action('cfw_checkout_payment_method_tab', 'cfw_payment_tab_content_billing_address', 20);
 */
remove_action('cfw_checkout_payment_method_tab', 'cfw_payment_tab_content_billing_address', 20);

/**
 * add billing details to info tab
 */
add_action('cfw_checkout_customer_info_tab', function () {
  //cfw_get_billing_checkout_fields(WC()->checkout());
  //We're not using cfw_get_billing_checkout_fields because we want phone
  // the code below is essentially copied from there

  $checkout = WC()->checkout();

  $billing_checkout_fields = apply_filters('cfw_get_billing_checkout_fields', $checkout->get_checkout_fields('billing'));

  //We're going to separate the billing fields into customer specific and order specific
  $order_details_fields = [];
  $gift_fields = [];

  echo '<div class="cfw-module">';
  foreach ($billing_checkout_fields as $key => $field) {
    // Don't output billing email or native billing phone
    // This logic is ugly, but basically we're saying:
    //   - If the field is billing phone and our wrap isn't present, skip the field
    //   - If the field is billing email, skip it
    //   - Otherwise, output it
    if ('billing_email' === $key) {
      continue;
    }
    if ('date' === $key) {
      $order_details_fields[$key] = $field;
      continue;
    }
    if ('card_message' === $key) {
      $gift_fields[$key] = $field;
      continue;
    }

    cfw_form_field($key, $field, $checkout->get_value($key));
  }
  echo '</div>';

  if (!empty($order_details_fields)) {
    echo '<div class="cfw-module"><h3>Order Details</h3>';
    foreach ($order_details_fields as $key => $field) {
      cfw_form_field($key, $field, $checkout->get_value($key));
    }
    echo '</div>';
  }

  if (!empty($gift_fields)) {
    echo '<div class="cfw-module"><h4>Gift Details (optional)</h4>';
    foreach ($gift_fields as $key => $field) {
      cfw_form_field($key, $field, $checkout->get_value($key));
    }
    echo '</div>';
  }

}, 40);

/**
 * Change label on "Continue to Delivery"
 */
add_filter('cfw_continue_to_shipping_method_label', function ($label) {
  return esc_html__('Continue to delivery details', 'checkout-wc');
});

/**
 * Add shipping address to shipping tab
 */
//add_action('cfw_checkout_shipping_method_tab', function () {
//}, 15);
add_action('cfw_checkout_shipping_method_tab', 'cfw_customer_info_address', 15);

/**
 * For now, just remove this
 * it shows ship to: when we want to enter shipping details
 */
remove_action('cfw_checkout_shipping_method_tab', 'cfw_shipping_method_address_review', 10);

/**
 * Fix the link on the summary on nthe payment tab
 */
remove_action('cfw_checkout_payment_method_tab', 'cfw_payment_method_address_review', 0);
add_action('cfw_checkout_payment_method_tab', function () {
  if (!wc_ship_to_billing_address_only()) {
    $ship_to_label = __('Ship to', 'checkout-wc');
  } else {
    $ship_to_label = cfw__('Address', 'woocommerce');
  }

  $long_class = '';

  if (strlen($ship_to_label) > 9) {
    $long_class = ' shipping-details-label-long';
  }
  ?>
    <ul id="cfw-payment-method-address-review" class="cfw-module">
        <li>
            <div class="inner">
                <div role="rowheader" class="shipping-details-label<?php echo $long_class; ?>">
					<?php _e('Contact', 'checkout-wc');?>
                </div>

                <div role="cell" class="shipping-details-content" id="cfw-payment-method-address-review-contact"></div>
            </div>

            <div role="cell" class="shipping-details-link">
                <a href="javascript:;" data-tab="#cfw-customer-info" class="cfw-tab-link cfw-small"><?php esc_html_e('Change', 'checkout-wc');?></a>
            </div>
        </li>

	    <?php if (WC()->cart->needs_shipping()): ?>
            <li>
                <div class="inner">
                    <div role="rowheader" class="shipping-details-label<?php echo $long_class; ?>">
                        <?php echo $ship_to_label; ?>
                    </div>

                    <div role="cell" class="shipping-details-content" id="cfw-payment-method-address-review-address"></div>
                </div>

                <div role="cell" class="shipping-details-link">
                    <a href="javascript:;" data-tab="#cfw-shipping-method" class="cfw-tab-link cfw-small"><?php esc_html_e('Change', 'checkout-wc');?></a>
                </div>
            </li>

            <li>
                <div class="inner">
                    <div role="rowheader" class="shipping-details-label<?php echo $long_class; ?>">
					    <?php _e('Method', 'checkout-wc');?>
                    </div>

                    <div role="cell" class="shipping-details-content" id="cfw-payment-method-address-review-shipping-method"></div>
                </div>

                <div role="cell" class="shipping-details-link">
                    <a href="javascript:;" data-tab="#cfw-shipping-method" class="cfw-tab-link cfw-small"><?php esc_html_e('Change', 'checkout-wc');?></a>
                </div>
            </li>
	    <?php endif;?>
    </ul>
	<?php
}, 0);

/*add_filter('cfw_load_checkout_template', function ($should_load) {
return false;
/if (!$should_load) {
return $should_load;
}

if (is_wc_endpoint_url('order-pay')) {
return false;
}

return $should_load;
},99);*/

/**
 * Disable checkout-wc on order-pay for now as it dont work
 */
add_filter('cfw_load_order_pay_template', '__return_false', 999);

/**
 * Maybe remove DOB field
 */
add_filter('woocommerce_billing_fields', function ($billing_fields) {
  //echo '<!-- BillingFields: ';
  //print_r($billing_fields);
  //echo '-->';

  //
  // IF category 'wine and spirits' is in cart
  // or addon group hamper alcohol
  // then don't remove
  // otherwise remove
  //

  // Set $cat_in_cart to false
  $cat_in_cart = false;

// Loop through all products in the Cart
  foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {

    // If Cart has category "wine-and-spirit", set $cat_in_cart to true
    if (has_term('wine-and-spirit', 'product_cat', $cart_item['product_id'])) {
      $cat_in_cart = true;
      break;
    }

    //TODO check addons
    //For now the best we can do is check if the item has addons & is a hamper
    if (!empty($cart_item['addons']) && has_term('hampers', 'product_cat', $cart_item['product_id'])) {
      $cat_in_cart = true;
      break;
    }
    //echo '<!-- CART ITEM ' . "\n";
    //print_r($cart_item['addons']);
    //echo '-->';
    //$cart_item['addons'][0]['field_name'] = '534-add-0';
    //$addons = \WC_Product_Addons_Helper::get_product_addons($cart_item['product_id']);
    //echo '<!-- Addons for ' . $cart_item['product_id'] . "\n";
    //print_r($addons);
    //echo '-->';
  }

// Do something if category "download" is in the Cart
  if (!$cat_in_cart) {
    unset($billing_fields['dob']);
  }

  return $billing_fields;
}, 10, 1);