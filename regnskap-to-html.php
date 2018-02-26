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

// :: Config
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

    $post = new AccountingConfigAccountingPost();
    $post->account_number = '1234';
    $post->name = 'Post name';
    $config->accounting_posts = array($post);

    echo json_encode($config, JSON_PRETTY_PRINT);
    echo chr(10);
    echo chr(10);
    exit;
}
/* @var AccountingConfig $config */
$config = json_decode(file_get_contents($statement_directory . '/config.json'));
if ($config == null) {
    echo 'Unable to read config.json' . chr(10);
    echo json_last_error_msg() . chr(10);
    exit;
}

// :: Collect the right files
$json_files = array();
$csv_files = array();
$manual_transaction_files = array();
foreach ($files as $file) {
    foreach ($config->ignoreFiles as $ignore_this_file) {
        if (str_ends_with($file, $ignore_this_file)) {
            continue 2;
        }
    }

    if (str_ends_with($file, 'manual-transactions.json')) {
        $manual_transaction_files[] = $file;
    }
    elseif (str_ends_with(strtolower($file), '.json')
        && !str_ends_with($file, 'account-transactions.json')
        && !str_ends_with($file, 'config.json')
    ) {
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
    var $ignoreFiles = array();
    /* @var AccountingConfigAccount[] $accounts */
    var $accounts = array();
    /* @var AccountingConfigAccountingPost[] $accounting_posts */
    var $accounting_posts = array();
    /* @var AccountingConfigAccountingSubject[] $accounting_subjects */
    var $accounting_subjects = array();
    /* @var AccountingConfigBudget[] $budgets */
    var $budgets = array();
    /* @var AccountingConfigBudgetPerSubject[] $budgets_per_subject */
    var $budgets_per_subject = array();
}

class AccountingConfigAccount {
    var $name;
    var $id;
    var $accounting_post;
}

class AccountingConfigAccountingPost {
    var $account_number;
    var $name;
}

class AccountingConfigAccountingSubject {
    var $name;
    var $key;

    public function __construct($key, $name) {
        $this->key = $key;
        $this->name = $name;
    }
}

class AccountingConfigBudget {
    var $name;
    /* @var AccountingConfigBudgetPost[] $posts */
    var $posts;
}

class AccountingConfigBudgetPerSubject extends AccountingConfigBudget {
    var $accounting_subject;
}

class AccountingConfigBudgetPost {
    var $account_number;
    var $amount;
    var $comment;

    public function __construct($account_number, $amount) {
        $this->account_number = $account_number;
        $this->amount = $amount;
        $this->comment = '';
    }
}

/**
 * Regnskap
 */
class FinancialStatement {
    /* @var AccountingDocument[] $documents */
    var $documents = array();
    var $posts = array();
    /* @var AccountingConfigAccountingSubject[] $subjects */
    var $subjects = array();
    /* @var AccountingConfigBudget[] $budgets */
    var $budgets = array();

    /* @var AccountingConfigBudgetPerSubject[] $budgets_per_subject */
    var $budgets_per_subject = array();

    var $relative_path;

    /**
     * @param AccountingConfig $config
     */
    function __construct($config) {
        $this->companyName = $config->companyName;
        $this->year = $config->year;
        $this->accounts = $config->accounts;

        foreach ($config->accounting_posts as $post) {
            $this->posts[$post->account_number] = $post->name;
        }

        $this->subjects = array(new AccountingConfigAccountingSubject('', 'Ingen'));
        foreach ($config->accounting_subjects as $subject) {
            $this->subjects[] = $subject;
        }


        $this->budgets = $config->budgets;
        $this->budgets_per_subject = $config->budgets_per_subject;
    }

    public function addTransaction(AccountingTransaction $account_transaction) {
        if (!isset($this->documents[$account_transaction->transaction_id])) {
            $this->documents[$account_transaction->transaction_id] = new AccountingDocument($account_transaction->transaction_id);
        }
        $this->documents[$account_transaction->transaction_id]->addTransaction($account_transaction);
    }

    /**
     * @param $account_transaction_id
     * @param $debug_obj
     * @return AccountingDocument
     * @throws Exception
     */
    public function getDocument($account_transaction_id, $debug_obj = null) {
        if (!isset($this->documents[$account_transaction_id])) {
            var_dump($debug_obj);
            throw new Exception('Unknown account_transaction_id. Unable to proceed.');
        }
        return $this->documents[$account_transaction_id];
    }

    public function getAccountNameHtml($accounting_post, $accounting_subject) {
        if ($accounting_post == null) {
            return '';
        }

        return '<a href="' . $this->relative_path . '/account_post/account_post-' . $accounting_post . '.html">'
        . '<span class="post">' . $accounting_post . '</span>'
        . ' - <span class="post_name">' . $this->posts[$accounting_post] . '</span>'
        . '</a>'
        . (!empty($accounting_subject)
            ? ' <a href="' . $this->relative_path . '/account_post/subject-' . $accounting_subject . '-account_post-' . $accounting_post . '.html"'
            . 'class="subject">(' . $accounting_subject . ')</a>'
            : '');
    }
}

/**
 * Bilag
 */
class AccountingDocument {
    /* @var AccountingTransaction[] $transactions */
    var $transactions = array();

    private $sum_debit = 0;
    private $sum_credit = 0;

    public function __construct($transaction_id) {
        $this->id = $transaction_id;
    }

    function addTransaction(AccountingTransaction $transaction) {
        $this->transactions[] = $transaction;

        if ($transaction->amount_debit != null) {
            $this->sum_debit += $transaction->amount_debit;
        }

        if (
            $transaction->currency_debit != null
            && $transaction->currency_credit != null
            && ($transaction->currency_debit != 'NOK' || $transaction->currency_credit != 'NOK')
        ) {
            throw new Exception('Multi currency not implemented.');
        }

        if ($transaction->amount_credit != null) {
            $this->sum_credit += $transaction->amount_credit;
        }
    }

    function getBankTransaction() {
        return $this->transactions[0];
    }

    public function getTimestampFirstTransaction() {
        return $this->transactions[0]->timestamp;
    }

    function getSumDebit() {
        return $this->sum_debit;
    }

    function getSumCredit() {
        return $this->sum_credit;
    }

    function isValid() {
        return $this->getSumDebit() == $this->getSumCredit();
    }

    function getStatus() {
        if (count($this->transactions) == 1) {
            return 'Mangler mot-postering.';
        }

        if ($this->getSumDebit() != $this->getSumCredit()) {
            return 'Mismatch på sum debit/kredit. '
            . 'Debit [' . $this->getSumDebit() . ']'
            . ' - kredit [' . $this->getSumCredit() . ']'
            . ' = ' . ($this->getSumDebit() - $this->getSumCredit()) . '.';
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
                         $accounting_post_credit, $amount_credit, $currency_credit,
                         $extra_info_html,
                         $accounting_subject_debit, $accounting_subject_credit,
                         $comment_debit, $comment_credit) {
        $this->transaction_id = $transaction_id;
        $this->timestamp = $timestamp;

        $this->accounting_post_debit = $accounting_post_debit;
        $this->amount_debit = $amount_debit;
        $this->currency_debit = $currency_debit;
        $this->accounting_subject_debit = $accounting_subject_debit;
        $this->comment_debit = $comment_debit;

        $this->accounting_post_credit = $accounting_post_credit;
        $this->amount_credit = $amount_credit;
        $this->currency_credit = $currency_credit;
        $this->accounting_subject_credit = $accounting_subject_credit;
        $this->comment_credit = $comment_credit;

        $this->extra_info_html = $extra_info_html;
    }

    public function printDateAmountsHtml(FinancialStatement $statement) {
        ?>
        <td class="date"><?= date('Y-m-d', $this->timestamp) ?></td>

        <td class="amount"><?= formatMoney($this->amount_debit, $this->currency_debit) ?></td>
        <td class="account_posting">
            <?= $statement->getAccountNameHtml($this->accounting_post_debit, $this->accounting_subject_debit) ?>
            <?= !empty($this->comment_debit) ? '<br><span class="account_posting_comment">' . htmlentities($this->comment_debit, ENT_QUOTES) . '</span>' : '' ?>
        </td>

        <td class="amount"><?= formatMoney($this->amount_credit, $this->currency_credit) ?></td>
        <td class="account_posting">
            <?= $statement->getAccountNameHtml($this->accounting_post_credit, $this->accounting_subject_credit) ?>
            <?= !empty($this->comment_credit) ? '<br><span class="account_posting_comment">' . htmlentities($this->comment_credit, ENT_QUOTES) . '</span>' : '' ?>
        </td>
    <?php
    }
}

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
        $extra_info_html = array();
        foreach ($transaction->labels as $label) {
            switch ($label->label_type) {
                case 'transaction type';
                    $color = 'label-info';
                    break;
                default:
                    $color = 'label-default';
                    break;
            }
            if (str_starts_with($label->label_type, 'card transaction')) {
                $color = 'label-success';
            }
            $extra_info_html[$label->label_type . '-' . $label->label] =
                '<span class="label ' . $color . '"
                    title="' . $label->label_type . '">' . $label->label . '</span> ';
        }
        $extra_info_html = implode(' ', $extra_info_html);
        $id = $transaction->id;
        if (isset($transaction->transaction_id_link) && !empty($transaction->transaction_id_link)) {
            // -> Linked transactions
            $id = $transaction->transaction_id_link;
        }
        if ($transaction->account_id_debit == $account_id) {
            $statement->addTransaction(new AccountingTransaction(
                $id,
                $transaction->timestamp,
                $account_id_to_accounting_post[$transaction->account_id_debit],
                $transaction->amount_debit,
                $transaction->currency_debit,
                null,
                null,
                null,
                $extra_info_html,
                null,
                null,
                null,
                null
            ));
        }

        if ($transaction->account_id_credit == $account_id) {
            $statement->addTransaction(new AccountingTransaction(
                $id,
                $transaction->timestamp,
                null,
                null,
                null,
                $account_id_to_accounting_post[$transaction->account_id_credit],
                $transaction->amount_credit,
                $transaction->currency_credit,
                $extra_info_html,
                null,
                null,
                null,
                null
            ));
        }
    }
}

// :: Get manual transactions
class ManualTransactionsFile {
    /* @var ManualTransaction[] $transactions */
    var $transactions;
}

class ManualTransaction {
    var $id;
    var $date;
    var $timestamp;
    var $accounting_post_debit;
    var $accounting_post_credit;
    var $accounting_subject_debit;
    var $accounting_subject_credit;
    var $amount_debit;
    var $amount_credit;
    var $currency_debit;
    var $currency_credit;

    var $comment_debit;
    var $comment_credit;
}

/* @var ManualTransactionsFile $manual_transactions */
foreach ($manual_transaction_files as $file) {
    $manual_transactions = json_decode(file_get_contents($file));
    if ($manual_transactions == null) {
        echo 'Unable to read manual_transactions.json' . chr(10);
        echo json_last_error_msg() . chr(10);
        exit;
    }
    foreach ($manual_transactions->transactions as $i => $manual_transaction) {
        $is_debit = (isset($manual_transaction->accounting_post_debit));
        $is_credit = (isset($manual_transaction->accounting_post_credit));
        if (isset($manual_transaction->timestamp)) {
            $timestamp = $manual_transaction->timestamp;
        }
        else {
            $timestamp = mktime(0, 0, 0, substr($manual_transaction->date, 5, 2), substr($manual_transaction->date, 8, 2), substr($manual_transaction->date, 0, 4));
        }
        $statement->addTransaction(new AccountingTransaction(
            $manual_transaction->id,
            $timestamp,
            ($is_debit ? $manual_transaction->accounting_post_debit : null),
            ($is_debit ? $manual_transaction->amount_debit : null),
            ($is_debit ? $manual_transaction->currency_debit : null),
            ($is_credit ? $manual_transaction->accounting_post_credit : null),
            ($is_credit ? $manual_transaction->amount_credit : null),
            ($is_credit ? $manual_transaction->currency_credit : null),
            '',
            ($is_debit ? $manual_transaction->accounting_subject_debit : null),
            ($is_credit ? $manual_transaction->accounting_subject_credit : null),
            ($is_debit ? $manual_transaction->comment_debit : null),
            ($is_credit ? $manual_transaction->comment_credit : null)
        ));
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
    var $accounting_subject;
}

foreach ($json_files as $file) {
    /* @var RenameFileServerJsonFile $obj */
    $obj = json_decode(file_get_contents($file));

    if (empty($obj->account_transaction_id)) {
        echo file_get_contents($file);
        var_dump($obj);
        throw new Exception('Missing account_transaction_id. Unable to proceed with: ' . str_replace($statement_directory, '', $file));
    }

    $document = $statement->getDocument($obj->account_transaction_id, $obj);
    $bank_transaction = $document->getBankTransaction();
    foreach ($obj->transactions as $file_transaction) {
        // Old format with accounting_post on main level instead of transaction level
        $file_transaction_accounting_post = (isset($file_transaction->accounting_post)
            ? $file_transaction->accounting_post
            : (isset($obj->accounting_post) ? $obj->accounting_post : '')
        );
        $file_transaction_accounting_subject = (isset($file_transaction->accounting_subject)
            ? $file_transaction->accounting_subject
            : $obj->accounting_subject);
        $file_transaction_comment = (isset($file_transaction->comment) ? $file_transaction->comment : $obj->comment);
        if ($bank_transaction->amount_credit != null) {
            $statement->addTransaction(new AccountingTransaction(
                $obj->account_transaction_id,
                mktime(0, 0, 0, substr($obj->date, 5, 2), substr($obj->date, 8, 2), substr($obj->date, 0, 4)),
                $file_transaction_accounting_post,
                str_replace(',', '.', $file_transaction->amount),
                $file_transaction->currency,
                null,
                null,
                null,
                '',
                $file_transaction_accounting_subject,
                null,
                $file_transaction_comment,
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
                $file_transaction->currency,
                '',
                null,
                $file_transaction_accounting_subject,
                null,
                $file_transaction_comment
            ));
        }

    }
}

// :: Sort transactions
function docSort(AccountingDocument $a, AccountingDocument $b) {
    return $a->getTimestampFirstTransaction() - $b->getTimestampFirstTransaction();
}

usort($statement->documents ,'docSort');

// :: Render
function renderTemplate($php_file, $result_file, FinancialStatement $statement, $parameter = null) {
    global $statement_directory;
    echo '[' . $statement->companyName . ' ' . $statement->year . '] - Rendering [' . $php_file . '] to [' . $result_file . '].' . chr(10);
    $relative_path = '.';
    if (strpos($result_file, '/') !== false) {
        $relative_path = '..';
    }
    $statement->relative_path = $relative_path;
    ob_start();
    if (!str_ends_with($php_file, '.css')) {
        ?>
        <h1><?= $statement->companyName ?> - Regnskap <?= $statement->year ?></h1>
        <link type="text/css" rel="stylesheet" href="<?= $relative_path ?>/style.css">


        <li><a href="<?= $relative_path ?>/transactions_warnings.html">Advarsler</a>
        <li><a href="<?= $relative_path ?>/transactions_all.html">Alle posteringer</a>
        <li><a href="<?= $relative_path ?>/regnskap.html">Regnskap</a>
        <li><a href="<?= $relative_path ?>/regnskap_all.html">Regnskap, alle poster</a>
    <?php
    }
    include __DIR__ . '/src/templates/' . $php_file;
    $output = ob_get_clean();

    file_put_contents($statement_directory . '/' . $result_file, $output);
}

renderTemplate('index.php', 'index.html', $statement);
renderTemplate('transactions_all.php', 'transactions_all.html', $statement);
renderTemplate('style.css', 'style.css', $statement);
renderTemplate('transactions_warnings.php', 'transactions_warnings.html', $statement);

renderTemplate('regnskap.php', 'regnskap.html', $statement, false);
renderTemplate('regnskap.php', 'regnskap_all.html', $statement, true);
// Render each accounting post
if (!file_exists($statement_directory . '/account_post')) {
    mkdir($statement_directory . '/account_post');
}
function renderPost(AccountPostRenderSettings $postSettings, $statement) {
    renderTemplate('account_post.php', $postSettings->getResultFileName(), $statement, $postSettings);
}

class AccountPostRenderSettings {
    public function __construct($post, $subject) {
        $this->post = $post;
        $this->subject = $subject;
    }

    public function getResultFileName() {
        if ($this->subject == null) {
            return 'account_post/account_post-' . $this->post . '.html';
        }
        return 'account_post/subject-' . $this->subject . '-account_post-' . $this->post . '.html';
    }

    public function getPageTitle() {
        if ($this->subject == null) {
            return 'Postering - ' . $this->post;
        }
        return 'Postering - ' . $this->post . ' - ' . $this->subject;
    }

    public function isCorrectTransaction($post, $subject) {
        if ($this->subject == null) {
            return $this->post == $post;
        }
        return $this->post == $post && $this->subject == $subject;
    }

    public function getLinkAllPostOnAccount(FinancialStatement $statement) {
        if ($this->subject == null) {
            return '';
        }
        return '<a href="' . $statement->relative_path . '/account_post/account_post-' . $this->post . '.html">'
        . 'Vis all transaksjoner på ' . $this->post . '</a>';
    }
}

$posts_renders = array();
foreach ($statement->documents as $document) {
    foreach ($document->transactions as $transaction) {
        if ($transaction->accounting_post_credit != null
            && !isset($posts_renders[$transaction->accounting_post_credit])
        ) {
            renderPost(new AccountPostRenderSettings($transaction->accounting_post_credit, null), $statement);
            foreach ($statement->subjects as $subject) {
                renderPost(new AccountPostRenderSettings($transaction->accounting_post_credit, $subject->key), $statement);
            }
            $posts_renders[$transaction->accounting_post_credit] = $transaction->accounting_post_credit;
        }
        if ($transaction->accounting_post_debit != null
            && !isset($posts_renders[$transaction->accounting_post_debit])
        ) {
            renderPost(new AccountPostRenderSettings($transaction->accounting_post_debit, null), $statement);
            foreach ($statement->subjects as $subject) {
                renderPost(new AccountPostRenderSettings($transaction->accounting_post_debit, $subject->key), $statement);
            }
            $posts_renders[$transaction->accounting_post_debit] = $transaction->accounting_post_debit;
        }
    }
}









