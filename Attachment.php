<?php

/**
 * Class Attachment
 */
class Attachment extends PostController {

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
    $description,
    $caption,
    $alt,
    $mime_type,
    $link;

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

    // Retrieve post meta
    $this->alt        = get_post_meta($this->id, '_wp_attachment_image_alt', true);
    $this->mime_type  = get_post_mime_type($this->id);
    $this->link       = wp_get_attachment_url($this->id);
  }

  /**
   * @return string
   */
  public function file_type() {
    $type = explode('/', $this->mime_type);
    return empty($type[1]) ? '' : strtoupper($type[1]);
  }

}


