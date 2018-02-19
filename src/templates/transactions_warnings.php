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

<table>
    <?php foreach ($errors as $message => $count) { ?>
        <tr>
            <td><?= $message ?></td>
            <td><?= $count ?></td>
        </tr>
    <?php } ?>
</table>


<?php

include __DIR__ . '/transaction-list.php';