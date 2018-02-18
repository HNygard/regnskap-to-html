#!/usr/bin/php
<?php

// :: Read file from current directory
$current_directory = $_SERVER['PWD'];
$files = getFileListInDirectory($current_directory);
function getFileListInDirectory($dir, &$results = array()) {
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $results[] = $path;
        }
        else {
            if ($value != "." && $value != "..") {
                getFileListInDirectory($path, $results);
                $results[] = $path;
            }
        }
    }

    return $results;
}

function str_ends_with($haystack, $needle) {
    return substr($haystack, strlen($haystack) - strlen($needle)) == $needle;
}

// :: Collect the right files
$json_files = array();
$csv_files = array();
foreach($files as $file) {
	if (str_ends_with('.json', strtolower($file))) {
		$json_files[] = $file;
	}
	if (str_ends_with('.csv', strtolower($file))) {
		$csv_files[] = $file;
	}
}

var_dump($json_files);
var_dump($csv_files);
