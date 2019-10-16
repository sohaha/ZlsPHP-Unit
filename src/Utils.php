<?php
declare(strict_types=1);

namespace Zls\Unit;

use Z;
use Zls\Action\Http;
use Zls\Command\Utils as log;

defined('TEST_HOST') || define('TEST_HOST', 'http://127.0.0.1:3780');

trait Utils
{
    use log;
    /** @var \Zls\Action\Http */
    public $http;
    public $res;

    public function __construct()
    {
        parent::__construct();
        $this->initUtils();
    }

    public function log($_)
    {
        $log = '';
        if (is_array($_)) {
            foreach ($_ as $key => $value) {
                try {
                    $value = is_string($value) ? $value : var_export($value, true);
                } catch (\Exception $e) {
                    $value = is_string($value) ? $value : print_r($value, true);
                }
                $log .= $key . ' : ' . $value . PHP_EOL;
            }
        } else {
            $log = print_r($_, true);
        }
        $trace        = debug_backtrace();
        $filepath     = Z::arrayGet($trace, '0.class');
        $line         = Z::arrayGet($trace, '0.line');
        $functionName = Z::arrayGet($trace, '1.function');
        $log          = $this->color($filepath . "@" . $functionName . '::' . $line . "\n", 'dark_gray');
        $log          = $log . $_ . PHP_EOL . PHP_EOL;
        fwrite(STDERR, $log);
    }

    public function initUtils()
    {
        $this->initColor();
        $this->http = new Http();
    }

    public function get($url, $data = [], $header = [])
    {
        return $this->request('get', $url, $data, $header);
    }

    public function post($url, $data = [], $header = [], $atUpload = true)
    {
        return $this->request('post', $url, $data, $header, $atUpload);
    }

    public function put($url, $data = [], $header = [], $atUpload = true)
    {
        return $this->request('put', $url, $data, $header, $atUpload);
    }

    public function delete($url, $data = [], $header = [], $atUpload = true)
    {
        return $this->request('delete', $url, $data, $header, $atUpload);
    }

    public function getJSON($url, $data = [], $header = [])
    {
        return $this->requestJSON('get', $url, $data, $header);
    }

    public function postJSON($url, $data = [], $header = [], $atUpload = true)
    {
        return $this->requestJSON('post', $url, $data, $header, $atUpload);
    }

    public function putJSON($url, $data = [], $header = [], $atUpload = true)
    {
        return $this->requestJSON('put', $url, $data, $header, $atUpload);
    }

    public function deleteJSON($url, $data = [], $header = [], $atUpload = true)
    {
        return $this->requestJSON('delete', $url, $data, $header, $atUpload);
    }

    public function requestJSON($type, $url, $data, $header, $atUpload = false)
    {
        return $this->request($type, $url, $data, self::headerAjax($header), $atUpload);
    }

    public function request($type, $url, $data, $header, $atUpload = false)
    {
        if (!Z::checkValue($url, 'url')) {
            $url = TEST_HOST . $url;
        }

        return Z::tap($this->result($this->http->request($type, $url, $data, $header, 0, null, true, $atUpload)), function ($res) {

        });
    }

    private function result($res)
    {
        $info = $this->http->info();
        $url  = Z::arrayGet($info, 'url');
        if ($this->http->errorCode() !== 0) {
            if (TEST_HOST && Z::strBeginsWith($url, TEST_HOST)) {
                $parse = parse_url(TEST_HOST);
                $this->printStrN();
                $this->error('Please start service( ' . TEST_HOST . ' ) first,execute the test');
                if (in_array(z::arrayGet($parse, 'host'), ["127.0.0.1", "localhost"])) {
                    $port = Z::arrayGet($parse, 'port', 80);
                    $this->printStr('Start command: ');
                    $this->printStrN("php zls start -C -P {$port}", 'green', 'white');
                }
                exit;
            }
            $this->printStrN("");
            $this->error($this->http->errorMsg());

            return "";
        }
        $json = @json_decode($res, true);
        if ($json) {
            $isError = array_key_exists('file', $json) && array_key_exists('line', $json) && array_key_exists('errorCode', $json);
            if (Z::config()->getShowError() && Z::arrayGet($json, 'code') === 0 && $isError) {
                $this->printStrN("\n");
                $this->error($url);
                $this->printStrN(Z::arrayGet($json, 'msg'));
                $this->printStrN();
            }
        }

        return $res;
    }

    public function jsonToArr()
    {
        return $this->http->data(true);
    }

    public function string()
    {
        return $this->http->data();
    }

    public function code()
    {
        return $this->http->code();
    }

    public function command($command = '')
    {
        $command = z::phpPath() . ' ' . Z::realPath('zls', false, false) . ' ' . $command;

        // $this->printStrN("Command: {$command}");
        return Z::command($command);
    }

    private static function headerAjax(array $header): array
    {
        return $header + ['X-Requested-With: XMLHttpRequest'];
    }
}
