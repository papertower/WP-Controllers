<?php

class Attachment extends Post {
  protected static
    $post_type  = 'attachment';

	public
		$description,
		$caption,
    $link,
		$alt;

  protected function load_properties($post) {
    parent::load_properties($post);

		// Set standard content
		$this->description	=& $this->content;
		$this->caption			=& $this->excerpt;

		// Retrieve post meta
    $this->alt	      = get_post_meta($this->id, '_wp_attachment_image_alt', true);
    $this->mime_type  = get_post_mime_type($this->id);
		$this->link	      = wp_get_attachment_url($this->id);
  }

  public function file_type() {
    $type = explode('/', $this->mime_type);
    return empty($type[1]) ? '' : strtoupper($type[1]);
  }
}

?>
