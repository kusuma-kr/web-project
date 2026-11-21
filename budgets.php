
<?php
require_once "config.php";
require_once "auth.php";

requireLogin();

$studentId = (int)$_SESSION["student_id"];

$month = date("Y-m");

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!hash_equals(csrfToken(), $_POST["csrf"] ?? "")) {
        die("Invalid security token. Refresh the page.");
    }

    $category = trim($_POST["category"] ?? "");
    $limit = $_POST["monthly_limit"] ?? 0;
    $budgetMonth = $_POST["budget_month"] ?? $month;

    if (
        $category === "" ||
        strlen($category) > 100 ||
        !is_numeric($limit) ||
        (float)$limit <= 0 ||
        (float)$limit > 99999999 ||
        !preg_match('/^\d{4}-\d{2}$/', $budgetMonth)
    ) {
        die("Invalid budget details.");
    }

    $limit = (float)$limit;

    $stmt = $conn->prepare(
        "INSERT INTO budgets
        (student_id, category, monthly_limit, budget_month)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        monthly_limit = VALUES(monthly_limit)"
    );

    $stmt->bind_param(
        "isds",
        $studentId,
        $category,
        $limit,
        $budgetMonth
    );

    $stmt->execute();

    header("Location: budgets.php?month=" . urlencode($budgetMonth));
    exit;
}

if (
    isset($_GET["month"]) &&
    preg_match('/^\d{4}-\d{2}$/', $_GET["month"])
) {
    $month = $_GET["month"];
}

$stmt = $conn->prepare(
    "SELECT b.category, b.monthly_limit,
    COALESCE(SUM(t.amount), 0) AS spent
    FROM budgets b
    LEFT JOIN transactions t
    ON t.student_id = b.student_id
    AND t.category = b.category
    AND t.type = 'expense'
    AND DATE_FORMAT(t.transaction_date, '%Y-%m') = b.budget_month
    WHERE b.student_id = ?
    AND b.budget_month = ?
    GROUP BY b.id, b.category, b.monthly_limit
    ORDER BY b.category"
);

$stmt->bind_param("is", $studentId, $month);
$stmt->execute();

$result = $stmt->get_result();

$budgets = [];

while ($row = $result->fetch_assoc()) {
    $budgets[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SpendWise - Budgets</title>
<link rel="stylesheet" href="style.css">

<style>
.budget-container {
    max-width: 1000px;
    margin: 30px auto;
}
.budget-form, .budget-card {
    background: white;
    padding: 22px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 3px 12px #0000000d;
}
.budget-form {
    display: grid;
    gap: 12px;
}
.budget-form input, .budget-form select {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 7px;
}
.budget-card h3 {
    margin-bottom: 12px;
}
.progress {
    height: 12px;
    background: #e8eee9;
    border-radius: 20px;
    overflow: hidden;
    margin: 12px 0;
}
.progress-fill {
    height: 100%;
    background: #159568;
    border-radius: 20px;
}
.progress-fill.warning {
    background: #e6a23c;
}
.progress-fill.danger {
    background: #dc4545;
}
.budget-message {
    font-size: 14px;
    font-weight: bold;
}
</style>
</head>

<body>

<div class="budget-container">

<h1>💰 Monthly Budget Management</h1>
<p>Plan your spending and manage your money wisely.</p>

<form method="GET" style="margin:20px 0">
    <label>Select Month</label>
    <input type="month" name="month"
        value="<?= htmlspecialchars($month) ?>">
    <button type="submit">View Budget</button>
</form>

<form method="POST" class="budget-form">

    <h2>Set Monthly Budget</h2>

    <input type="hidden" name="csrf"
        value="<?= htmlspecialchars(csrfToken()) ?>">

    <label>Category</label>
    <select name="category" required>
        <option value="">Select Category</option>
        <option value="Food">Food</option>
        <option value="Transport">Transport</option>
        <option value="Shopping">Shopping</option>
        <option value="Education">Education</option>
        <option value="Entertainment">Entertainment</option>
        <option value="Other">Other</option>
    </select>

    <label>Monthly Limit (₹)</label>
    <input type="number" name="monthly_limit"
        min="1" step="0.01" required>

    <label>Budget Month</label>
    <input type="month" name="budget_month"
        value="<?= htmlspecialchars($month) ?>" required>

    <button type="submit">Save Budget</button>

</form>

<h2>Your Monthly Budgets</h2>

<?php if (empty($budgets)): ?>
    <div class="budget-card">
        <p>No budgets created for this month yet.</p>
    </div>
<?php endif; ?>

<?php foreach ($budgets as $budget):

    $limit = (float)$budget["monthly_limit"];
    $spent = (float)$budget["spent"];

    $percentage = $limit > 0
        ? ($spent / $limit) * 100
        : 0;

    $barWidth = min(100, $percentage);

    $class = $percentage >= 100
        ? "danger"
        : ($percentage >= 80 ? "warning" : "");

?>

<div class="budget-card">

    <h3><?= htmlspecialchars($budget["category"]) ?></h3>

    <p>Budget: ₹<?= number_format($limit, 2) ?></p>

    <p>Spent: ₹<?= number_format($spent, 2) ?></p>

    <p>Remaining:
        ₹<?= number_format(max(0, $limit - $spent), 2) ?>
    </p>

    <div class="progress">
        <div class="progress-fill <?= $class ?>"
            style="width:<?= $barWidth ?>%">
        </div>
    </div>

    <p class="budget-message">
        <?php if ($percentage >= 100): ?>
            ⚠️ Budget exceeded!
        <?php elseif ($percentage >= 80): ?>
            ⚠️ Warning: You have used
            <?= round($percentage) ?>% of your budget.
        <?php else: ?>
            ✅ You are within your budget.
        <?php endif; ?>
    </p>

</div>

<?php endforeach; ?>

<a href="index.php">← Back to Dashboard</a>

</div>
</body>
</html>
