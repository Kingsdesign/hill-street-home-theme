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
    // if (!isset($_COOKIE[wc_sc_cookie_name()]) || $_COOKIE[wc_sc_cookie_name()] !== $location) {
    //   setcookie(wc_sc_cookie_name(), $location, time() + 60 * 60 * 24 * 365); //365 days expiry
    //    }
  }
  return $headers;
}, 10, 1);