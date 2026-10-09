<?php
declare(strict_types=1);
namespace Sahara\Core\Contracts;
use Sahara\Core\PrayerTimes\PrayerSettings;
interface PrayerTimeProvider {
    public function calculate(string $date,float $latitude,float $longitude,PrayerSettings $settings): array;
}
