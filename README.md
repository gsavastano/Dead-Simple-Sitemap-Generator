# Dead Simple Sitemap Generator 0.4

This script will crawl all unique internal links found on a given website. 

## How to Install

Drop this into your `composer.json`
manifest file:

    {
        "require": {
            "gsavastano/dssg": "0.4"
        }
    }

Then run `composer install`.

## Setup

[optional] create a config file as follows

	{
		"protocol": "http",
		"url": "example.com",
		"extension": [
			".htm",
			".html",
			".php",
			"/",
			".aspx",
			".asp"
		],
		"filename": "sitemap.xml",
		"priority": "0.3",
		"frequency": "daily"
	}

you can use the command line to ovveride all parameters except the extensions list

if no config file is provided, the following list of extensions will be scanned:

- .htm
- .html
- .php
- .asp
- .aspx
- /

## Getting Started

Create a simple PHP file

	#!/usr/bin/env php
    <?php
	
	require 'vendor/autoload.php';
	use gsavastano\Dssg\Crawl;
	
	$crawler = new Crawl;
	
	//optional if passing params by CLI
	$crawler->loadConfig(__DIR__.'/config.json');
	
	$crawler->startCrawl();

or use the example provided.
	
then launch it from the command line

	php yourfile.php -uexample.com -ahttps -p0.2 -fdaily -sexample.xml

to check if you set the right values

	php yourfile.php -uexample.com -ahttps -p0.2 -fdaily -sexample.xml -c

to see the options

	php yourfile.php -h

please consider: the script WILL timeout with big/huge websites, if that happens try increasing your memory_limit

	ini_set('memory_limit', '2048M');
	

## Limitations

- Url Validation relies on http code 200, if your ISP has a curtesy page for inexistent domains, the you'll get a false positive
- it is not possible to specify extensions list with CLI
- doesn't always parse correctly all URL parameters (ex: ?#&)
- all URL indexed have the same priority and frequency
- it does not index assets
- it does not index links pointing to external websites
- it does not index subdomains
- basic test coverage

## System Requirements

* PHP 5.3.0+

## License

MIT Public License