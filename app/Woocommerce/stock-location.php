<?php
namespace App;

/**
 * Since removal of stock locations plugin + orders by location, we have moved the remaining functionality to this file
 *
 * In here;
 *
 * - Add _stock_location meta to orders
 * - Add stock location header to headers (not actually necessary, I think...)
 */

/**
 * new_order_set_location
 * When an order is placed, set the location meta (_stock_location)
 */
add_action('woocommerce_new_order', function ($order_id, $order) {
  $sc_data = get_sc_data();
  $location = isset($sc_data['location']) ? $sc_data['location'] : null;
  if ($location) {
    update_post_meta($order_id, '_stock_location', $location);
    update_post_meta($order_id, '_order_sc_location', $location);
    $note = __(sprintf("Stock location set to: %s", $location));
    // Add the note
    $order->add_order_note($note);
  }
}, 10, 2);

//Add the stock location header
// USeful for cache busting, debugging etc
add_filter('wp_headers', function ($headers) {
  $sc_data = get_sc_data();
  $location = isset($sc_data['location']) ? $sc_data['location'] : null;
  if ($location) {
    $headers['X-WC_STORE_CHOOSER_LOCATION'] = $location;
    $location_cookie_name = wc_sc_cookie_name() . '_location';
    if (!isset($_COOKIE[$location_cookie_name]) || $_COOKIE[$location_cookie_name] !== $location) {
      setcookie($location_cookie_name, $location, time() + 60 * 60 * 24 * 365); //365 days expiry
    }
  }
  return $headers;
}, 10, 1);

/**
 * Hide stock from some stores
 * based on stock_location
 */

add_filter('woocommerce_product_is_visible', '\App\hide_product_by_location', 10, 2);
add_filter('woocommerce_is_purchasable', '\App\hide_product_by_location', 10, 2);

function hide_product_by_location($purchasable, $product) {
  if (is_admin()) {
    return $purchasable;
  }
  $sc_data = get_sc_data();
  $location = isset($sc_data['location']) ? $sc_data['location'] : null;

  if ($location && !has_term($location, 'location', $product_id)) {
    return false;
  }
  return $purchasable;
}