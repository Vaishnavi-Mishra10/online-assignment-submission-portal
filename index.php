<?php
session_start();
include 'db.php';

if(isset($_POST['login'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);
    
    if(mysqli_num_rows($result) == 1){
        $user = mysqli_fetch_assoc($result);
        if(password_verify($password, $user['password'])){
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password!";
        }
    } else {
        $error = "Invalid email or password!";
    }
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Assignment Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    body.dark-mode { background-color: #121212 !important; color: #e4e4e4 !important; }
    .dark-mode .card { 
        background-color: #1e1e1e !important; 
        border: 1px solid #333 !important; 
        box-shadow: 0 0 20px rgba(0,0,0,0.5) !important;
    }
    .dark-mode .form-control { 
        background-color: #2d2d2d !important; 
        color: #e4e4e4 !important; 
        border-color: #444 !important; 
    }
    .dark-mode .form-control:focus {
        background-color: #2d2d2d !important;
        color: #e4e4e4 !important;
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
    }
    .dark-mode .form-control::placeholder { color: #888 !important; }
    .dark-mode label { color: #e4e4e4 !important; }
    .dark-mode .text-muted { color: #a0a0a0 !important; }
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
        <div class="col-md-5">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <!-- LOGIN PAGE LOGO -->
                <div class="text-center mb-4">
                    <img src="logo.png" alt="OASP Logo" style="height:100px; width:100px; border-radius:50%; box-shadow:0 6px 15px rgba(0,0,0,0.2); border:4px solid #0d6efd;">
                    <h3 class="mt-3 text-primary">OASP Portal</h3>
                    <p class="text-muted">Online Assignment Submission</p>
                </div>
                    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary w-100">Login <i class="fas fa-sign-in-alt"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const toggleBtn = document.getElementById('darkModeToggle');
    const body = document.body;
    
    // 1. Page load pe check karo - pehle se dark mode on hai kya?
    if(localStorage.getItem('darkMode') === 'enabled') {
        body.classList.add('dark-mode');
        toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
        toggleBtn.classList.remove('btn-outline-dark');
        toggleBtn.classList.add('btn-outline-light');
    }
    
    // 2. Button click pe chalega
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
