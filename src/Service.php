<?php

namespace Signalize\ModuleHue;

use Signalize\Cache;
use Signalize\Service\Base;
use Signalize\Socket\Package;

class Service extends Base
{

    public function worker()
    {
//        $device = new Device("/dev/ttyUSB0", 115200);
//        $device->subscribe(function (Package $data) {
//            $this->send($data);
//        });
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

        $address = $package->offsetGet('address');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://" . $address . "/api");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'devicetype=signalize');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = json_decode(curl_exec($ch));
        curl_close($ch);

        if ($result->error) {
            throw new \Exception($result->error, 415);
        }


        $data = Cache::get('module-hue');
        $data->username = $result;
        Cache::save('module-hue', $data);

        return new Package($data);
    }

}