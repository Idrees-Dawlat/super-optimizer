<?php
/**
 * Packaging script for Super Optimizer
 */

$root = dirname(__DIR__);
$dist = $root . DIRECTORY_SEPARATOR . 'dist';

if (!is_dir($dist)) {
    mkdir($dist, 0755, true);
}

$zip_file = $dist . DIRECTORY_SEPARATOR . 'super-optimizer.zip';
if (file_exists($zip_file)) {
    unlink($zip_file);
}

$zip = new ZipArchive();
if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Error: Unable to create zip file at {$zip_file}\n");
    exit(1);
}

$source = realpath($root);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$count = 0;
foreach ($iterator as $file) {
    if (!$file->isDir()) {
        $file_path = $file->getRealPath();
        $relative  = substr($file_path, strlen($source) + 1);
        $norm_rel  = str_replace('\\', '/', $relative);

        // Exclusions
        if (
            strpos($norm_rel, '.git') === 0 ||
            strpos($norm_rel, 'dist/') === 0 ||
            strpos($norm_rel, 'bin/') === 0 ||
            strpos($norm_rel, 'tests/') === 0 ||
            $norm_rel === '.gitignore'
        ) {
            continue;
        }

        $zip->addFile($file_path, 'super-optimizer/' . $norm_rel);
        $count++;
    }
}

$zip->close();
echo "Packaging complete: {$count} files added to dist/super-optimizer.zip (" . round(filesize($zip_file) / 1024, 2) . " KB)\n";
