<?php
// transaction-report.php

// Start of the PHP script

// Function to fetch transaction history
function fetchTransactionHistory() {
    // Database connection and query to fetch transaction history
    // This is a placeholder for the actual implementation
    return [
        ['id' => 1, 'description' => 'Transaction 1', 'amount' => 100, 'date' => '2026-03-01'],
        ['id' => 2, 'description' => 'Transaction 2', 'amount' => 200, 'date' => '2026-03-02'],
    ];
}

// Function to generate printable report
function generateReport($transactions) {
    // Start of the printable report
    echo '<h1>Transaction Report</h1>';
    echo '<table border="1">';
    echo '<tr><th>ID</th><th>Description</th><th>Amount</th><th>Date</th></tr>';
    foreach ($transactions as $transaction) {
        echo '<tr>';
        echo '<td>' . $transaction['id'] . '</td>';
        echo '<td>' . $transaction['description'] . '</td>';
        echo '<td>' . $transaction['amount'] . '</td>';
        echo '<td>' . $transaction['date'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Fetch transaction history
$transactions = fetchTransactionHistory();
// Generate the report
generateReport($transactions);
?>
