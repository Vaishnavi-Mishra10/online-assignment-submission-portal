<?php
include 'db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

// Delete User
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM users WHERE id = $id");
    header("Location: dashboard.php");
    exit();
}

// Update User
if(isset($_POST['update'])){
    $id = $_POST['id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = $_POST['role'];
    
    // Check if email exists for other users
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' AND id != $id");
    if(mysqli_num_rows($check) > 0){
        $error = "Email already exists for another user.";
    } else {
        if(!empty($_POST['password'])){
            $password = $_POST['password'];
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=?, role=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $email, $password, $role, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $email, $role, $id);
        }
        
        if($stmt->execute()){
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Update failed. Please try again.";
        }
    }
}

$id = $_GET['id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $id"));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><a href="dashboard.php" class="btn btn-sm btn-secondary">Back to Dashboard</a> Update User Details</div>
                <div class="card-body">
                    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                        <div class="mb-3"><label>Full Name</label><input type="text" name="name" class="form-control" value="<?php echo $user['name']; ?>" required></div>
                        <div class="mb-3"><label>Email Address</label><input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required></div>
                        <div class="mb-3"><label>New Password</label><input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password"></div>
                        <div class="mb-3">
                            <label>User Role</label>
                            <select name="role" class="form-control" required>
                                <option value="student" <?php if($user['role']=='student') echo 'selected'; ?>>Student</option>
                                <option value="teacher" <?php if($user['role']=='teacher') echo 'selected'; ?>>Teacher</option>
                                <option value="admin" <?php if($user['role']=='admin') echo 'selected'; ?>>Admin</option>
                            </select>
                        </div>
                        <button type="submit" name="update" class="btn btn-warning w-100">Update User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>