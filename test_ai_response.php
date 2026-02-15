<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');

if (!class_exists('\core_ai\aiactions\responses\response_generate_text')) {
    echo "Class not found.\n";
    exit;
}

$ref = new ReflectionClass('\core_ai\aiactions\responses\response_generate_text');
echo "Methods:\n";
foreach ($ref->getMethods() as $method) {
    echo " - " . $method->getName() . "\n";
}
