## WordPress development for the [OOP](https://en.wikipedia.org/wiki/Object-oriented_programming) Programmer
#### So what's the story?
WordPress is an excellent framework. It's secure, robust, extensible, and is fanatical about legacy support. What it's not, however, is strongly written using modern techniques. Please understand, that's not a sleight at the expense of the hard-working contributors. A lot of the code in WordPress was written years ago for versions of PHP that preceded the mass amounts of improvements made in PHP 5.3.x. The other side of it is that it's catered towards non-developers. That is, folks who want to make a website, and are willing to learn a little PHP, but have no intention of learning more than they need to for their site. And that's ok! But, for those who do want to develop in a stronger, more OOP manner, it can be a drag.

#### Enter WP Controllers!
The WP Controllers were developed by a developer for developers. It's an attempt to take the base objects in WordPress (posts, terms, users, etc.) and give a class for each one. Instead of memorizing a lot of obscure functions, you simply handle the object and use all its provided functions. Not only does it simplify things, but it also speeds up development. A lot.

#### Magic sucks
OK, magic is fun, but not when developing. At times something working, but not knowing why, can be just as frustrating as a bug. Gah! WordPress, unfortunately, in the name of being friendly for non-developers, does a lot of magic. For example, when inside "the loop" of a wp_query, you're meant to use all these crazy functions that iterate the loop, grab the post in the loop, display specific things based on the current iteration of the loop, etc.. Wow! What ever happened to a foreach loop!?

Ultimately, WordPress is a database-driven PHP framework. Nothing more, nothing less. The database is MySQL, and it operates using SQL. You can interact with the information like any other database, and the framework itself can be extended and amended like any other framework. No magic. The goal should be to be able to get all the benefits of the WordPress framework, without sacrificing the natural (and growing) capabilities of PHP and SQL.

#### In a nutshell
These classes make it possible to feel like you're working in PHP while enjoying all the benefits of WordPress. You lose nothing by using them, and it doesn't break *anything* in Wordpress. You're free to use as much or as little of these as you'd like. If you are (or aspire to be) a PHP developer, then you'll probably really like using this; if not, it may overwhelm you, and that's no problem.

#### How to use it
##### Include it
Currently, simply download and drop this into your plugin or theme, then from your plugin file or theme functions.php, include the autoload.php file. So, assuming you dropped the controllers directory right inside your theme, you would add the following line to your functions.php:
```
include 'controllers/autoload.php';
```

##### Get a controller
Easy as that. Now, to get a controller (e.g. post), use the `get_post_controller` function. Simply pass a post id, slug, or object. Don't worry about type casting, it will figure itself out. Or, if you're inside a post single template (or page), just call
```
$Post = get_post_controller()
```
with no arguments and it will return the controller for the active post.

##### Use the controller
Once you have the controller, all the standard properties will be available minus the 'post_' prefix. So post_type, for example, would be
```
$Post->type
```
Meta is accessed via the Meta class, which is automatically added to every controller. You can retrieve single or all values and apply functions to the results

```
// Retrieves single meta
$Post->meta->sub_title

// All meta or empty array
$Post->meta->all_movies('all')

// The controllers for every value
$Post->meta->related_posts('controllers')
```

##### Familiarize yourself
The best thing to do from here would be to look through the controllers and check out the available functions. There's some pretty cool ones and they keep being added. For example,

```
$Post->terms($taxonomies)
```

returns all the term controllers for that post. Another one is

```
$Post->excerpt(50)
```

which would return either all the excerpt (if used and has actual content) or the first 50 (or however many you want) **words** of the content, with all the shortcodes and tags stripped.

#### Get Involved
Have an idea? Make a pull request!

Have a problem or question? Make an issue!

This isn't just to make WordPress more object-oriented, it's also to make development faster, less buggy, and more efficient. It's a fantastic way to fulfill the [DRY](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself) principal.
