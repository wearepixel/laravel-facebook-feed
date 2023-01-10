Laravel Facebook Feed
==============

A simple package to easily create an XML feed for Facebooks Shipping API to parse and retrieve your products on a frequent basis.

We recommend adding this to an API controller and generating it on the fly each time so you can be sure your products are always up to date within your merchant center.

## Installation

```
composer require wearepixel/laravel-facebook-feed
```

## Example
```php
use Wearepixel\LaravelFacebookFeed\LaravelFacebookFeed;

$feed = LaravelFacebookFeed::init(
	'Product Feed',
	'App product Feed',
	'https://mystore.com'
);

$feed->addItem([
	'id' => 'item_001',
	'title' => 'Blue Nikes',
	'link' => 'https://mystore.com/products/blue-nikes',
	'price' => 29.99,
	'image_link' => 'https://mystore.com/images/blue-nikes-001.jpg',
]);

return $feed->generate();
```