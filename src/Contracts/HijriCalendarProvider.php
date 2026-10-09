<?php
declare(strict_types=1);
namespace Sahara\Core\Contracts;
use Sahara\Core\Hijri\HijriDate;
interface HijriCalendarProvider {
    public function fromGregorian(string $date): HijriDate;
    public function toGregorian(int $year,int $month,int $day): string;
}
