<?php
declare(strict_types=1);
namespace Sahara\Core\Hijri;
final readonly class HijriDate implements \JsonSerializable {
    public function __construct(public int $year,public int $month,public int $day,public string $gregorian,public string $method) {}
    public function jsonSerialize(): array {
        return ['year'=>$this->year,'month'=>$this->month,'day'=>$this->day,'gregorian'=>$this->gregorian,'method'=>$this->method,'status'=>'calculated','schema_version'=>1];
    }
}
