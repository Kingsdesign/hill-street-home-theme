<?php

namespace App;

use Roots\Sage\Assets\JsonManifest;
use Roots\Sage\Container;
use Roots\Sage\Template\Blade;
use Roots\Sage\Template\BladeProvider;

/**
 * Theme assets
 */
add_action('wp_enqueue_scripts', function () {
  global $post;
  wp_enqueue_style('sage/main.css', asset_path('styles/main.css'), false, null);
  wp_enqueue_script('sage/main.js', asset_path('scripts/main.js'), ['jquery'], null, true);
  $main_data = ['ajax_url' => admin_url('admin-ajax.php')];
  if (is_product()) {
    $main_data['single_product'] = [
      'hide_addons' => (has_term('edible', 'product_cat', $post) || has_term('fresh', 'product_cat', $post)) && get_post_field('slug', $post) !== 'gift-card',
      'product_id' => $post->ID,
    ];

    wp_enqueue_script('sage/single-product.js', asset_path('scripts/single-product.js'), ['jquery'], null, true);
  }
  if (class_exists('\WC_OrderByLocation')) {
    $main_data['cookie_name'] = \WC_OrderByLocation::$location_var_name;
  }
  wp_localize_script('sage/main.js', 'main_data', $main_data);

  /**
   * Store chooser needs some logic
   */
  //Js-cookie is provided by WC
  if (class_exists('\WC_OrderByLocation')) {
    $terms = array_map(function ($term) {
      return ['id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug];
    },
      get_terms(array(
        'taxonomy' => 'location',
        'hide_empty' => false,
      ))
    );

    if (class_exists('\WC_OrderByLocation')) {
      wp_enqueue_script('sage/store-chooser.js', asset_path('scripts/store-chooser.js'), ['jquery', 'js-cookie'], null, true);
      wp_localize_script('sage/store-chooser.js', 'store_chooser_data', array(
        'locations' => $terms,
        'cookie_name' => \WC_OrderByLocation::$location_var_name,
        'ajax_url' => admin_url('admin-ajax.php'),
      ));
    }
  }

  if (is_single() && comments_open() && get_option('thread_comments')) {
    wp_enqueue_script('comment-reply');
  }
}, 100);

/**
 * Add Fonts
 */
add_action('wp_enqueue_scripts', function () {
  //For some reason the combined url doesnt work atm.
  //TODO invesigate
  wp_enqueue_style('google-fonts-montserrat', 'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,700;1,400&display=swap', false, null);
  wp_enqueue_style('google-fonts-playfair', 'https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap', false, null);
  ///wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,700;1,400&family=Playfair+Display&display=swap', false, 'keep_version');
  //wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,700;1,400|Playfair+Display&display=swap', false, null);
});
//Preconnect and dns-prefetch fonts
add_action('wp_head', function () {
  /*<link rel="dns-prefetch" href="//fonts.googleapis.com">*/

  ?>
<link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
<?php
});

/**
 * Theme setup
 */
add_action('after_setup_theme', function () {
  /**
   * Enable features from Soil when plugin is activated
   * @link https://roots.io/plugins/soil/
   */
  add_theme_support('soil-clean-up');
  //add_theme_support('soil-jquery-cdn');
  add_theme_support('soil-nav-walker');
  add_theme_support('soil-nice-search');
  add_theme_support('soil-relative-urls');
//add_theme_support('soil-disable-rest-api');
  //add_theme_support('soil-disable-asset-versioning');
  add_theme_support('soil-disable-trackbacks');
//add_theme_support('soil-google-analytics', 'UA-XXXXX-Y');
  add_theme_support('soil-js-to-footer');

  //Custom asset versioning in below

  /**
   * Enable plugins to manage the document title
   * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
   */
  add_theme_support('title-tag');

  /**
   * Register navigation menus
   * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
   */
  register_nav_menus([
    'primary_navigation' => __('Primary Navigation', 'sage'),
    'footer_navigation' => __('Footer Navigation', 'sage'),
    'legal_navigation' => __('Legal  Navigation', 'sage'),
  ]);

  /**
   * Enable post thumbnails
   * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
   */
  add_theme_support('post-thumbnails');

  /**
   * Enable HTML5 markup support
   * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
   */
  add_theme_support('html5', ['caption', 'comment-form', 'comment-list', 'gallery', 'search-form']);

  /**
   * Enable selective refresh for widgets in customizer
   * @link https://developer.wordpress.org/themes/advanced-topics/customizer-api/#theme-support-in-sidebars
   */
  add_theme_support('customize-selective-refresh-widgets');

  /**
   * Use main stylesheet for visual editor
   * @see resources/assets/styles/layouts/_tinymce.scss
   */
  add_editor_style(asset_path('styles/main.css'));

  /**
   * Add Woocommerce Support */
  add_theme_support('woocommerce', array(
    'thumbnail_image_width' => 415,
  ));
  /*, array(
  'thumbnail_image_width' => 150,
  'single_image_width'    => 300,

  'product_grid'          => array(
  'default_rows'    => 3,
  'min_rows'        => 2,
  'max_rows'        => 8,
  'default_columns' => 4,
  'min_columns'     => 2,
  'max_columns'     => 5,
  ),
  ) );*/

  //Disable woocommerce stylesheets
  add_filter('woocommerce_enqueue_styles', '__return_empty_array');
}, 20);

/**
 * Register sidebars
 */
add_action('widgets_init', function () {
  $config = [
    'before_widget' => '<section class="widget %1$s %2$s">',
    'after_widget' => '</section>',
    'before_title' => '<h3>',
    'after_title' => '</h3>',
  ];
  register_sidebar([
    'name' => __('Primary', 'sage'),
    'id' => 'sidebar-primary',
  ] + $config);
  register_sidebar([
    'name' => __('Footer', 'sage'),
    'id' => 'sidebar-footer',
  ] + $config);
});

/**
 * Updates the `$post` variable on each iteration of the loop.
 * Note: updated value is only available for subsequently loaded views, such as partials
 */
add_action('the_post', function ($post) {
  sage('blade')->share('post', $post);
});

/**
 * Setup Sage options
 */
add_action('after_setup_theme', function () {
  /**
   * Add JsonManifest to Sage container
   */
  sage()->singleton('sage.assets', function () {
    return new JsonManifest(config('assets.manifest'), config('assets.uri'));
  });

  /**
   * Add Blade to Sage container
   */
  sage()->singleton('sage.blade', function (Container $app) {
    $cachePath = config('view.compiled');
    if (!file_exists($cachePath)) {
      wp_mkdir_p($cachePath);
    }
    (new BladeProvider($app))->register();
    return new Blade($app['view']);
  });

  /**
   * Create @asset() Blade directive
   */
  sage("blade")->compiler()->directive('asset', function ($asset) {
    return "<?= " . __NAMESPACE__ . "\\asset_path({$asset}); ?>";
  });

  sage("blade")->compiler()->directive("svg", function ($svgName) {
    //$svgContent = @file_get_contents(asset_path("svg/" . $svgName . ".svg"));
    $svgContent = @file_get_contents(get_template_directory() . "/assets/svg/" . $svgName . ".svg");
    if (empty($svgContent)) {
      return null;
    }

    $svgContent = str_replace("<svg", "<svg class=\"icon\" ", $svgContent);

    return $svgContent;

  });

  /**
   * Register custom image sizes
   */
  add_image_size('wc_single_lqip', 220, 220, true);
  add_image_size('wc_single_large', 1000, 1000, true);
  add_image_size('carousel_slide', 2000, 1125, true);
});

/**
 * Add theme options ACF page
 */
if (function_exists('acf_add_options_page')) {

  acf_add_options_page(array(
    'page_title' => 'Theme General Settings',
    'menu_title' => 'Hill Street Settings',
    'menu_slug' => 'theme-general-settings',
    'capability' => 'edit_posts',
    'redirect' => false,
  ));

  acf_add_options_sub_page(array(
    'page_title' => 'Homepage',
    'menu_title' => 'Homepage',
    'parent_slug' => 'theme-general-settings',
  ));

  acf_add_options_sub_page(array(
    'page_title' => 'Theme Header Settings',
    'menu_title' => 'Header',
    'parent_slug' => 'theme-general-settings',
  ));

  acf_add_options_sub_page(array(
    'page_title' => 'Theme Footer Settings',
    'menu_title' => 'Footer',
    'parent_slug' => 'theme-general-settings',
  ));

  acf_add_options_sub_page(array(
    'page_title' => 'Location Settings',
    'menu_title' => 'Locations',
    'parent_slug' => 'theme-general-settings',
  ));

  acf_add_options_sub_page(array(
    'page_title' => 'Delivery Settings',
    'menu_title' => 'Delivery',
    'parent_slug' => 'theme-general-settings',
  ));

  acf_add_options_sub_page(array(
    'page_title' => 'Order Settings',
    'menu_title' => 'Order Settings',
    'parent_slug' => 'theme-general-settings',
  ));

  /*acf_add_options_sub_page(array(
'page_title' => 'Theme Footer Settings',
'menu_title' => 'Footer',
'parent_slug' => 'theme-general-settings',
));*/

}

/**
 * Cloudinary support
 */
/*add_filter( 'wp_get_attachment_image_src', function($image, $attachment_id, $size, $icon) {
if(in_array($size['crop'])) {

}
$image = array($src, $width, $height);
}, 10, 4 );*/

/**
 * Replace jQuery
 */
add_action('wp_enqueue_scripts', function () {
  global $wp_scripts;
  //$wp_scripts->registered['jquery-core']->src = get_theme_file_uri() . '/cash/dist/cash.js';
  if (!is_admin() && (function_exists('is_checkout') && !is_checkout() || !function_exists('is_checkout'))) {
    $wp_scripts->registered['jquery-core']->src = get_theme_file_uri() . '/jquery-3.5.0.min.js';
    $wp_scripts->registered['jquery']->deps = ['jquery-core'];
    wp_deregister_script('jquery-migrate');
  }
  //$wp_scripts->registered['jquery-core']->src = get_theme_file_uri() . '/cash.js';

});

/**
 * Custom asset verisioning
 * based on soil
 */
function remove_script_version($src, $handle) {
  //global $wp_styles;
  if (strpos($src, '?ver=')) {
    //print_r($wp_styles['enqueued'][])
    $src = remove_query_arg('ver', $src);
  }

  return $src;
}
add_filter('script_loader_src', __NAMESPACE__ . '\\remove_script_version', 15, 2);
add_filter('style_loader_src', __NAMESPACE__ . '\\remove_script_version', 15, 2);

/**
 * Remove unwanted scripts/styles
 */
add_action('wp_enqueue_scripts', function () {
  //Variation swatches
  wp_dequeue_style('rtwpvs');
  wp_deregister_style('rtwpvs');

  //Addons
  wp_dequeue_style('woocommerce-addons-css');
  wp_deregister_style('woocommerce-addons-css');

  //global $wp_styles;
  //print_r($wp_styles);
}, 99);