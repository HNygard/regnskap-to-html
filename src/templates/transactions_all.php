<?php
/* @var FinancialStatement $statement */
?>
<h1><?= $statement->companyName ?> - Regnskap <?= $statement->year ?></h1>
<h2>Alle bilag</h2>

<style>
    tr.transaction_id_first td {
        border-top: 1px solid gray;
    }
    td.account_posting {
        border-left: 1px dashed #CCCCCC;
    }
    td {
        padding-left: 5px;
        padding-right: 5px;
    }
</style>

<table>
    <thead>
    <th>ID</th>
    <th>Date</th>
    <th>Post</th>
    <th>Beløp</th>
    <th>Post</th>
    <th>Beløp</th>
    </thead>
    <tbody>
    <?php
    foreach ($statement->documents as $transaction_id => $document) {
        /* @var AccountingDocument $document */
        foreach ($document->transactions as $i => $transaction) {
            /* @var AccountingTransaction $transaction */
            ?>
            <tr class="<?= ($i == 0
                ? 'transaction_id_first'
                : ($i == count($document->transactions) - 1 ? 'transaction_id_last' : '')
            ) ?>">
                <?php
                if ($i == 0) {
                    ?>
                    <td class="transaction_id"<?= ($i == 0 ? ' rowspan="' . count($document->transactions) . '"' : '') ?>>
                        <?= $transaction_id ?>
                    </td>
                <?php } ?>
                <td><?= date('Y-m-d', $transaction->timestamp) ?></td>

                <td class="account_posting"><?= $transaction->accounting_post_debit ?></td>
                <td><?= formatMoney($transaction->amount_debit, $transaction->currency_debit) ?></td>

                <td class="account_posting"><?= $transaction->accounting_post_credit ?></td>
                <td><?= formatMoney($transaction->amount_credit, $transaction->currency_credit) ?></td>

                <?php
                if ($i == 0) {
                    ?>
                    <td class="document_status"<?= ($i == 0 ? ' rowspan="' . count($document->transactions) . '"' : '') ?>>
                        <?= $document->isValid() ? '✓' : '✕' ?>
                        <?= $document->getStatus() ?>
                    </td>
                <?php } ?>
            </tr>
        <?php
        }
    }

    ?>
    </tbody>
</table>