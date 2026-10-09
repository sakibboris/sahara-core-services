<?php
declare(strict_types=1);
spl_autoload_register(function(string $class): void {
    $file=__DIR__.'/../src/'.str_replace('Sahara\\Core\\','',$class);
    $file=str_replace('\\','/',$file).'.php';
    if(is_file($file)) require $file;
});
use Sahara\Core\Qibla\QiblaCalculator;
use Sahara\Core\Hijri\LocalHijriCalendarProvider;
use Sahara\Core\Ramadan\RamadanService;
use Sahara\Core\Ramadan\OfficialOverride;
use Sahara\Core\PrayerTimes\LocalPrayerTimeProvider;
use Sahara\Core\PrayerTimes\PrayerSettings;
$passed=0; $failed=0;
function test(string $name, callable $f): void {
    global $passed,$failed;
    try {$f(); $passed++; echo "PASS $name\n";} catch(Throwable $e) {$failed++; echo "FAIL $name: ".$e->getMessage()."\n";}
}
function same(mixed $expected,mixed $actual): void { if($expected!==$actual) throw new RuntimeException(var_export($expected,true).' != '.var_export($actual,true)); }
function near(float $expected,float $actual,float $tolerance): void {if(!is_finite($actual)||abs($expected-$actual)>$tolerance)throw new RuntimeException("$actual outside tolerance of $expected");}
function rejects(callable $f,string $class=InvalidArgumentException::class): void {try{$f();}catch(Throwable $e){if($e instanceof $class)return;throw $e;}throw new RuntimeException('Expected rejection');}
test('Qibla trusted city bearings',function(){
 $q=new QiblaCalculator();near(243.8,$q->calculate(24.7136,46.6753)->bearing,0.3);
 near(119.0,$q->calculate(51.5074,-0.1278)->bearing,0.3);
 near(58.48,$q->calculate(40.7128,-74.0060)->bearing,0.3);
 $r=$q->calculate(24.7136,46.6753);near(790,$r->distanceKm,10);same(1,$r->jsonSerialize()['schema_version']);
});
test('Qibla invalid and ambiguous coordinates',function(){
 $q=new QiblaCalculator();
 foreach([[91,0],[0,181],[NAN,1],[INF,2]] as [$a,$b])rejects(fn()=>$q->calculate($a,$b));
 rejects(fn()=>$q->calculate(21.4225,39.8262),DomainException::class);
 rejects(fn()=>$q->calculate(-21.4225,-140.1738),DomainException::class);
});
test('Civil Hijri epoch and known date',function(){
 $h=new LocalHijriCalendarProvider();same('2024-03-11',$h->toGregorian(1445,9,1));
 same([1445,9,1],array_values(array_intersect_key($h->fromGregorian('2024-03-11')->jsonSerialize(),array_flip(['year','month','day']))));
 same('0622-07-19',$h->toGregorian(1,1,1));
});
test('Hijri round trips, leap years and methods',function(){
 foreach(['civil','tabular-astronomical'] as $method){
  $h=new LocalHijriCalendarProvider($method);
  for($year=1400;$year<=1460;$year++)foreach([1,9,12] as $month)foreach([1,29] as $day){
   $d=$h->fromGregorian($h->toGregorian($year,$month,$day));same([$year,$month,$day],[$d->year,$d->month,$d->day]);
  }
 }
 $h=new LocalHijriCalendarProvider();rejects(fn()=>$h->toGregorian(1445,2,30));
 rejects(fn()=>$h->fromGregorian('2024-02-30'));rejects(fn()=>new LocalHijriCalendarProvider('universal'));
 same('0622-07-18',(new LocalHijriCalendarProvider('tabular-astronomical'))->toGregorian(1,1,1));
});
test('Ramadan interval, status and next Ramadan',function(){
 $r=new RamadanService(new LocalHijriCalendarProvider());
 $v=$r->forDate('2024-03-11');same(true,$v['is_ramadan']);same(1,$v['day']);same('calculated',$v['status']);
 same('2024-04-09',$v['end']);same(false,$r->forDate('2024-04-10')['is_ramadan']);
 same(1446,$r->forDate('2024-04-10')['next']['hijri_year']);
});
test('Official Ramadan override including calculated boundary',function(){
 $o=new OfficialOverride('SA',1445,'2024-03-10','2024-04-08','Test authority','https://example.test/declaration');
 $r=new RamadanService(new LocalHijriCalendarProvider(),[$o]);
 $v=$r->forDate('2024-03-10','SA');same(true,$v['is_ramadan']);same(1,$v['day']);same('confirmed',$v['status']);
 same(false,$r->forDate('2024-04-09','SA')['is_ramadan']);
 same(false,$r->forDate('2024-03-10','GB')['is_ramadan']);
 rejects(fn()=>new OfficialOverride('SA',1445,'2024-03-10','2024-04-20','Test','https://example.test'));
 rejects(fn()=>new OfficialOverride('SA',1445,'2024-03-10','2024-04-08','',''));
});
test('Prayer order and approximate independently authored solar acceptance windows',function(){
 $p=new LocalPrayerTimeProvider();$r=$p->calculate('2024-03-11',24.7136,46.6753,new PrayerSettings(timezone:'Asia/Riyadh'));
 $t=$r['times'];$last=0;
 foreach(['fajr','sunrise','dhuhr','asr','maghrib','isha'] as $key){$ts=strtotime($t[$key]);if($ts<=$last)throw new RuntimeException('Not chronological');$last=$ts;}
 // Broad solar acceptance bounds, not a copied provider fixture.
 $rise=new DateTimeImmutable($t['sunrise']);near(6.1,(float)$rise->format('G')+(float)$rise->format('i')/60,0.1);
 $noon=new DateTimeImmutable($t['dhuhr']);near(12.05,(float)$noon->format('G')+(float)$noon->format('i')/60,0.1);
 same('Asia/Riyadh',$r['timezone']);same('mwl',$r['method']);
});
test('Prayer Asr, interval, adjustments, DST and validation',function(){
 $p=new LocalPrayerTimeProvider();
 $a=$p->calculate('2024-03-11',24.7136,46.6753,new PrayerSettings(timezone:'Asia/Riyadh'));
 $b=$p->calculate('2024-03-11',24.7136,46.6753,new PrayerSettings(timezone:'Asia/Riyadh',asrFactor:2,adjustments:['dhuhr'=>3],ishaMinutes:90));
 same(180,strtotime($b['times']['dhuhr'])-strtotime($a['times']['dhuhr']));
 if(strtotime($b['times']['asr'])<=strtotime($a['times']['asr']))throw new RuntimeException('Hanafi Asr must be later');
 same(5400,strtotime($b['times']['isha'])-strtotime($b['times']['maghrib']));
 $ny=$p->calculate('2024-03-10',40.7128,-74.006,new PrayerSettings(timezone:'America/New_York'));
 same('-04:00',substr($ny['times']['dhuhr'],-6));
 rejects(fn()=>new PrayerSettings(fajrAngle:91));rejects(fn()=>new PrayerSettings(asrFactor:3));
 rejects(fn()=>new PrayerSettings(adjustments:['wrong'=>10]));
 rejects(fn()=>$p->calculate('2024-02-30',0,0,new PrayerSettings()));
});
test('High latitude twilight caps and polar unavailability',function(){
 $p=new LocalPrayerTimeProvider();
 $r=$p->calculate('2024-06-21',51.5074,-0.1278,new PrayerSettings(timezone:'Europe/London',highLatitudeRule:'seventh'));
 if($r['times']['fajr']===null||$r['times']['isha']===null)throw new RuntimeException('Expected twilight estimate');
 if(!in_array('high-latitude-adjustment',$r['warnings'],true))throw new RuntimeException('Missing approximation warning');
 $polar=$p->calculate('2024-06-21',78.2232,15.6469,new PrayerSettings(timezone:'Arctic/Longyearbyen'));
 same(null,$polar['times']['sunrise']);same(null,$polar['times']['fajr']);
 if(!in_array('polar-sun-unavailable',$polar['warnings'],true))throw new RuntimeException('Polar data must not be fabricated');
});
test('International date line respects the requested local date',function(){
 $r=(new LocalPrayerTimeProvider())->calculate('2024-03-11',1.8721,-157.4278,new PrayerSettings(timezone:'Pacific/Kiritimati'));
 foreach($r['times'] as $value)same('2024-03-11',substr($value,0,10));
});
test('Polar night never invents Asr below the horizon',function(){
 $r=(new LocalPrayerTimeProvider())->calculate('2024-12-21',78.2232,15.6469,new PrayerSettings(timezone:'Arctic/Longyearbyen'));
 same(null,$r['times']['asr']);same(null,$r['times']['sunrise']);
});
echo "$passed passed; $failed failed\n";exit($failed?1:0);
