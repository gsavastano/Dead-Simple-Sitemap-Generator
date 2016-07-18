<?php

namespace gsavastano\Dssg;

class Dssg
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
     * @var array Default extensions
     */
    private $defaultExtensions = array('.htm', '.html', '.php', '/', '.aspx', '.asp');

    /**
     * @var object File Pointer
     */
    private $fileHandle = false;

    /**
     * @var bool Check Script status
     */
    private $ready = false;

    const NL = "\n";
    const VERSION = '0.2';

    public function __construct()
    {
        if (!$this->isCli()) {
            die('Please use Command Line');
        }
    }

    private function isCli()
    {
        return php_sapi_name() === 'cli';
    }

    private function loadStaticConfig($file)
    {
        if (!file_exists($file) || !fopen($file, 'r')) {
            die('Cannot open '.$file.NL);
        }

        $this->config = json_decode(file_get_contents($file), true);
    }

    private function loadIntercativeConfig()
    {
        $this->options = getopt($this->args);
        if (!empty($this->options)) {
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
    }

    public function loadConfig($file = false)
    {
        if ($file) {
            $this->loadStaticConfig($file);
        }
        $this->loadIntercativeConfig();
        $this->validateConfig();

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

    private function validateConfig()
    {
        if (!$this->valFilename($this->config['filename'])) {
            die('Sitemap file name not valid'.self::NL);
        }
        if (!$this->valProtocol($this->config['protocol'])) {
            die('Procol type not valid'.self::NL);
        }

        $completeUrl = $this->config['protocol'].'://'.$this->config['url'];

        if (!$this->valUrl($completeUrl)) {
            die('Target url type not valid'.self::NL);
        }

        $this->config['url'] = filter_var($completeUrl, FILTER_SANITIZE_URL);

        if (!$this->valFrequency($this->config['frequency'])) {
            die('Frequency value not valid'.self::NL);
        }
        if (!$this->valPriority($this->config['priority'])) {
            die('Priority value not valid'.self::NL);
        }
        if (!isset($this->config['extension'])) {
            $this->config['extension'] = $this->defaultExtensions;
        }
    }

    private function printConfig($json = false)
    {
        if (!$json) {
            echo '[Crawler Config Variables]'.self::NL;
            foreach ($this->config as $option => $value) {
                $value = is_array($value) ? implode(', ', $value) : $value;
                echo $option.' => '.$value.self::NL;
            }
            echo self::NL;
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

    protected function relToAbs($rel, $base)
    {
        extract(parse_url($base));

        if (strpos($rel, '//') === 0) {
            return $scheme.':'.$rel;
        }

        if (parse_url($rel, PHP_URL_SCHEME) != '') {
            return $rel;
        }
        $firstChar = substr($rel, 0, 1);

        if ($firstChar == '#'  || $firstChar == '?') {
            return $base.$rel;
        }
        $path = preg_replace('#/[^/]*$#', '', $path);

        if ($firstChar == '/') {
            $path = '';
        }

        $abs = $host.$path.'/'.$rel;

        $abs = preg_replace("/(\/\.?\/)/", "/", $abs);
        $abs = preg_replace("/\/(?!\.\.)[^\/]+\/\.\.\//", "/", $abs);
        
        return  $scheme.'://'.$abs;
    }

    protected function getUrl($url)
    {
        $agent = 'Mozilla/5.0(compatible;)';

        $curlHandler = curl_init();
        curl_setopt($curlHandler, CURLOPT_AUTOREFERER, true);
        curl_setopt($curlHandler, CURLOPT_URL, $url);
        curl_setopt($curlHandler, CURLOPT_USERAGENT, $agent);
        curl_setopt($curlHandler, CURLOPT_VERBOSE, 1);
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

    protected function doScan($url)
    {
        if (in_array($url, $this->scanned)) {
            return;
        }

        array_push($this->scanned, $url);

        $html = str_get_html($this->getUrl($url));
        if (!is_object($html)) {
            return;
        }

        $links = $html->find('a');
        unset($html);

        foreach ($links as $val) {
            $nextUrl = $val->href or '';

            $fragmentSplit = explode('#', $nextUrl);
            $nextUrl = $fragmentSplit[0];

            $nextUrl = @$this->relToAbs($nextUrl, $this->config['url']);

            $nextUrl = filter_var($nextUrl, FILTER_SANITIZE_URL);

            if (substr($nextUrl, 0, strlen($this->config['url'])) == $this->config['url']) {
                if (!in_array($nextUrl, $this->scanned)) {
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
        }
    }

    private function createFile()
    {
        $this->fileHandle = fopen($this->config['filename'], 'w');
        if (!$this->fileHandle) {
            die('Sitemap File not available'.self::NL);
        }

        fwrite(
            $this->fileHandle,
            '<?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9"
                    http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
            <url>'.self::NL.'
            <loc>'.htmlentities($this->config['url']).'</loc>
                <changefreq>'.$this->config['frequency'].'</changefreq>
                <priority>'.$this->config['priority'].'</priority>
            </url>'.self::NL
        );

        return;
    }

    private function saveAndClose()
    {
        if (!$this->fileHandle) {
            die("Can't file sitemap file to close".self::NL);
        }
        fwrite($this->fileHandle, '</urlset>'.self::NL);
        fclose($this->fileHandle);

        echo 'Done.'.self::NL;
        echo $this->config['filename'].' created.'.self::NL;
    }

    protected function valProtocol($val)
    {
        $protocols = array('http', 'https');

        return in_array($val, $protocols);
    }

    protected function valFilename($val)
    {
        $pattern = '/^(?!.*\/)(\w|\s|-)+\.xml$/';

        return preg_match($pattern, $val) == 1 ? true : false;
    }

    protected function valFrequency($val)
    {
        $frequencies = array('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never');

        return in_array($val, $frequencies);
    }

    protected function valPriority($val)
    {
        if (filter_var($val, FILTER_VALIDATE_FLOAT) <= 1 && is_numeric($val)) {
            return $val > 0 ? true : false;
        }

        return false;
    }

    protected function valUrl($url)
    {
        if (!preg_match('%^(?:(?:https?)://)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$%uiS', $url)) {
            return false;
        }

        $curlHandler = curl_init($url);
        curl_setopt($curlHandler, CURLOPT_HEADER, true);
        curl_setopt($curlHandler, CURLOPT_NOBODY, true);
        curl_setopt($curlHandler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandler, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curlHandler, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($curlHandler);
        $httpCode = curl_getinfo($curlHandler, CURLINFO_HTTP_CODE);
        curl_close($curlHandler);

        if ($httpCode == "200") {
            return true;
        }
        
        return false;
    }


    private function printHelp()
    {
        echo '
Dead Simple Sitemap Generator version '.self::VERSION.'
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
Dead Simple Sitemap Generator version '.self::VERSION.'
Giovanni Savastano(gsavastano@gmail.com)
MIT Licence
Use at your own risk :)
';
        die();
    }
}
