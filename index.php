<?php
include "db.php";

// Handle new task
if (isset($_POST['task'])) {
    $task = $_POST['task'];
    $sql = "INSERT INTO tasks (name) VALUES ('$task')";
    mysqli_query($conn, $sql);
    header("Location: index.php"); // Refresh after adding
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM tasks WHERE id=$id";
    mysqli_query($conn, $sql);
    header("Location: index.php"); // Refresh after deleting
}

// Fetch tasks
$result = mysqli_query($conn, "SELECT * FROM tasks ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Task Manager</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f6f9;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
      margin: 0;
      padding: 20px;
    }

    .container {
      background: white;
      width: 400px;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    h1 {
      text-align: center;
      color: #333;
    }

    form {
      display: flex;
      margin-bottom: 20px;
    }

    form input {
      flex: 1;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 8px 0 0 8px;
      outline: none;
    }

    form button {
      background: #0078D7;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 0 8px 8px 0;
      cursor: pointer;
      transition: background 0.3s;
    }

    form button:hover {
      background: #005ea6;
    }

    ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    ul li {
      background: #f9f9f9;
      margin: 8px 0;
      padding: 10px;
      border-radius: 8px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border: 1px solid #eee;
    }

    ul li span {
      flex: 1;
    }

    ul li a {
      text-decoration: none;
      color: #ff4d4d;
      font-weight: bold;
      padding: 5px 10px;
      border-radius: 6px;
      transition: background 0.3s;
    }

    ul li a:hover {
      background: #ffe6e6;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>📋 Task Manager</h1>

    <form method="POST" action="">
      <input type="text" name="task" placeholder="Enter new task" required>
      <button type="submit">Add</button>
    </form>

    <ul>
      <?php while($row = mysqli_fetch_assoc($result)) { ?>
        <li>
          <span><?php echo htmlspecialchars($row['name']); ?></span>
          <a href="?delete=<?php echo $row['id']; ?>">❌</a>
        </li>
      <?php } ?>
    </ul>
  </div>
</body>
</html>
