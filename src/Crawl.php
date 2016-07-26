<?php

namespace gsavastano\Dssg;

include_once __DIR__.'/Validate.php';

class Crawl
{
    /**
     * @var array Configuration values
     */
    protected $config = [];

    /**
     * @var array List of Values from CLI
     */
    public $options = [];

    /**
     * @var array List of URLs already scanned
     */
    private $scanned = [];

    /**
     * @var string CLI Arguments
     */
    private $args = 'vhcs::a::u::f::p::';

    /**
     * @var object File Pointer
     */
    private $fileHandle = false;

    /**
     * @var bool Check Script status
     */
    private $ready = false;

    private $validate;

    const _NL = "\n";
    const _VERSION = '0.4';

    public function __construct()
    {
        if (!Validate::isCli()) {
            die('Please use Command Line');
        }
    }

    private function loadStaticConfig($file)
    {
        if (!file_exists($file) || !fopen($file, 'r')) {
            $this->config['error'][] = 'Cannot open configuration file';
        }

        $this->config = json_decode(file_get_contents($file), true);
    }

    private function loadIntercativeConfig()
    {
        $this->options = getopt($this->args);
        if (empty($this->options)) {
            return;
        }
        if (isset($this->options['a'])) {
            $this->config['protocol'] = $this->options['a'];
        }
        if (isset($this->options['u'])) {
            $this->config['url'] = $this->options['u'];
        }
        if (isset($this->options['s'])) {
            $this->config['filename'] = $this->options['s'];
        }
        if (isset($this->options['f'])) {
            $this->config['frequency'] = $this->options['f'];
        }
        if (isset($this->options['p'])) {
            $this->config['priority'] = $this->options['p'];
        }
    }

    public function loadConfig($file = false)
    {
        if ($file) {
            $this->loadStaticConfig($file);
        }
        $this->loadIntercativeConfig();
        $this->config = Validate::validateConfig($this->config);

        if (isset($this->config['error'])) {
            foreach ($this->config['error'] as $error) {
                echo $error.self::_NL;
            }
            die();
        }

        if (isset($this->options['h'])) {
            $this->printHelp();
        }
        if (isset($this->options['v'])) {
            $this->printVersion();
        }
        if (isset($this->options['c'])) {
            $this->printConfig();
        }

        $this->ready = true;

        return;
    }

    private function printConfig($json = false)
    {
        if (!$json) {
            echo '[Crawler Config Variables]'.self::_NL;
            foreach ($this->config as $option => $value) {
                $value = is_array($value) ? implode(', ', $value) : $value;
                echo $option.' => '.$value.self::_NL;
            }
            echo self::_NL;
        } else {
            return json_encode($this->config);
        }
        die();
    }

    public function startCrawl()
    {
        if (!$this->ready) {
            $this->loadConfig();
        }
        $this->createFile();
        $this->doScan($this->config['url']);
        $this->saveAndClose();

        return;
    }

    protected function getUrl($url)
    {
        $agent = 'Mozilla/5.0(compatible;)';
        $curlHandler = curl_init();
        curl_setopt($curlHandler, CURLOPT_AUTOREFERER, true);
        curl_setopt($curlHandler, CURLOPT_URL, $url);
        curl_setopt($curlHandler, CURLOPT_USERAGENT, $agent);
        curl_setopt($curlHandler, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlHandler, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curlHandler, CURLOPT_HEADER, 0);
        curl_setopt($curlHandler, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_CONNECTTIMEOUT, 5);

        $data = curl_exec($curlHandler);

        curl_close($curlHandler);
        return $data;
    }

    protected function clearUrlList(array $list)
    {
        if (empty($list)) {
            return;
        }

        $params = parse_url($this->config['url']);

        $needle = $params['host'];
        $lenght = strlen($this->config['url']);
        if (stripos($needle, 'www') !== false) {
            $needle = substr($needle, 4);
        }
        unset($params);

        //remove duplicates
        $list = array_merge(array_flip(array_flip($list)));
        
        //transform relative links to absolute, remove links to external websites and to subdomains
        foreach ($list as $i => $link) {
            //rel to abs - basic version
            if (stripos($link, "/") === 0) {
                $link = $this->config['url'].$link;
            }
            //no subdomains or external websites
            if (substr($link, 0, $lenght) !== $this->config['url']) {
                $link = $this->config['url'];
            }
            $list[$i] = filter_var($link, FILTER_SANITIZE_URL);
        }

        //remove all links that have already been scanned.
        foreach ($list as $i => $link) {
            if (in_array($link, $this->scanned)) {
                unset($list[$i]);
            }
        }

        //remove duplicates again
        $list = array_merge(array_flip(array_flip($list)));

        return $list;
    }

    protected function doScan($url)
    {
        if (in_array($url, $this->scanned)) {
            return;
        }
        echo self::_NL.'Scanning: '.$url;
        array_push($this->scanned, $url);

        $dom = new \DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);
        $dom->loadHTML($this->getUrl($url));
        libxml_use_internal_errors($internalErrors);
        $links = array();
        foreach ($dom->getElementsByTagName('a') as $link) {
            $links[] = $link->getAttribute('href');
        }

        $links = $this->clearUrlList($links);
        
        if (empty($links)) {
            return;
        }
        foreach ($links as $val) {
            $nextUrl = $val or '';

            foreach ($this->config['extension'] as $ext) {
                if (strpos($nextUrl, trim($ext)) > 0) {
                    fwrite(
                        $this->fileHandle,
                        '
                        <url>
                            <loc>'.htmlentities($nextUrl).'</loc>
                            <changefreq>'.$this->config['frequency'].'</changefreq>
                            <priority>'.$this->config['priority'].'</priority>
                        </url>'
                    );
                    $this->doScan($nextUrl);
                }
            }
        }
    }

    private function createFile()
    {
        $this->fileHandle = fopen($this->config['filename'], 'w');
        if (!$this->fileHandle) {
            die('Sitemap File not available'.self::_NL);
        }

        fwrite(
            $this->fileHandle,
            '<?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
                    http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
            <url>'.self::_NL.'
            <loc>'.htmlentities($this->config['url']).'</loc>
                <changefreq>'.$this->config['frequency'].'</changefreq>
                <priority>'.$this->config['priority'].'</priority>
            </url>'.self::_NL
        );

        return;
    }

    private function saveAndClose()
    {
        if (!$this->fileHandle) {
            die("Can't find sitemap file to close".self::_NL);
        }
        fwrite($this->fileHandle, '</urlset>'.self::_NL);
        fclose($this->fileHandle);

        echo self::_NL.'Done.'.self::_NL.$this->config['filename'].' created.'.self::_NL;
    }

    private function printHelp()
    {
        echo '
Dead Simple Sitemap Generator version '.self::_VERSION.'
Giovanni Savastano(gsavastano@gmail.com)
MIT Licence
Use at your own risk :)

Usage: php sitemap.php [options] ...

Option  Meaning
-v  Display program version
-h  Print Help 
-h  Print Crawler Config
-s  Set Output file name  
-a  Set Procol
-u  Set Target URL 
-f  Set Frequency
-p  Set Priority
';
        die();
    }

    private function printVersion()
    {
        echo '
Dead Simple Sitemap Generator version '.self::_VERSION.'
Giovanni Savastano(gsavastano@gmail.com)
MIT Licence
Use at your own risk :)
';
        die();
    }
}
