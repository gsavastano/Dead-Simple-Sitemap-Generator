<?php namespace gsavastano\Dssg;

class Dssg
{
    /**
    * @var array List of Defaults Configuration values
     */
    protected $defaults = [];

    /**
     * @var array Configuration values
     */
    protected $config = [];

    /**
     * @var string Version number
     */
    protected $version = 'debug';

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
    private $args = 'vhs::a::u::f::p::';

    /**
     * @var object File Pointer
     */
    private $fileHandle = false;

    /**
     * @var bool Check Script status
     */
    private $ready = false;

    public function __construct()
    {
        if (!$this->isCli()) {
            die('Please use Command Line');
        }
    }

   /**
     * @var bool Turn On/Off reading from CLI
     */
    private function isCli()
    {
        return php_sapi_name() === 'cli';
    }

    public function setConfig()
    {
        if ($this->ready) {
            return;
        }

        $this->defaults = parse_ini_file('sitemap.ini');

        $this->options = getopt($this->args);

        $this->version = $this->defaults['version'];

            //handle Help, Version right away
            if (isset($this->options['h'])) {
                $this->printHelp();
            }
        if (isset($this->options['v'])) {
            $this->printVersion();
        }

        $this->config = array(
                    'sitemap' => $this->valFileName($this->options['s']) ? $this->options['s'] : $this->defaults['sitemap'],
                    'protocol' => $this->valProtocol($this->options['a']) ? $this->options['a'] : $this->defaults['protocol'],
                    'url' => filter_var(isset($this->options['u']) ? $this->options['u'] : $this->defaults['url'], FILTER_SANITIZE_URL),
                    'frequency' => $this->valFrequency($this->options['f']) ? $this->options['f'] : $this->defaults['frequency'],
                    'priority' => $this->valPriority($this->options['p']) ? $this->options['p'] : $this->defaults['priority'],
                    'extension' => explode(',', $this->defaults['extension']),
                );

        $this->config['url'] = filter_var($this->config['protocol'].'://'.$this->config['url'], FILTER_SANITIZE_URL);

        $this->createFile($this->options['sitemap']);
        $this->ready = true;
    }

    public function valProtocol($val)
    {
        $protocols = array('http', 'https');

        return in_array($val, $protocols);
    }

    public function valFileName($val)
    {
        $pattern = '/^(?!.*\/)(\w|\s|-)+\.xml$/';

        return preg_match($pattern, $val) == 1 ? true : false;
    }

    public function valFrequency($val)
    {
        $frequencies = array('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never');

        return in_array($val, $frequencies);
    }

    public function valPriority($val)
    {
        if (filter_var($val, FILTER_VALIDATE_FLOAT) <= 1 && is_numeric($val)) {
            return $val > 0 ? true : false;
        }

        return false;
    }

    public function printHelp()
    {
        echo "
Dead Simple Sitemap Generator version ".$this->version."
Giovanni Savastano (gsavastano@gmail.com)
MIT Licence
Use at your own risk :)

Usage: php sitemap.php [options] ...

Option 	Meaning
-v 	Display program version
-h 	Print Help 
-s 	Set Output file name  
-a 	Set Procol
-u 	Set Target URL 
-f 	Set Frequency
-p 	Set Priority
";
        die();
    }

    public function printVersion()
    {
        echo "
Dead Simple Sitemap Generator version ".$this->version."
Giovanni Savastano (gsavastano@gmail.com)
MIT Licence
Use at your own risk :)
";
        die();
    }

    protected function relToAbs($rel, $base)
    {
        if (!$this->ready) {
            return;
        }

        if (strpos($rel, '//') === 0) {
            return $this->options['protocol'].$rel;
        }

        if (parse_url($rel, PHP_URL_SCHEME) != '') {
            return $rel;
        }
        $firstChar = substr($rel, 0, 1);

        if ($firstChar == '#'  || $firstChar == '?') {
            return $base.$rel;
        }

        extract(parse_url($base));

        $path = preg_replace('#/[^/]*$#',  '', $path);

        if ($firstChar ==  '/') {
            $path = '';
        }

        $abs = "$host$path/$rel";

        $expr = array('#(/.?/)#', '#/(?!..)[^/]+/../#');
        for ($n = 1; $n > 0;  $abs = preg_replace($expr, '/', $abs, -1, $n)) {
        }

        return  $scheme.'://'.$abs;
    }

    public function getUrl($url)
    {
        if (!$this->ready) {
            return;
        }
        $agent = 'Mozilla/5.0 (compatible;)';

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

    public function doScan($url)
    {
        if (!$this->ready) {
            return;
        }
        if (!$this->fileHandle) {
            echo "You must first create the sitemap file. Call createFile() to do that";
            die();
        }

        $url = filter_var($url, FILTER_SANITIZE_URL);

        if (!filter_var($url, FILTER_VALIDATE_URL) || in_array($url, $this->scanned)) {
            return;
        }

        array_push($this->scanned, $url);
        $html = str_get_html($this->getUrl($url));
        if (is_object($html)) {
            $links = $html->find('a');
            unset($html);
        } else {
            return;
        }

        if (!(is_array($links) && !empty($links))) {
            return;
        }

        foreach ($links as $val) {
            $nextUrl = $val->href or '';

            $fragmentSplit = explode('#', $nextUrl);
            $nextUrl = $fragmentSplit[0];

            if ((substr($nextUrl, 0, 7) != 'http://')  &&
                    (substr($nextUrl, 0, 8) != 'https://') &&
                    (substr($nextUrl, 0, 6) != 'ftp://')   &&
                    (substr($nextUrl, 0, 7) != 'mailto:')) {
                $nextUrl = @$this->relToAbs($nextUrl, $url);
            }

            $nextUrl = filter_var($nextUrl, FILTER_SANITIZE_URL);

            if (substr($nextUrl, 0, strlen($this->config['url'])) == $this->config['url']) {
                $ignore = false;

                if (!filter_var($nextUrl, FILTER_VALIDATE_URL)) {
                    $ignore = true;
                }

                if (in_array($nextUrl, $this->scanned)) {
                    $ignore = true;
                }

                if (!$ignore) {
                    foreach ($this->config['extension'] as $ext) {
                        if (strpos($nextUrl, trim($ext)) > 0) {
                            fwrite($this->fileHandle, 
                                            "   <url>\n".
                                            "       <loc>".htmlentities($nextUrl)."</loc>\n".
                                            "       <changefreq>".$this->config['frequency']."</changefreq>\n".
                                            "       <priority>".$this->config['priority']."</priority>\n".
                                            "   </url>\n");
                            $this->doScan($nextUrl);
                        }
                    }
                }
            }
        }
    }

    public function startCrawl()
    {
        $this->setConfig();
        $this->doScan($this->config['url']);

        return;
    }

    private function createFile($fileName = null)
    {
        $this->config['sitemap'] = is_null($fileName) || !$this->valFileName($fileName) ? $this->config['sitemap'] : $fileName;

        $this->fileHandle = fopen($this->config['sitemap'], 'w');
        if (!$this->fileHandle) {
            echo "Cannot create ".$this->config['sitemap']."!\n";

            return;
        }

        fwrite($this->fileHandle, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
             "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n".
             "        xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n".
             "        xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\n".
             "        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n".
             "<url>\n".
             "  <loc>".htmlentities($this->config['url'])."</loc>\n".
             "      <changefreq>".$this->config['frequency']."</changefreq>\n".
             "      <priority>".$this->config['priority']."</priority>\n".
             "  </url>\n");

        return;
    }

    public function saveAndClose()
    {
        if (!$this->fileHandle) {
            echo 'You must first create the sitemap file. Call createFile() to do that';
            die();
        }
        fwrite($this->fileHandle, "</urlset>\n");
        fclose($this->fileHandle);

        echo "Done."."\n";
        echo $this->config['sitemap']." created."."\n";
    }
}
