<?php
/* --------- CONFIG --------- */
// Reads from environment vars (set these in Azure → Configuration)
$DB_HOST = getenv("DB_HOST") ?: "php-server1.mysql.database.azure.com";
$DB_USER = getenv("DB_USER") ?: "user";
$DB_PASS = getenv("DB_PASS") ?: "Aqib1234";
$DB_NAME = getenv("DB_NAME") ?: "taskdb";

/* --------- DB CONNECT --------- */
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$db_ok = $conn && mysqli_ping($conn);
$messages = [];

if (!$db_ok) {
  $messages[] = [
    "type" => "error",
    "text" => "Database connection failed. Check DB_HOST/DB_USER/DB_PASS/DB_NAME in App Settings."
  ];
} else {
  // Ensure table exists
  $createSql = "CREATE TABLE IF NOT EXISTS tasks (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB";
  if (!mysqli_query($conn, $createSql)) {
    $messages[] = ["type" => "error", "text" => "Could not ensure table: " . htmlspecialchars(mysqli_error($conn))];
  }
}

/* --------- ACTIONS --------- */
if ($db_ok && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task'])) {
  $task = trim($_POST['task']);
  if ($task === "") {
    $messages[] = ["type" => "error", "text" => "Task cannot be empty."];
  } elseif (mb_strlen($task) > 255) {
    $messages[] = ["type" => "error", "text" => "Task too long (max 255 chars)."];
  } else {
    $stmt = mysqli_prepare($conn, "INSERT INTO tasks (name) VALUES (?)");
    mysqli_stmt_bind_param($stmt, "s", $task);
    if (mysqli_stmt_execute($stmt)) {
      header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); // PRG pattern
      exit;
    } else {
      $messages[] = ["type" => "error", "text" => "Insert failed: " . htmlspecialchars(mysqli_error($conn))];
    }
  }
}

if ($db_ok && isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  if ($id > 0) {
    $stmt = mysqli_prepare($conn, "DELETE FROM tasks WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
      header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
      exit;
    } else {
      $messages[] = ["type" => "error", "text" => "Delete failed: " . htmlspecialchars(mysqli_error($conn))];
    }
  }
}

/* --------- FETCH --------- */
$tasks = [];
if ($db_ok) {
  $res = mysqli_query($conn, "SELECT id, name, created_at FROM tasks ORDER BY id DESC");
  if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
      $tasks[] = $row;
    }
  } else {
    $messages[] = ["type" => "error", "text" => "Query failed: " . htmlspecialchars(mysqli_error($conn))];
  }
}

/* --------- HELPER --------- */
function mask($s) {
  if ($s === "") return "";
  $len = strlen($s);
  if ($len <= 4) return str_repeat("•", $len);
  return substr($s, 0, 1) . str_repeat("•", $len - 2) . substr($s, -1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Task Manager (PHP + MySQL)</title>
<style>
  :root {
    --bg: #f5f7fb;
    --card: #ffffff;
    --text: #222;
    --muted: #666;
    --accent: #0a7cff;
    --accent-hover: #075cc0;
    --danger: #e5484d;
    --danger-bg: #ffecec;
    --border: #e9edf3;
    --ok: #12a150;
    --warn: #b58900;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0; background: var(--bg); color: var(--text);
    font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding: 24px;
  }
  .wrap {
    width: 100%; max-width: 680px; background: var(--card); border: 1px solid var(--border);
    border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,.05); overflow: hidden;
  }
  header {
    padding: 18px 22px; display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid var(--border); background: linear-gradient(180deg, #fff, #fafcff);
  }
  .title { font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
  .badge {
    font-size: 12px; padding: 6px 10px; border-radius: 999px; border: 1px solid var(--border);
    background: #f8fafc; color: var(--muted);
  }
  .badge.ok { color: var(--ok); border-color: #cde9da; background: #f2fbf6; }
  .badge.err { color: var(--danger); border-color: #ffd1d4; background: #fff5f6; }
  main { padding: 22px; }

  .env {
    display: grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; margin: 10px 0 18px;
    color: var(--muted); font-size: 12px;
  }
  .env div { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

  .messages { display: grid; gap: 10px; margin-bottom: 16px; }
  .msg {
    padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: #f9fbff;
    font-size: 14px;
  }
  .msg.error { border-color: #ffd1d4; background: var(--danger-bg); color: #8a1c20; }
  .msg.ok { border-color: #cde9da; background: #f3fcf7; color: #0f6a3f; }

  form.add {
    display: flex; gap: 10px; margin-bottom: 18px;
  }
  .input {
    flex: 1; padding: 12px 14px; border: 1px solid var(--border); border-radius: 12px;
    outline: none; font-size: 14px; background: #fbfdff;
  }
  .input:focus { border-color: #c7dbff; box-shadow: 0 0 0 4px #e8f1ff; }
  .btn {
    padding: 12px 16px; border: 1px solid transparent; border-radius: 12px; cursor: pointer;
    background: var(--accent); color: #fff; font-weight: 600; font-size: 14px;
  }
  .btn:hover { background: var(--accent-hover); }

  .list {
    border: 1px solid var(--border); border-radius: 12px; overflow: hidden; background: #fff;
  }
  .row {
    display: grid; grid-template-columns: 1fr auto auto; gap: 10px; align-items: center;
    padding: 12px 14px; border-bottom: 1px solid var(--border);
  }
  .row:last-child { border-bottom: 0; }
  .name { font-weight: 600; }
  .time { color: var(--muted); font-size: 12px; }
  .del {
    text-decoration: none; font-size: 13px; padding: 8px 10px; border-radius: 10px;
    border: 1px solid #ffd1d4; color: var(--danger); background: #fff;
  }
  .del:hover { background: var(--danger-bg); }
  footer { padding: 16px 22px; color: var(--muted); font-size: 12px; border-top: 1px solid var(--border); }
</style>
</head>
<body>
  <div class="wrap">
    <header>
      <div class="title">📋 Task Manager</div>
      <div class="badge <?= $db_ok ? 'ok' : 'err' ?>">
        <?= $db_ok ? 'DB Connected' : 'DB Not Connected' ?>
      </div>
    </header>

    <main>
      <div class="env">
        <div><strong>DB_HOST:</strong> <?= htmlspecialchars($DB_HOST) ?></div>
        <div><strong>DB_USER:</strong> <?= htmlspecialchars($DB_USER) ?></div>
        <div><strong>DB_NAME:</strong> <?= htmlspecialchars($DB_NAME) ?></div>
        <div><strong>DB_PASS:</strong> <?= htmlspecialchars(mask($DB_PASS)) ?></div>
      </div>

      <?php if (!empty($messages)): ?>
        <div class="messages">
          <?php foreach ($messages as $m): ?>
            <div class="msg <?= $m['type'] === 'error' ? 'error' : 'ok' ?>">
              <?= htmlspecialchars($m['text']) ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form class="add" method="post" action="">
        <input class="input" type="text" name="task" placeholder="Add a new task…" required />
        <button class="btn" type="submit">Add</button>
      </form>

      <div class="list">
        <?php if ($db_ok && count($tasks) === 0): ?>
          <div class="row"><div class="name">No tasks yet. Add one above 👆</div><div></div><div></div></div>
        <?php endif; ?>

        <?php foreach ($tasks as $t): ?>
          <div class="row">
            <div>
              <div class="name"><?= htmlspecialchars($t['name']) ?></div>
              <div class="time">#<?= (int)$t['id'] ?> • <?= htmlspecialchars($t['created_at']) ?></div>
            </div>
            <a class="del" href="?delete=<?= (int)$t['id'] ?>" onclick="return confirm('Delete this task?')">Delete</a>
          </div>
        <?php endforeach; ?>
      </div>
    </main>

    <footer>
      PHP renders this page on the server and talks to MySQL. If you see “DB Not Connected”, fix App Settings.
    </footer>
  </div>
</body>
</html>
