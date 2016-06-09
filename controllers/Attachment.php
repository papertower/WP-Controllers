<?php

/**
 * Class Attachment
 */
class Attachment extends Post {

  /**
   * @var string $post_type
   */
  protected static
    $post_type  = 'attachment';

  /**
   * @var string $description
   * @var string $caption
   * @var string $alt
   * @var string $mime_type
   * @var string $link
   */
  public
    $is_image = false,
    $description,
    $caption;

  /**
   * Attachment constructor.
   *
   * @param WP_Post $post
   */
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

  /**
   * @return string
   */
  public function file_type() {
    $type = explode('/', $this->mime_type());
    return empty($type[1]) ? '' : strtoupper($type[1]);
  }

}
