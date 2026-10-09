<?php
declare(strict_types=1);
namespace Sahara\Core\PrayerTimes;
use Sahara\Core\Contracts\PrayerTimeProvider;
use Sahara\Core\Support\Inputs;
final class LocalPrayerTimeProvider implements PrayerTimeProvider {
    private function mod(float $n,float $base): float {return fmod(fmod($n,$base)+$base,$base);}
    private function sun(float $julian): array {
        $d=$julian-2451545.0;
        $g=deg2rad($this->mod(357.529+0.98560028*$d,360));
        $q=$this->mod(280.459+0.98564736*$d,360);
        $longitude=deg2rad($q+1.915*sin($g)+0.020*sin(2*$g));
        $obliquity=deg2rad(23.439-0.00000036*$d);
        $ra=$this->mod(rad2deg(atan2(cos($obliquity)*sin($longitude),cos($longitude)))/15,24);
        $equation=$q/15-$ra;
        if($equation>12)$equation-=24;
        if($equation< -12)$equation+=24;
        return [asin(sin($obliquity)*sin($longitude)),$equation];
    }
    private function event(float $jd,float $latitude,float $longitude,float $altitude,bool $morning,float $guess): ?float {
        $lat=deg2rad($latitude);
        for($i=0;$i<3;$i++){
            [$declination,$equation]=$this->sun($jd+$guess/24);
            $denominator=cos($lat)*cos($declination);
            if(abs($denominator)<1e-12)return null;
            $cosine=(sin(deg2rad($altitude))-sin($lat)*sin($declination))/$denominator;
            if(abs($cosine)>1)return null;
            $delta=rad2deg(acos($cosine))/15;
            $guess=12-$longitude/15-$equation+($morning?-$delta:$delta);
        }
        return $guess;
    }
    public function calculate(string $date,float $latitude,float $longitude,PrayerSettings $settings): array {
        Inputs::coordinates($latitude,$longitude);$base=Inputs::date($date);$tz=new \DateTimeZone($settings->timezone);
        $year=(int)$base->format('Y');
        if($year<1800||$year>2200)throw new \InvalidArgumentException('Solar approximation validated only for 1800–2200.');
        // Select the solar cycle whose noon falls on the requested local date.
        $jd=$base->getTimestamp()/86400+2440587.5;
        [, $initialEquation]=$this->sun($jd+(12-$longitude/15)/24);
        $candidate=$base->setTimestamp($base->getTimestamp()+(int)round((12-$longitude/15-$initialEquation)*3600))->setTimezone($tz);
        $shift=(int)Inputs::date($candidate->format('Y-m-d'))->diff(Inputs::date($date))->format('%r%a');
        $base=$base->modify(($shift>=0?'+':'').$shift.' days');
        $jd=$base->getTimestamp()/86400+2440587.5;
        [$declination,$equation]=$this->sun($jd+(12-$longitude/15)/24);
        $noon=12-$longitude/15-$equation;
        $sunrise=$this->event($jd,$latitude,$longitude,-0.833,true,$noon-6);
        $sunset=$this->event($jd,$latitude,$longitude,-0.833,false,$noon+6);
        $fajr=$this->event($jd,$latitude,$longitude,-$settings->fajrAngle,true,$noon-7);
        $isha=$this->event($jd,$latitude,$longitude,-$settings->ishaAngle,false,$noon+7);
        $asrAltitude=rad2deg(atan(1/($settings->asrFactor+tan(abs(deg2rad($latitude)-$declination)))));
        $asr=abs($latitude-rad2deg($declination))>=90 ? null : $this->event($jd,$latitude,$longitude,$asrAltitude,false,$noon+3);
        $warnings=[];
        if($sunrise===null||$sunset===null){
            $fajr=null;$isha=null;$warnings[]='polar-sun-unavailable';
        } else {
            // Adjacent nights preserve seasonal change instead of assuming a constant 24-hour cycle.
            $previousSunset=$this->event($jd-1,$latitude,$longitude,-0.833,false,$noon+6);
            $nextSunrise=$this->event($jd+1,$latitude,$longitude,-0.833,true,$noon-6);
            if($settings->highLatitudeRule!=='none' && $previousSunset!==null && $nextSunrise!==null){
                $fraction=fn(float $angle):float=>match($settings->highLatitudeRule){'middle'=>0.5,'seventh'=>1/7,default=>$angle/60};
                $dawnCap=(24+$sunrise-$previousSunset)*$fraction($settings->fajrAngle);
                $nightCap=(24+$nextSunrise-$sunset)*$fraction($settings->ishaAngle);
                if($fajr===null || $sunrise-$fajr>$dawnCap){$fajr=$sunrise-$dawnCap;$warnings[]='high-latitude-adjustment';}
                if($isha===null || $isha-$sunset>$nightCap){$isha=$sunset+$nightCap;$warnings[]='high-latitude-adjustment';}
            }
            if($settings->ishaMinutes!==null)$isha=$sunset+$settings->ishaMinutes/60;
        }
        $hours=['fajr'=>$fajr,'sunrise'=>$sunrise,'dhuhr'=>$noon,'asr'=>$asr,'maghrib'=>$sunset,'isha'=>$isha];
        $times=[];
        foreach($hours as $key=>$hour){
            $times[$key]=$hour===null?null:$base->setTimestamp($base->getTimestamp()+(int)round($hour*60+($settings->adjustments[$key]??0))*60)->setTimezone($tz)->format(DATE_ATOM);
            if($hour===null && !in_array('polar-sun-unavailable',$warnings,true))$warnings[]='unavailable-'.$key;
        }
        return ['schema_version'=>1,'date'=>$date,'timezone'=>$settings->timezone,'method'=>$settings->method,
            'algorithm'=>'solar-v1','status'=>'calculated','times'=>$times,'warnings'=>array_values(array_unique($warnings)),
            'parameters'=>['fajr_angle'=>$settings->fajrAngle,'isha_angle'=>$settings->ishaAngle,'isha_minutes'=>$settings->ishaMinutes,
                'asr_factor'=>$settings->asrFactor,'high_latitude_rule'=>$settings->highLatitudeRule,'adjustments'=>$settings->adjustments]];
    }
}
