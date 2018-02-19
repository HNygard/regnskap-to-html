<?php
/* @var FinancialStatement $statement */
?>
<h1><?= $statement->companyName ?> - Regnskap <?= $statement->year ?></h1>
<h2>Alle bilag</h2>

<?php
$documents = $statement->documents;
include __DIR__ . '/transaction-list.php';