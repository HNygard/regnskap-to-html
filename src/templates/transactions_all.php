<?php
/* @var FinancialStatement $statement */
?>
<h2>Alle bilag</h2>

<?php
$documents = $statement->documents;
include __DIR__ . '/transaction-list.php';