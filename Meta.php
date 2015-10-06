<?php
class Meta {
  private
    $object_id,
    $object_type,
    $data = array();

  public function __construct($id, $type) {
    $this->object_id = $id;
    $this->object_type = $type;
  }

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

  public function __get($name) {
    $this->get_meta($name);
    return $this->data[$name];
  }

  public function __isset($name) {
    return isset($this->data[$name]);
  }

  public function __unset($name) {
    unset($this->data[$name]);
  }

  private function single($key) {
    return $this->data[$key][0];
  }

  private function all($key) {
    return $this->data[$key];
  }

  private function get_meta($key, $single = false) {
    if ( isset($this->data[$key]) ) return;

    switch ($this->object_type) {
      case 'post':
      case 'user':
      case 'comment':
        $this->data[$key] =  get_metadata($this->object_type, $this->object_id, $key, $single);
        break;

      case 'term':
        $this->data[$key] = get_term_meta($this->object_id, $key, $single);
        break;
    }
  }
}
?>
