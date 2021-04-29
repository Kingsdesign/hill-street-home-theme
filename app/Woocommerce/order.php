<?php

namespace App;

/**
 * Add card message field to new orders in admin
 */
add_action('woocommerce_admin_order_data_after_shipping_address', function ($order) {

  $isDraft = ('auto-draft' === $order->get_status() || 'draft' === $order->get_status());

  $card_message = $order->get_meta('card_message');

  ?>
<div class="<?php echo $isDraft ? '' : 'edit_address'; ?>">
  <p class="form-field form-field-wide">
    <label for="card_message">
      <?php _e('Card Message:', 'woocommerce');?>
    </label>
    <textarea id="card_message" name="card_message"><?php echo esc_html($card_message); ?></textarea>
  </p>
</div>
<?php
/*
if ($isDraft): ?>
<div class="address">
  <p><strong>Card Message:</strong> <?php echo esc_html($card_message); ?></p>
</div>
<?php
endif;*/

});

/**
 * Save card message in new orders in admin
 */
add_action('save_post', function ($post_id, $post, $update) {

  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return $post_id;
  }

  if (!current_user_can('edit_shop_orders', $post_id)) {
    return $post_id;
  }

  $order = wc_get_order($post_id);
  if (!$order) {
    return $post_id;
  }

  if (!$order) { //|| $order->get_status() !== 'pending') {
    return $post_id;
  }

  if (isset($_POST['card_message'])) {
    $order->update_meta_data('card_message', sanitize_text_field($_POST['card_message']));
    $order->save_meta_data();
  }
  return $post_id;
}, 10, 3);

/**
 * Make admin fields required
 */
add_filter('woocommerce_admin_billing_fields', function ($fields) {
  $fields['billing']['first_name'] = array('required' => 'required');
  return $fields;
});

add_action('admin_footer', function () {
  global $pagenow;
  if (!(($pagenow === 'post.php' && get_post_type() === 'shop_order') || ($pagenow === 'post-new.php' && get_post_type() === 'shop_order'))) {
    return;
  }

  ?>
<script>
(function($) {

  // getSubmitButton().disable();

  document.addEventListener('submit', handleSubmitEvent);

  function handleSubmitEvent(e) {
    if (e.target.matches('form#post')) {
      if (!validateFields()) {

        e.preventDefault();
      }
      // console.log('submit')
      // getSubmitButton().enable();
      // validateFields();
    }
  }


  function validateFields() {
    const requiredFields = [
      'billing_first_name',
      'billing_last_name',
      'billing_address_1',
      'billing_city',
      'billing_postcode',
      'billing_email',
      'billing_phone',
      'order-fulfilment-method',
      'order-fulfilment-date',
      'order-stock-location'
    ];

    let isValid = true;

    const invalidFields = [];

    requiredFields.forEach(fieldName => {
      const field = getField(fieldName);
      const isFieldValid = !!field.value();
      if (!isFieldValid) {
        field.setIsValid(false);
        isValid = false;
        invalidFields.push(fieldName);
      } else {
        field.setIsValid(true)
      }
    });

    if (!isValid) {
      triggerEditAddress();
    }

    console.log(isValid, invalidFields);

    return isValid;
  }

  function triggerEditAddress() {

    const el = document.querySelector('a.edit_address');
    if (el) {
      triggerNative(el, 'click');
    }
  }

  function getField(name) {
    const [fieldEl, formFieldEl] = name.substr(0, 6) === 'order-' ? getCustomField(name) : getWCField(name);
    return {
      value() {
        if (!fieldEl) return;
        return fieldEl.value;
      },
      setIsValid(isValid) {
        if (isValid && formFieldEl) {
          formFieldEl.classList.remove('isInvalid')
        }
        if (!isValid && formFieldEl) {
          formFieldEl.classList.add('isInvalid')
        }
      }
    }
  }

  function getCustomField(name) {
    const formFieldEl = document.querySelector('#order_meta .form-field._' + name + '_field');
    const fieldEl = formFieldEl ? document.querySelector('#order_meta #' + name) : null;
    return [fieldEl, formFieldEl];
  }

  function getWCField(name) {
    const formFieldEl = document.querySelector('.edit_address .form-field._' + name + '_field')
    const fieldEl = formFieldEl ? formFieldEl.querySelector('#_' + name) : null;
    return [fieldEl, formFieldEl];
  }


  function getSubmitButton() {
    return {
      disable() {
        const buttonEl = document.querySelector('button.save_order');
        if (!buttonEl) return;
        buttonEl.classList.add('disabled');
        buttonEl.setAttribute('disabled', 'disabled');
      },
      enable() {
        const buttonEl = document.querySelector('button.save_order');
        if (!buttonEl) return;
        buttonEl.classList.remove('disabled');
        buttonEl.removeAttribute('disabled');
      }
    }
  }

  function triggerNative(el, eventName) {
    var event = document.createEvent('HTMLEvents');
    event.initEvent(eventName, true, false);
    el.dispatchEvent(event);
  }
})(jQuery);
</script>

<style>
:root {
  --red-error: #EF4444;
}

.form-field.isInvalid label {
  color: var(--red-error);
}

.form-field.isInvalid input,
.form-field.isInvalid select {
  box-shadow: 0 0 0 1px var(--red-error);
  border-color: var(--red-error);
}
</style>
<?php
}, 999);

/**
 * Print card message
 */
add_action('woocommerce_admin_order_data_after_shipping_address', function ($order) {
  global $post;
  $card_message = $order->get_meta('card_message');
  if (!empty($card_message)) {
    $url = add_query_arg([
      'print_card_message' => 1,
      'order_id' => $post->ID,
    ], admin_url());
    echo '<a target="_blank" href="' . $url . '" class="button print-card-message">Print card message</a>';
  }
}, 20, 1);

add_action('admin_footer', function () {
  global $pagenow;

  if (!($pagenow === 'edit.php' && isset($_REQUEST['post_type']) && $_REQUEST['post_type'] === 'shop_order')) {
    return;
  }

  $url = add_query_arg([
    'print_card_message' => 1,
    'order_id' => '{{order_id}}',
  ], admin_url());
  ?>
<script>
(function() {

  const url = decodeURIComponent("<?php echo urlencode($url); ?>");


  document.addEventListener('submit', function(e) {
    if (e.target.matches('#posts-filter')) {
      if (e.target.elements['action'].value === 'print_card_message') {
        e.preventDefault();
        const post_ids = Array.from(e.target.querySelectorAll('input[name="post[]"]:checked')).map(el => el
          .value);
        window.open(url.replace(/{{order_id}}/, post_ids.join(',')), '_blank');
      }
    }
  });


})();
</script>
<?php
}, 100);

// add_action('wp_ajax__print_card_message', function() {
add_action('admin_init', function () {

  if (!isset($_REQUEST['print_card_message'])) {
    return;
  }

  $order_ids = isset($_REQUEST['order_id']) ? $_REQUEST['order_id'] : null;
  if (!$order_ids) {
    return;
  }
  $order_ids = explode(',', $order_ids);

  \App\print_card_messages($order_ids);

  die();

}, 0);

add_filter('wp_check_filetype_and_ext', function ($types, $file, $filename, $mimes) {

  if (false !== strpos($filename, '.ttf')) {
    $types['ext'] = 'ttf';
    $types['type'] = 'font/ttf|application/font-ttf|application/x-font-ttf|application/octet-stream';
  }

  return $types;
}, 10, 4);

add_filter('upload_mimes', function ($mimes) {
  $mimes['ttf'] = 'font/ttf|application/font-ttf|application/x-font-ttf|application/octet-stream';
  return $mimes;
});

add_filter('bulk_actions-edit-shop_order', function ($actions) {
  $actions['print_card_message'] = __('Print Card Message', 'woocommerce');
  return $actions;
}
  , 20, 1);

// Make the action from selected orders
add_filter('handle_bulk_actions-edit-shop_order', function ($redirect_to, $action, $post_ids) {
  if ($action !== 'print_card_message') {
    return $redirect_to;
  }

  // $processed_ids = array();

  // foreach ($post_ids as $post_id) {
  //   $order = wc_get_order($post_id);
  //   $order_data = $order->get_data();

  //   $processed_ids[] = $post_id;
  // }

  return $redirect_to = add_query_arg(array(
    'print_card_message' => '1',
    'order_id' => implode(',', $post_ids),
    // 'processed_count' => count($processed_ids),
    // 'processed_ids' => implode(',', $processed_ids),
  ), $redirect_to);
}, 10, 3);

function print_card_messages($order_ids) {
  $messages = [];
  foreach ($order_ids as $order_id) {
    $message = get_post_meta($order_id, 'card_message', true);
    if (!empty($message)) {
      $messages[] = [
        'message' => $message,
        'order_id' => $order_id,
      ];
    }
  }

  if (empty($messages)) {
    return;
  }

  $uploads = wp_upload_dir(null, true, false);
  if ($uploads['error']) {
    return;
  }

  $basedir = $uploads['basedir'];
  $baseurl = $uploads['baseurl'];

  $tmp = trailingslashit($basedir) . 'tmp';
// $tmpurl = trailingslashit($baseurl) . 'tmp';

// if (!is_dir($tmp)) {
  //   wp_mkdir_p($tmp);
  // }

  $date = new \DateTime();

  $filename = 'card-message-' . $order_id . '-' . $date->format('Y-m-d') . '.pdf';

  $defaultConfig = (new \Mpdf\Config\ConfigVariables())->getDefaults();
  $fontDirs = $defaultConfig['fontDir'];

  $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
  $fontData = $defaultFontConfig['fontdata'];

  $mpdfOpts = [
    'tempDir' => $tmp,
  ];

  $background_image_id = get_field('card_message_background', 'options');
  $background_image = null;
  if ($background_image_id) {
    $background_image = wp_get_attachment_image_src($background_image_id, 'full')[0];
  }

  $font_file = get_field('card_message_font', 'options');
  $font_file_path = null;
  if ($font_file) {

    $font_file_path = get_attached_file($font_file['ID']);

    $mpdfOpts = array_merge($mpdfOpts, [
      'fontDir' => array_merge($fontDirs, [
        dirname($font_file_path),
      ]),
      'fontdata' => $fontData + [
        'cardfont' => [
          'R' => basename($font_file_path),
        ],
      ],
      'default_font' => 'cardfont',
    ]);
  }

  $pages = array_chunk($messages, 4);

  $mpdf = new \Mpdf\Mpdf($mpdfOpts);
  $mpdf->WriteHTML('
<style>
@page {
  sheet-size: A4-L;
  font-family: "cardfont";
   margin: 0;
  background-image: url(' . $background_image . ');
  background-repeat: no-repeat;
  background-image-resize: 6;
}
table.grid {
  width: 100%;
  height: 100%;
  border-collapse: collapse;
}
td.grid-item {
  height: 10.486cm;
  text-align: center;
  width: 50%;
}
table.inner-grid {
  width: 100%;
  height: 100%;
}
.message {
  height: 8cm;
  font-size: 26pt;
  padding: 1cm;
}
.order-no {
  text-align:left;
  padding: 0.25cm;
  font-size: 13pt;
}
</style>');
  foreach ($pages as $index => $page) {

    if (count($page) < 4) {
      $page = array_merge($page, array_fill(count($page), 4 - count($page), ['message' => '&nbsp;', 'order_id' => null]));
    }

    $messages_html = '<tr>';
    $count = 0;
    foreach ($page as $index => $item) {

      $message = $item['message'];
      $order_id = $item['order_id'];

      $message_html = '
      <table class="inner-grid">
      <tbody>
        <tr>
          <td class="message">' . $message . '</td>
        </tr>
        <tr>
        <td class="order-no">Order #' . $order_id . '</td>
        </tr>
      </tbody>
      </table>';

      $messages_html .= '<td class="grid-item">' . $message_html . '</td>';
      $count++;
      if ($count === 2 && $index < count($page) - 1) {
        $count = 0;
        $messages_html .= '</tr><tr>';
      }
    }
    $messages_html .= '</tr>';

//     print_r('<table class="grid">
    // <tbody>
    // ' . $messages_html . '
    // </tbody>
    // </table>
    // ');
    //     die;

    $mpdf->WriteHTML('<table class="grid">
<tbody>
' . $messages_html . '
</tbody>
</table>
');
    if ($index < count($pages) - 2) {

      $mpdf->AddPage();
    }
  }

  $mpdf->Output();
}