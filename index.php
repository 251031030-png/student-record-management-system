<?php
require_once "db.php";

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$errors = [];
$success = "";

// CREATE: save a new student.
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "add") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $department = trim($_POST["department"] ?? "");
    $semester = filter_input(INPUT_POST, "semester", FILTER_VALIDATE_INT);
    $cgpa = filter_input(INPUT_POST, "cgpa", FILTER_VALIDATE_FLOAT);

    if ($name === "" || $email === "" || $department === "" || !$semester || $semester < 1 || $semester > 12
        || $cgpa === false || $cgpa < 0 || $cgpa > 4) {
        $errors[] = "Enter valid values in every field. Semester must be 1–12 and CGPA must be 0–4.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO students (name, email, department, semester, cgpa)
                 VALUES (:name, :email, :department, :semester, :cgpa)"
            );
            $stmt->execute([
                ":name" => $name, ":email" => $email, ":department" => $department,
                ":semester" => $semester, ":cgpa" => $cgpa
            ]);
            header("Location: index.php?added=1");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Could not add the student. The email may already be registered.";
        }
    }
}

// UPDATE: save changes to an existing student.
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "update") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $department = trim($_POST["department"] ?? "");
    $semester = filter_input(INPUT_POST, "semester", FILTER_VALIDATE_INT);
    $cgpa = filter_input(INPUT_POST, "cgpa", FILTER_VALIDATE_FLOAT);

    if (!$id || $name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || $department === ""
        || !$semester || $semester < 1 || $semester > 12 || $cgpa === false || $cgpa < 0 || $cgpa > 4) {
        $errors[] = "Update failed. Check that all fields contain valid values.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "UPDATE students
                 SET name=:name, email=:email, department=:department, semester=:semester, cgpa=:cgpa
                 WHERE id=:id"
            );
            $stmt->execute([
                ":name" => $name, ":email" => $email, ":department" => $department,
                ":semester" => $semester, ":cgpa" => $cgpa, ":id" => $id
            ]);
            header("Location: index.php?updated=1");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Could not update the student. The email may already be used by another student.";
        }
    }
}

// DELETE: delete only after the browser confirmation dialog is accepted.
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "delete") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = :id");
        $stmt->execute([":id" => $id]);
        header("Location: index.php?deleted=1");
        exit;
    }
}

// Read status messages after redirect.
if (isset($_GET["added"])) $success = "Student record added successfully.";
if (isset($_GET["updated"])) $success = "Student record updated successfully.";
if (isset($_GET["deleted"])) $success = "Student record deleted successfully.";

// SEARCH/FILTER: match name, email, department, or student ID.
$search = trim($_GET["search"] ?? "");
if ($search !== "") {
    $stmt = $pdo->prepare(
        "SELECT * FROM students
         WHERE name LIKE :term OR email LIKE :term OR department LIKE :term
            OR CAST(id AS CHAR) LIKE :term
         ORDER BY id DESC"
    );
    $stmt->execute([":term" => "%".$search."%"]);
    $students = $stmt->fetchAll();
} else {
    $students = $pdo->query("SELECT * FROM students ORDER BY id DESC")->fetchAll();
}

// SUMMARY: aggregate functions return live values from the database.
$summary = $pdo->query(
    "SELECT COUNT(*) AS total_students,
            COALESCE(AVG(cgpa), 0) AS average_cgpa,
            COALESCE(MAX(cgpa), 0) AS highest_cgpa
     FROM students"
)->fetch();

// Load a record into the form when the Edit button is clicked.
$editStudent = null;
if (isset($_GET["edit"]) && ctype_digit($_GET["edit"])) {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
    $stmt->execute([":id" => (int)$_GET["edit"]]);
    $editStudent = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Record Management</title>
    <style>
        :root { --ink:#172033; --muted:#667085; --line:#e5e9f0; --blue:#2457d6; --bg:#f5f7fb; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Arial, sans-serif; background:var(--bg); color:var(--ink); }
        .wrap { max-width:1150px; margin:32px auto; padding:0 18px; }
        header { margin-bottom:22px; }
        h1 { margin:0 0 7px; font-size:28px; }
        .sub { color:var(--muted); margin:0; }
        .stats { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin:22px 0; }
        .card, .panel { background:white; border:1px solid var(--line); border-radius:12px; box-shadow:0 3px 12px #17203308; }
        .card { padding:18px; }
        .label { color:var(--muted); font-size:13px; }
        .value { font-size:25px; font-weight:700; margin-top:7px; }
        .layout { display:grid; grid-template-columns:340px 1fr; gap:18px; align-items:start; }
        .panel { padding:20px; }
        h2 { margin:0 0 16px; font-size:19px; }
        label { display:block; font-size:13px; font-weight:600; margin:12px 0 6px; }
        input { width:100%; padding:10px 11px; border:1px solid #cfd6e2; border-radius:7px; font-size:14px; }
        button, .btn { border:0; border-radius:7px; padding:10px 13px; cursor:pointer; font-weight:600; font-size:13px; text-decoration:none; display:inline-block; }
        .primary { background:var(--blue); color:white; }
        .secondary { background:#eef2f8; color:#26344d; }
        .danger { background:#feecec; color:#b42318; }
        .actions { display:flex; gap:7px; align-items:center; }
        .form-actions { display:flex; gap:8px; margin-top:16px; }
        .searchbar { display:flex; gap:8px; margin-bottom:14px; }
        .searchbar input { flex:1; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        th, td { padding:11px 9px; border-bottom:1px solid var(--line); text-align:left; }
        th { color:#475467; background:#f9fafb; }
        .table-wrap { overflow-x:auto; }
        .notice { padding:11px 13px; border-radius:8px; margin:12px 0; font-size:14px; }
        .ok { background:#e9f8ef; color:#176b3a; }
        .error { background:#fff0ef; color:#a12b21; }
        .empty { color:var(--muted); padding:20px 4px; text-align:center; }
        .small { color:var(--muted); font-size:12px; margin-top:12px; }
        @media (max-width:850px) { .layout { grid-template-columns:1fr; } }
        @media (max-width:560px) { .stats { grid-template-columns:1fr; } .wrap { margin-top:20px; } }
    </style>
</head>
<body>
<div class="wrap">
    <header>
        <h1>Student Record Management</h1>
        <p class="sub">Add, view, search, update, and delete student records in one place.</p>
    </header>

    <section class="stats">
        <div class="card"><div class="label">Total Students (COUNT)</div><div class="value"><?= h($summary["total_students"]) ?></div></div>
        <div class="card"><div class="label">Average CGPA (AVG)</div><div class="value"><?= number_format((float)$summary["average_cgpa"], 2) ?></div></div>
        <div class="card"><div class="label">Highest CGPA (MAX)</div><div class="value"><?= number_format((float)$summary["highest_cgpa"], 2) ?></div></div>
    </section>

    <?php if ($success): ?><div class="notice ok"><?= h($success) ?></div><?php endif; ?>
    <?php foreach ($errors as $error): ?><div class="notice error"><?= h($error) ?></div><?php endforeach; ?>

    <main class="layout">
        <section class="panel">
            <h2><?= $editStudent ? "Update Student" : "Add New Student" ?></h2>
            <form method="post">
                <input type="hidden" name="action" value="<?= $editStudent ? "update" : "add" ?>">
                <?php if ($editStudent): ?><input type="hidden" name="id" value="<?= h($editStudent["id"]) ?>"><?php endif; ?>
                <label for="name">Student Name</label>
                <input id="name" name="name" required maxlength="100" value="<?= h($editStudent["name"] ?? "") ?>" placeholder="e.g. Ayesha Rahman">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required maxlength="120" value="<?= h($editStudent["email"] ?? "") ?>" placeholder="student@example.com">
                <label for="department">Department</label>
                <input id="department" name="department" required maxlength="80" value="<?= h($editStudent["department"] ?? "") ?>" placeholder="e.g. CSE">
                <label for="semester">Semester (1–12)</label>
                <input id="semester" name="semester" type="number" min="1" max="12" required value="<?= h($editStudent["semester"] ?? "") ?>">
                <label for="cgpa">CGPA (0.00–4.00)</label>
                <input id="cgpa" name="cgpa" type="number" min="0" max="4" step="0.01" required value="<?= h($editStudent["cgpa"] ?? "") ?>">
                <div class="form-actions">
                    <button class="primary" type="submit"><?= $editStudent ? "Save Changes" : "Add Student" ?></button>
                    <?php if ($editStudent): ?><a class="btn secondary" href="index.php">Cancel</a><?php endif; ?>
                </div>
            </form>
            <p class="small">All fields are required. CGPA must be between 0 and 4.</p>
        </section>

        <section class="panel">
            <h2>Student Records</h2>
            <form class="searchbar" method="get">
                <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search by ID, name, email, or department">
                <button class="primary" type="submit">Search</button>
                <?php if ($search !== ""): ?><a class="btn secondary" href="index.php">Clear</a><?php endif; ?>
            </form>
            <div class="table-wrap">
                <table>
                    <thead><tr>
                        <th>ID</th><th>Name</th><th>Email</th><th>Department</th><th>Semester</th><th>CGPA</th><th>Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php if (!$students): ?>
                        <tr><td colspan="7" class="empty">No student records found.</td></tr>
                    <?php else: foreach ($students as $student): ?>
                        <tr>
                            <td><?= h($student["id"]) ?></td>
                            <td><?= h($student["name"]) ?></td>
                            <td><?= h($student["email"]) ?></td>
                            <td><?= h($student["department"]) ?></td>
                            <td><?= h($student["semester"]) ?></td>
                            <td><?= number_format((float)$student["cgpa"], 2) ?></td>
                            <td>
                                <div class="actions">
                                    <a class="btn secondary" href="index.php?edit=<?= h($student["id"]) ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Are you sure you want to delete this student record?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= h($student["id"]) ?>">
                                        <button class="danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="small">Showing <?= count($students) ?> record(s)<?= $search !== "" ? " matching “".h($search)."”" : "" ?>.</p>
        </section>
    </main>
</div>
</body>
</html>
