<?php

declare(strict_types=1);

use Wearepixel\LaravelFacebookFeed\Exceptions\MissingRequiredFieldException;
use Wearepixel\LaravelFacebookFeed\LaravelFacebookFeed;

function validProduct(array $overrides = []): array
{
    return array_merge([
        'id'         => 'prod-1',
        'link'       => 'https://example.com/product',
        'title'      => 'Test Product',
        'price'      => '29.99 AUD',
        'image_link' => 'https://example.com/image.jpg',
    ], $overrides);
}

describe('init', function () {
    it('stores title, description, and link', function () {
        $feed = LaravelFacebookFeed::init('My Store', 'My Desc', 'https://example.com');
        expect($feed->title)->toBe('My Store')
            ->and($feed->description)->toBe('My Desc')
            ->and($feed->link)->toBe('https://example.com');
    });

    it('defaults to empty strings', function () {
        $feed = LaravelFacebookFeed::init();
        expect($feed->title)->toBe('')
            ->and($feed->description)->toBe('')
            ->and($feed->link)->toBe('');
    });
});

describe('addItem', function () {
    it('accepts a valid item without throwing', function () {
        $feed = LaravelFacebookFeed::init('', '', '');
        $feed->addItem(validProduct());
        expect($feed->toXml())->toContain('prod-1');
    });

    it('throws for missing required field', function (string $field) {
        $product = validProduct();
        unset($product[$field]);
        expect(fn () => LaravelFacebookFeed::init()->addItem($product))
            ->toThrow(MissingRequiredFieldException::class);
    })->with(['id', 'link', 'title', 'price', 'image_link']);

    it('throws with a message containing the field name', function () {
        $product = validProduct();
        unset($product['price']);
        expect(fn () => LaravelFacebookFeed::init()->addItem($product))
            ->toThrow(MissingRequiredFieldException::class, 'price');
    });
});

describe('toXml', function () {
    it('returns a string', function () {
        $feed = LaravelFacebookFeed::init('', '', '');
        expect($feed->toXml())->toBeString();
    });

    it('contains the RSS xmlns:g attribute', function () {
        $feed = LaravelFacebookFeed::init('', '', '');
        expect($feed->toXml())->toContain('xmlns:g="http://base.google.com/ns/1.0"');
    });

    it('contains the RSS version attribute', function () {
        $feed = LaravelFacebookFeed::init('', '', '');
        expect($feed->toXml())->toContain('version="2.0"');
    });

    it('includes the channel title, description and link', function () {
        $feed = LaravelFacebookFeed::init('My Store', 'My Desc', 'https://example.com');
        $xml = $feed->toXml();
        expect($xml)
            ->toContain('<title>My Store</title>')
            ->toContain('<description>My Desc</description>')
            ->toContain('<link>https://example.com</link>');
    });

    it('includes product data', function () {
        $feed = LaravelFacebookFeed::init('', '', '');
        $feed->addItem(validProduct());
        $xml = $feed->toXml();
        expect($xml)
            ->toContain('<id>prod-1</id>')
            ->toContain('<title>Test Product</title>');
    });

    it('wraps products in item tags', function () {
        $feed = LaravelFacebookFeed::init('', '', '');
        $feed->addItem(validProduct());
        expect($feed->toXml())
            ->toContain('<item>')
            ->toContain('</item>');
    });

    it('handles multiple products', function () {
        $feed = LaravelFacebookFeed::init('', '', '');
        $feed->addItem(validProduct(['id' => 'prod-1']));
        $feed->addItem(validProduct(['id' => 'prod-2']));
        $xml = $feed->toXml();
        expect($xml)
            ->toContain('prod-1')
            ->toContain('prod-2');
        expect(substr_count($xml, '<item>'))->toBe(2);
    });

    it('includes product price and image_link fields', function () {
        $feed = LaravelFacebookFeed::init('', '', '');
        $feed->addItem(validProduct());
        $xml = $feed->toXml();
        expect($xml)
            ->toContain('<price>29.99 AUD</price>')
            ->toContain('<image_link>https://example.com/image.jpg</image_link>');
    });

    it('generates valid xml with no products', function () {
        $feed = LaravelFacebookFeed::init('My Store', 'My Desc', 'https://example.com');
        $xml = $feed->toXml();
        expect($xml)
            ->toBeString()
            ->toContain('<title>My Store</title>')
            ->not->toContain('<item>');
    });

    it('includes the xml declaration', function () {
        $feed = LaravelFacebookFeed::init('', '', '');
        expect($feed->toXml())->toStartWith('<?xml');
    });

    it('generates parseable xml', function () {
        $feed = LaravelFacebookFeed::init('Store', 'Desc', 'https://example.com');
        $feed->addItem(validProduct());
        $result = simplexml_load_string($feed->toXml());
        expect($result)->not->toBeFalse();
    });
});

describe('generate', function () {
    it('returns an HTTP 200 response', function () {
        $feed = LaravelFacebookFeed::init('', '', '');
        expect($feed->generate()->getStatusCode())->toBe(200);
    });

    it('sets the content type to application/rss+xml', function () {
        $feed = LaravelFacebookFeed::init('', '', '');
        expect($feed->generate()->headers->get('Content-Type'))->toContain('application/rss+xml');
    });

    it('returns the xml in the response body', function () {
        $feed = LaravelFacebookFeed::init('Store', 'Desc', 'https://example.com');
        $feed->addItem(validProduct());
        expect($feed->generate()->getContent())->toBe($feed->toXml());
    });
});
