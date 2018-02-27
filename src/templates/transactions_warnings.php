<?php
/* @var FinancialStatement $statement */
?>
<h2>Advarsler</h2>

<?php
$documents = array();
foreach ($statement->documents as $document) {
    if (!$document->isValid()) {
        $documents[] = $document;
    }
}

include __DIR__ . '/transaction-list.php';