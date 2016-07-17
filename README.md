# Dead Simple Sitemap Generator 0.1

This library will crawl all unique internal links found on a given website
up to a specified maximum page depth.

## How to Install

You can install this library with [Composer][composer]. Drop this into your `composer.json`
manifest file:

    {
        "require": {
            "gsavastano/dssg": "0.1"
        }
    }

Then run `composer install`.

## Getting Started

Here's a quick demo to crawl a website:

    <?php
    require 'vendor/autoload.php';

    // Initiate crawl
    $crawler = new \gsavastano\Dssg\Dssg;
    $crawler->startCrawl();

it will create a sitemap.xml (or whatever name you prefer)

## Setup

There are two ways to set up the crawler:

* change variables in src/sitemap.ini
* use the command line - use php example/crawl.php -h for help
 

## System Requirements

* PHP 5.3.0+

## License

MIT Public License

[composer]: http://getcomposer.org/
[psr2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md