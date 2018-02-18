<?php
/* @var FinancialStatement $statement */
?>
<h1><?= $statement->companyName ?> - Regnskap <?= $statement->year ?></h1>
<h2>All transactions</h2>

<style>
    tr.transaction_id_first td {
        border-top: 1px solid gray;
    }
    tr.transaction_id_last td {
        border-bottom: 1px solid gray;
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
    foreach ($statement->transactions as $transaction_id => $transactions) {
        foreach ($transactions as $i => $transaction) {
            /* @var AccountingTransaction $transaction */
            ?>
            <tr class="<?= ($i == 0
                ? 'transaction_id_first'
                : ($i == count($transactions) - 1 ? 'transaction_id_last' : '')
            ) ?>">
                <?php
                if ($i == 0) {
                    ?>
                    <td<?= ($i == 0 ? ' colspan="' . count($transactions) . '"' : '') ?>>
                        <?= $transaction_id ?>
                    </td>
                <?php } ?>
                <td><?= date('Y-m-d', $transaction->timestamp) ?></td>

                <td><?= $transaction->accounting_post_debit ?></td>
                <td><?= formatMoney($transaction->amount_debit, $transaction->currency_debit) ?></td>

                <td><?= $transaction->accounting_post_credit ?></td>
                <td><?= formatMoney($transaction->amount_credit, $transaction->currency_credit) ?></td>
            </tr>
        <?php
        }
    }

    ?>
    </tbody>
</table>