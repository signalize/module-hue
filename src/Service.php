<?php

namespace Signalize\ModuleHue;

use Signalize\Cache;
use Signalize\Service\Base;
use Signalize\Socket\Package;

class Service extends Base
{

    public function worker()
    {
        $lights = $this->callAPI('lights');
        var_dump($lights);


        sleep(1);
    }

    /**
     * @param Package $package
     * @return Package|null
     * @throws \Exception
     */
    public function execute(Package $package)
    {
        switch ($package->offsetGet('execute')) {
            case "pair":
                return $this->pair($package);
            case "call":
                return $this->call($package);
        }
    }

    /**
     * @param Package $package
     * @return Package
     * @throws \Exception
     */
    private function pair(Package $package)
    {
        if (!$package->offsetExists('address')) {
            throw new \Exception('Address property is missing!', 415);
        }

        # Save address to config
        Cache::set('module-hue', 'address', $package->offsetGet('address'));
        $result = $this->callAPI(null, ['devicetype' => 'signalize']);
        Cache::set('module-hue', 'username', $result[0]->success->username);

        return new Package(Cache::open('module-hue'));
    }


    /**
     * @param Package $package
     * @return Package
     * @throws \Exception
     */
    private function call(Package $package)
    {
        if (!$package->offsetGet('endpoint')) {
            throw new \Exception('Cannot execute! No endpoint defined!', 415);
        }

        $credential = Cache::open('module-hue');
        if (!$credential->address) {
            throw new \Exception('Cannot execute! No paired address!', 415);
        }

        $endpoint = $package->offsetGet('endpoint');
        $data = $package->offsetGet('data');

        $result = $this->callAPI($endpoint, $data);
        return new Package($result);
    }


    /**
     * @param null $uri
     * @param null $data
     * @return bool|string
     * @throws \Exception
     */
    private function callAPI($uri = null, $data = null)
    {
        $settings = Cache::open('module-hue');
        if (!$settings->address) {
            throw new \Exception('Cannot execute! No saved settings found!', 415);
        }

        if ($uri !== null) {
            if (!$settings->username) {
                throw new \Exception('Cannot execute! Not paired to device!', 415);
            }
            $uri = "/" . $settings->username . "/" . $uri;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://" . $settings->address . "/api" . $uri);
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, is_null($uri) ? "POST" : "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $json = json_decode($result);
        curl_close($ch);

        if (isset($result[0]) && $result[0]->error) {
            throw new \Exception(json_encode($result[0]->error), 415);
        }
        return $json;
    }

}