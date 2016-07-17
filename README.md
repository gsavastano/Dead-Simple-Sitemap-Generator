# Dead Simple Sitemap Generator 0.1

This library will crawl all unique internal links found on a given website
up to a specified maximum page depth.

## How to Install

You can install this library with [Composer][composer]. Drop this into your `composer.json`
manifest file:

    {
        "require": {
            "gsavastano/dssg": "dev-master"
        }
    }

Then run `composer install`.

## Getting Started

Create a simple PHP File as follows

    <?php
	
	require 'vendor/autoload.php';
	use gsavastano\dssg\dssg;
	
	$crawler = new Dssg;
	$crawler->startCrawl();
	
then lunch it from the command line

	php yourfile.php -ahttps -ugoogle.it -fweekly -p0.5 -ssitemap.xml

to see the options

	php yourfile.php -h

please consider: the script WILL timeout with big/huge websites, if that happens try increasing your memory_limit

	<?php
	ini_set('memory_limit', '2048M');



## Setup

There are two ways to set up the crawler:

* change variables in src/sitemap.ini
* use the command line - use php example/crawl.php -h for help
 


## Limitations
- requires a lot of memory for big/huge sites
- doesn't always parse correctly all URL parameters (ex: ?#&)
- all URL indexed have the same priority and frequency
- it doesn't validate config.ini values, only inputs from command line and the start URL
- dd
- s

## System Requirements

* PHP 5.3.0+

## License

MIT Public License

[composer]: http://getcomposer.org/