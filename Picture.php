<?php

class Picture extends Attachment {
  protected static $post_type = 'image';

  public $sizes = array();

  public function details($size) {
    if ( !isset($this->sizes[$size]) )
      $this->sizes[$size] = wp_get_attachment_image_src($this->id, $size);

    return $this->sizes[$size];
  }

  public function src($size) {
    $details = $this->details($size);
    return $details[0];
  }

  public function width($size) {
    $details = $this->details($size);
    return $details[1];
  }

  public function height($size) {
    $details = $this->details($size);
    return $details[2];
  }

  public function is_resized($size) {
    $details = $this->details($size);
    return $details[3];
  }
};

if ( !function_exists('get_picture_controller') ) {
  function get_picture_controller($key = null, $options = array()) { return Picture::get_controller($key, $options); }
}

?>
