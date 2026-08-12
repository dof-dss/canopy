<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$directories = [$root . '/bin', $root . '/src', $root . '/tests', $root . '/tools'];
$failures = [];

foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $command = sprintf('php -l %s 2>&1', escapeshellarg($file->getPathname()));
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            $failures[] = implode("\n", $output);
        }

        $output = [];
    }
}

if ($failures !== []) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "PHP syntax checks passed.\n");
