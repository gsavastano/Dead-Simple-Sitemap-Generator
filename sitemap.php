<?php
	ini_set('memory_limit', '2048M');
	require_once "simple_html_dom.php";

	$defaults = parse_ini_file("sitemap.ini");
	define ('VERSION', $config['version']);

	$options = getopt("v::h::c::s::u::f::p::");
	$scanned = array ();

	if(isset($options['h'])) printHelp();
	if(isset($options['v'])) printVersion();

	$config = array(
		'sitemap' 	=> 	valFileName($options['s']) 			? $options['s'] : $defaults['sitemap'],
		'url' 		=>	valUrl($options['u']) 				? $options['u'] : $defaults['url'],
		'frequency' => 	valFrequency($options['f']) 		? $options['s'] : $defaults['frequency'],
		'priority' 	=> 	valPriority($options['f']) 			? $options['p'] : $defaults['priority'],
		'extension'	=>	explode(',',$defaults['extension']),
	);

	$config['url'] = filter_var ($config['url'], FILTER_SANITIZE_URL);

	if(isset($options['c'])) printConfig();
 
	function valFileName ($val) {
		$pattern = '/^(?!.*\/)(\w|\s|-)+\.xml$/';
		return preg_match($pattern, $val);
	}

	function valUrl ($val) {
		$pattern = '@(https?)://(-\.)?([^\s/?\.#-]+\.?)+(/[^\s]*)?$@iS';
		return preg_match($pattern, $val);		
	}
	
	function valFrequency ($val) {
		$frequencies = array('always','hourly','daily','weekly','monthly','yearly','never');
		return in_array($val, $frequencies);
	}
	
	function valPriority ($val) {
		return filter_var( $val, FILTER_VALIDATE_FLOAT	) > 1 || !is_numeric($val) ? false : $val;
	}

	function printHelp() {
		echo '
Dead Simple Sitemap Generator version '.VERSION.'
Giovanni Savastano (gsavastano@gmail.com)
MIT Licence
Use at your own risk :)

Usage: php sitemap.php [options] ...

Option 	Meaning
-v 	Display program version
-h 	Print Help 
-s 	Set Output file name  
-u 	Set Target URL 
-f 	Set Frequency
-p 	Set Priority
';
		die();
	}

	function printVersion() {
		echo '
Dead Simple Sitemap Generator version '.VERSION.'
Giovanni Savastano (gsavastano@gmail.com)
MIT Licence
Use at your own risk :)
';
		die();
	}

	function printConfig() {
		global $config;
		foreach ($config as $option => $value) {
			$value = is_array($value) ? implode(',',$value) : $value;
			echo $option." => ".$value."\n";
		}
		die();
	}

	function rel2abs($rel, $base) {
		if(strpos($rel,"//") === 0) {
			return "http:".$rel;
		}
		/* return if  already absolute URL */
		if  (parse_url($rel, PHP_URL_SCHEME) != '') return $rel;
		$first_char = substr ($rel, 0, 1);
		/* queries and  anchors */
		if ($first_char == '#'  || $first_char == '?') return $base.$rel;
		/* parse base URL  and convert to local variables:
		$scheme, $host,  $path */
		extract(parse_url($base));
		/* remove  non-directory element from path */
		$path = preg_replace('#/[^/]*$#',  '', $path);
		/* destroy path if  relative url points to root */
		if ($first_char ==  '/') $path = '';
		/* dirty absolute  URL */
		$abs =  "$host$path/$rel";
		/* replace '//' or  '/./' or '/foo/../' with '/' */
		$re =  array('#(/.?/)#', '#/(?!..)[^/]+/../#');
		for($n=1; $n>0;  $abs=preg_replace($re, '/', $abs, -1, $n)) {}
		/* absolute URL is  ready! */
		return  $scheme.'://'.$abs;
	}

	function getUrl ($url) {
		$agent = "Mozilla/5.0 (compatible;)";

		$ch = curl_init();
		curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_USERAGENT, $agent);
		curl_setopt ($ch, CURLOPT_VERBOSE, 1);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt ($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);

		$data = curl_exec($ch);

		curl_close($ch);

		return $data;
	}

	function doScan ($url) {
		global $config, $scanned, $pf;

		$url = filter_var ($url, FILTER_SANITIZE_URL);

		if (!filter_var ($url, FILTER_VALIDATE_URL) || in_array ($url, $scanned)) {
			return;
		}

		array_push ($scanned, $url);
		$html = str_get_html (getUrl ($url));
		if(is_object($html)) {
			$links = $html->find('a');
			unset($html);
		} else 
			return;
		
		if(!(is_array($links) && !empty($links))) return;

		foreach ($links as $val) {
			$next_url = $val->href or "";

			$fragment_split = explode ("#", $next_url);
			$next_url       = $fragment_split[0];

			if ((substr ($next_url, 0, 7) != "http://")  && 
				(substr ($next_url, 0, 8) != "https://") &&
				(substr ($next_url, 0, 6) != "ftp://")   &&
				(substr ($next_url, 0, 7) != "mailto:")) {
				$next_url = @rel2abs ($next_url, $url);
			}

			$next_url = filter_var ($next_url, FILTER_SANITIZE_URL);

			if (substr ($next_url, 0, strlen ($config['url'])) == $config['url']) {
				$ignore = false;

				if (!filter_var ($next_url, FILTER_VALIDATE_URL)) {
					$ignore = true;
				}

				if (in_array ($next_url, $scanned)) {
					$ignore = true;
				}

				if (!$ignore) {
					foreach ($config['extension'] as $ext) {
						if (strpos ($next_url, trim($ext)) > 0) {
							fwrite ($pf, "  <url>\n" .
										 "    <loc>" . htmlentities ($next_url) ."</loc>\n" .
										 "    <changefreq>".$config['frequency']."</changefreq>\n" .
										 "    <priority>".$config['priority']."</priority>\n" .
										 "  </url>\n");
							doScan ($next_url);
						}
					}
				}
			}
		}
	}


	$pf = fopen ($config['sitemap'], "w");
	if (!$pf) {
		echo "Cannot create ".$config['sitemap']."!" . "\n";
		return;
	}

	fwrite ($pf, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
				 "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n" .
				 "        xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
				 "        xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\n" .
				 "        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n" .
				 "  <url>\n" .
				 "    <loc>" . htmlentities ($config['url']) ."</loc>\n" .
				 "    <changefreq>".$config['frequency']."</changefreq>\n" .
				 "    <priority>".$config['priority']."</priority>\n" .
				 "  </url>\n");

	doScan ($config['url']);
	fwrite ($pf, "</urlset>\n");
	fclose ($pf);

	echo "Done." . "\n";
	echo $config['sitemap']." created." . "\n";
?>