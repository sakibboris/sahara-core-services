<?php
declare(strict_types=1);
$root=dirname(__DIR__);$failed=false;
foreach(['src','tests','tools'] as $dir){
 foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$dir",FilesystemIterator::SKIP_DOTS)) as $f){
  if($f->getExtension()!=='php')continue;
  passthru(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($f->getPathname()),$exit);$failed=$failed||$exit!==0;
 }
}
exit($failed?1:0);
