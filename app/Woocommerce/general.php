<?php

namespace App;

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
//remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price');

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
 * hide suffix on loop
 */
add_filter('woocommerce_get_price_suffix', function ($html, $product, $price, $qty) {
  global $post;
  if (is_archive()) {
    return '';
  }
  //Also hide on 'related' etc
  if (is_product() && get_post_field('ID', $post) === $product->get_id()) {
    return '';
  }
  return $html;
}, 10, 4);

/**
 * Change sale flash text
 */
add_filter('woocommerce_sale_flash', function ($text, $post, $_product) {
  if (is_archive()) {
    return '';
  }

  return '<span class="onsale">On sale</span>';
}, 10, 3);

/**
 * Remove variable product price range
 */
add_filter('woocommerce_variable_sale_price_html', __NAMESPACE__ . '\\variable_product_price_html', 10, 2);
add_filter('woocommerce_variable_price_html', __NAMESPACE__ . '\\variable_product_price_html', 10, 2);

function variable_product_price_html($v_price, $v_product) {

  $variable_format = 'From: %1$s';

  if (is_archive()) {
    $variable_format = '%1$s+';
  }

// Product Price
  $prod_prices = array($v_product->get_variation_price('min', true), $v_product->get_variation_price('max', true));
  $prod_price = $prod_prices[0] !== $prod_prices[1] ? sprintf(__($variable_format, 'woocommerce'), wc_price($prod_prices[0])) : wc_price($prod_prices[0]);

// Regular Price
  $regular_prices = array($v_product->get_variation_regular_price('min', true), $v_product->get_variation_regular_price('max', true));
  sort($regular_prices);
  $regular_price = $regular_prices[0] !== $regular_prices[1] ? sprintf(__($variable_format, 'woocommerce'), wc_price($regular_prices[0])) : wc_price($regular_prices[0]);

  if ($prod_price !== $regular_price) {
    $prod_price = '<del>' . $regular_price . $v_product->get_price_suffix() . '</del> <ins>' .
    $prod_price . $v_product->get_price_suffix() . '</ins>';
  }
  return $prod_price;
}

/**
 * Hide SKU
 */
//add_filter('wc_product_sku_enabled', '__return_false');

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

//add_action('woocommerce_before_single_product', function () {
//global $post;
//if (is_product() && (has_term('edible', 'product_cat', $post) || has_term('fresh', 'product_cat', $post)) && sc_location_is('devonport') && get_post_field('slug', $post) !== 'gift-card') {
//remove_action('woocommerce_before_add_to_cart_button', array($GLOBALS['Product_Addon_Display'], 'display'), 10);
//remove_action('woocommerce_before_variations_form', array($GLOBALS['Product_Addon_Display'], 'reposition_display_for_variable_product'), 10);

//}
//});

add_action('woocommerce_product_addons_start', function ($post_id) {
  echo '<div class="addons-wrapper hidden">';
}, 10, 1);
add_action('woocommerce_product_addons_end', function ($post_id) {
  echo '</div>';
}, 10, 1);

/**
 * Hide alcohol from devonport & sandybay
 */
add_filter('woocommerce_product_is_visible', function ($visible, $product_id) {
  if (is_admin()) {
    return $visible;
  }
  if (is_alcohol($product_id) && (sc_location_is('devonport') || sc_location_is('sandy-bay'))) {
    return false;
  }
  return $visible;
}, 100, 2);
add_filter('woocommerce_is_purchasable', function ($purchasable, $product) {
  if (is_admin()) {
    return $purchasable;
  }
  if (is_alcohol($product->get_id()) && (sc_location_is('devonport') || sc_location_is('sandy-bay'))) {
    return false;
  }
  return $purchasable;
}, 100, 2);

/**
 * Helper function to check if product can be shipped
 */
function is_undeliverable_user() {
  $sc_data = get_sc_data();
  if (empty($sc_data)) {
    return false;
  }

  if (empty($sc_data['method']) || empty($sc_data['postcode']) || empty($sc_data['suburb'])) {
    return false;
  }

//Only apply to delivery
  if (sc_method_is('pickup')) {
    return false;
  }

//Exclude mainland postcodes
  $is_tasmania = strpos($sc_data['postcode'], '7') === 0;
  $is_good_postcode = false;
  $is_bad_suburb = false;

  if ($is_tasmania && !empty($postcodes = get_field('delivery_fresh_postcodes', 'options'))) {
    $postcodes = explode("\n", $postcodes);
    foreach ($postcodes as $postcode) {
      if (strcasecmp(trim($postcode), trim($sc_data['postcode']))) {
        $is_good_postcode = true;
        break;
      }
    }
  }

  if ($is_tasmania && $is_good_postcode && !empty($suburbs = get_field('delivery_no_fresh', 'options'))) {
    $suburbs = explode("\n", $suburbs);
    foreach ($suburbs as $suburb) {
      if (strcasecmp(trim($suburb), trim($sc_data['suburb'])) === 0) {
        $is_bad_suburb = true;
        break;
      }
    }
  }

  return (!$is_tasmania || !$is_good_postcode || $is_bad_suburb);
}
function is_product_deliverable($product = null) {
  global $post;
  if ($product === null) {
    $product = $post; //\wc_get_product($post);
  }
  if (!$product) {
    return false;
  }

  if (!has_term('fresh', 'product_cat', $product) && !has_term('flowers', 'product_cat', $product)) {
    return true;
  }

  return !is_undeliverable_user();
}

function ajax_is_product_deliverable() {
  if (!isset($_POST['product'])) {
    return true;
  }

  wp_send_json(is_product_deliverable($_POST['product']));
  exit;
}

add_action('wp_ajax_product_deliverable', __NAMESPACE__ . '\\ajax_is_product_deliverable');
add_action('wp_ajax_nopriv_product_deliverable', __NAMESPACE__ . '\\ajax_is_product_deliverable');

/** Hide from shop if not deliverable */
add_action('woocommerce_product_query', function ($q) {

  if (is_admin()) {
    return $q;
  }

  if (is_undeliverable_user()) {
    $tax_query = (array) $q->get('tax_query');

    $tax_query[] = array(
      'taxonomy' => 'product_cat',
      'field' => 'slug',
      'terms' => array('fresh', 'flowers'),
      'operator' => 'NOT IN',
    );

    $q->set('tax_query', $tax_query);
  }

  return $q;

});
// add_filter('woocommerce_product_is_visible', function ($visible, $product_id) {
//   if (!$visible) {
//     return $visible;
//   }

//   return is_product_deliverable($product_id);
// }, 10, 2);

// Hide categories with no visible products
add_filter('woocommerce_get_product_subcategories_cache_key', function ($key, $parent_id) {
  $sc_data = get_sc_data();
  if (!empty($sc_data) && isset($sc_data['postcode'])) {
    $key .= '-' . $sc_data['method'] . '-' . $sc_data['postcode'];
  }
  return $key;
}, 10, 2);
add_filter('get_terms', function ($terms, $taxonomies, $args) {

// // if it is a product category and on the shop page
  if (in_array('product_cat', $taxonomies) && !is_admin()) {
    if (is_undeliverable_user()) {
      $new_terms = [];
      foreach ($terms as $key => $term) {
        if (is_object($term) && !($term->slug === 'fresh' || $term->slug === 'flowers')) {
          $new_terms[] = $term;
        }
      }
      $terms = $new_terms;
    }
  }
  return $terms;

}, 10, 3);

/**
 * Hide add to cart, and show message if its a no fresh
 * * @hooked woocommerce_single_product_summary woocommerce_template_single_add_to_cart - 30
 */
add_action('woocommerce_single_product_summary', function () {

  //if (!is_product_deliverable()) {
  //remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
  add_action('woocommerce_single_product_summary', function () {
    echo '<div class="delivery-no-fresh hidden"><div class="bg-gray-50 border border-solid border-gray-300 px-3 py-4 my-3 text-center">';
    echo '<p>We\'re sorry, but it looks like we can\'t deliver this product to you. We do offer pickup from <strong data-sc-val="location_display"></strong>.</p>';
    echo '</div></div>';
  }, 30);
  //}

}, 29);

/**
 * Also remove shipping methods if it's fresh and a nogo
 */
/*add_filter('woocommerce_package_rates', function ($rates) {
$deliverable = true;
foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
if (!is_product_deliverable($cart_item['product_id'])) {
$deliverable = false;
}
}
if (!$deliverable) {
return null;
}

return $rates;

}, 100);*/

/**
 * Add continue shopping link to cart
 */
add_action('woocommerce_proceed_to_checkout', function () {
  $shop_page_url = get_permalink(wc_get_page_id('shop'));
  //echo '<a href='.App::relative_url($url).'>'..'</a>
  //print_r(\sage('blade'));
  //print_r(get_class(sage('blade')->compiler()));
  //print_r(get_class_methods('Illuminate\View\Compilers\BladeCompiler'));
  echo sage('blade')->compiler()->compileString('<a href="' . \App\Controllers\App::relative_url($shop_page_url) . '">@svg(MdChevronLeft) Continue shopping</a>');
  //print_r(get_class_methods('App\Controllers\App'));
  //echo \App\Controllers\App::relative_url($shop_page_url);
  //echo sage('blade')->compiler()->compileString('{!! App::relative_url(wc_get_shop_url()) !!}');
  //echo sage('blade')->compiler()->compileString('<a href="@php App::relative_url(wc_get_shop_url()) @php">@svg(MdChevronLeft) Continue shopping</a>');
});

/**
 * Never show alcohol as a related product
 */
add_filter('woocommerce_related_products', function ($related_posts, $product_id, $args) {
  // HERE define your product category slug
  $term_slug = 'wine-and-spirits';

  // Get the product Ids in the defined product category
  $exclude_ids = wc_get_products(array(
    'status' => 'publish',
    'limit' => -1,
    'category' => array($term_slug),
    'return' => 'ids',
  ));

  return array_diff($related_posts, $exclude_ids);
}
  , 10, 3);

/**
 * Helper function to determine whether product is alcohol
 */
function is_alcohol($product_id) {
  $term_slug = 'wine-and-spirits';
  return has_term($term_slug, 'product_cat', $product_id);
}

/**
 * Add class to loop items
 */
/*add_filter('woocommerce_post_class', function ($classes, $product) {
$classes[] = 'loop-item--';
return $classes;
}, 10, 2);*/
add_filter('product_cat_class', function ($classes, $class, $category) {
  $classes[] = 'product-category--' . esc_attr($category->slug);
  return $classes;
}, 10, 3);

/**
 * Add message about quantity and addons
 */
add_action('woocommerce_after_add_to_cart_quantity', function () {
  if (($text = get_field('add_on_quantity_notice', 'options')) && !empty($text)):
  ?>
  <div class="text-sm text-gray-600 mt-3 mb-5"><?php echo $text; ?></div>
  <?php
endif;
}, 999);

/**
 * Force store chooser
 */

add_action('woocommerce_before_add_to_cart_button', function () {
  echo '<div class="maybe-show-add-to-cart" data-sc-if="%location%" data-sc-cloak>';
});
add_action('woocommerce_after_add_to_cart_button', function () {
  echo '</div>';
  echo '<div data-sc-else class="maybe-show-add-to-cart--no-location" data><button type="button" data-sc-show class="block w-full bg-blue-50 border border-solid border-gray-300 px-3 py-4 my-3 text-center">';
  echo '<p class="underline">Set your store to add to cart</p>
  <noscript><p>This site requires javascript. Please enable javascript to add to cart.</p></noscript>';
  echo '</button></div>';
});