<?php
/**
 * Customer processing order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-processing-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails
 * @version 3.7.0
 */

if (!defined('ABSPATH')) {
  exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email);?>

<?php /* translators: %s: Customer first name */?>
<p><?php printf(esc_html__('Hi %s,', 'woocommerce'), esc_html($order->get_billing_first_name()));?></p>
<?php /* translators: %s: Order number */?>
<?php /**
 * REMOVED THIS LINE
 * ADDED below
 */?>
 <p><strong>Order number: <?php echo esc_html($order->get_order_number()); ?></strong></p>

 <?php
$location = $order->get_meta('_stock_location');
$suburb = $order->get_meta('_order_sc_suburb');

//add fulfilment date/time/method to email
$fulfil_method = $order->get_meta('_order_sc_method');
$fulfil_date = $order->get_meta('date');
$fulfil_date = $fulfil_date ? DateTime::createFromFormat('M j, Y', $fulfil_date) : false;
$fulfil_date_fmt = $fulfil_date ? $fulfil_date->format('F j, Y') : "";
$fulfil_time = $order->get_meta('time');
$fulfil_location = $fulfil_method === 'pickup' ? \ucwords(strtolower(implode(" ", explode("-", $location)))) : ucwords(strtolower($suburb));

$fulfil_parts = [];
$fulfil_parts[] = $fulfil_method === 'pickup' ? 'from' : 'to';
$fulfil_parts[] = $fulfil_location;
$fulfil_parts[] = '-';
$fulfil_parts[] = $fulfil_time ? $fulfil_time : '';
$fulfil_parts[] = $fulfil_date_fmt;
?>
 <p><strong><?php echo ucfirst($fulfil_method); ?></strong> <?php echo implode(" ", $fulfil_parts); ?> </p>

<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
/*if ($additional_content) {
echo wp_kses_post(wpautop(wptexturize($additional_content)));
}*/

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
