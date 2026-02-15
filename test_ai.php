<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');

if (!class_exists('\core_ai\manager')) {
    echo "core_ai\\manager class not found.\n";
    exit;
}

$ref = new ReflectionClass('\core_ai\manager');

if ($ref->hasMethod('process_action')) {
    echo "process_action exists.\n";
    echo "Is static? " . ($ref->getMethod('process_action')->isStatic() ? 'YES' : 'NO') . "\n";
} else {
    echo "process_action does NOT exist.\n";
}

if ($ref->getConstructor()) {
    echo "Constructor params: " . $ref->getConstructor()->getNumberOfRequiredParameters() . "\n";
    foreach ($ref->getConstructor()->getParameters() as $param) {
        echo " - " . $param->getName() . "\n";
    }
} else {
    echo "No constructor.\n";
}
