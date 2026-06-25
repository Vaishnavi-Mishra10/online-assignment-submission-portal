<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'teacher' && $_SESSION['role'] != 'admin')) {
    header("Location: login.php");
    exit();
}

$sql = "SELECT u.id, u.name, u.email, COUNT(s.id) as total_submissions 
        FROM users u 
        LEFT JOIN submissions s ON u.id = s.student_id 
        WHERE u.role = 'student' 
        GROUP BY u.id 
        ORDER BY u.name ASC";
$result = $conn->query($sql);
$total_students = $result->num_rows;
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Students - OASP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #1a1a2e; color: #fff; }
        .card { background-color: #16213e; border: none; }
        .table { color: #fff; }
        .navbar { background-color: #0f3460 !important; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <span class="navbar-text">Logged in as: <?php echo $_SESSION['name']; ?></span>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card p-3 text-center">
                    <h3><?php echo $total_students; ?></h3>
                    <p>Total Students</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-info text-dark">
                <h4><i class="fas fa-users"></i> All Students List</h4>
            </div>
            <div class="card-body">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Email ID</th>
                            <th>Total Submissions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        while($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><span class="badge bg-primary"><?php echo $row['total_submissions']; ?> Assignments</span></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>