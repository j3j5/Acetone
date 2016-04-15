<?php

namespace JDare\Acetone;

use App;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use JDare\Acetone\Exceptions\AcetoneException;

class Acetone
{

    private $server, $forceException;
    private $config;
    public function __construct()
    {
        $this->config = config('acetone');
        $this->server = trim($this->config['server']['address'], "/");
        if (!$this->server) {
            throw new AcetoneException("Varnish server address configuration must be specified");
        }
        if (strpos($this->server, "http://") === false) {
            $this->server = "http://" . $this->server;
        }
        $this->forceException = array_key_exists('force_exceptions', $this->config) ? $this->config['force_exceptions'] : 'auto';
    }

    /**
     * Will purge a single URL from the Varnish Cache. Accepts arrays of multiple URLs.
     * Warning: if purging many URLs, use banMany instead, it has significant performance benefits.
     *
     *
     * @param string|array $url
     * @return bool True on success, False on failure
     * @throws Exceptions\AcetoneException
     */
    public function purge($url)
    {
        if (is_array($url)) {
            return array_walk($url, array($this, "purge"));
        }

        try {
            \Log::debug("Purging: ".$url);
            $curl = curl_init($this->server.$url);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PURGE");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $output = curl_exec($curl);
            \Log::debug($output);
            return true;
        } catch (\Exception $e) {
            \Log::error($e);
            return false;
        }
        //return $this->simpleCacheRequest("PURGE", $url);
    }

    /**
     * Same as purge, but will perform a REFRESH request instead.
     *
     * @param $url
     * @return bool
     */
    public function refresh($url)
    {
        if (is_array($url)) {
            return array_walk($url, array($this, "refresh"));
        }

        return $this->simpleCacheRequest("REFRESH", $url);
    }

    /**
     * Will send a BAN request to the Varnish Cache. If $regex is set to true, the $url parameter will be treated as a raw regex string and
     * sent straight along as the x-ban-url header. This is useful for banning multiple items by matching them.
     *
     * @param $url
     * @param bool $regex
     * @return bool
     * @throws Exceptions\AcetoneException
     */
    public function ban($url, $regex = false)
    {
        if (is_array($url)) {
            return array_walk($url, array($this, "ban"));
        }

        $path = null;
        if ($regex == false) {
            $url = parse_url($url);
            if (isset($url['path'])) {
                $path = "^" . $url['path'] . "$";
            } else {
                throw new AcetoneException("URL to Ban could not be parsed");
            }
        } else {
            $path = $url;
        }
/*
        try {
            \Log::debug("Banning: ".$path);
            $curl = curl_init($path);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "BAN");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
            $output = curl_exec($curl);
            \Log::debug($output);
            return true;
        } catch (\Exception $e) {
            \Log::error($e);
            return false;
        }*/
        \Log::debug("Banning: ".$path);
        $client = new Client($this->server);
        $request = $client->createRequest("BAN", $path, array(
        array_key_exists('ban_url_header', $this->config) ? $this->config['ban_url_header'] : "x-ban-url" => $path,
        ));

        try {
            $response = $request->send();
        } catch (ClientErrorResponseException $e) {
            $this->handleException($e);
            return false;
        }

        if ($response->getStatusCode() == 200) {
            return true;
        }
        return false;
    }

    /**
     * Shortcut to help with banning many elements based off a shared url prefix
     *
     * Sample Usage:
     *
     * Acetone::banMany("/posts/");
     *
     * This would have the effect of banning any URL which starts with /posts/
     * e.g. /posts/title-1, /posts/title-2, etc.
     *
     * @param $urlPrefix
     * @return bool
     */
    public function banMany($urlPrefix)
    {
        if (is_array($urlPrefix)) {
            $banString = "(";
            foreach ($urlPrefix as $url) {
                $banString .= "^" . $url . "|" ;
            }
            $banString = trim($banString, "|") . ")";
            return $this->ban($banString, true);
        }
        return $this->ban("^" . $urlPrefix, true);
    }

    /**
     * Fundamentals for placing a simple cache invalidation request to Varnish.
     *
     * @param $method
     * @param $url
     * @return bool
     * @throws Exceptions\AcetoneException
     */
    private function simpleCacheRequest($method, $url)
    {
        $url = parse_url($url);
        $path = null;
        if (isset($url['path'])) {
            $path = $url['path'];
        } else {
            throw new AcetoneException("URL could not be parsed");
        }

        $client = new Client($this->server);
        $request = $client->createRequest($method, $path);
        try {
            $response = $request->send();
        } catch (ClientErrorResponseException $e) {
            $this->handleException($e);
            return false;
        }
        if ($response->getStatusCode() == 200) {
            return true;
        }
        return false;
    }

    /**
     * Handles any exceptions thrown by Guzzle.
     *
     * @param \Exception $e
     * @throws \Exception
     */
    private function handleException(\Exception $e)
    {
        if (App::environment() !== "production" && $this->forceException === 'auto') {
            throw $e;
        }

        if ($this->forceException === true) {
            throw $e;
        }
    }
}
