<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 4/18/18
 * Time: 2:43 PM
 */

namespace WPControllers;

class PostTest extends \WP_UnitTestCase {
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

  public function test_get_controller_post_type() {
    // Root level post
    $this->assertSame('post', Post::get_controller_post_type(Post::class));

    // Child post
    $this->assertSame('page', Post::get_controller_post_type(Page::class));

    // TODO: Test classes with no explicit post type
    // TODO: Test template classes
  }

  public function test_wp_post_properties() {
    $post = $this->factory()->post->create_and_get();
    $controller = Post::get_controller($post);

    $properties = get_object_vars($post);
    foreach($properties as $key => $value) {
      $this->assertSame($value, $controller->$key, "Post controller should support the WP_Post->$key property");
    }
  }

  public function test_url() {
    $post = $this->factory()->post->create_and_get();
    $controller = Post::get_controller($post);

    $this->assertSame(get_permalink($post), $controller->url());
  }

  public function test_archive_url() {
    $this->assertEquals(get_post_type_archive_link('post'), Post::archive_url());
  }

  public function test_author() {
    $user = $this->factory()->user->create_and_get();
    $post = $this->factory()->post->create_and_get([
      'post_author'   => $user->ID
    ]);

    $post_controller = Post::get_controller($post);
    $user_controller = $post_controller->author();

    $this->assertEquals($user->ID, $user_controller->id);
  }

  public function test_date() {
    $post = $this->factory()->post->create_and_get();
    $controller = Post::get_controller($post);

    // Local timezone
    $timestamp = strtotime($post->post_date);
    $this->assertSame($timestamp, $controller->date('timestamp'));
    $this->assertSame(date('d:m:Y', $timestamp), $controller->date('d:m:Y'));

    // GMT
    $timestamp = strtotime($post->post_date_gmt);
    $this->assertSame($timestamp, $controller->date('timestamp', true));
    $this->assertSame(date('d:m:Y', $timestamp), $controller->date('d:m:Y', true));
  }

  public function test_modified() {
    $post = $this->factory()->post->create_and_get();
    $controller = Post::get_controller($post);

    // Local timezone
    $timestamp = strtotime($post->post_modified);
    $this->assertSame($timestamp, $controller->modified('timestamp'));
    $this->assertSame(date('d:m:Y', $timestamp), $controller->modified('d:m:Y'));

    // GMT
    $timestamp = strtotime($post->post_modified_gmt);
    $this->assertSame($timestamp, $controller->modified('timestamp', true));
    $this->assertSame(date('d:m:Y', $timestamp), $controller->modified('d:m:Y', true));
  }
}
