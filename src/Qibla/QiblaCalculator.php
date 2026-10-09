<?php
declare(strict_types=1);
namespace Sahara\Core\Qibla;
use Sahara\Core\Contracts\QiblaProvider;
use Sahara\Core\Support\Inputs;
final class QiblaCalculator implements QiblaProvider {
    public function calculate(float $latitude,float $longitude): QiblaResult {
        Inputs::coordinates($latitude,$longitude);
        $lat=deg2rad($latitude);$target=deg2rad(21.4225);$delta=deg2rad(39.8262-$longitude);
        $cosine=sin($lat)*sin($target)+cos($lat)*cos($target)*cos($delta);
        $arc=acos(max(-1.0,min(1.0,$cosine)));
        if($arc<1e-7 || abs(M_PI-$arc)<1e-7) throw new \DomainException('Qibla bearing is undefined at the target or its antipode.');
        $bearing=rad2deg(atan2(sin($delta)*cos($target),cos($lat)*sin($target)-sin($lat)*cos($target)*cos($delta)));
        return new QiblaResult(fmod($bearing+360,360),6371.0088*$arc);
    }
}
