<?php
include 'db.php';
//session_start();

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Update logic
if(isset($_POST['update_assignment'])){
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $due_date = $_POST['due_date'];
    
    mysqli_query($conn, "UPDATE assignments SET title='$title', description='$desc', due_date='$due_date' WHERE id=$id AND teacher_id=$user_id");
    header("Location: dashboard.php?msg=updated");
    exit();
}

// Fetch old data
$assign = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM assignments WHERE id=$id AND teacher_id=$user_id"));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Assignment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="container mt-4">
    <h3><i class="fas fa-edit"></i> Edit Assignment</h3>
    <form method="POST" class="card p-3">
        <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" class="form-control" value="<?php echo $assign['title']; ?>" required>
        </div>
        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control" required><?php echo $assign['description']; ?></textarea>
        </div>
        <div class="mb-3">
            <label>Due Date</label>
            <input type="datetime-local" name="due_date" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($assign['due_date'])); ?>" required>
        </div>
        <button type="submit" name="update_assignment" class="btn btn-primary">Update Assignment</button>
        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
    </form>
</body>
</html>