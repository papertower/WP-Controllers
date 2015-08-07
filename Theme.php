<?php
class Theme {
  private static $_settings;

  private static function get_settings() {
    return isset(self::$_settings) ? self::$_settings
      : self::$_settings = get_option('general-theme-options');
  }

  public static function header_image($image_id, $size, $return_url = true) {
    // Return picture if id is valid
    $Image = null;
    if ( is_numeric($image_id) ) {
      $Image = get_picture_controller($image_id);
      if ( is_wp_error($Image) ) $Image = null;
    }

    if ( empty($Image) ) {
    $settings = self::get_settings();
    $Image = is_numeric($settings['header-background'])
      ? get_picture_controller($settings['header-background'])
      : null;
    }
    
    if ( $Image ) {
      return $return_url ? $Image->src($size) : $Image;
    }
  }

  public static function header_color() {
    $settings = self::get_settings();
    return isset($settings['header-color']) ? $settings['header-color'] : 'blue';
  }
}
?>
