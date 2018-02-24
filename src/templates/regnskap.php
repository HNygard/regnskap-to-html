<?php
/* @var FinancialStatement $statement */
/* @var bool $parameter */
$show_all_accounts = $parameter;

$resultat_poster = array();
$balanse_poster = array();
// TODO: This loop can be better. Loop over all transactions once.
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
        <?php
        if ($show_budget) {
            foreach ($statement->budgets as $budget) {
                ?>
                <th><?= $budget->name ?></th>
            <?php
            }
        }
        ?>
        </thead>
        <tbody>
        <?php
        foreach ($accounting_posts as $accounting_post => $sum) {
            $budgets = array();
            $budget_comment = array();
            if ($show_budget) {
                foreach ($statement->budgets as $i => $budget) {
                    $budgets[$i] = 0;
                    foreach ($budget->posts as $post) {
                        if ($post->account_number == $accounting_post) {
                            $budgets[$i] = $post->amount;
                            if (!empty($post->comment)) {
                                $budget_comment[] = $post->comment;
                            }
                        }
                    }
                }
            }

            if (!$show_all_accounts && $sum == 0) {
                continue;
            }
            ?>
            <tr class="bordered">
                <td class="account_posting"><?= $statement->getAccountNameHtml($accounting_post) ?></td>
                <td class="amount"><?= formatMoney($sum, 'NOK') ?></td>
                <?php
                if ($show_budget) {
                    foreach ($budgets as $budget_amount) {
                        $budget_diff = $sum - $budget_amount;
                        ?>
                        <td class="budget amount"><?= formatMoney($budget_amount, 'NOK') ?></td>
                        <td class="budget_diff amount <?= ($budget_diff < 0 ? 'amount_negative' : '') ?>">
                            <?= formatMoney($budget_diff, 'NOK') ?>
                        </td>
                    <?php
                    }
                    ?>
                    <td class="budget_comment"><?= implode('<br>', $budget_comment) ?></td>
                <?php
                }
                ?>
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
<?php $printAccountingOverview($statement, $balanse_poster, $show_all_accounts, false); ?>
    <h2>Budsjettkontroll</h2>
<?php $printAccountingOverview($statement, $resultat_poster, $show_all_accounts, true); ?>