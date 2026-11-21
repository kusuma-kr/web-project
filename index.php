
<?php
require_once "auth.php";
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>SpendWise | Student Budget Tracker</title>

    <link rel="stylesheet" href="style.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <meta name="csrf-token"
          content="<?= htmlspecialchars(csrfToken()) ?>">
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <h2>💰 SpendWise</h2>

        <p>Student Finance</p>

        <nav>
            <a href="index.php">🏠 Dashboard</a>

            <a href="#transactions">💳 Transactions</a>

            <a href="budgets.php">📊 Monthly Budgets</a>
        </nav>

        <p class="side-note">
            Manage your money wisely.
        </p>

    </aside>


    <!-- MAIN CONTENT -->
    <main class="main">

        <!-- HEADER -->
        <header>

            <div>
                <h1>Dashboard</h1>

                <p>
                    Welcome,
                    <?= htmlspecialchars($_SESSION["student_name"]) ?>!
                </p>
            </div>

            <div class="user-profile">

                <button onclick="openForm()">
                    + Add Transaction
                </button>

                <form action="logout.php" method="POST">

                    <input type="hidden" name="csrf"
                        value="<?= htmlspecialchars(csrfToken()) ?>">

                    <button type="submit" class="delete-btn">
                        Logout
                    </button>

                </form>

            </div>

        </header>


        <!-- DASHBOARD CARDS -->
        <section class="cards">

            <div class="card">
                <span>Total Balance</span>
                <h2 id="balance">₹0.00</h2>
            </div>

            <div class="card">
                <span>Total Income</span>
                <h2 id="income">₹0.00</h2>
            </div>

            <div class="card">
                <span>Total Expenses</span>
                <h2 id="expense">₹0.00</h2>
            </div>

            <div class="card">
                <span>Transactions</span>
                <h2 id="count">0</h2>
            </div>

        </section>


        <!-- CHARTS -->
        <section class="charts">

            <div class="panel">
                <h3>Expense Breakdown</h3>
                <canvas id="expenseChart"></canvas>
            </div>

            <div class="panel">
                <h3>Income vs Expenses</h3>
                <canvas id="incomeChart"></canvas>
            </div>

        </section>


        <!-- TRANSACTIONS -->
        <section class="panel" id="transactions">

            <h3>Recent Transactions</h3>

            <input
                id="search"
                placeholder="Search transactions..."
                oninput="render()"
            >

            <div class="table-wrap">

                <table>

                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody id="transactionList"></tbody>

                </table>

            </div>

        </section>

    </main>

</div>


<!-- ADD TRANSACTION MODAL -->
<div class="modal" id="modal">

    <form id="transactionForm" class="form-box">

        <h2 id="formTitle">Add Transaction</h2>

        <input type="hidden" id="id">

        <label>Transaction Type</label>

        <select id="type" required>
            <option value="income">Income</option>
            <option value="expense">Expense</option>
        </select>


        <label>Title</label>

        <input
            id="title"
            required
            maxlength="150"
            placeholder="Enter title"
        >


        <label>Category</label>

        <input
            id="category"
            required
            maxlength="100"
            placeholder="Enter category"
        >


        <label>Amount (₹)</label>

        <input
            id="amount"
            type="number"
            min="0.01"
            step="0.01"
            required
        >


        <label>Date</label>

        <input
            id="date"
            type="date"
            required
        >


        <label>Notes</label>

        <textarea
            id="notes"
            placeholder="Optional notes"
        ></textarea>


        <button type="submit">
            Save Transaction
        </button>

        <button
            type="button"
            class="cancel"
            onclick="closeForm()"
        >
            Cancel
        </button>

    </form>

</div>


<!-- JAVASCRIPT -->
<script src="app.js"></script>

</body>
</html>
