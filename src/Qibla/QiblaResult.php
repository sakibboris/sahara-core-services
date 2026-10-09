<?php
declare(strict_types=1);
namespace Sahara\Core\Qibla;
final readonly class QiblaResult implements \JsonSerializable {
    public function __construct(public float $bearing,public float $distanceKm) {}
    public function jsonSerialize(): array {
        return ['schema_version'=>1,'bearing_degrees'=>$this->bearing,'distance_km'=>$this->distanceKm,'reference'=>'true-north','algorithm'=>'great-circle-v1'];
    }
}
