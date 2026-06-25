<?php
session_start();
if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit; }
include 'db.php';

$id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$id"));

$msg = "";
if(isset($_POST['update'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    if(!empty($_POST['new_password'])){
        $pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET name='$name', email='$email', password='$pass' WHERE id=$id");
    } else {
        mysqli_query($conn, "UPDATE users SET name='$name', email='$email' WHERE id=$id");
    }
    $_SESSION['name'] = $name;
    $msg = "Profile Updated Successfully!";
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$id"));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body.dark-mode { background-color: #1a1a1a; color: #fff; }
        .dark-mode .card, .dark-mode .form-control { background-color: #2d2d2d; color: #fff; border-color: #444; }
        .dark-mode .navbar { background-color: #0d6efd !important; }
    </style>
</head>
<body>
    <!-- Navbar with Dark Mode Toggle -->
    <nav class="navbar navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <span class="navbar-brand">Assignment Portal</span>
            <div>
                <button id="darkModeToggle" class="btn btn-light btn-sm me-2"><i class="fas fa-moon"></i></button>
                <a href="dashboard.php" class="btn btn-light btn-sm me-2"><i class="fas fa-arrow-left"></i> Dashboard</a>
                <a href="logout.php" class="btn btn-light btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>My Profile</h2>
        
        <?php if($msg) echo "<div class='alert alert-success'>$msg</div>"; ?>
        
        <div class="card col-md-6 p-4">
            <form method="POST">
                <div class="mb-3">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo $user['name']; ?>" required>
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                </div>
                <div class="mb-3">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Leave blank if you don't want to change">
                </div>
                <button type="submit" name="update" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
    </div>

<script>
// Dark Mode JS - Same as Dashboard
const toggle = document.getElementById('darkModeToggle');
const body = document.body;
const icon = toggle.querySelector('i');

// Page load pe check karo
if(localStorage.getItem('darkMode') === 'enabled'){
    body.classList.add('dark-mode');
    icon.classList.replace('fa-moon', 'fa-sun');
}

toggle.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    if(body.classList.contains('dark-mode')){
        localStorage.setItem('darkMode', 'enabled');
        icon.classList.replace('fa-moon', 'fa-sun');
    } else {
        localStorage.setItem('darkMode', 'disabled');
        icon.classList.replace('fa-sun', 'fa-moon');
    }
});
</script>
</body>
</html>