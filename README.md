# Dead Simple Sitemap Generator 0.1

This library will crawl all unique internal links found on a given website.

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
	
then launch it from the command line

	php yourfile.php -uexample.com -ahttps -p0.2 -fdaily -sexample.xml
to see the options

	php yourfile.php -h

please consider: the script WILL timeout with big/huge websites, if that happens try increasing your memory_limit

	<?php
	ini_set('memory_limit', '2048M');


## Limitations
- requires a lot of memory for big/huge sites
- doesn't always parse correctly all URL parameters (ex: ?#&)
- all URL indexed have the same priority and frequency
- it does not index assets
- it does not index links pointing to external websites
- it does not index subdomains
- it is not possible to set default values 
- it has bugs, I'm sure of it.
- test coverage is....basic 
- far from being PSR-[1-x] compliant

## System Requirements

* PHP 5.3.0+

## License

MIT Public License

[composer]: http://getcomposer.org/