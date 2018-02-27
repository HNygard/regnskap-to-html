<?php
/* @var FinancialStatement $statement */
/* @var AccountingDocument[] $documents */

$errors = array();
foreach ($documents as $document2) {
    $status = $document2->getStatus();
    if (!isset($errors[$status])) {
        $errors[$status] = 0;
    }
    $errors[$status]++;

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
    <th class="amount">Beløp</th>
    <th class="text">Debit - Post</th>
    <th class="amount">Beløp</th>
    <th class="text">Kredit - Post</th>
    <th class="text">Status</th>
    <th class="text">Ekstra info</th>
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

                <?php $transaction->printDateAmountsHtml($statement); ?>

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