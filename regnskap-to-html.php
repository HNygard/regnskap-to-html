#!/usr/bin/php
<?php
$current_directory = $_SERVER['PWD'];
$statement_directory = $current_directory . '/regnskap';

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

require_once __DIR__ . '/src/common.php';

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

class AccountingConfig {
    var $companyName;
    var $year;
    /* @var AccountingConfigAccount[] $accounts */
    var $accounts = array();
}

class AccountingConfigAccount {
    var $name;
    var $id;
    var $accounting_post;
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
    $config_account = new AccountingConfigAccount();
    $config_account->name = 'My Bank Account';
    $config_account->id = 'bank-123123123';
    $config_account->accounting_post = '1920';
    $config->accounts = array($config_account);
    echo json_encode($config, JSON_PRETTY_PRINT);
    echo chr(10);
    echo chr(10);
    exit;
}

var_dump($json_files);
var_dump($csv_files);

class FinancialStatement {
    /* @var AccountingTransaction[][] $transactions */
    var $transactions = array();

    /**
     * @param AccountingConfig $config
     */
    function __construct($config) {
        $this->companyName = $config->companyName;
        $this->year = $config->year;
        $this->accounts = $config->accounts;
    }

    public function addTransaction(AccountingTransaction $account_transaction) {
        if (!isset($this->transactions[$account_transaction->transaction_id])) {
            $this->transactions[$account_transaction->transaction_id] = array();
        }
        $this->transactions[$account_transaction->transaction_id][] = $account_transaction;
    }
}

class AccountingTransaction {
    function __construct($transaction_id, $timestamp,
                         $accounting_post_debit, $amount_debit, $currency_debit,
                         $accounting_post_credit, $amount_credit, $currency_credit) {
        $this->transaction_id = $transaction_id;
        $this->timestamp = $timestamp;

        $this->accounting_post_debit = $accounting_post_debit;
        $this->amount_debit = $amount_debit;
        $this->currency_debit = $currency_debit;

        $this->accounting_post_credit = $accounting_post_credit;
        $this->amount_credit = $amount_credit;
        $this->currency_credit = $currency_credit;
    }
}

$config = json_decode(file_get_contents($statement_directory . '/config.json'));
$statement = new FinancialStatement($config);

// :: Get data - Bank accounts over API
$year_start = mktime(0, 0, 0, 1, 1, $statement->year);
$year_end = mktime(0, 0, 0, 12, 31, $statement->year);
if (!file_exists($statement_directory . '/account-transactions.json')) {
    $bank_accounts = array();
    foreach ($statement->accounts as $account) {
        $bank_accounts[] = $account->id;
    }
    $api_transactions = getUrl('http://localhost:13080/account_transactions_api/' . implode(',', $bank_accounts) . '/' . $year_start . '/' . $year_end)['body'];
    file_put_contents($statement_directory . '/account-transactions.json', $api_transactions);
}
else {
    $api_transactions = file_get_contents($statement_directory . '/account-transactions.json');
}
$api_transactions_per_account = json_decode($api_transactions);
if ($api_transactions_per_account == null || count($api_transactions_per_account) == 0) {
    throw new Exception('No API transactions. Setup incomplete.');
}
$account_id_to_accounting_post = array();
foreach ($statement->accounts as $account) {
    $account_id_to_accounting_post[$account->id] = $account->accounting_post;
}
foreach ($api_transactions_per_account as $account_id => $api_transactions_for_account) {
    foreach ($api_transactions_for_account->transactions as $transaction) {
        if ($transaction->account_id_debit == $account_id) {
            $statement->addTransaction(new AccountingTransaction(
                $transaction->id,
                $transaction->timestamp,
                $account_id_to_accounting_post[$transaction->account_id_debit],
                $transaction->amount_debit,
                $transaction->currency_debit,
                null,
                null,
                null
            ));
        }

        if ($transaction->account_id_credit == $account_id) {
            $statement->addTransaction(new AccountingTransaction(
                $transaction->id,
                $transaction->timestamp,
                null,
                null,
                null,
                $account_id_to_accounting_post[$transaction->account_id_credit],
                $transaction->amount_credit,
                $transaction->currency_credit
            ));
        }
    }
}

// :: Render
function renderTemplate($php_file, $result_file, FinancialStatement $statement) {
    echo '[' . $statement->companyName . ' ' . $statement->year . '] - Rendering [' . $php_file . '] to [' . $result_file . '].' . chr(10);
    ob_start();
    include __DIR__ . '/src/templates/' . $php_file;
    $output = ob_get_clean();

    file_put_contents($result_file, $output);
}

renderTemplate('index.php', $statement_directory . '/index.html', $statement);
renderTemplate('transactions_all.php', $statement_directory . '/transactions_all.html', $statement);