<?php

namespace App;

/**
 * Add extra messaging about store location & method after initial
 * messaging (before details table)
 */
//do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);
add_action('woocommerce_email_order_details', function ($order, $sent_to_admin, $plain_text, $email) {
  if ($email->id === 'customer_processing_order' || $email->id === 'customer_completed_order') {
    $location = $order->get_meta('_stock_location');
    $method = $order->get_meta('_order_sc_method');
    $suburb = $order->get_meta('_order_sc_suburb');

    if ($location && $method) {
      $location_display = ucwords(strtolower(implode(" ", explode("-", $location))));
      $method_display = ucfirst($method);
      //$suburb = \ucwords(strtolower($suburb));

      if ($email->id === 'customer_processing_order') {
        $string = 'Thank you for your purchase. We will start preparing your order and we will let you know when it is ';
        $string .= ($method === 'pickup') ? ('ready to be collected from Hill Street ' . $location_display . '.') : 'on its way.';
        $string = '<p>' . $string . '</p>';
      }
      if ($email->id === 'customer_completed_order') {
        $string = 'Just a quick note to let you know that your order from Hill Street Home ';
        $string .= ($method === 'pickup') ? ('is ready to be collected from Hill Street ' . $location_display . '.') : 'will be dispatched today.';
        $string = '<p>' . $string . '</p>';
        if ($method !== 'pickup') {
          $string .= '<p>Please note that deliveries to regional Tasmania generally arrive the next day. Deliveries to addresses elsewhere in Australia take between 2 and 8 days to arrive, depending on the delivery address.</p>';
        }
      }
    } else {
      //Generic fallback
      if ($email->id === 'customer_processing_order') {
        $string = '<p>Thank you for your purchase. We will start preparing your order and we will let you know when it is on its way, or ready to be collected if you have chosen in-store pick up.</p>';
      }
      if ($email->id === 'customer_completed_order') {
        $string = '<p>Just a quick note to let you know that your order from Hill Street Home is ready. If you selected to pick your order up in-store it is now available for you to collect from your nominated store. If you selected delivery, your order will be dispatched today via our couriers. Please note that deliveries to regional Tasmania generally arrive the next day. Deliveries to addresses elsewhere in Australia take between 2 and 8 days to arrive, depending on the delivery address.</p>';
      }
    }

    //echo '<p>' . esc_html($string) . '</p>';
    echo $string;

    //$string = 'We will send you another email to notify you when you order is ';
    //$string .= $method === 'pickup' ? 'availble for collection' : ('dispatched to ' . $suburb);
    //$string .= ' from Hill Street ' . $location;
    //echo '<p>' . esc_html($string) . '</p>';

  }
}, 5, 4);

/**
 * Add thanks & contact data
 */
//woocommerce_email_customer_details
add_action('woocommerce_email_order_details', function ($order, $sent_to_admin, $plain_text, $email) {
  if ($email->id === 'customer_processing_order' || $email->id === 'customer_completed_order' || $email->id === 'customer_on_hold_order') {
    $location = $order->get_meta('_stock_location');
    echo '<p>If you have any questions please don’t hesitate to get in touch with us on:</p>';
    if ($location === 'devonport') {
      echo '<p>03 6127 5355 for Devonport</p>';
    } else {
      echo '<p>03 6234 6849 for West Hobart</p>';
    }
    echo '<p>&nbsp;</p>';
    echo '<p>Kind regards,<br/><br/>The Hill Street Home team.</p>';
  }
}, 9, 4);

/**
 * Add method & store location to meta
 * This will be added to any email that uses this hook (processing, completed etc)
 */
add_filter('woocommerce_email_order_meta_fields', function ($fields, $sent_to_admin, $order) {
  $location = $order->get_meta('_stock_location');
  $suburb = $order->get_meta('_order_sc_suburb');

  if ($method = $order->get_meta('_order_sc_method')) {
    $value = \ucfirst($method);

    $fields['method'] = ['value' => $value, 'label' => 'Fulfilment'];
  }
  if ($sent_to_admin && $location) {
    $fields['location'] = ['value' => $location, 'label' => 'Store'];
  }
  if ($sent_to_admin && $suburb) {
    $fields['suburb'] = ['value' => \ucwords(strtolower($suburb)), 'label' => 'Suburb'];
  }
  if ($sent_to_admin && $postcode = $order->get_meta('_order_sc_postcode')) {
    $fields['postcode'] = ['value' => $postcode, 'label' => 'Postcode'];
  }
  /*if ($sent_to_admin) {
  $fields['packed_by'] = ['value' => '___', 'label' => 'Packed By'];
  $fields['completed_by'] = ['value' => '___', 'label' => 'Completed By'];
  $fields['courier_used'] = ['value' => '___', 'label' => 'Courier Used'];
  }*/
  return $fields;
}, 10, 3);

/**
 * Add fields to fill in by hand. What?
 */
add_action('woocommerce_email_customer_details', function ($order, $sent_to_admin, $plain_text, $email) {
  if (!$email->is_customer_email() && ($email->id === 'new_order')) {
    //echo '<p>================================</p>';
    echo '<p>Packed by: _________________________________</p>';
    echo '<p>Completed by: _________________________________</p>';
    echo '<p>Courier used: _________________________________</p>';
  }
}, 10, 4);

/**
 * Direct emails to appropriate stores
 */
function conditional_email_recipient($recipient, $order) {
  // Bail on WC settings pages since the order object isn't yet set yet
  // Not sure why this is even a thing, but shikata ga nai

  $page = isset($_GET['page']) ? $_GET['page'] : '';
  if ('wc-settings' === $page) {
    return $recipient;
  }

  // just in case
  /*if (!($order instanceof WC_Order)) {
  return $recipient;
  }*/

  $location = $order->get_meta('_stock_location');
  if (empty($location)) {
    error_log('Order placed without _stock_location! Order: ' . $order->id);
    //Send alert. This shouldn't happen, so we'd like to know
    wp_mail(get_option('admin_email'), get_option('blogname') . '  - Order placed without stock location', 'An order was just placed on ' . get_option('blogname') . ' which is missing the stock location metadata. Order ID: ' . $order->id);
    return $recipient;
  }

  $location_emails = get_field('location_emails', 'options');
  if (!$location_emails || empty($location_emails)) {
    return $recipient;
  }

  foreach ($location_emails as $location_email) {
    if (!empty($location_email['location']) && !empty($location_email['email'])) {
      if ($location_email['location']->slug === $location) {
        $recipient .= ', ' . $location_email['email'];
        break;
      }
    }
  }
  ;

  return $recipient;
}
add_filter('woocommerce_email_recipient_new_order', __NAMESPACE__ . '\\conditional_email_recipient', 10, 2);
add_filter('woocommerce_email_recipient_failed_order', __NAMESPACE__ . '\\conditional_email_recipient', 10, 2);

/**
 * Remove styles from admin emails
 */
add_filter('woocommerce_email_styles', function ($css, $email) {
  if (!$email->is_customer_email()) {
    return "";
  }

  return $css;
}, 10, 2);