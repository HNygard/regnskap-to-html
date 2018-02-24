<?php
/* @var FinancialStatement $statement */
/* @var AccountingDocument[] $documents */
?>

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
                <td class="account_posting"><?= $statement->getAccountNameHtml($transaction->accounting_post_debit, $transaction->accounting_subject_debit) ?></td>

                <td class="amount"><?= formatMoney($transaction->amount_credit, $transaction->currency_credit) ?></td>
                <td class="account_posting"><?= $statement->getAccountNameHtml($transaction->accounting_post_credit, $transaction->accounting_subject_credit) ?></td>

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