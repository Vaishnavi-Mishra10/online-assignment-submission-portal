<?php
include 'db.php';
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

if(isset($_POST['register'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = $_POST['role'];
    
    // Check if email already exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if(mysqli_num_rows($check) > 0){
        $error = "Email already exists. Please use a different email.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hash, $role);
        if($stmt->execute()){
            $success = "User registered successfully";
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body.dark-mode { background-color: #121212 !important; color: #e4e4e4 !important; }
    .dark-mode .card { background-color: #1e1e1e !important; border: 1px solid #333 !important; }
    .dark-mode .form-control, .dark-mode .form-select { 
        background-color: #2d2d2d !important; 
        color: #e4e4e4 !important; 
        border-color: #444 !important; 
    }
    .dark-mode label { color: #e4e4e4 !important; }
    .dark-mode .card-header { background-color: #252525 !important; border-color: #333 !important; }
</style>
</head>
<body>
<div style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
    <button class="btn btn-outline-dark btn-sm" id="darkModeToggle">
        <i class="fas fa-moon"></i>
    </button>
</div>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><a href="dashboard.php" class="btn btn-sm btn-secondary">Back to Dashboard</a> Register New User</div>
                <div class="card-body">
                    <?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
                    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    <form method="POST">
                        <div class="mb-3"><label>Full Name</label><input type="text" name="name" class="form-control" required></div>
                        <div class="mb-3"><label>Email Address</label><input type="email" name="email" class="form-control" required></div>
                        <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                        <div class="mb-3">
                            <label>User Role</label>
                            <select name="role" class="form-control" required>
                                <option value="">Select Role</option>
                                <option value="student">Student</option>
                                <option value="teacher">Teacher</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <button type="submit" name="register" class="btn btn-primary w-100">Register User</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const toggleBtn = document.getElementById('darkModeToggle');
    const body = document.body;
    
    // Page load pe check karo dark mode on tha kya
    if(localStorage.getItem('darkMode') === 'enabled') {
        body.classList.add('dark-mode');
        toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
        toggleBtn.classList.remove('btn-outline-dark');
        toggleBtn.classList.add('btn-outline-light');
    }
    
    // Button click pe chalega
    toggleBtn.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
        if(body.classList.contains('dark-mode')) {
            localStorage.setItem('darkMode', 'enabled');
            toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
            toggleBtn.classList.remove('btn-outline-dark');
            toggleBtn.classList.add('btn-outline-light');
        } else {
            localStorage.setItem('darkMode', 'disabled');
            toggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
            toggleBtn.classList.remove('btn-outline-light');
            toggleBtn.classList.add('btn-outline-dark');
        }
    });
</script>
</body>
</html>