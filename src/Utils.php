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
    public $http;
    public $res;

    public function __construct()
    {
        parent::__construct();
        $this->initColor();
        $this->http = new Http();
    }

    public function get($url, $data = [], $header = [])
    {
        return $this->request($url, $data, $header);
    }


    public function post($url, $data = [], $header = [], $atUpload = true)
    {
        return $this->request($url, $data, $header, $atUpload);
    }

    public function request($url, $data, $header, $atUpload = false)
    {
        if (!Z::checkValue($url, 'url')) {
            $url = TEST_HOST . $url;
        }

        return Z::tap($this->result($this->http->request('post', $url, $data, $header, 0, null, true, $atUpload)), function ($res) {

        });
    }

    private function result($res)
    {
        if ($this->http->errorCode() !== 0) {
            $info = $this->http->info();
            if (TEST_HOST && Z::strBeginsWith(Z::arrayGet($info, 'url'), TEST_HOST)) {
                $parse = parse_url(TEST_HOST);
                $this->printStrN();
                $this->error('Please start service(' . TEST_HOST . ') first,execute the test');
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
}
