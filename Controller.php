<?php
/**
 * The base class for handling all controllers
 *
 * @version 1.0.0
 * @author Jason Adams <jason.the.adams@gmail.com>
 */
abstract class Controller {
  public $meta;

  abstract public function meta();
//   abstract static public function get_controller($key, $options);

  protected function _meta($meta) {
    $this->meta = new StdClass();
    $matches = null;

    foreach($meta as $key => $values) {
      preg_match('/^(?:\{(.*)\})?(.*)/', $key, $matches);

      $prefix = $matches[1];
      $key = str_replace('-', '_', $matches[2]);

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
  }
}
?>
