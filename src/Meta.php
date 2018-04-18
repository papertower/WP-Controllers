<?php

namespace WPControllers;

/**
 * Class Meta
 * The meta class for all controllers. Provides a quick and cached way of accessing meta for objects.
 */
class Meta {

  /**
   * @var int $object_id
   * @var string $object_type
   * @var array $data
   */
  protected
    $object_id,
    $object_type,
    $data = array();

  /**
   * Meta constructor.
   *
   * @param int $id
   * @param string $type
   */
  public function __construct($id, $type) {
    $this->object_id = $id;
    $this->object_type = $type;
  }

  /**
   * @param string $name
   * @param array $arguments
   *
   * @return mixed
   */
  public function __call($name, $arguments) {
    $this->get_meta($name);

    if ( empty($arguments) ) {
      return $this->data[$name];
    }

    $function = array_shift($arguments);
    array_unshift($arguments, $this->data[$name]);

    if ( is_string($function) && method_exists($this, $function) ) {
      $function = array($this, $function);
    }

    if ( is_callable($function) ) {
      return call_user_func_array($function, $arguments);
    } else {
      return $this->data[$name];
    }
  }

  /**
   * @param string $name
   *
   * @return mixed
   */
  public function __get($name) {
    $this->get_meta($name);

    if ( is_array($this->data[$name]) ) {
      switch(count($this->data[$name])) {
        case 0: return '';
        case 1: return $this->data[$name][0];
        default: return $this->data[$name];
      }
    } else {
      return $this->data[$name];
    }
  }

  /**
   * @param string $name
   *
   * @return bool
   */
  public function __isset($name) {
    if ( !empty($this->data[$name]) ) {
      return true;
    }

    $this->get_meta($name);
    return !empty($this->data[$name]);
  }

  /**
   * @param string $name
   */
  public function __unset($name) {
    unset($this->data[$name]);
  }

  /**
   * Used to retrieve meta with a key not permitted by PHP
   * For example, if a key is 'my-field', the hyphen is not set
   * @param string $key key used in the database
   * @param string $name name to store under for future retrieval
   */
  public function store($key, $name) {
    $this->get_meta($key, $name);
  }

  /**
   * @param string $key
   * @param null $name
   * @param bool|false $single
   */
  protected function get_meta($key, $name = null, $single = false) {
    $name = is_null($name) ? $key : $name;
    if ( isset($this->data[$name]) ) return;

    switch ($this->object_type) {
      case 'post':
      case 'user':
      case 'comment':
        $this->data[$name] =  get_metadata($this->object_type, $this->object_id, $key, $single);
        break;

      case 'term':
        $this->data[$name] = get_term_meta($this->object_id, $key, $single);
        break;
    }
  }

  /**
   * @param array $value
   *
   * @return mixed|null
   */
  protected function single($value) {
    return isset($value[0]) ? $value[0] : null;
  }

  /**
   * @param $value
   *
   * @return mixed|null
   */
  protected function all($value) {
    return is_array($value) ? $value : array();
  }

  protected function controllers($values) {
    if ( is_array($values) ) {
      if ( !empty($values) ) {
        return get_post_controllers($values);
      } else {
        return array();
      }
    } else {
      return $values;
    }
  }

  /**
   * @param array $values
   *
   * @return Post|null
   */
  protected function controller($values) {
    if ( is_array($values) && !empty($values[0]) ) {
      return get_post_controller($values[0]);
    } else {
      return null;
    }
  }

  protected function date($values, $format) {
    if ( empty($values[0]) ) return '';
    return date($format, strtotime($values[0]));
  }

}
