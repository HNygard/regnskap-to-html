<?php
/* @var FinancialStatement $statement */
/* @var AccountPostRenderSettings $parameter */
?>
<h2><?= $parameter->getPageTitle() ?></h2>

<?=$parameter->getLinkAllPostOnAccount($statement) ?>

<?php
$errors = array();
$documents = array();
foreach ($statement->documents as $document) {
    $document_with_account = false;
    foreach ($document->transactions as $transaction) {
        if ($parameter->isCorrectTransaction($transaction->accounting_post_credit, $transaction->accounting_subject_credit)
            || $parameter->isCorrectTransaction($transaction->accounting_post_debit, $transaction->accounting_subject_debit)) {
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
    <th class="amount">Beløp</th>
    <th class="text">Debit - Post</th>
    <th class="amount">Beløp</th>
    <th class="text">Kredit - Post</th>
    <th class="amount">Saldo</th>
    <th>Status</th>
    <th class="text">Ekstra info</th>
    </thead>
    <tbody>
    <?php
    $sum = 0;
    foreach ($documents as $transaction_id => $document) {
        /* @var AccountingDocument $document */
        foreach ($document->transactions as $i => $transaction) {
            /* @var AccountingTransaction $transaction */
            if ($parameter->isCorrectTransaction($transaction->accounting_post_credit, $transaction->accounting_subject_credit)) {
                $sum -= $transaction->amount_credit;
            }
            else if ($parameter->isCorrectTransaction($transaction->accounting_post_debit, $transaction->accounting_subject_debit)) {
                $sum += $transaction->amount_debit;
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

                <?php $transaction->printDateAmountsHtml($statement); ?>

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