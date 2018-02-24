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

// Sums
// 0000-3999 = Sum inntekter
// 4000-7999 = Sum kostnader
// Result = Sum inntekter + sum kostnader + annet
$summarizer = function ($posts) {
    $sum_inntekter = 0;
    $sum_kostnader = 0;
    $resultat = 0;
    foreach ($posts as $post => $amount) {
        if ($post < 4000) {
            $sum_inntekter += $amount;
        }
        elseif ($post < 8000) {
            $sum_kostnader += $amount;
        }
        $resultat += $amount;
    }
    return array(
        'sum_inntekter' => $sum_inntekter,
        'sum_kostnader' => $sum_kostnader,
        'resultat' => $resultat
    );
};
$sum = $summarizer($resultat_poster);
$resultat_poster[3999] = $sum['sum_inntekter'];
$resultat_poster[7999] = $sum['sum_kostnader'];
$resultat_poster[10000] = $sum['sum_inntekter'];
$resultat_poster[10001] = $sum['sum_kostnader'];
$resultat_poster[10002] = $sum['resultat'];
$statement->posts[3999] = 'SUM INNTEKTER';
$statement->posts[7999] = 'SUM KOSTNADER';
$statement->posts[10000] = 'Sum inntekter';
$statement->posts[10001] = 'Sum kostnader';
$statement->posts[10002] = 'RESULTAT';

foreach ($statement->budgets as $budget) {
    $posts = array();
    foreach ($budget->posts as $post) {
        $posts[$post->account_number] = $post->amount;
    }
    $sum = $summarizer($posts);
    $budget->posts[] = new AccountingConfigBudgetPost(3999, $sum['sum_inntekter']);
    $budget->posts[] = new AccountingConfigBudgetPost(7999, $sum['sum_kostnader']);
    $budget->posts[] = new AccountingConfigBudgetPost(10000, $sum['sum_inntekter']);
    $budget->posts[] = new AccountingConfigBudgetPost(10001, $sum['sum_kostnader']);
    $budget->posts[] = new AccountingConfigBudgetPost(10002, $sum['resultat']);
}

ksort($resultat_poster);
ksort($balanse_poster);

$printAccountingOverview = function (FinancialStatement $statement, $accounting_posts, $show_all_accounts, $show_budget) {
    ?>
    <table class="regnskap">
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
            <tr class="bordered accounting-post-<?= $accounting_post ?>">
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

<style>
    table.regnskap
    td.account_posting .post {
        color: gray;
    }
    table.regnskap td.account_posting .post_name {
        color: black;
    }
</style>