<?php
declare(strict_types=1);
namespace Sahara\Core\PrayerTimes;
final readonly class PrayerSettings {
    public function __construct(
        public string $timezone='UTC',
        public string $method='mwl',
        public float $fajrAngle=18,
        public float $ishaAngle=17,
        public ?int $ishaMinutes=null,
        public int $asrFactor=1,
        public string $highLatitudeRule='angle',
        public array $adjustments=[],
    ) {
        try {new \DateTimeZone($timezone);} catch(\Throwable){throw new \InvalidArgumentException('Invalid timezone.');}
        if(!is_finite($fajrAngle)||!is_finite($ishaAngle)||$fajrAngle<=0||$fajrAngle>30||$ishaAngle<=0||$ishaAngle>30
            || !in_array($asrFactor,[1,2],true)||!in_array($highLatitudeRule,['none','middle','seventh','angle'],true)
            || ($ishaMinutes!==null && ($ishaMinutes<1||$ishaMinutes>240))||trim($method)==='') {
            throw new \InvalidArgumentException('Invalid prayer calculation parameters.');
        }
        foreach($adjustments as $key=>$minutes){
            if(!in_array($key,['fajr','sunrise','dhuhr','asr','maghrib','isha'],true)||!is_int($minutes)||abs($minutes)>120)throw new \InvalidArgumentException('Invalid minute adjustment.');
        }
    }
}
