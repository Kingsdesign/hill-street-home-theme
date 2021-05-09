<?php
/*
 * MOdifications:
 * - Add order number under title
 * - removed SKU
 * - add fulfil data to shipping column
 * - add "ship to" text
 */
?>

<?php if (!defined('ABSPATH')) {
  exit;
}
// Exit if accessed directly ?>
<?php do_action('wpo_wcpdf_before_document', $this->type, $this->order);?>

<table class="head container">
  <tr>
    <td class="header">
      <?php
if ($this->has_header_logo()) {
  $this->header_logo();
} else {
  echo $this->get_title();
}
?>
    </td>
    <td class="shop-info">
      <div class="shop-name">
        <h3><?php $this->shop_name();?></h3>
      </div>
      <div class="shop-address"><?php $this->shop_address();?></div>
    </td>
  </tr>
</table>

<h1 class="document-type-label">
  <?php if ($this->has_header_logo()) {
  echo $this->get_title();
}
?>
</h1>

<?php // MODIFICATION : add order number here ?>
<h3 style="font-size: 12pt; margin-bottom: 5mm;"><strong>Order number: <?php $this->order_number();?></strong></h3>
<?php // END MODIFICATION ?>

<?php // MODIFICATION: add fulfilment data
$fulfil_method = $this->order->get_meta('_order_sc_method');
$fulfil_date = $this->order->get_meta('date');
$fulfil_date = $fulfil_date ? DateTime::createFromFormat('M j, Y', $fulfil_date) : false;
$fulfil_date_fmt = $fulfil_date ? $fulfil_date->format('F j, Y') : "";
$fulfil_time = $this->order->get_meta('time');
?>
<p style="font-size: 10pt; margin-bottom: 5mm;">
  Preferred <?php echo ucfirst($fulfil_method); ?>:
  <?php echo $fulfil_time ? $fulfil_time . ", " : ""; ?><?php echo $fulfil_date_fmt; ?>
</p>
<?php // END MODIFICATION ?>

<?php do_action('wpo_wcpdf_after_document_label', $this->type, $this->order);?>

<table class="order-data-addresses">
  <tr>
    <td class="address shipping-address">
      <?php // MODIFICATION add "ship to" text ?>
      <h3><?php _e('Ship To:', 'woocommerce-pdf-invoices-packing-slips');?></h3>
      <?php // END MODIFICATION ?>
      <!-- <h3><?php _e('Shipping Address:', 'woocommerce-pdf-invoices-packing-slips');?></h3> -->
      <?php do_action('wpo_wcpdf_before_shipping_address', $this->type, $this->order);?>
      <?php $this->shipping_address();?>
      <?php do_action('wpo_wcpdf_after_shipping_address', $this->type, $this->order);?>
      <?php if (isset($this->settings['display_email'])) {?>
      <div class="billing-email"><?php $this->billing_email();?></div>
      <?php }?>
      <?php if (isset($this->settings['display_phone'])) {?>
      <div class="billing-phone"><?php $this->billing_phone();?></div>
      <?php }?>
      <div class="customer-notes">
        <?php do_action('wpo_wcpdf_before_customer_notes', $this->type, $this->order);?>
        <?php if ($this->get_shipping_notes()): ?>
        <h3><?php _e('Delivery Instruction', 'woocommerce-pdf-invoices-packing-slips');?></h3>
        <?php $this->shipping_notes();?>
        <?php endif;?>
        <?php do_action('wpo_wcpdf_after_customer_notes', $this->type, $this->order);?>
      </div>
    </td>
    <td class="address billing-address">
      <?php if (isset($this->settings['display_billing_address']) && $this->ships_to_different_address()) {?>
      <h3><?php _e('Billing Address:', 'woocommerce-pdf-invoices-packing-slips');?></h3>
      <?php do_action('wpo_wcpdf_before_billing_address', $this->type, $this->order);?>
      <?php $this->billing_address();?>
      <?php do_action('wpo_wcpdf_after_billing_address', $this->type, $this->order);?>
      <?php }?>
    </td>
    <td class="order-data">
      <table>
        <?php do_action('wpo_wcpdf_before_order_data', $this->type, $this->order);?>
        <tr class="order-number">
          <th><?php _e('Order Number:', 'woocommerce-pdf-invoices-packing-slips');?></th>
          <td><?php $this->order_number();?></td>
        </tr>
        <tr class="order-date">
          <th><?php _e('Order Date:', 'woocommerce-pdf-invoices-packing-slips');?></th>
          <td><?php $this->order_date();?></td>
        </tr>
        <tr class="shipping-method">
          <th><?php _e('Shipping Method:', 'woocommerce-pdf-invoices-packing-slips');?></th>
          <td><?php $this->shipping_method();?></td>
        </tr>
        <?php do_action('wpo_wcpdf_after_order_data', $this->type, $this->order);?>
      </table>
    </td>
  </tr>
</table>

<?php do_action('wpo_wcpdf_before_order_details', $this->type, $this->order);?>

<table class="order-details">
  <thead>
    <tr>
      <th class="product"><?php _e('Product', 'woocommerce-pdf-invoices-packing-slips');?></th>
      <th class="quantity"><?php _e('Quantity', 'woocommerce-pdf-invoices-packing-slips');?></th>
    </tr>
  </thead>
  <tbody>
    <?php $items = $this->get_order_items();if (sizeof($items) > 0): foreach ($items as $item_id => $item): ?>
    <tr class="<?php echo apply_filters('wpo_wcpdf_item_row_class', $item_id, $this->type, $this->order, $item_id); ?>">
      <td class="product">
        <?php $description_label = __('Description', 'woocommerce-pdf-invoices-packing-slips'); // registering alternate label translation ?>
        <span class="item-name"><?php echo $item['name']; ?></span>
        <?php do_action('wpo_wcpdf_before_item_meta', $this->type, $item, $this->order);?>
        <span class="item-meta"><?php echo $item['meta']; ?></span>
        <dl class="meta">
          <?php $description_label = __('SKU', 'woocommerce-pdf-invoices-packing-slips'); // registering alternate label translation ?>
          <?php // MODIFICATION: remove sku
  /*
  <?php if (!empty($item['sku'])): ?><dt class="sku"><?php _e('SKU:', 'woocommerce-pdf-invoices-packing-slips');?></dt>
          <dd class="sku"><?php echo $item['sku']; ?></dd><?php endif;?>
          */
          //END MODIFICATION ?>
          <?php if (!empty($item['weight'])): ?><dt class="weight">
            <?php _e('Weight:', 'woocommerce-pdf-invoices-packing-slips');?></dt>
          <dd class="weight"><?php echo $item['weight']; ?><?php echo get_option('woocommerce_weight_unit'); ?></dd>
          <?php endif;?>
        </dl>
        <?php do_action('wpo_wcpdf_after_item_meta', $this->type, $item, $this->order);?>
      </td>
      <td class="quantity"><?php echo $item['quantity']; ?></td>
    </tr>
    <?php endforeach;endif;?>
  </tbody>
</table>

<?php do_action('wpo_wcpdf_after_order_details', $this->type, $this->order);?>

<?php do_action('wpo_wcpdf_before_customer_notes', $this->type, $this->order);?>
<div class="customer-notes">
  <?php if ($this->get_shipping_notes()): ?>
  <h3><?php _e('Customer Notes', 'woocommerce-pdf-invoices-packing-slips');?></h3>
  <?php $this->shipping_notes();?>
  <?php endif;?>
</div>
<?php do_action('wpo_wcpdf_after_customer_notes', $this->type, $this->order);?>

<?php if ($this->get_footer()): ?>
<div id="footer">
  <?php $this->footer();?>
</div><!-- #letter-footer -->
<?php endif;?>

<?php do_action('wpo_wcpdf_after_document', $this->type, $this->order);?>