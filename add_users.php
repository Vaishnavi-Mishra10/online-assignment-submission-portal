<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Add User Logic - Tera purana code
if(isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";
    if($conn->query($sql)) {
        $success = "User added successfully!";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// CSV Upload Logic 
if(isset($_POST['upload_csv'])) {
    if(isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        $count = 0;
        
        // Skip header row
        fgetcsv($handle);
        
        while(($data = fgetcsv($handle, 1000, ","))!== FALSE) {
            $name = mysqli_real_escape_string($conn, $data[0]);
            $email = mysqli_real_escape_string($conn, $data[1]);
            $password = password_hash($data[2], PASSWORD_DEFAULT);
            $role = mysqli_real_escape_string($conn, $data[3]);
            
            // Check if email already exists
            $check = "SELECT id FROM users WHERE email = '$email'";
            $result = $conn->query($check);
            
            if($result->num_rows == 0) {
                $sql = "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$role')";
                if($conn->query($sql)) {
                    $count++;
                }
            }
        }
        fclose($handle);
        
        if($count > 0) {
            $success = "$count users uploaded successfully!";
        } else {
            $error = "No new users uploaded. Check CSV format or duplicate emails.";
        }
    } else {
        $error = "Please select a valid CSV file.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Users - OASP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #1a1a2e; color: #fff; }
        .card { background-color: #16213e; border: none; }
        .navbar { background-color: #0f3460 !important; }
        .form-control, .form-select { background-color: #0f3460; color: #fff; border: 1px solid #444; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <span class="navbar-text">Welcome, <?php echo $_SESSION['name']; ?> (Admin)</span>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <i class="fas fa-user-plus"></i> Add New User
            </div>
            <div class="card-body">
            <form method="POST" class="mb-3">
    <div class="row g-2">
        <div class="col-md-3">
            <input type="text" name="name" class="form-control" placeholder="Name" required>
        </div>
        <div class="col-md-3">
            <input type="email" name="email" class="form-control" placeholder="Email" required>
        </div>
        <div class="col-md-2">
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>
        <div class="col-md-2">
            <select name="role" class="form-control" required>
                <option value="student">Student</option>
                <option value="teacher">Teacher</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" name="add_user" class="btn btn-success w-100">
                <i class="fas fa-plus"></i> Add User
            </button>
        </div>
    </div>
</form>
            </div>
        </div>
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="fas fa-file-csv"></i> Bulk Upload Students via CSV
            </div>
            <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
    <div class="row">
        <div class="col-md-6">
            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
            <small class="text-muted">CSV Format: name,email,password,role</small>
        </div>
        <div class="col-md-6">
            <button type="submit" name="upload_csv" class="btn btn-info">
                <i class="fas fa-upload"></i> Upload CSV
            </button>
            <a href="sample.csv" class="btn btn-secondary" download>
                <i class="fas fa-download"></i> Sample CSV
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
</body>
</html>