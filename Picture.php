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
   */
  public $sizes = array();

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
   * @param string $size
   *
   * @return string
   */
  public function src($size) {
    $details = $this->details($size);
    return $details[0];
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

