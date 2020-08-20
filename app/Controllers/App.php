<?php

namespace App\Controllers;

//use Cloudinary;
use Sober\Controller\Controller;

\Cloudinary::config(array(
  "cloud_name" => "hshome",
  "api_key" => "193372732745538",
  "api_secret" => "AfzXMSPeQL_1zMqzDKEdwgu3hHY",
  "secure" => true,
));

class App extends Controller {
  public function siteName() {
    return get_bloginfo('name');
  }

  public static function title() {
    if (is_home()) {
      if ($home = get_option('page_for_posts', true)) {
        return get_the_title($home);
      }
      return __('Latest Posts', 'sage');
    }
    if (is_archive()) {
      return get_the_archive_title();
    }
    if (is_search()) {
      return sprintf(__('Search Results for %s', 'sage'), get_search_query());
    }
    if (is_404()) {
      return __('Not Found', 'sage');
    }
    return get_the_title();
  }

  /**
   * Namespace for barba js
   * This is poorly tested...
   */
  public static function namespace () {
    if (is_front_page()) {
      return 'home';
    }
    if (is_singular()) {
      return get_post_field('post_type', get_post());
    }

    $current_page = sanitize_post($GLOBALS['wp_the_query']->get_queried_object());
    if ($current_page && \property_exists($current_page, 'post_name')) {
      return $current_page->post_name;
    }
    if ($current_page instanceof WP_Post_Type) {
      return $current_page->name;
    }
    return null;
  }

  /**
   * get the header logo html
   */
  public static function header_logo() {
    global $_wp_additional_image_sizes;
    $logo = get_field('header_logo', 'options');
    //add_image_size('header_logo', 130, 40, false);
    if (!$logo) {
      return;
    }

    $_wp_additional_image_sizes['header_logo'] = array(
      'width' => 332,
      'height' => 75,
      'crop' => false,
    );
    echo wp_get_attachment_image($logo, 'header_logo');
  }

  /**
   * get the header logo html
   */
  public static function footer_logo() {
    global $_wp_additional_image_sizes;
    $logo = get_field('footer_logo', 'options');
    //add_image_size('header_logo', 130, 40, false);
    if (!$logo) {
      return;
    }

    $_wp_additional_image_sizes['footer_logo'] = array(
      'width' => 332,
      'height' => 75,
      'crop' => false,
    );
    echo wp_get_attachment_image($logo, 'footer_logo');
  }

  /**
   *
   */
  public static function relative_url($url) {
    return str_replace(home_url(), '', $url);
  }

  /**
   * Get a responsive image using cloudinary, but fall back to wp compatible if not possible
   * @param $image_id int
   * @param $size string|array wordpress compatible size
   * @param $args
   */
  public static function image_html($image_id, $size = 'full', $args = []) {
    //TODO some check if cloudinary enabled
    //echo wp_get_attachment_image($image_id, $size);
    //$meta = wp_get_attachment_metadata($image_id);
    $filename = basename(get_attached_file($image_id));
    //echo 'Filename: ' . $filename;
    echo cl_image_tag($filename, array("width" => "auto", "crop" => "scale", "responsive" => "true", "responsive_placeholder" => "blank"));
  }

  /**
   * Get instagram posts from simple feed
   */
  public function instagram_posts() {
    if (!function_exists('simple_instagram_feed')) {
      return;
    }

    $data = simple_instagram_feed(['ig' => 'hillstreethome']);

    if ($data && isset($data['posts']) && !empty($data['posts'])) {
      return count($data['posts']) > 6 ? array_slice($data['posts'], 0, 6) : $data['posts'];
    }
    return null;
  }

  /**
   * Literally just a wrapper around get_product_addons from wc product addons
   * Just makes sure the class/function exist
   */
  public static function get_product_addons($product_id) {
    if (!class_exists('WC_Product_Addons_Helper')) {
      return array();
    }

    if (!method_exists(WC_Product_Addons_Helper, 'get_product_addons')) {
      return array();
    }

    return WC_Product_Addons_Helper::get_product_addons($product_id);
  }
}