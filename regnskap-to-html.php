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
    if (str_ends_with(strtolower($file), '.json')
        && !str_ends_with($file, 'account-transactions.json')
        && !str_ends_with($file, 'config.json')) {
        $json_files[] = $file;
    }
    if (str_ends_with(strtolower($file), '.csv')) {
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

/**
 * Regnskap
 */
class FinancialStatement {
    /* @var AccountingDocument $documents */
    var $documents = array();

    /**
     * @param AccountingConfig $config
     */
    function __construct($config) {
        $this->companyName = $config->companyName;
        $this->year = $config->year;
        $this->accounts = $config->accounts;
    }

    public function addTransaction(AccountingTransaction $account_transaction) {
        if (!isset($this->documents[$account_transaction->transaction_id])) {
            $this->documents[$account_transaction->transaction_id] = new AccountingDocument();
        }
        $this->documents[$account_transaction->transaction_id]->transactions[] = $account_transaction;
    }
}

/**
 * Bilag
 */
class AccountingDocument {
    /* @var AccountingTransaction[] $transactions */
    var $transactions = array();

    function getBankTransaction() {
        return $this->transactions[0];
    }

    function getSumDebit() {
        $sum_debit = 0;
        foreach ($this->transactions as $transaction) {
            if ($transaction->amount_debit != null) {
                $sum_debit += $transaction->amount_debit;
            }

            if (
                $transaction->currency_debit != null
                && $transaction->currency_debit != 'NOK'
            ) {
                throw new Exception('Multi currency not implemented.');
            }
        }
        return $sum_debit;
    }

    function getSumCredit() {
        $sum_credit = 0;
        foreach ($this->transactions as $transaction) {
            if ($transaction->amount_credit != null) {
                $sum_credit += $transaction->amount_credit;
            }

            if (
                $transaction->currency_credit != null
                && $transaction->currency_credit != 'NOK'
            ) {
                throw new Exception('Multi currency not implemented.');
            }
        }
        return $sum_credit;
    }

    function isValid() {
        return $this->getSumDebit() == $this->getSumCredit();
    }

    function getStatus() {
        if (count($this->transactions) == 1) {
            return 'Mangler mot-postering.';
        }

        if ($this->getSumDebit() != $this->getSumCredit()) {
            return 'Mismatch på sum debit/kredit. Debit [' . $this->getSumDebit() . '] - kredit [' . $this->getSumCredit() . '] = ' . ($this->getSumDebit() - $this->getSumCredit()) .'.';
        }

        return 'OK.';
    }
}

/**
 * Postering
 */
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

// :: Geta data - JSON files from https://github.com/HNygard/renamefile-server-nodejs
class RenameFileServerJsonFile {
    var $date;
    var $accounting_subject;
    // Optional:
    var $accounting_post;
    // Optional:
    var $payment_type;
    // Optional:
    var $account_transaction_id;
    // Optional:
    var $invoice_date;
    var $amount;
    var $currency;
    var $comment;

    /* @var RenameFileServerJsonFileTransaction[] $transactions */
    var $transactions;
}

class RenameFileServerJsonFileTransaction {
    var $amount;
    var $currency;
    var $comment;
    var $accounting_post;
}

foreach ($json_files as $file) {
    /* @var RenameFileServerJsonFile $obj */
    $obj = json_decode(file_get_contents($file));

    if (empty($obj->account_transaction_id)) {
        echo file_get_contents($file);
        var_dump($obj);
        throw new Exception('Missing account_transaction_id. Unable to proceed.');
    }

    if (!isset($statement->documents[$obj->account_transaction_id])) {
        var_dump($obj);
        throw new Exception('Unknown account_transaction_id. Unable to proceed.');
    }

    $bank_transaction = $statement->documents[$obj->account_transaction_id]->getBankTransaction();
    foreach ($obj->transactions as $file_transaction) {
        // Old format with accounting_post on main level instead of transaction level
        $file_transaction_accounting_post = (isset($file_transaction->accounting_post) ? $file_transaction->accounting_post : $obj->accounting_post);
        if ($bank_transaction->amount_credit != null) {
            $statement->addTransaction(new AccountingTransaction(
                $obj->account_transaction_id,
                mktime(0, 0, 0, substr($obj->date, 5, 2), substr($obj->date, 8, 2), substr($obj->date, 0, 4)),
                $file_transaction_accounting_post,
                str_replace(',', '.', $file_transaction->amount),
                $file_transaction->currency,
                null,
                null,
                null
            ));
        }
        else {
            $statement->addTransaction(new AccountingTransaction(
                $obj->account_transaction_id,
                mktime(0, 0, 0, substr($obj->date, 5, 2), substr($obj->date, 8, 2), substr($obj->date, 0, 4)),
                null,
                null,
                null,
                $file_transaction_accounting_post,
                str_replace(',', '.', $file_transaction->amount),
                $file_transaction->currency
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