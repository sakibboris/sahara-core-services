<?php
declare(strict_types=1);
namespace Sahara\Core\Contracts;
use Sahara\Core\Qibla\QiblaResult;
interface QiblaProvider { public function calculate(float $latitude,float $longitude): QiblaResult; }
