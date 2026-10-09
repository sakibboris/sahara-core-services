<?php
declare(strict_types=1);
namespace Sahara\Core\Hijri;
use Sahara\Core\Contracts\HijriCalendarProvider;
use Sahara\Core\Support\Inputs;
final readonly class LocalHijriCalendarProvider implements HijriCalendarProvider {
    private \DateTimeImmutable $epoch;
    public function __construct(public string $method='civil') {
        $this->epoch=match($method){
            'civil'=>Inputs::date('0622-07-19'),
            'tabular-astronomical'=>Inputs::date('0622-07-18'),
            default=>throw new \InvalidArgumentException('Unsupported Hijri method.')
        };
    }
    private function start(int $year): int {return 354*($year-1)+(int)floor((3+11*$year)/30);}
    private function monthStart(int $month): int {return (int)ceil(29.5*($month-1));}
    public function daysInMonth(int $year,int $month): int {
        if($year<1 || $year>9665 || $month<1 || $month>12) throw new \InvalidArgumentException('Hijri year/month out of supported range.');
        return $month===12 ? (((11*$year+14)%30<11)?30:29) : ($month%2===1?30:29);
    }
    public function toGregorian(int $year,int $month,int $day): string {
        if($day<1 || $day>$this->daysInMonth($year,$month)) throw new \InvalidArgumentException('Invalid Hijri day.');
        return $this->epoch->modify('+'.($this->start($year)+$this->monthStart($month)+$day-1).' days')->format('Y-m-d');
    }
    public function fromGregorian(string $date): HijriDate {
        $value=Inputs::date($date);
        $days=(int)$this->epoch->diff($value)->format('%r%a');
        if($days<0) throw new \InvalidArgumentException('Dates before the selected Hijri epoch are unsupported.');
        $year=(int)floor((30*$days+10646)/10631);
        while($this->start($year+1)<=$days)$year++;
        while($this->start($year)>$days)$year--;
        $dayOfYear=$days-$this->start($year);
        $month=1;
        while($month<12 && $this->monthStart($month+1)<=$dayOfYear)$month++;
        return new HijriDate($year,$month,$dayOfYear-$this->monthStart($month)+1,$date,$this->method);
    }
}
