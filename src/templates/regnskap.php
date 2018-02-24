<?php
/* @var FinancialStatement $statement */
/* @var bool $parameter */
$show_all_accounts = $parameter;

$resultat_poster = array();
$balanse_poster = array();
foreach ($statement->posts as $accounting_post => $accounting_post_name) {
    $sum = 0;
    foreach ($statement->documents as $document) {
        foreach ($document->transactions as $transaction) {
            if ($transaction->accounting_post_debit == $accounting_post) {
                $sum -= $transaction->amount_debit;
            }
            if ($transaction->accounting_post_credit == $accounting_post) {
                $sum += $transaction->amount_credit;
            }
        }
    }

    if ($accounting_post >= 3000) {
        $resultat_poster[$accounting_post] = $sum;
    }
    else {
        $balanse_poster[$accounting_post] = $sum;
    }
}
ksort($resultat_poster);
ksort($balanse_poster);

$printAccountingOverview = function (FinancialStatement $statement, $accounting_posts, $show_all_accounts, $show_budget) {
    ?>
    <table>
        <thead>
        <th>Konto</th>
        <th>Beløp</th>
        </thead>
        <tbody>
        <?php
        foreach ($accounting_posts as $accounting_post => $sum) {
            if (!$show_all_accounts && $sum == 0) {
                continue;
            }
            ?>
            <tr class="bordered">
                <td class="account_posting"><?= $statement->getAccountNameHtml($accounting_post) ?></td>
                <td class="amount"><?= formatMoney($sum, 'NOK') ?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
<?php
}

?>


    <h2>Regnskap</h2>
<?php $printAccountingOverview($statement, $resultat_poster, $show_all_accounts, false); ?>
    <h2>Balanse</h2>
<?php $printAccountingOverview($statement, $balanse_poster, $show_all_accounts, false); /*?>
    <h2>Budsjettkontroll</h2>
<?php $printAccountingOverview($statement, $resultat_poster, $show_all_accounts, true); */?>