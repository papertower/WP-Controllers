<?php

/**
 * Class Picture
 */
class Picture extends Attachment {
  /**
   * @var string $post_type
   */
  protected static $post_type = 'image';

  /**
   * @var array $sizes
   * @var boolean $is_image
   */
  public
    $sizes = array(),
    $is_image = true;

  /**
   * @param string $size
   *
   * @return mixed
   */
  public function details($size) {
    if ( !isset($this->sizes[$size]) )
      $this->sizes[$size] = wp_get_attachment_image_src($this->id, $size);

    return $this->sizes[$size];
  }

  /**
   * src
   * Returns the image src attribute for a given size. If $wide_size is provided the returned src
   * will depend on the original dimensions of the image. If the image is square then either
   * $square_size will be used or $size by default.
   *
   * @param string $size
   * @param string $wide_size
   * @param string $square_size
   *
   * @return string
   */
  public function src($size, $wide_size = null, $square_size = null) {
    if ( !$wide_size ) {
      $details = $this->details($size);
      return $details[0];
    } else {
      $width = $this->width('full');
      $height = $this->height('full');

      if ( $width > $height ) {
        $detials = $this->details($wide_size);
      } elseif ( $height > $width ) {
        $details = $this->details($size);
      } else {
        $details = $square_size ? $this->details($square_size) : $this->details($size);
      }

      return $details[0];
    }
  }

  /**
   * srcset.
   * Accepts an associative array where the key is the qualifier (e.g. 200w) and the value is the
   * size to display. If an array is passed as the value then it follows the same parameters as the
   * src function.
   *
   * A string can also be provided that's parsed into the usable parts:
   *    200w->small|600w->medium|1200->tall-large,tall-wide
   *
   * @param array|string $sizes associative array of sizes
   *
   * @return string
   */
  public function srcset($sizes) {
    if ( is_string($sizes) ) {
      $sizes = explode('|', $sizes);
      $parsed_sizes = array();
      foreach($sizes as $pair) {
        list($qualifier, $size) = explode('->', $pair);
        $parsed_sizes[trim($qualifier)] = explode(',', trim($size));
      }
      $sizes = $parsed_sizes;
    }

    $list = array();
    foreach($sizes as $qualifier => $size) {
      $src = is_array($size) ? call_user_func_array(array($this, 'src'), $size) : $this->src($size);
      $list[] = "$src $qualifier";
    }

    return implode(', ', $list);
  }

  /**
   * @param string $size
   *
   * @return int
   */
  public function width($size) {
    $details = $this->details($size);
    return $details[1];
  }

  /**
   * @param string $size
   *
   * @return int
   */
  public function height($size) {
    $details = $this->details($size);
    return $details[2];
  }

  /**
   * @param string $size
   *
   * @return bool
   */
  public function is_resized($size) {
    $details = $this->details($size);
    return $details[3];
  }
};
