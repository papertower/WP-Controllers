<?php

class Attachment extends PostController {
  protected static
    $post_type  = 'attachment';

  public
    $is_image = false,
    $description,
    $caption;

  protected function __construct($post) {
    parent::__construct($post);

    // Set standard content
    $this->description  =& $this->content;
    $this->caption      =& $this->excerpt;
  }

  public function alt() {
    return isset($this->_alt) ? $this->_alt
      : $this->_alt = get_post_meta($this->id, '_wp_attachment_image_alt', true);
  }

  public function mime_type() {
    return isset($this->_mime_type) ? $this->_mime_type
      : $this->_mime_type = get_post_mime_type($this->id);
  }

  public function link() {
    return isset($this->_link) ? $this->_link
      : $this->_link = wp_get_attachment_url($this->id);
  }

  public function file_type() {
    $type = explode('/', $this->mime_type());
    return empty($type[1]) ? '' : strtoupper($type[1]);
  }
}

?>
