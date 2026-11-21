
<?php
require_once "config.php";
require_once "auth.php";

header("Content-Type: application/json");

requireLogin();

$studentId = (int)$_SESSION["student_id"];
$action = $_GET["action"] ?? "";

// JSON response helper
function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Verify security token for POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";

    if (!hash_equals(csrfToken(), $token)) {
        respond(["error" => "Invalid security token"], 403);
    }
}

// DELETE TRANSACTION
if ($action === "delete" && $_SERVER["REQUEST_METHOD"] === "POST") {

    $input = json_decode(file_get_contents("php://input"), true);
    $id = (int)($input["id"] ?? 0);

    if ($id <= 0) {
        respond(["error" => "Invalid transaction ID"], 400);
    }

    $stmt = $conn->prepare(
        "DELETE FROM transactions
         WHERE id = ? AND student_id = ?"
    );

    $stmt->bind_param("ii", $id, $studentId);
    $stmt->execute();

    respond(["success" => true]);
}

// LIST TRANSACTIONS
if ($action === "list" && $_SERVER["REQUEST_METHOD"] === "GET") {

    $stmt = $conn->prepare(
        "SELECT * FROM transactions
         WHERE student_id = ?
         ORDER BY transaction_date DESC, id DESC"
    );

    $stmt->bind_param("i", $studentId);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    respond($data);
}

// ADD OR UPDATE TRANSACTION
if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    in_array($action, ["add", "update"], true)) {

    $input = json_decode(file_get_contents("php://input"), true);

    if (!is_array($input)) {
        respond(["error" => "Invalid data"], 400);
    }

    $id = (int)($input["id"] ?? 0);
    $type = $input["type"] ?? "";
    $title = trim($input["title"] ?? "");
    $category = trim($input["category"] ?? "");
    $amount = $input["amount"] ?? 0;
    $date = $input["transaction_date"] ?? "";
    $notes = trim($input["notes"] ?? "");

    if (
        !in_array($type, ["income", "expense"], true) ||
        $title === "" ||
        strlen($title) > 150 ||
        $category === "" ||
        strlen($category) > 100 ||
        !is_numeric($amount) ||
        !is_finite((float)$amount) ||
        (float)$amount <= 0 ||
        (float)$amount > 99999999 ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
    ) {
        respond(["error" => "Invalid transaction details"], 400);
    }

    [$year, $month, $day] = array_map(
        "intval",
        explode("-", $date)
    );

    if (!checkdate($month, $day, $year)) {
        respond(["error" => "Invalid transaction date"], 400);
    }

    $amount = (float)$amount;

    if ($action === "update") {

        if ($id <= 0) {
            respond(["error" => "Invalid transaction ID"], 400);
        }

        $stmt = $conn->prepare(
            "UPDATE transactions
             SET type = ?, title = ?, category = ?,
                 amount = ?, transaction_date = ?, notes = ?
             WHERE id = ? AND student_id = ?"
        );

        $stmt->bind_param(
            "sssdssii",
            $type,
            $title,
            $category,
            $amount,
            $date,
            $notes,
            $id,
            $studentId
        );

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO transactions
            (type, title, category, amount,
             transaction_date, notes, student_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssdssi",
            $type,
            $title,
            $category,
            $amount,
            $date,
            $notes,
            $studentId
        );
    }

    if (!$stmt->execute()) {
        respond(["error" => "Unable to save transaction"], 500);
    }

    respond(["success" => true]);
}

// INVALID REQUEST
respond(["error" => "Invalid request"], 400);
?>
