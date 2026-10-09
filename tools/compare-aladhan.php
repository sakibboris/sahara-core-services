<?php
declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';
use Sahara\Core\PrayerTimes\LocalPrayerTimeProvider;
use Sahara\Core\PrayerTimes\PrayerSettings;
$places=[
 ['Riyadh','2024-03-11',24.7136,46.6753,'Asia/Riyadh'],
 ['London winter','2024-12-21',51.5074,-0.1278,'Europe/London'],
 ['New York DST','2024-03-10',40.7128,-74.006,'America/New_York'],
 ['Sydney','2024-10-09',-33.8688,151.2093,'Australia/Sydney'],
 ['Dhaka','2024-10-09',23.8103,90.4125,'Asia/Dhaka'],
];
$failed=false;
foreach($places as [$name,$date,$lat,$lon,$tz]){
 $query=http_build_query(['latitude'=>$lat,'longitude'=>$lon,'method'=>3,'school'=>0,'timezonestring'=>$tz]);
 $ch=curl_init('https://api.aladhan.com/v1/timings/'.date('d-m-Y',strtotime($date)).'?'.$query);
 curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>25]);
 $raw=curl_exec($ch);$http=curl_getinfo($ch,CURLINFO_HTTP_CODE);
 if($raw===false||$http!==200)throw new RuntimeException('Comparison provider unavailable; HTTP '.$http);
 $remote=json_decode($raw,true,512,JSON_THROW_ON_ERROR)['data']['timings'];
 $local=(new LocalPrayerTimeProvider())->calculate($date,$lat,$lon,new PrayerSettings(timezone:$tz));
 $max=0;
 foreach(['fajr','sunrise','dhuhr','asr','maghrib','isha'] as $key){
  $v=new DateTimeImmutable($local['times'][$key]);$minutes=(int)$v->format('G')*60+(int)$v->format('i');
  [$h,$m]=array_map('intval',explode(':',$remote[ucfirst($key)]));
  $delta=abs($minutes-($h*60+$m));$delta=min($delta,1440-$delta);$max=max($max,$delta);
 }
 echo "$name: maximum difference $max minutes (tolerance 3)\n";$failed=$failed||$max>3;
}
exit($failed?1:0);
