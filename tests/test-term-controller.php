<?php

namespace WPControllers;

class TermTest extends \WP_UnitTestCase {
  public function test_url() {
    $term = $this->factory()->term->create_and_get();
    $controller = Term::get_controller($term);

    $this->assertSame(get_term_link($term), $controller->url());
  }

  public function test_parent() {
    $parent = $this->factory()->term->create_and_get();
    $child = $this->factory()->term->create_and_get([
      'parent'  => $parent->term_id
    ]);

    $controller = Term::get_controller($child);
    $parent_controller = $controller->parent();

    $this->assertSame($parent->term_id, $parent_controller->id);
  }

  public function test_children() {
    $parent = $this->factory()->term->create_and_get();
    $children = $this->factory()->term->create_many(2, [
      'parent'  => $parent->term_id
    ]);

    $controller = Term::get_controller($parent);
    $children = $controller->children();

    $this->assertSame($children, wp_list_pluck($controller->children(), 'term_id'));
  }
}
