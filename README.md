# Dead Simple Sitemap Generator v.0.1

This is a simple and small script to create a XML sitemap for Google and other search engines. 
It has many limitations, see below

Sitemap format: [http://www.sitemaps.org/protocol.html](http://www.sitemaps.org/protocol.html)

##Features
 - Crawls webpages
 - Generates seperate XML file which gets updated every time the script gets executed
 - command line options to ovveride default settings and config.ini file for static variables

## Usage
 - Configure by modifing sitemap.ini or with command line (Use -h to get list of options)
    - Select URL to crawl
    - Select the file to which the sitemap will be saved
    - Select priority and frequency

CLI command to create the XML file: `php sitemap.php [OPTIONS]`


Included scripts:

 - [PHP Simple HTML DOM Parser](http://simplehtmldom.sourceforge.net/) - A HTML DOM parser written in PHP5+ let you manipulate HTML in a very easy way!.


## Limitations
- requires a lot of memory for big/huge sites
- doesn't always parse correctly all URL parameters (ex: ?#&)
- all URL indexed have the same priority and frequency
- should be a composer package
- it doesn't validate config.ini values, only inputs from command line
