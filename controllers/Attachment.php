<?php

/**
 * Class Attachment
 */
class Attachment extends Post {

  /**
   * @var string $post_type
   */
  public static
    $controller_post_type  = 'attachment';

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
    $this->mime_type    =& $post->post_mime_type;
  }

  public function alt() {
    return $this->meta->_wp_attachment_image_alt;
  }

  public function mime_type() {
    return $this->mime_type;
  }

  public function link() {
    return isset($this->_link) ? $this->_link
      : $this->_link = wp_get_attachment_url($this->id);
  }

  public function path() {
    return isset($this->_path) ? $this->_path
      : $this->_path = get_attached_file($this->id);
  }

  public function file_size() {
    return isset($this->_file_size) ? $this->_file_size
      : filesize($this->path());
  }

  /**
   * @return string
   */
  public function file_type() {
    $type = explode('/', $this->mime_type);
    return empty($type[1]) ? '' : strtoupper($type[1]);
  }

}
