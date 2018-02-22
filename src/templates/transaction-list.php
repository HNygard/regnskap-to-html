<?php
/* @var FinancialStatement $statement */
/* @var AccountingDocument[] $documents */
?>
<style>
    table {
        border-collapse: collapse;
    }

    tr.transaction_id_first td {
        border-top: 1px solid gray;
    }

    td.transaction_id {
        font-size: 0.6em;
    }

    td.account_posting {
        border-left: 1px dashed #CCCCCC;
    }

    td.account_posting .post_name {
        color: gray;
    }

    td.amount {
        font-weight: bold;
        text-align: right;
    }

    td.date,
    td.account_posting,
    td.amount {
        white-space: nowrap;
    }

    td {
        padding: 5px;
    }

    td.extra_info {
        padding-bottom: 0;
        padding-top: 0;
        line-height: 2em;
    }

    tr:hover td {
        background-color: #EEEEEE;
    }

    /* Bootstrap label */
    .label-default {
        background-color: #777;
    }
    .label-success {
        background-color: #5cb85c;
    }
    .label-info {
        background-color: #5bc0de;
    }
    .label {
        display: inline;
        padding: .2em .6em .3em;
        font-size: 75%;
        font-weight: 700;
        line-height: 1;
        color: #fff;
        text-align: center;
        white-space: nowrap;
        vertical-align: baseline;
        border-radius: .25em;
    }
    * {
        -webkit-box-sizing: border-box;
        -moz-box-sizing: border-box;
        box-sizing: border-box;
    }
</style>

<table>
    <thead>
    <th>ID</th>
    <th>Date</th>
    <th>Beløp</th>
    <th>Post</th>
    <th>Beløp</th>
    <th>Post</th>
    <th>Status</th>
    <th>Ekstra info</th>
    </thead>
    <tbody>
    <?php
    foreach ($documents as $transaction_id => $document) {
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
                        <?= $document->id ?>
                    </td>
                <?php } ?>
                <td class="date"><?= date('Y-m-d', $transaction->timestamp) ?></td>

                <td class="amount"><?= formatMoney($transaction->amount_debit, $transaction->currency_debit) ?></td>
                <td class="account_posting"><?= $statement->getAccountNameHtml($transaction->accounting_post_debit) ?></td>

                <td class="amount"><?= formatMoney($transaction->amount_credit, $transaction->currency_credit) ?></td>
                <td class="account_posting"><?= $statement->getAccountNameHtml($transaction->accounting_post_credit) ?></td>

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