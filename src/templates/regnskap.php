<?php
/* @var FinancialStatement $statement */
/* @var bool $parameter */
$show_all_accounts = $parameter;

$subject_name = array();
$resultat_poster = array();
$resultat_poster_subject = array();
foreach ($statement->subjects as $subject) {
    $subject_name[$subject->key] = $subject->name;
    $resultat_poster_subject[$subject->key] = array();
}
$balanse_poster = array();
// TODO: This loop can be better. Loop over all transactions once.
foreach ($statement->posts as $accounting_post => $accounting_post_name) {
    $sum = 0;
    $sum_subject = array();
    foreach ($statement->subjects as $subject) {
        $sum_subject[$subject->key] = 0;
    }

    foreach ($statement->documents as $document) {
        foreach ($document->transactions as $transaction) {
            if ($transaction->accounting_post_debit == $accounting_post) {
                $sum -= $transaction->amount_debit;
                $sum_subject[$transaction->accounting_subject_debit] -= $transaction->amount_debit;
            }
            if ($transaction->accounting_post_credit == $accounting_post) {
                $sum += $transaction->amount_credit;
                $sum_subject[$transaction->accounting_subject_credit] += $transaction->amount_credit;
            }
        }
    }

    if ($accounting_post >= 3000) {
        $resultat_poster[$accounting_post] = $sum;
        foreach ($statement->subjects as $subject) {
            $resultat_poster_subject[$subject->key][$accounting_post] = $sum_subject[$subject->key];
        }
    }
    else {
        $balanse_poster[$accounting_post] = -$sum;
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
$append_sum = function($summarizer, $posts) {
    $sum = $summarizer($posts);
    $posts[3999] = $sum['sum_inntekter'];
    $posts[7999] = $sum['sum_kostnader'];
    $posts[10000] = $sum['sum_inntekter'];
    $posts[10001] = $sum['sum_kostnader'];
    $posts[10002] = $sum['resultat'];
    return $posts;
};
$resultat_poster = $append_sum($summarizer, $resultat_poster);
$statement->posts[3999] = 'SUM INNTEKTER';
$statement->posts[7999] = 'SUM KOSTNADER';
$statement->posts[10000] = 'Sum inntekter';
$statement->posts[10001] = 'Sum kostnader';
$statement->posts[10002] = 'RESULTAT';

$tmp_balanse = $summarizer($balanse_poster);
$balanse_poster[10003] = $tmp_balanse['sum_inntekter'] + $tmp_balanse['sum_kostnader'] + $tmp_balanse['resultat'];
$statement->posts[10003] = 'BALANSE';

foreach ($statement->budgets_per_subject as $budget) {
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

$printAccountingOverview = function (FinancialStatement $statement, $accounting_posts, $show_all_accounts, $show_budget, $all_budgets, $accounting_subject) {
    ?>
    <table class="regnskap">
        <thead>
        <th>Konto</th>
        <th>Beløp</th>
        <?php
        if ($show_budget) {
            foreach ($all_budgets as $budget) {
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
            $any_budget = false;
            if ($show_budget) {
                foreach ($all_budgets as $i => $budget) {
                    $budgets[$i] = 0;
                    foreach ($budget->posts as $post) {
                        if ($post->account_number == $accounting_post) {
                            $budgets[$i] = $post->amount;
                            if (!empty($post->comment)) {
                                $budget_comment[] = $post->comment;
                            }
                            $any_budget = true;
                        }
                    }
                }
            }

            if (!$show_all_accounts && $sum == 0 && !$any_budget) {
                continue;
            }
            ?>
            <tr class="bordered accounting-post-<?= $accounting_post ?>">
                <td class="account_posting"><?= $statement->getAccountNameHtml($accounting_post, $accounting_subject) ?></td>
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
<?php $printAccountingOverview($statement, $resultat_poster, $show_all_accounts, false, $statement->budgets, null); ?>
<h2>Balanse</h2>
<?php $printAccountingOverview($statement, $balanse_poster, $show_all_accounts, false, $statement->budgets, null); ?>
<?php
foreach ($resultat_poster_subject as $subject_key => $posts) {
    $posts = $append_sum($summarizer, $posts);
    $budgets = array();
    foreach($statement->budgets_per_subject as $budget_per_subject) {
        if ($budget_per_subject->accounting_subject == $subject_key) {
            $budgets[] = $budget_per_subject;
        }
    }
    echo '<h2>Budsjettkontroll - ' . $subject_name[$subject_key] . '</h2>' . chr(10);
    ksort($posts);
    $printAccountingOverview($statement, $posts, $show_all_accounts, true, $budgets, $subject_key);
}
?>

<style>
    table.regnskap
    td.account_posting .post {
        color: gray;
    }

    table.regnskap td.account_posting .post_name {
        color: black;
    }
</style>