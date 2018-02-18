#!/usr/bin/php
<?php
$current_directory = $_SERVER['PWD'];
$statement_directory = $current_directory . '/regnskap';

class AccountingConfig {
    var $companyName;
    var $year;
    var $accounts = array();
}

// :: Read file from current directory
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
foreach ($files as $file) {
    if (str_ends_with('.json', strtolower($file))) {
        $json_files[] = $file;
    }
    if (str_ends_with('.csv', strtolower($file))) {
        $csv_files[] = $file;
    }
}

// :: Config and setup
if (!file_exists($statement_directory)) {
    mkdir($statement_directory);
}

if (!file_exists($statement_directory . '/config.json')) {
    echo chr(10);
    echo chr(10);
    echo '========> Missing config.json' . chr(10);
    echo $statement_directory . '/config.json' . chr(10);
    echo chr(10);
    echo chr(10);
    $config = new AccountingConfig();
    $config->companyName = 'My Company';
    $config->year = '1971';
    $config->accounts = array('bank-123123123');
    echo json_encode($config, JSON_PRETTY_PRINT);
    echo chr(10);
    echo chr(10);
    exit;
}

var_dump($json_files);
var_dump($csv_files);

class FinancialStatement {
    /**
     * @param AccountingConfig $config
     */
    function __construct($config) {
        $this->companyName = $config->companyName;
        $this->year = $config->year;
        $this->accounts = $config->accounts;
    }
}

$config = json_decode(file_get_contents($statement_directory . '/config.json'));
$statement = new FinancialStatement($config);

function renderTemplate($php_file, $result_file, FinancialStatement $statement) {
    echo '[' . $statement->companyName . ' ' . $statement->year . '] - Rendering [' . $php_file . '] to [' . $result_file . '].' . chr(10);
    ob_start();
    include __DIR__ . '/src/templates/' . $php_file;
    $output = ob_get_clean();

    file_put_contents($result_file, $output);
}

renderTemplate('index.php', $statement_directory . '/index.html', $statement);