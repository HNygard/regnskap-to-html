<?php
/* @var FinancialStatement $statement */
?>
<h1><?= $statement->companyName ?> - Regnskap <?= $statement->year ?></h1>
<h2>Advarsler</h2>

<?php
$errors = array();
$documents = array();
foreach ($statement->documents as $document) {
    if (!$document->isValid()) {
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

<?php

include __DIR__ . '/transaction-list.php';