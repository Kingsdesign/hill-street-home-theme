<?php

namespace App;

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
 * Merge default restrictions with date based restrictions
 * must parse first!
 */
function merge_date_restrictions_with_defaults($defaults, $restrictions) {
  $today_now = new \DateTime();

  $final_restrictions = $defaults;

  foreach ($restrictions as $location => $restriction) {
    foreach ($restriction as $method => $restriction_method) {
      $date = \DateTime::createFromFormat('Y-m-d', $restriction_method['date']);

      if (!$date) {
        continue;
      }

      $date->setTime(0, 0, 0);
      $end_date = !empty($restriction_method['end_date']) ? \DateTime::createFromFormat('Y-m-d', $restriction_method['end_date']) : null;
      if (!$end_date) {
        if ($today_now->diff($date)->d === 0) {
          //'deep' merge
          if (!isset($final_restrictions[$location])) {
            $final_restrictions[$location] = [$method => $restriction_method];
          } else {
            $final_restrictions[$location][$method] = $restriction_method;
          }
        }
      } else {
        $end_date->setTime(0, 0, 0);
        if ($today_now->diff($date)->d <= 0 && $today_now->diff($end_date)->d >= 0) {
          echo "IS in range\n";
          //'deep' merge
          if (!isset($final_restrictions[$location])) {
            $final_restrictions[$location] = [$method => $restriction_method];
          } else {
            $final_restrictions[$location][$method] = $restriction_method;
          }
        }
      }
    }

    //$date_diff = date_diff()
  }

  return $final_restrictions;
}

function find_location_by_id($location_id, $location_terms) {
  foreach ($location_terms as $location_term) {
    if ($location_term->term_id === $location_id) {
      return $location_term;
    }
  }
  return null;
}

/**
 * Take an array of restrictions, and turn them into an object by store
 * [0]=>locations['x','y','z'] ==> {x:..., y:..., z:...}
 */
function parse_date_restriction_defaults($restrictions) {

  $byLocation = []; //Assoc array of restrictions by location. Order in the input array matters.

  //get all the terms here once.
  // we can be pretty confident there will only be a small number
  $location_terms = get_terms(array('taxonomy' => 'location', 'hide_empty' => false));
  foreach ($restrictions as $restriction) {
    foreach ($restriction['location'] as $location_id) {
      $location_term = find_location_by_id($location_id, $location_terms);
      if (!$location_term) {
        continue;
      }

      $byLocation[$location_term->slug] = [];
      foreach ($restriction['method'] as $restriction_method) {
        $byLocation[$location_term->slug][$restriction_method] = [
          'day_offset' => $restriction['day_offset'],
          'time_cutoff' => $restriction['time_cutoff'],
          'type' => isset($restriction['type']) ? $restriction['type'] : 'default',
          'date' => isset($restriction['date']) ? $restriction['date'] : null,
          'end_date' => isset($restriction['end_date']) ? $restriction['end_date'] : null,
        ];
      }
    }
  }

  return $byLocation;
}

function parse_date_restrictions($restrictions) {
  $location_terms = get_terms(array('taxonomy' => 'location', 'hide_empty' => false));
  foreach ($restrictions as &$restriction) {
    $locations = [];
    foreach ($restriction['location'] as $location_id) {
      $location_term = find_location_by_id($location_id, $location_terms);
      if ($location_term) {
        $locations[] = $location_term->slug;
      }
    }
    $restriction['location'] = $locations;
  }
  return $restrictions;
}

function get_checkout_date_restrictions() {
  $defaults = get_field('order_date_defaults', 'options');
  $restrictions = get_field('order_date_restrictions', 'options');

  $defaults = parse_date_restriction_defaults($defaults);
  $defaults = merge_date_restrictions_with_defaults($defaults, parse_date_restriction_defaults($restrictions));

  $restrictions = parse_date_restrictions($restrictions);
  return ['defaults' => $defaults, 'restrictions' => $restrictions];
}

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
    $data = array('cookie_name' => \WC_OrderByLocation::$location_var_name, 'date_restrictions' => get_checkout_date_restrictions());
    echo "<script>\n/* <![CDATA[ */\n";
    echo 'var custom_checkout_data = ' . json_encode($data);
    echo "\n/* ]]> */\n</script>";
    wp_enqueue_script('sage/checkout.js', asset_path('scripts/checkout.js'), array(), null, false);
    wp_print_scripts('sage/checkout.js');
    //wp_localize_script('sage/checkout.js', 'custom_checkout_data', array('cookie_name' => \WC_OrderByLocation::$location_var_name));
  }
  //WARNING HAX
  // This forces checkout-wc not to validate shipping fields on customer info tab
  /*echo '<script>(function(w){
try {
w.cfwEventData.settings.needs_shipping_address = false;
} catch(e) {}
})(window);</script>';*/
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
add_action('cfw_checkout_customer_info_tab', function () {
  if (!WC()->cart->needs_shipping()) {
    remove_action('cfw_checkout_customer_info_tab', 'cfw_customer_info_address', 40);
  }
}, 39);

/**
 * Remove billing address from payment tab
 * @hooked add_action('cfw_checkout_payment_method_tab', 'cfw_payment_tab_content_billing_address', 20);
 */
remove_action('cfw_checkout_payment_method_tab', 'cfw_payment_tab_content_billing_address', 20);
add_action('cfw_checkout_payment_method_tab', function () {
  do_action('cfw_checkout_before_billing_address');
  //echo '<input type="hidden" name="bill_to_different_address" id="billing_same_as_shipping_radio" value="different_from_shipping" />';
  echo '<input id="ship-to-different-address-checkbox" style="display: none" type="checkbox" name="ship_to_different_address" value="' . (WC()->cart->needs_shipping_address() ? 1 : 0) . '" checked="checked" />';
  do_action('cfw_checkout_after_billing_address');
  do_action('cfw_checkout_after_payment_tab_billing_address');
}, 20);

/**
 * add billing details to info tab
 * this section is largely doing nothing now, as the order_details fields have been moved to additional details
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

  /*if (!empty($order_details_fields)) {
  echo '<div class="cfw-module"><h3>Order Details</h3>';
  foreach ($order_details_fields as $key => $field) {
  cfw_form_field($key, $field, $checkout->get_value($key));
  }
  echo '</div>';
  }*/

  /*if (!empty($gift_fields)) {
echo '<div class="cfw-module"><h4>Gift Details (optional)</h4>';
foreach ($gift_fields as $key => $field) {
cfw_form_field($key, $field, $checkout->get_value($key));
}
echo '</div>';
}*/

}, 30);

/**
 * Remove additional fields from payment method tab
 */
remove_action('cfw_checkout_payment_method_tab', 'cfw_payment_tab_content_order_notes', 30);
add_action('cfw_checkout_customer_info_tab', function () {
  echo '<div class="cfw-module"><h3>Order Details</h3>';
  cfw_payment_tab_content_order_notes();
  echo '</div>';
}, 30);

/**
 * Add shipping address to shipping tab
 */
//add_action('cfw_checkout_shipping_method_tab', function () {
//}, 15);
//add_action('cfw_checkout_shipping_method_tab', 'cfw_customer_info_address', 15);

/**
 * For now, just remove this
 * it shows ship to: when we want to enter shipping details
 */
//remove_action('cfw_checkout_shipping_method_tab', 'cfw_shipping_method_address_review', 10);

/**
 * Fix the link on the summary on nthe payment tab
 */
//remove_action('cfw_checkout_payment_method_tab', 'cfw_payment_method_address_review', 0);

/*add_action('cfw_checkout_payment_method_tab', function () {
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
}, 0);*/

/**
 * Maybe remove DOB field & rewards checkbox
 * if not alcohol
 */
add_filter('woocommerce_billing_fields', function ($billing_fields) {
  if (is_admin()) {
    return;
  }

  /**
   * Hide rewards checkbox if in west-hobart
   */
  if (sc_location_is('west-hobart')) {
    unset($billing_fields['phone_rewards']);
  }

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

/**
 * add messaging to thank you page
 */
add_action('cfw_thank_you_content', function ($order, $order_statuses) {
  $location = $order->get_meta('_stock_location');
  $method = $order->get_meta('_order_sc_method');
  if ($location && $method) {
    $location_display = ucwords(strtolower(implode(" ", explode("-", $location))));

    cfw_thank_you_section_auto_wrap(function ($method, $location_display) {
      echo '<h3>Thank you</h3>';
      $string = 'Thank you for your purchase. We will start preparing your order and we will let you know when it is ';
      $string .= ($method === 'pickup') ? ('ready to be collected from Hill Street ' . $location_display . '.') : ' on its way.';
      $string = '<p>' . $string . '</p>';
      echo $string;
    }, 'hsh-thank-you-message', [$method, $location_display]);
  }
}, 10, 2);

/*add_action(
'cfw_thank_you_content', function (WC_Order $order) {
if ($order->needs_shipping_address()) {
cfw_thank_you_section_auto_wrap('cfw_thank_you_order_updates', 'cfw-order-updates', array($order));
}
}, 60, 1
);*/
