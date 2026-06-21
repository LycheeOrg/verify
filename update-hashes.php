<?php

require __DIR__ . '/vendor/autoload.php';

$configPath = __DIR__ . '/config/verify.php';
$config = require $configPath;

$hashes = [];
foreach ($config['validation'] as $class => $value) {
    $file = (new ReflectionClass($class))->getFileName();
    $content = file_get_contents($file);
    $content = preg_replace('~\R~u', "\n", $content);
    $hashes[$class] = sha1($content);
}

$lines = file($configPath);
$output = '';
foreach ($lines as $line) {
    $replaced = false;
    foreach ($hashes as $class => $hash) {
        $short = (new ReflectionClass($class))->getShortName();
        if (str_contains($line, $short . '::class')) {
            $output .= preg_replace("/=>\s*'[a-f0-9]+'/", "=> '" . $hash . "'", $line);
            $replaced = true;
            break;
        }
    }
    if (!$replaced) {
        $output .= $line;
    }
}

file_put_contents($configPath, $output);

echo "Updated hashes in config/verify.php:\n";
foreach ($hashes as $class => $hash) {
    echo "  " . (new ReflectionClass($class))->getShortName() . ": " . $hash . "\n";
}
