<?php

namespace Dssg;

class Validate
{
    /**
     * @var array Default extensions
     */
    const _DEFAULT_EXTENSIONS = array('.htm', '.html', '.php', '/', '.aspx', '.asp');

    /**
     * @var array Valid protocols
     */
    const _VALID_PROTOCOLS = array('http', 'https');

    /**
     * @var array Valid frequencies
     */
    const _VALID_FREQUENCIES = array('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never');


    public function __construct()
    {
    }

    public static function isCli()
    {
        return php_sapi_name() === 'cli';
    }

    public static function valProtocol($val)
    {
        return in_array($val, self::_VALID_PROTOCOLS);
    }

    public static function valFilename($val)
    {
        $pattern = '/^(?!.*\/)(\w|\s|-)+\.xml$/';

        return preg_match($pattern, $val) == 1 ? true : false;
    }

    public static function valFrequency($val)
    {
        return in_array($val, self::_VALID_FREQUENCIES);
    }

    public static function valPriority($val)
    {
        if (filter_var($val, FILTER_VALIDATE_FLOAT) <= 1 && is_numeric($val)) {
            return $val > 0 ? true : false;
        }

        return false;
    }

    public static function valUrl($url)
    {
        if (!preg_match(
            '%^(?:(?:https?)://)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3})'.
            '{3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])'.
            '(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}'.
            '|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:'.
            '(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.'.
            '(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.'.
            '(?:[a-z\x{00a1}-\x{ffff}]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$%uiS',
            $url
        )) {
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
            unset($output);
            return true;
        }
        
        return false;
    }

    public static function validateConfig($config = array())
    {
        if (empty($config)) {
            $config['error'][] = 'No configuration given';
        }
        if (!self::valFilename($config['filename'])) {
            $config['error'][] = 'Sitemap file name not valid';
        }
        if (!self::valProtocol($config['protocol'])) {
            $config['error'][] = 'Procol type not valid';
        }

        $completeUrl = $config['protocol'].'://'.$config['url'];
        $params = parse_url($completeUrl);
        $completeUrl = $params['scheme'].'://'.$params['host'];
        unset($params);

        if (!self::valUrl($completeUrl)) {
            $config['error'][] = 'Target url type not valid';
        }

        $config['url'] = filter_var($completeUrl, FILTER_SANITIZE_URL);

        if (!self::valFrequency($config['frequency'])) {
            $config['error'][] = 'Frequency value not valid';
        }
        if (!self::valPriority($config['priority'])) {
            $config['error'][] = 'Priority value not valid';
        }
        if (!isset($config['extension'])) {
            $config['extension'] = self::_DEFAULT_EXTENSIONS;
        }
        return $config;
    }
}
