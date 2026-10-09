<?php
declare(strict_types=1);
namespace Sahara\Core\Ramadan;
use Sahara\Core\Support\Inputs;
final readonly class OfficialOverride {
    public function __construct(public string $country,public int $hijriYear,public string $start,public string $end,public string $source,public string $sourceUrl) {
        $a=Inputs::date($start);$b=Inputs::date($end);$length=(int)$a->diff($b)->format('%r%a')+1;
        if(!preg_match('/^[A-Z]{2}$/',$country)||$hijriYear<1||!in_array($length,[29,30],true)||trim($source)===''||!filter_var($sourceUrl,FILTER_VALIDATE_URL)||parse_url($sourceUrl,PHP_URL_SCHEME)!=='https') {
            throw new \InvalidArgumentException('An official override requires country, Hijri year, a 29/30 day interval and HTTPS provenance.');
        }
    }
}
