<?php

namespace App;

add_filter('gform_init_scripts_footer', '__return_true');

add_filter('gform_pre_render_1', __NAMESPACE__ . '\\gform_populate_product_select');
add_filter('gform_pre_validation_1', __NAMESPACE__ . '\\gform_populate_product_select');
add_filter('gform_pre_submission_filter_1', __NAMESPACE__ . '\\gform_populate_product_select');
add_filter('gform_admin_pre_render_1', __NAMESPACE__ . '\\gform_populate_product_select');

function gform_populate_product_select($form) {

  if ($form['id'] !== 1 || is_admin()) {
    return $form;
  }

  foreach ($form['fields'] as $field) {

    if ($field->type !== 'multiselect' || strpos($field->cssClass, 'product-select') === false) {
      continue;
    }

    $args = array(
      'limit' => -1,
      'status' => 'publish',
    );
    $products = wc_get_products($args);

    $choices = array();

    foreach ($products as $product) {
      $choices[] = array('text' => $product->get_name(), 'value' => $product->get_id());
    }

    // update 'Select a Post' to whatever you'd like the instructive option to be
    $field->placeholder = 'Select a Product';
    $field->choices = $choices;

  }

  return $form;
}