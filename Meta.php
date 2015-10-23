<?php

/**
 * Class Meta
 */
class Meta {

  /**
   * @var int $object_id
   * @var string $object_type
   * @var array $data
   */
  private
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

    return is_array($this->data[$name]) && count($this->data[$name]) === 1
      ? $this->data[$name][0]
      : $this->data[$name];
  }

  /**
   * @param string $name
   *
   * @return bool
   */
  public function __isset($name) {
    return isset($this->data[$name]);
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
   * @return mixed
   */
  public function store($key, $name) {
    $this->get_meta($key, $name);
  }

  /**
   * @param string $key
   * @param null $name
   * @param bool|false $single
   */
  private function get_meta($key, $name = null, $single = false) {
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
  private function single($value) {
    return isset($value[0]) ? $value[0] : null;
  }

  /**
   * @param string $key
   *
   * @return mixed|null
   */
  private function all($key) {
    return $this->data[$key];
  }

  /**
   * @param array $values
   *
   * @return array
   */
  private function images($values) {
    if ( is_array($values) ) {
      return get_picture_controllers($values);
    } else {
      return $values;
    }
  }

  /**
   * @param array $values
   *
   * @return PostController
   */
  private function image($values) {
    if ( is_array($values) && isset($values[0]) ) {
      return get_picture_controller($values[0]);
    } else {
      return $values;
    }
  }

}

