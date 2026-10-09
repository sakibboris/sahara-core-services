<?php
declare(strict_types=1);
namespace Sahara\Core\Support;
final class Inputs {
    public static function coordinates(float $latitude, float $longitude): void {
        if (!is_finite($latitude) || !is_finite($longitude) || abs($latitude)>90 || abs($longitude)>180) {
            throw new \InvalidArgumentException('Coordinates must be finite degrees within latitude ±90 and longitude ±180.');
        }
    }
    public static function date(string $date): \DateTimeImmutable {
        $value=\DateTimeImmutable::createFromFormat('!Y-m-d',$date,new \DateTimeZone('UTC'));
        if (!$value || $value->format('Y-m-d')!==$date) throw new \InvalidArgumentException('Expected a valid YYYY-MM-DD date.');
        return $value;
    }
}
