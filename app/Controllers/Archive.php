<?php

namespace App\Controllers;

use Sober\Controller\Controller;

class Archive extends Controller {

  public static function hasGallery() {
// get the current taxonomy term
    $term = get_queried_object();
    if (!$term) {
      return;
    }

    $gallery = get_field('category_gallery', $term);
    return !empty($gallery);
  }

  public static function carousel() {
    // get the current taxonomy term
    $term = get_queried_object();
    if (!$term) {
      return;
    }

    $gallery = get_field('category_gallery', $term);
    //return print_r($gallery, true);
    if (empty($gallery)) {
      return;
    }

    return \App\template('partials.carousel', array('slides' => $gallery, 'arrows' => true));
  }
}
