<?php
/* @var FinancialStatement $statement */
/* @var int $parameter */
/* @var String $relative_path */
?>
<h2>Postering <?= $parameter ?></h2>

<?php
$errors = array();
$documents = array();
foreach ($statement->documents as $document) {
    $document_with_account = false;
    foreach ($document->transactions as $transaction) {
        if ($transaction->accounting_post_credit == $parameter || $transaction->accounting_post_debit == $parameter) {
            $document_with_account = true;
        }
    }


    if ($document_with_account) {
        $documents[] = $document;
        $status = $document->getStatus();
        if (!isset($errors[$status])) {
            $errors[$status] = 0;
        }
        $errors[$status]++;
    }
}
?>

<table style="border-collapse: collapse">
    <?php foreach ($errors as $message => $count) { ?>
        <tr>
            <td style="border: 1px solid black;"><?= $message ?></td>
            <td style="border: 1px solid black;"><?= $count ?></td>
        </tr>
    <?php } ?>
</table>
<br><br>

<table>
    <thead>
    <th>ID</th>
    <th>Date</th>
    <th>Beløp</th>
    <th>Post</th>
    <th>Beløp</th>
    <th>Post</th>
    <th>Saldo</th>
    <th>Status</th>
    <th>Ekstra info</th>
    </thead>
    <tbody>
    <?php
    $sum = 0;
    foreach ($documents as $transaction_id => $document) {
        /* @var AccountingDocument $document */
        foreach ($document->transactions as $i => $transaction) {
            /* @var AccountingTransaction $transaction */
            if ($transaction->accounting_post_credit == $parameter) {
                $sum += $transaction->amount_credit;
            }
            else if ($transaction->accounting_post_debit == $parameter) {
                $sum -= $transaction->amount_debit;
            }

            ?>
            <tr class="bordered">
                <?php
                if ($i == 0) {
                    ?>
                    <td class="transaction_id"<?= ($i == 0 ? ' rowspan="' . count($document->transactions) . '"' : '') ?>>
                        <?= $document->id ?>
                    </td>
                <?php } ?>
                <td class="date"><?= date('Y-m-d', $transaction->timestamp) ?></td>

                <td class="amount"><?= formatMoney($transaction->amount_debit, $transaction->currency_debit) ?></td>
                <td class="account_posting"><?= $statement->getAccountNameHtml($transaction->accounting_post_debit, $transaction->accounting_subject_debit, $relative_path) ?></td>

                <td class="amount"><?= formatMoney($transaction->amount_credit, $transaction->currency_credit) ?></td>
                <td class="account_posting"><?= $statement->getAccountNameHtml($transaction->accounting_post_credit, $transaction->accounting_subject_credit, $relative_path) ?></td>

                <td class="amount"><?= formatMoney($sum, 'NOK') ?></td>

                <?php
                if ($i == 0) {
                    ?>
                    <td class="document_status"<?= ($i == 0 ? ' rowspan="' . count($document->transactions) . '"' : '') ?>>
                        <?= $document->isValid() ? '✓' : '✕' ?>
                        <?= $document->getStatus() ?>
                    </td>
                <?php } ?>

                <td class="extra_info"><?= $transaction->extra_info_html ?></td>
            </tr>
        <?php
        }
    }

    ?>
    </tbody>
</table>