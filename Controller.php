<?php
/**
 * The base class for handling all controllers
 *
 * @version 0.7.0
 * @author Jason Adams <jason.the.adams@gmail.com>
 */
abstract class Controller {
  public $meta;

  abstract public function meta();

  /**
   * Retrieves all the meta for the object and stores it in $this->meta
   * Also checks key for prefix (e.g. {prefix}key) and processes meta accordingly
   * Lastly, converts all hyphens to underscores, to fix a guilty habit
   *
   * @since 0.7.0
   * @param array $meta result of get_post_custom of equivalent
   */
  protected function _meta($meta) {
    $this->meta = new StdClass();
    $matches = null;

    foreach($meta as $key => $values) {
      // Retrieve prefix in {prefix} if used
      if ( $key[0] === '{' && preg_match('/^(?:\{(.*)\})?(.*)/', $key, $matches) ) {
        $prefix = $matches[1];
        $key = str_replace('-', '_', $matches[2]);
      } else {
        $prefix = '';
        $key = str_replace('-', '_', $key);
      }

      switch($prefix) {
        case 'array':
          // Always return an array
          $this->meta->$key = array_map('maybe_unserialize', $values);
          break;

        case 'pg':
          // Piklist group
          $parsed_array = piklist::object_format(maybe_unserialize($values[0]));
          $this->meta->$key = $parsed_array[0];
          break;

        case 'pag':
          // Piklist add-more group
          $this->meta->$key = piklist::object_format(maybe_unserialize($values[0]));
          break;

        case 'autop':
          // Apply wpautop function
          $this->meta->$key = wpautop($values[0]);
          break;

        case 'antispam':
          // Apply antispambot function
          $this->meta->$key = antispambot($values[0]);

        default:
          $this->meta->$key = count($values) === 1
            ? maybe_unserialize($values[0])
            : array_map('maybe_unserialize', $values);
      }
    }
    
    return $this->meta;
  }
}
?>
