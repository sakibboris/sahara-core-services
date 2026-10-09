<?php
declare(strict_types=1);
namespace Sahara\Core\Ramadan;
use Sahara\Core\Contracts\HijriCalendarProvider;
use Sahara\Core\Support\Inputs;
final class RamadanService {
    private array $overrides=[];
    public function __construct(private readonly HijriCalendarProvider $hijri,array $overrides=[]) {
        foreach($overrides as $override){
            if(!$override instanceof OfficialOverride)throw new \InvalidArgumentException('Invalid override.');
            $key=$override->country.':'.$override->hijriYear;
            if(isset($this->overrides[$key]))throw new \InvalidArgumentException('Duplicate country/year override.');
            $calculated=$this->hijri->toGregorian($override->hijriYear,9,1);
            if(abs((int)Inputs::date($calculated)->diff(Inputs::date($override->start))->format('%r%a'))>7)throw new \InvalidArgumentException('Override is outside its Hijri year Ramadan window.');
            $this->overrides[$key]=$override;
        }
    }
    public function interval(int $year,?string $country=null): array {
        $override=$this->overrides[($country??'').':'.$year]??null;
        return ['hijri_year'=>$year,'start'=>$override?->start??$this->hijri->toGregorian($year,9,1),
            'end'=>$override?->end??Inputs::date($this->hijri->toGregorian($year,10,1))->modify('-1 day')->format('Y-m-d'),
            'status'=>$override?'confirmed':'calculated','source'=>$override?->source??'local-hijri',
            'source_url'=>$override?->sourceUrl,'country'=>$country];
    }
    public function forDate(string $date,?string $country=null): array {
        $value=Inputs::date($date);$h=$this->hijri->fromGregorian($date);$interval=$this->interval($h->year,$country);
        $active=$date>=$interval['start'] && $date<=$interval['end'];
        $next=$date<$interval['start']?$interval:$this->interval($h->year+1,$country);
        return ['schema_version'=>1,'gregorian'=>$date,'hijri'=>$h->jsonSerialize(),'is_ramadan'=>$active,
            'day'=>$active?(int)Inputs::date($interval['start'])->diff($value)->format('%a')+1:null,
            ...$interval,'next'=>$next];
    }
}
