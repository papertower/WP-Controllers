<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 4/18/18
 * Time: 2:43 PM
 */

use \WPControllers\Post;

class PostTest extends WP_UnitTestCase {
  public function test_get_controller() {
    $post = $this->factory()->post->create_and_get();

    // Get controller by WP_Post
    $controller = Post::get_controller($post);
    $this->assertInstanceOf(Post::class, $controller);

    // Get controller by id
    $controller = Post::get_controller($post->ID);
    $this->assertInstanceOf(Post::class, $controller);

    // Get controller by slug
    $controller = Post::get_controller($post->post_name);
    $this->assertInstanceOf(Post::class, $controller);

    // Get controller by single template
    $this->go_to("/p={$post->ID}");
    $controller = Post::get_controller();
    $this->assertInstanceOf(Post::class, $controller);
    $this->assertEquals($post->ID, $controller->id);
  }

  public function test_url() {
    $post = $this->factory()->post->create_and_get();
    $controller = Post::get_controller($post);

    $this->assertSame(get_permalink($post), $controller->url());
  }

  public function test_wp_post_properties() {
    $post = $this->factory()->post->create_and_get();
    $controller = Post::get_controller($post);

    $properties = get_object_vars($post);
    foreach($properties as $key => $value) {
      $this->assertSame($value, $controller->$key, "Post controller should support the WP_Post->$key property");
    }
  }
}
