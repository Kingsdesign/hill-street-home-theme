<?php

namespace App\Controllers;

use Sober\Controller\Controller;

class FrontPage extends Controller {

  public function campaign() {
    $campaign = get_field('homepage_campaign', 'options');
    if (!empty($campaign['banner']) || !empty($campaign['heading']) || !empty($campaign['content'])) {
      return $campaign;
    }

    return false;
  }

  public function categories() {
    $categories = get_field('homepage_categories', 'options');
    if (!empty($categories)) {
      return $categories;
    }

    return null;
  }

  /**
   * Display categories by order that ids are specified in in incoming array of ids
   * $ids = [5,2,6], order should be 5,2,6
   * Taken from class-wc-shortcodes, product_categories()
   * line 152
   */
  public static function product_categories($ids, $atts) {

    $hide_empty = true;

    $atts = shortcode_atts(
      array(
        'limit' => '-1',
        'columns' => '4',
        'hide_empty' => 1,
        'ids' => '',
      ),
      $atts,
    );

//$ids = array_filter(array_map('trim', explode(',', $atts['ids'])));

    $args = array(
      'hide_empty' => true,
      'include' => $ids,
    );

    //maybe we dont want this filter?
    $product_categories = apply_filters(
      'woocommerce_product_categories',
      get_terms('product_cat', $args)
    );

    if ($hide_empty) {
      foreach ($product_categories as $key => $category) {
        if (0 === $category->count) {
          unset($product_categories[$key]);
        }
      }
    }

    $atts['limit'] = '-1' === $atts['limit'] ? null : intval($atts['limit']);
    if ($atts['limit']) {
      $product_categories = array_slice($product_categories, 0, $atts['limit']);
    }

    usort($product_categories, function ($a, $b) use ($ids) {
      if ($a->term_id === $b->term_id) {
        return 0;
      }

      $a_pos = array_search($a->term_id, $ids);
      $b_pos = array_search($b->term_id, $ids);

      return ($a_pos < $b_pos) ? -1 : 1;
    });

    $columns = absint($atts['columns']);

    wc_set_loop_prop('columns', $columns);
    wc_set_loop_prop('is_shortcode', true);

    ob_start();

    if ($product_categories) {
      woocommerce_product_loop_start();

      foreach ($product_categories as $category) {
        wc_get_template(
          'content-product_cat.php',
          array(
            'category' => $category,
          )
        );
      }

      //Inject our buttons
      $shop_page_url = App::relative_url(get_permalink(wc_get_page_id('shop')));
      echo '<li class="product-category product buttons md:col-span-3 md:grid md:grid-cols-2 md:gap-4 md:pt-6">';
      echo '<span class="block"><a class="flex items-center justify-center" href="' . esc_url($shop_page_url) . '"><span>Shop all</span></a></span>';
      echo '<span class="block"><button class="flex items-center justify-center" data-modal-trigger="modal-search"><span>Search</span></button></span>';
      echo '</li>';

      woocommerce_product_loop_end();
    }

    woocommerce_reset_loop();

    return '<div class="woocommerce columns-' . $columns . '">' . ob_get_clean() . '</div>';
  }
}
