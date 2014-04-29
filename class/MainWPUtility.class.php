<?php
class MainWPUtility
{
    public static function startsWith($haystack, $needle)
    {
        return !strncmp($haystack, $needle, strlen($needle));
    }

    public static function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    public static function getNiceURL($pUrl, $showHttp = false)
    {
        $url = $pUrl;

        if (self::startsWith($url, 'http://'))
        {
            if (!$showHttp) $url = substr($url, 7);
        }
        else if (self::startsWith($pUrl, 'https://'))
        {
            if (!$showHttp) $url = substr($url, 8);
        }
        else
        {
            if ($showHttp) $url = 'http://'.$url;
        }

        if (self::endsWith($url, '/'))
        {
            if (!$showHttp) $url = substr($url, 0, strlen($url) - 1);
        }
        else
        {
            $url = $url . '/';
        }
        return $url;
    }

    public static function limitString($pInput, $pMax = 500)
    {
        $output = strip_tags($pInput);
        if (strlen($output) > $pMax) {
            // truncate string
            $outputCut = substr($output, 0, $pMax);
            // make sure it ends in a word so assassinate doesn't become ass...
            $output = substr($outputCut, 0, strrpos($outputCut, ' ')).'...';
        }
        echo $output;
    }

    public static function isAdmin()
    {
        global $current_user;
        if ($current_user->ID == 0) return false;

        if ($current_user->wp_user_level == 10 || (isset($current_user->user_level) && $current_user->user_level == 10) || current_user_can('level_10')) {
            return true;
        }
        return false;
    }

    public static function isWebsiteAvailable($url)
    {
        if (!MainWPUtility::isDomainValid($url)) return false;

        return MainWPUtility::tryVisit($url);
    }

    private static function isDomainValid($url)
    {
        //check, if a valid url is provided
        return filter_var($url, FILTER_VALIDATE_URL);
    }


    public static function tryVisit($url)
    {
        $agent = 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)';
        $postdata = array('test' => 'yes');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        if ($data === FALSE)
        {
            $err = curl_error($ch);
            return array('error' => ($err == '' ? 'Invalid host.' : $err));
        }
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        $realurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        $host = parse_url($realurl, PHP_URL_HOST);
        if ($http_status == '200')
        {
            $dnsRecord = dns_get_record($host);
            if ($dnsRecord === false)
            {
                return array('error' => 'Invalid host.');
            }
            else
            {
                $found = false;
                if (!isset($dnsRecord['host']))
                {
                    foreach ($dnsRecord as $dnsRec)
                    {
                        if ($dnsRec['host'] == $host)
                        {
                            $found = true;
                            break;
                        }
                    }
                }
                else
                {
                    $found = ($dnsRecord['host'] == $host);
                }
                if (!$found)
                {
                    return array('error' => 'Invalid host.'); // Got redirected to: ' . $dnsRecord['host'])));
                }
            }
        }
        return array('httpCode' => $http_status, 'error' => $err, 'httpCodeString' => self::getHttpStatusErrorString($http_status));
    }



    protected static function getHttpStatusErrorString($httpCode)
    {
        if ($httpCode == 100) return "Continue";
        if ($httpCode == 101) return "Switching Protocols";
        if ($httpCode == 200) return "OK";
        if ($httpCode == 201) return "Created";
        if ($httpCode == 202) return "Accepted";
        if ($httpCode == 203) return "Non-Authoritative Information";
        if ($httpCode == 204) return "No Content";
        if ($httpCode == 205) return "Reset Content";
        if ($httpCode == 206) return "Partial Content";
        if ($httpCode == 300) return "Multiple Choices";
        if ($httpCode == 301) return "Moved Permanently";
        if ($httpCode == 302) return "Found";
        if ($httpCode == 303) return "See Other";
        if ($httpCode == 304) return "Not Modified";
        if ($httpCode == 305) return "Use Proxy";
        if ($httpCode == 306) return "(Unused)";
        if ($httpCode == 307) return "Temporary Redirect";
        if ($httpCode == 400) return "Bad Request";
        if ($httpCode == 401) return "Unauthorized";
        if ($httpCode == 402) return "Payment Required";
        if ($httpCode == 403) return "Forbidden";
        if ($httpCode == 404) return "Not Found";
        if ($httpCode == 405) return "Method Not Allowed";
        if ($httpCode == 406) return "Not Acceptable";
        if ($httpCode == 407) return "Proxy Authentication Required";
        if ($httpCode == 408) return "Request Timeout";
        if ($httpCode == 409) return "Conflict";
        if ($httpCode == 410) return "Gone";
        if ($httpCode == 411) return "Length Required";
        if ($httpCode == 412) return "Precondition Failed";
        if ($httpCode == 413) return "Request Entity Too Large";
        if ($httpCode == 414) return "Request-URI Too Long";
        if ($httpCode == 415) return "Unsupported Media Type";
        if ($httpCode == 416) return "Requested Range Not Satisfiable";
        if ($httpCode == 417) return "Expectation Failed";
        if ($httpCode == 500) return "Internal Server Error";
        if ($httpCode == 501) return "Not Implemented";
        if ($httpCode == 502) return "Bad Gateway";
        if ($httpCode == 503) return "Service Unavailable";
        if ($httpCode == 504) return "Gateway Timeout";
        if ($httpCode == 505) return "HTTP Version Not Supported";

        return null;
    }
//
//    private static function tryVisit($url)
//    {
//        $agent= 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)';
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, $url);
//        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_VERBOSE, false);
//        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
//        curl_setopt($ch, CURLOPT_SSLVERSION, 3);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
//        $page = curl_exec($ch);
//        //echo curl_error($ch);
//        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//        curl_close($ch);
//        if ($httpcode >= 200 && $httpcode < 400) return true;
//        else return false;
//    }

    /**
     * @static
     * @param $user WP_User object!
     * @return
     */
    static function getNotificationEmail($user = null)
    {
        //todo: work with correct single user email..
        if ($user == null)
        {
            global $current_user;
            $user = $current_user;
        }

        if ($user == null) return null;

        if (!($user instanceof WP_User)) return null;

        $userExt = MainWPDB::Instance()->getUserExtension();
        if ($userExt->user_email != '') return $userExt->user_email;

        return $user->user_email;
    }

    /*
     * $website: Expected object ($website->id, $website->url, ... returned by MainWPDB)
     * $what: What function needs to be called - defined in the mainwpplugin
     * $params: (Optional) array(key => value, key => value);
     */

    static function getPostDataAuthed(&$website, $what, $params = null)
    {
        if ($website && $what != '') {
            $data = array();
            $data['user'] = $website->adminname;
            $data['function'] = $what;
            $data['nonce'] = rand(0,9999);
            if ($params != null) {
                $data = array_merge($data, $params);
            }

            if (($website->nossl == 0) && function_exists('openssl_verify')) {
                $data['nossl'] = 0;
                @openssl_sign($what . $data['nonce'], $signature, base64_decode($website->privkey));
            }
            else
            {
                $data['nossl'] = 1;
                $signature = md5($what . $data['nonce'] . $website->nosslkey);
            }
            $data['mainwpsignature'] = base64_encode($signature);
            return http_build_query($data, '', '&');
        }
        return null;
    }

    static function getGetDataAuthed($website, $paramValue, $paramName = 'where', $asArray = false)
    {
        $params = array();
        if ($website && $paramValue != '')
        {
            $nonce = rand(0,9999);
            if (($website->nossl == 0) && function_exists('openssl_verify')) {
                $nossl = 0;
                openssl_sign($paramValue . $nonce, $signature, base64_decode($website->privkey));
            }
            else
            {
                $nossl = 1;
                $signature = md5($paramValue . $nonce . $website->nosslkey);
            }
            $signature = base64_encode($signature);

            $params = array(
                'login_required' => 1,
                'user' => $website->adminname,
                'mainwpsignature' => rawurlencode($signature),
                'nonce' => $nonce,
                'nossl' => $nossl,
                $paramName => rawurlencode($paramValue)
            );
        }

        if ($asArray) return $params;

        $url = (isset($website->siteurl) && $website->siteurl != '' ? $website->siteurl : $website->url);
        $url .= (substr($url, -1) != '/' ? '/' : '');
        $url .= '?';

        foreach ($params as $key => $value)
        {
            $url .= $key . '=' . $value . '&';
        }

        return rtrim($url, '&');
    }

    /*
     * $url: String
     * $admin: admin username
     * $what: What function needs to be called - defined in the mainwpplugin
     * $params: (Optional) array(key => value, key => value);
     */

    static function getPostDataNotAuthed($url, $admin, $what, $params = null)
    {
        if ($url != '' && $admin != '' && $what != '') {
            $data = array();
            $data['user'] = $admin;
            $data['function'] = $what;
            if ($params != null) {
                $data = array_merge($data, $params);
            }
            return http_build_query($data, '', '&');
        }
        return null;
    }

    /*
     * $websites: Expected array of objects ($website->id, $website->url, ... returned by MainWPDB) indexed by the object->id
     * $what: What function needs to be called - defined in the mainwpplugin
     * $params: (Optional) array(key => value, key => value);
     * $handler: Name of a function to be called:
     *      function handler($data, $website, &$output) {}
     *          the $data = data returned by the request, $website = website object returned by MainWPDB
     *          $output has to be filled in by the handler-function - it is used as an output variable!
     */

    static function fetchUrlsAuthed(&$websites, $what, $params = null, $handler, &$output, $whatPage = null)
    {
        if (!is_array($websites) || empty($websites)) return;

        $chunkSize = 10;
        if (count($websites) > $chunkSize)
        {
            $total = count($websites);
            $loops = ceil($total / $chunkSize);
            for ($i = 0; $i < $loops; $i++)
            {
                self::fetchUrlsAuthed(array_slice($websites, $i * $chunkSize, $chunkSize, true), $what, $params, $handler, $output, $whatPage);
                sleep(5);
            }

            return;
        }

        $agent= 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)';
        $mh = curl_multi_init();

        $handleToWebsite = array();
        $requestUrls = array();
        foreach ($websites as $website)
        {
            $url = $website->url;
            if (substr($url, -1) != '/') { $url .= '/'; }
            $url .= 'wp-admin/';
            if ($whatPage != null) $url .= $whatPage;

            $_new_post = null;
            if (isset($params) && isset($params['new_post']))
            {
                $_new_post = $params['new_post'];
                $params = apply_filters('mainwp-pre-posting-posts', (is_array($params) ? $params : array()), (object)array('id' => $website->id, 'url' => $website->url, 'name' => $website->name));
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            $postdata = MainWPUtility::getPostDataAuthed($website, $what, $params);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            curl_setopt($ch, CURLOPT_USERAGENT, $agent);
            curl_multi_add_handle($mh, $ch);
            $handleToWebsite[$ch] = $website;
            $requestUrls[$ch] = $website->url;

            if ($_new_post != null) $params['new_post'] = $_new_post; // reassign new_post
        }

        do
        {
            curl_multi_exec($mh, $running); //Execute handlers
            //$ready = curl_multi_select($mh);
            while ($info = curl_multi_info_read($mh))
            {
                $data = curl_multi_getcontent($info['handle']);
                $contains = (preg_match('/<mainwp>(.*)<\/mainwp>/', $data, $results) > 0);
                curl_multi_remove_handle($mh, $info['handle']);

                if (!$contains && isset($requestUrls[$info['handle']]))
                {
                  curl_setopt($info['handle'], CURLOPT_URL, $requestUrls[$info['handle']]);
                  curl_multi_add_handle($mh, $info['handle']);
                  unset($requestUrls[$info['handle']]);
                  $running++;
                  continue;
                }

                if ($handler != null)
                {
                    $site = &$handleToWebsite[$info['handle']];
                    call_user_func($handler, $data, $site, $output);
                }

                unset($handleToWebsite[$info['handle']]);
                if (gettype($info['handle']) == 'resource') curl_close($info['handle']);
                unset($info['handle']);
            }
        } while ($running > 0);

        curl_multi_close($mh);

        return true;
    }

    static function fetchUrlAuthed(&$website, $what, $params = null, $checkConstraints = false)
    {
        if ($params == null) $params = array();
        $params['optimize'] = ((get_option("mainwp_optimize") == 1) ? 1 : 0);

        $postdata = MainWPUtility::getPostDataAuthed($website, $what, $params);
        $information = MainWPUtility::fetchUrl($website, $website->url, $postdata, $checkConstraints);
      
        if (is_array($information) && isset($information['sync']))
        {
            MainWPSync::syncInformationArray($website, $information['sync']);
            unset($information['sync']);
        }

        return $information;
    }

    static function fetchUrlNotAuthed($url, $admin, $what, $params = null)
    {
        $postdata = MainWPUtility::getPostDataNotAuthed($url, $admin, $what, $params);
        $website = null;
        return MainWPUtility::fetchUrl($website, $url, $postdata);
    }

    static function fetchUrlClean($url, $postdata)
    {
        $agent= 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        $data = curl_exec($ch);
        curl_close($ch);
        if (!$data) {
            throw new Exception('HTTPERROR');
        }
        else
        {
            return $data;
        }
    }

    static function fetchUrl(&$website, $url, $postdata, $checkConstraints = false)
    {
        try
        {
            $tmpUrl = $url;
            if (substr($tmpUrl, -1) != '/') { $tmpUrl .= '/'; }

            return self::_fetchUrl($website, $tmpUrl . 'wp-admin/', $postdata, $checkConstraints);
        }
        catch (Exception $e)
        {
            try
            {
                return self::_fetchUrl($website, $url, $postdata, $checkConstraints);
            }
            catch (Exception $ex)
            {
                throw $e;
            }
        }
    }

    static function _fetchUrl(&$website, $url, $postdata, $checkConstraints = false)
    {
        $agent= 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)';

        //todo: finish
        $identifier = null;
        if ($checkConstraints)
        {
            $semLock = '103218'; //SNSyncLock
            //Lock
            $identifier = MainWPUtility::getLockIdentifier($semLock);

            //Check the delays
            //In MS
            $minimumDelay = ((get_option('mainwp_minimumDelay') == false) ? 100 : get_option('mainwp_minimumDelay'));
            if ($minimumDelay > 0) $minimumDelay = $minimumDelay / 1000;
            $minimumIPDelay = ((get_option('mainwp_minimumIPDelay') == false) ? 300 : get_option('mainwp_minimumIPDelay'));
            if ($minimumIPDelay > 0) $minimumIPDelay = $minimumIPDelay / 1000;

            MainWPUtility::endSession();
            $delay = true;
            while ($delay)
            {
                MainWPUtility::lock($identifier);

                if ($minimumDelay > 0)
                {
                    //Check last request overall
                    $lastRequest = MainWPDB::Instance()->getLastRequestTimestamp();
                    if ($lastRequest > ((microtime(true)) - $minimumDelay))
                    {
                        //Delay!
                        MainWPUtility::release($identifier);
                        usleep(($minimumDelay - ((microtime(true)) - $lastRequest)) * 1000 * 1000);
                        continue;
                    }
                }

                if ($minimumIPDelay > 0 && $website != null)
                {
                    //Get ip of this site url
                    $ip = MainWPDB::Instance()->getWPIp($website->id);

                    if ($ip != null && $ip != '')
                    {
                        //Check last request for this site
                        $lastRequest = MainWPDB::Instance()->getLastRequestTimestamp($ip);

                        //Check last request for this subnet?
                        if ($lastRequest > ((microtime(true)) - $minimumIPDelay))
                        {
                            //Delay!
                            MainWPUtility::release($identifier);
                            usleep(($minimumIPDelay - ((microtime(true)) - $lastRequest)) * 1000 * 1000);
                            continue;
                        }
                    }
                }

                $delay = false;
            }

            //Check the simultaneous requests
            $maximumRequests = ((get_option('mainwp_maximumRequests') == false) ? 50 : get_option('mainwp_maximumRequests'));
            $maximumIPRequests = ((get_option('mainwp_maximumIPRequests') == false) ? 5 : get_option('mainwp_maximumIPRequests'));

            $first = true;
            $delay = true;
            while ($delay)
            {
                if (!$first) MainWPUtility::lock($identifier);
                else $first = false;

                //Clean old open requests (may have timed out or something..)
                MainWPDB::Instance()->closeOpenRequests();

                if ($maximumRequests > 0)
                {
                    $nrOfOpenRequests = MainWPDB::Instance()->getNrOfOpenRequests();
                    if ($nrOfOpenRequests >= $maximumRequests)
                    {
                        //Delay!
                        MainWPUtility::release($identifier);
                        //Wait 200ms
                        usleep(200000);
                        continue;
                    }
                }

                if ($maximumIPRequests > 0 && $website != null)
                {
                    //Get ip of this site url
                    $ip = MainWPDB::Instance()->getWPIp($website->id);

                    if ($ip != null && $ip != '')
                    {
                        $nrOfOpenRequests = MainWPDB::Instance()->getNrOfOpenRequests($ip);
                        if ($nrOfOpenRequests >= $maximumIPRequests)
                        {
                            //Delay!
                            MainWPUtility::release($identifier);
                            //Wait 200ms
                            usleep(200000);
                            continue;
                        }
                    }
                }

                $delay = false;
            }
        }

        if ($website != null)
        {
            //Log the start of this request!
            MainWPDB::Instance()->insertOrUpdateRequestLog($website->id, null, microtime(true), null);
        }

        if ($identifier != null)
        {
            //Unlock
            MainWPUtility::release($identifier);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        if ((ini_get('max_execution_time') != 0) && (ini_get('max_execution_time') < 300))
        {
            curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        }
        $data = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        $real_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        $host = parse_url($real_url, PHP_URL_HOST);
        $ip = gethostbyname($host);

        if ($website != null)
        {
            MainWPDB::Instance()->insertOrUpdateRequestLog($website->id, $ip, null, microtime(true));
        }

        if (($data === false) && ($http_status == 0)) {
            throw new MainWPException('HTTPERROR', $err);
        }
        else if (preg_match('/<mainwp>(.*)<\/mainwp>/', $data, $results) > 0) {
            $result = $results[1];
            $information = unserialize(base64_decode($result));
            return $information;
        }
        else
        {
            throw new MainWPException('NOMAINWP', $url);
        }
    }

    static function ctype_digit($str)
    {
        return (is_string($str) || is_int($str) || is_float($str)) && preg_match('/^\d+\z/', $str);
    }

    static function log($text)
    {

    }

    public static function downloadToFile($url, $file)
    {
        if (file_exists($file)) {
            @unlink($file);
        }

        if (!file_exists(dirname($file)))
        {
            @mkdir(dirname($file), 0777, true);
        }

        if (!file_exists(dirname($file)))
        {
            throw new MainWPException(__('Could not create directory to download the file.'));
        }

        $fp = fopen($file, 'w');
        $agent= 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    static function getBaseDir()
    {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . DIRECTORY_SEPARATOR;
    }

    public static function getMainWPDir()
    {
        $upload_dir = wp_upload_dir();
        $dir = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'mainwp' . DIRECTORY_SEPARATOR;
        $url = $upload_dir['baseurl'] . '/mainwp/';
        if (!file_exists($dir)) {
            @mkdir($dir, 0777, true);
        }
        if (!file_exists($dir . 'index.php'))
        {
            @touch($dir . 'index.php');
        }
        return array($dir, $url);
    }

    public static function getMainWPSpecificDir($dir = null)
    {
        if (MainWPSystem::Instance()->isSingleUser())
        {
            $userid = 0;
        }
        else
        {
            global $current_user;
            $userid = $current_user->ID;
        }
        $dirs = self::getMainWPDir();
        return $dirs[0] . $userid . ($dir != null ? DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR : '');
    }

    public static function getMainWPSpecificUrl($dir)
    {
        if (MainWPSystem::Instance()->isSingleUser())
        {
            $userid = 0;
        }
        else
        {
            global $current_user;
            $userid = $current_user->ID;
        }
        $dirs = self::getMainWPDir();
        return $dirs[1] . $userid . '/' . $dir . '/';
    }

    public static function getAlexaRank($domain)
    {
        $remote_url = 'http://data.alexa.com/data?cli=10&dat=snbamz&url=' . trim($domain);
        $search_for = '<POPULARITY URL';
        $part = '';
        if ($handle = @fopen($remote_url, "r"))
        {
            while (!feof($handle))
            {
                $part .= fread($handle, 100);
                $pos = strpos($part, $search_for);
                if ($pos === false)
                    continue;
                else
                    break;
            }
            $part .= fread($handle, 100);
            fclose($handle);
        }
        if (!stristr($part, $search_for)) return 0;

        $str = explode($search_for, $part);
        $str = array_shift(explode('"/>', $str[1]));
        $str = explode('TEXT="', $str);

        return $str[1];
    }


    protected static function StrToNum($Str, $Check, $Magic)
    {
        $Int32Unit = 4294967296; // 2^32

        $length = strlen($Str);
        for ($i = 0; $i < $length; $i++)
        {
            $Check *= $Magic;
            //If the float is beyond the boundaries of integer (usually +/- 2.15e+9 = 2^31),
            //  the result of converting to integer is undefined
            //  refer to http://www.php.net/manual/en/language.types.integer.php
            if ($Check >= $Int32Unit) {
                $Check = ($Check - $Int32Unit * (int)($Check / $Int32Unit));
                //if the check less than -2^31
                $Check = ($Check < -2147483648) ? ($Check + $Int32Unit) : $Check;
            }
            $Check += ord($Str{$i});
        }
        return $Check;
    }

//--> for google pagerank
/*
* Genearate a hash for a url
*/
    protected static function HashURL($String)
    {
        $Check1 = MainWPUtility::StrToNum($String, 0x1505, 0x21);
        $Check2 = MainWPUtility::StrToNum($String, 0, 0x1003F);

        $Check1 >>= 2;
        $Check1 = (($Check1 >> 4) & 0x3FFFFC0) | ($Check1 & 0x3F);
        $Check1 = (($Check1 >> 4) & 0x3FFC00) | ($Check1 & 0x3FF);
        $Check1 = (($Check1 >> 4) & 0x3C000) | ($Check1 & 0x3FFF);

        $T1 = (((($Check1 & 0x3C0) << 4) | ($Check1 & 0x3C)) << 2) | ($Check2 & 0xF0F);
        $T2 = (((($Check1 & 0xFFFFC000) << 4) | ($Check1 & 0x3C00)) << 0xA) | ($Check2 & 0xF0F0000);

        return ($T1 | $T2);
    }

    //--> for google pagerank
/*
* genearate a checksum for the hash string
*/
    protected static function CheckHash($Hashnum)
    {
        $CheckByte = 0;
        $Flag = 0;

        $HashStr = sprintf('%u', $Hashnum);
        $length = strlen($HashStr);

        for ($i = $length - 1; $i >= 0; $i--)
        {
            $Re = $HashStr{$i};
            if (1 === ($Flag % 2)) {
                $Re += $Re;
                $Re = (int)($Re / 10) + ($Re % 10);
            }
            $CheckByte += $Re;
            $Flag++;
        }

        $CheckByte %= 10;
        if (0 !== $CheckByte) {
            $CheckByte = 10 - $CheckByte;
            if (1 === ($Flag % 2)) {
                if (1 === ($CheckByte % 2)) {
                    $CheckByte += 9;
                }
                $CheckByte >>= 1;
            }
        }

        return '7' . $CheckByte . $HashStr;
    }

    //get google pagerank
    public static function getpagerank($url)
    {
        $query = "http://toolbarqueries.google.com/tbr?client=navclient-auto&ch=" . MainWPUtility::CheckHash(MainWPUtility::HashURL($url)) . "&features=Rank&q=info:" . $url . "&num=100&filter=0";
        $data = MainWPUtility::file_get_contents_curl($query);
        $pos = strpos($data, "Rank_");
        if ($pos === false) {
        }
        else
        {
            $pagerank = substr($data, $pos + 9);
            return $pagerank;
        }
    }

    protected static function file_get_contents_curl($url)
    {
        $agent= 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

    public static function getGoogleCount($domain)
    {
        $content = file_get_contents('http://ajax.googleapis.com/ajax/services/' .
                'search/web?v=1.0&filter=0&q=site:' . urlencode($domain));
        $data = json_decode($content);

        if (empty($data)) return 0;
        if (!property_exists($data, 'responseData')) return 0;
        if (!property_exists($data->responseData, 'cursor')) return 0;
        if (!property_exists($data->responseData->cursor, 'estimatedResultCount')) return 0;

        return intval($data->responseData->cursor->estimatedResultCount);
    }

    public static function countRecursive($array, $levels)
    {
        if ($levels == 0) return count($array);
        $levels--;

        $count = 0;
        foreach ($array as $value)
        {
            if (is_array($value) && ($levels > 0)) {
                $count += MainWPUtility::countRecursive($value, $levels - 1);
            }
            else
            {
                $count += count($value);
            }
        }
        return $count;
    }

    public static function sortmulti($array, $index, $order, $natsort = FALSE, $case_sensitive = FALSE)
    {
        $sorted = array();
        if (is_array($array) && count($array) > 0) {
            foreach (array_keys($array) as $key)
                $temp[$key] = $array[$key][$index];
            if (!$natsort) {
                if ($order == 'asc')
                    asort($temp);
                else
                    arsort($temp);
            }
            else
            {
                if ($case_sensitive === true)
                    natsort($temp);
                else
                    natcasesort($temp);
                if ($order != 'asc')
                    $temp = array_reverse($temp, TRUE);
            }
            foreach (array_keys($temp) as $key)
                if (is_numeric($key))
                    $sorted[] = $array[$key];
                else
                    $sorted[$key] = $array[$key];
            return $sorted;
        }
        return $sorted;
    }

    public static function getSubArrayHaving($array, $index, $value)
    {
        $output = array();
        if (is_array($array) && count($array) > 0) {
            foreach ($array as $arrvalue)
            {
                if ($arrvalue[$index] == $value) $output[] = $arrvalue;
            }
        }
        return $output;
    }

    public static function http_post($request, $http_host, $path, $port = 80, $pApplication = 'main', $throwException = false) {

        if ($pApplication == 'main') $pApplication = 'MainWP/1.1';//.MainWPSystem::Instance()->getVersion();
        else $pApplication = 'MainWPExtension/'.$pApplication.'/v';

        // use the WP HTTP class if it is available
//        if ( function_exists( 'wp_remote_post' ) ) {
        $http_args = array(
            'body'			=> $request,
            'headers'		=> array(
                'Content-Type'	=> 'application/x-www-form-urlencoded; ' .
                        'charset=' . get_option( 'blog_charset' ),
                'Host'			=> $http_host,
                'User-Agent'	=> $pApplication
            ),
            'httpversion'	=> '1.0',
            'timeout'		=> 15
        );
        $mainwp_url = "http://{$http_host}{$path}";

        $response = wp_remote_post( $mainwp_url, $http_args );
//        return $response;

        if ( is_wp_error( $response ) )
        {
            if ($throwException)
            {
                throw new Exception($response->get_error_message());
            }
            return '';
        }

        return array( $response['headers'], $response['body'] );
//        } else {
//            $http_request  = "POST $path HTTP/1.0\r\n";
//            $http_request .= "Host: $host\r\n";
//            $http_request .= 'Content-Type: application/x-www-form-urlencoded; charset=' . get_option('blog_charset') . "\r\n";
//            $http_request .= "Content-Length: {$content_length}\r\n";
//            $http_request .= "User-Agent: {$akismet_ua}\r\n";
//            $http_request .= "\r\n";
//            $http_request .= $request;
//
//            $response = '';
//            if( false != ( $fs = @fsockopen( $http_host, $port, $errno, $errstr, 10 ) ) ) {
//                fwrite( $fs, $http_request );
//
//                while ( !feof( $fs ) )
//                    $response .= fgets( $fs, 1160 ); // One TCP-IP packet
//                fclose( $fs );
//                $response = explode( "\r\n\r\n", $response, 2 );
//            }
//            return $response;
//        }
    }

    static function trimSlashes($elem) { return trim($elem, '/'); }

    public static function renderToolTip($pText, $pUrl = null, $pImage = 'images/info.png', $style = null)
    {
        $output = '<span class="tooltipcontainer">';
        if ($pUrl != null) $output .= '<a href="' . $pUrl . '" target="_blank">';
        $output .= '<img src="' . plugins_url($pImage, dirname(__FILE__)) . '" class="tooltip" style="'.($style == null ? '' : $style).'" />';
        if ($pUrl != null) $output .= '</a>';
        $output .= '<span class="tooltipcontent" style="display: none;">' . $pText;
        if ($pUrl != null) $output .= ' (Click to read more)';
        $output .= '</span></span>';
        echo $output;
    }

    public static function encrypt($str, $pass)
    {
        $pass = str_split(str_pad('', strlen($str), $pass, STR_PAD_RIGHT));
        $stra = str_split($str);
        foreach ($stra as $k => $v)
        {
            $tmp = ord($v) + ord($pass[$k]);
            $stra[$k] = chr($tmp > 255 ? ($tmp - 256) : $tmp);
        }
        return base64_encode(join('', $stra));
    }

    public static function decrypt($str, $pass)
    {
        $str = base64_decode($str);
        $pass = str_split(str_pad('', strlen($str), $pass, STR_PAD_RIGHT));
        $stra = str_split($str);
        foreach ($stra as $k => $v)
        {
            $tmp = ord($v) - ord($pass[$k]);
            $stra[$k] = chr($tmp < 0 ? ($tmp + 256) : $tmp);
        }
        return join('', $stra);
    }

    public static function encrypt_legacy($string, $key)
    {
        if (function_exists('mcrypt_encrypt'))
            return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $string, MCRYPT_MODE_CBC, md5(md5($key))));
        else
            return base64_encode($string);
    }

    public static function decrypt_legacy($encrypted, $key)
    {
        if (function_exists('mcrypt_encrypt'))
            return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($encrypted), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
        else
            return base64_decode($encrypted);
    }

    /**
     * @return WP_Filesystem_Base
     */
    public static function getWPFilesystem()
    {
        global $wp_filesystem;

        if (empty($wp_filesystem))
        {
            ob_start();
            if (file_exists(ABSPATH . '/wp-admin/includes/screen.php')) include_once(ABSPATH . '/wp-admin/includes/screen.php');
            if (file_exists(ABSPATH . '/wp-admin/includes/template.php')) include_once(ABSPATH . '/wp-admin/includes/template.php');
            $creds = request_filesystem_credentials('test');
            ob_end_clean();
            if (empty($creds))
            {
                define('FS_METHOD', 'direct');
            }
            $init = WP_Filesystem($creds);
        }
        else
        {
            $init = true;
        }

        return $init;
    }

    public static function sanitize($str)
    {
        return preg_replace("/[\\\\\/\:\"\*\?\<\>\|]+/", "", $str);
    }

    public static function formatEmail($to, $body)
    {
        return '<br>
<div>
            <br>
            <div style="background:#ffffff;padding:0 1.618em;font:13px/20px Helvetica,Arial,Sans-serif;padding-bottom:50px!important">
                <div style="width:600px;background:#fff;margin-left:auto;margin-right:auto;margin-top:10px;margin-bottom:25px;padding:0!important;border:10px Solid #fff;border-radius:10px;overflow:hidden">
                    <div style="display: block; width: 100% ; background-image: url(http://mainwp.com/wp-content/uploads/2013/02/debut_light.png) ; background-repeat: repeat; border-bottom: 2px Solid #7fb100 ; overflow: hidden;">
                      <div style="display: block; width: 95% ; margin-left: auto ; margin-right: auto ; padding: .5em 0 ;">
                         <div style="float: left;"><a href="http://mainwp.com"><img src="http://mainwp.com/wp-content/uploads/2013/07/MainWP-Logo-1000-300x62.png" alt="MainWP" height="30"/></a></div>
                         <div style="float: right; margin-top: .6em ;">
                            <span style="display: inline-block; margin-right: .8em;"><a href="http://extensions.mainwp.com" style="font-family: Helvetica, Sans; color: #7fb100; text-transform: uppercase; font-size: 14px;">Extensions</a></span>
                            <span style="display: inline-block; margin-right: .8em;"><a style="font-family: Helvetica, Sans; color: #7fb100; text-transform: uppercase; font-size: 14px;" href="http://mainwp.com/forum">Support</a></span>
                            <span style="display: inline-block; margin-right: .8em;"><a style="font-family: Helvetica, Sans; color: #7fb100; text-transform: uppercase; font-size: 14px;" href="http://docs.mainwp.com">Documentation</a></span>
                            <span style="display: inline-block; margin-right: .5em;" class="mainwp-memebers-area"><a href="http://mainwp.com/member/login/index" style="padding: .6em .5em ; border-radius: 50px ; -moz-border-radius: 50px ; -webkit-border-radius: 50px ; background: #1c1d1b; border: 1px Solid #000; color: #fff !important; font-size: .9em !important; font-weight: normal ; -webkit-box-shadow:  0px 0px 0px 5px rgba(0, 0, 0, .1); box-shadow:  0px 0px 0px 5px rgba(0, 0, 0, .1);">Members Area</a></span>
                         </div><div style="clear: both;"></div>
                      </div>
                    </div>
                    <div>
                        <p>Hello MainWP User!<br></p>
                        ' . $body . '
                        <div></div>
                        <br />
                        <div>MainWP</div>
                        <div><a href="http://www.MainWP.com" target="_blank">www.MainWP.com</a></div>
                        <p></p>
                    </div>

                    <div style="display: block; width: 100% ; background: #1c1d1b;">
                      <div style="display: block; width: 95% ; margin-left: auto ; margin-right: auto ; padding: .5em 0 ;">
                        <div style="padding: .5em 0 ; float: left;"><p style="color: #fff; font-family: Helvetica, Sans; font-size: 12px ;">© 2013 MainWP. All Rights Reserved.</p></div>
                        <div style="float: right;"><a href="http://mainwp.com"><img src="http://mainwp.com/wp-content/uploads/2013/07/MainWP-Icon-300.png" height="45"/></a></div><div style="clear: both;"></div>
                      </div>
                   </div>
                </div>
                <center>
                    <br><br><br><br><br><br>
                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#ffffff;border-top:1px solid #e5e5e5">
                        <tbody><tr>
                            <td align="center" valign="top" style="padding-top:20px;padding-bottom:20px">
                                <table border="0" cellpadding="0" cellspacing="0">
                                    <tbody><tr>
                                        <td align="center" valign="top" style="color:#606060;font-family:Helvetica,Arial,sans-serif;font-size:11px;line-height:150%;padding-right:20px;padding-bottom:5px;padding-left:20px;text-align:center">
                                            This email is sent from your MainWP Dashboard.
                                            <br>
                                            If you do not wish to receive these notices please re-check your preferences in the MainWP Settings page.
                                            <br>
                                            <br>
                                        </td>
                                    </tr>
                                </tbody></table>
                            </td>
                        </tr>
                    </tbody></table>

                </center>
            </div>
</div>
<br>';
    }

    public static function endSession()
    {
        session_write_close();
        if (ob_get_length() > 0) ob_end_flush();
    }

    public static function getLockIdentifier($pLockName)
    {
        if (($pLockName == null) || ($pLockName == false)) return false;

        if (function_exists('sem_get')) return sem_get($pLockName);
        else
        {
            $fh = @fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lock' . $pLockName . '.txt', 'w+');
            if (!$fh) return false;

            return $fh;
        }

        return false;
    }

    public static function lock($pIdentifier)
    {
        if (($pIdentifier == null) || ($pIdentifier == false)) return false;

        if (function_exists('sem_acquire')) return sem_acquire($pIdentifier);
        else
        {
            //Retry lock 3 times
            for ($i = 0; $i < 3; $i++)
            {
                if (@flock($pIdentifier, LOCK_EX))
                {
                    // acquire an exclusive lock
                    return $pIdentifier;
                }
                else
                {
                    //Sleep before lock retry
                    sleep(1);
                }
            }
            return false;
        }

        return false;
    }

    public static function release($pIdentifier)
    {
        if (($pIdentifier == null) || ($pIdentifier == false)) return false;

        if (function_exists('sem_release')) return sem_release($pIdentifier);
        else
        {
            @flock($pIdentifier, LOCK_UN); // release the lock
            @fclose($pIdentifier);
        }
        return false;
    }

    public static function getTimestamp($timestamp)
    {
        $gmtOffset = get_option('gmt_offset');

        return ($gmtOffset ? ($gmtOffset * HOUR_IN_SECONDS) + $timestamp : $timestamp);
    }

    public static function formatTimestamp($timestamp)
    {
        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
    }

    public static function human_filesize($bytes, $decimals = 2)
    {
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

    public static function mapSite(&$website, $keys)
    {
        $outputSite = array();
        foreach ($keys as $key)
        {
            $outputSite[$key] = $website->$key;
        }
        return (object)$outputSite;
    }

    public static function can_edit_website(&$website)
    {
        if ($website == null) return false;

        //Everyone may change this website
        if (MainWPSystem::Instance()->isSingleUser()) return true;

        global $current_user;
        return ($website->userid == $current_user->ID);
    }

    public static function can_edit_group(&$group)
    {
        if ($group == null) return false;

        //Everyone may change this website
        if (MainWPSystem::Instance()->isSingleUser()) return true;

        global $current_user;
        return ($group->userid == $current_user->ID);
    }

    public static function can_edit_backuptask(&$task)
    {
        if ($task == null) return false;

        if (MainWPSystem::Instance()->isSingleUser()) return true;

        global $current_user;
        return ($task->userid == $current_user->ID);
    }

    public static function get_current_wpid()
    {
        global $current_user;
        return $current_user->current_site_id;
    }

    public static function set_current_wpid($wpid)
    {
        global $current_user;
        $current_user->current_site_id = $wpid;
    }

    public static function array_merge($arr1, $arr2)
    {
        if (!is_array($arr1) && !is_array($arr2)) return array();
        if (!is_array($arr1)) return $arr2;
        if (!is_array($arr2)) return $arr1;

        $output = array();
        foreach ($arr1 as $el)
        {
            $output[] = $el;
        }
        foreach ($arr2 as $el)
        {
            $output[] = $el;
        }
        return $output;
    }

    public static function getCronSchedules($schedules)
    {
        $schedules['5minutely'] = array(
            'interval' => 5 * 60, // 5minutes in seconds
            'display' => __('Once every 5 minutes', 'mainwp'),
        );
        $schedules['minutely'] = array(
            'interval' => 1 * 60, // 1minute in seconds
            'display' => __('Once every minute', 'mainwp'),
        );
        return $schedules;
    }


    public static function mime_content_type($filename)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        }

		if (function_exists('mime_content_type')) {
            return mime_content_type($filename);
        }

		$mime_types = array(

			'txt' => 'text/plain',
			'htm' => 'text/html',
			'html' => 'text/html',
			'php' => 'text/html',
			'css' => 'text/css',
			'js' => 'application/javascript',
			'json' => 'application/json',
			'xml' => 'application/xml',
			'swf' => 'application/x-shockwave-flash',
			'flv' => 'video/x-flv',

			// images
			'png' => 'image/png',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'gif' => 'image/gif',
			'bmp' => 'image/bmp',
			'ico' => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif' => 'image/tiff',
			'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml',

			// archives
			'zip' => 'application/zip',
			'rar' => 'application/x-rar-compressed',
			'exe' => 'application/x-msdownload',
			'msi' => 'application/x-msdownload',
			'cab' => 'application/vnd.ms-cab-compressed',

			// audio/video
			'mp3' => 'audio/mpeg',
			'qt' => 'video/quicktime',
			'mov' => 'video/quicktime',

			// adobe
			'pdf' => 'application/pdf',
			'psd' => 'image/vnd.adobe.photoshop',
			'ai' => 'application/postscript',
			'eps' => 'application/postscript',
			'ps' => 'application/postscript',

			// ms office
			'doc' => 'application/msword',
			'rtf' => 'application/rtf',
			'xls' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',

			// open office
			'odt' => 'application/vnd.oasis.opendocument.text',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		);

		$ext = strtolower(array_pop(explode('.',$filename)));
		if (array_key_exists($ext, $mime_types)) {
			return $mime_types[$ext];
		}

        return 'application/octet-stream';
    }
}

?>
