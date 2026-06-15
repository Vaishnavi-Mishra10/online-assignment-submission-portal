<?php
include 'db.php';
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$name = $_SESSION['name'];

// DELETE ASSIGNMENT - TEACHER
if(isset($_GET['delete_assignment']) && $role == 'teacher'){
    $assign_id = $_GET['delete_assignment'];
    $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT teacher_id FROM assignments WHERE id=$assign_id"));
    if($check['teacher_id'] == $user_id){
        mysqli_query($conn, "DELETE FROM submissions WHERE assignment_id=$assign_id");
        mysqli_query($conn, "DELETE FROM assignments WHERE id=$assign_id");
        header("Location: dashboard.php?msg=deleted");
        exit();
    }
}

// Handle New Assignment Creation by Teacher
if(isset($_POST['create_assignment']) && $role == 'teacher'){
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $due_date = $_POST['due_date'];
    
    $stmt = $conn->prepare("INSERT INTO assignments (title, description, due_date, teacher_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $title, $desc, $due_date, $user_id);
    $stmt->execute();
    $success = "Assignment created successfully";
}

// Handle Assignment Submission by Student
if(isset($_POST['submit_assignment']) && $role == 'student'){
    $assignment_id = $_POST['assignment_id'];
    $file = $_FILES['file'];
    
    $file_name = time() . '_' . basename($file['name']);
    $target = "uploads/" . $file_name;
    
    if(move_uploaded_file($file['tmp_name'], $target)){
        $check = mysqli_query($conn, "SELECT due_date FROM assignments WHERE id = $assignment_id");
        $row = mysqli_fetch_assoc($check);
        $is_late = (strtotime(date("Y-m-d H:i:s")) > strtotime($row['due_date'])) ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO submissions (assignment_id, student_id, file_path, is_late) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisi", $assignment_id, $user_id, $file_name, $is_late);
        $stmt->execute();
        $success = "Assignment submitted successfully";
    } else {
        $error = "File upload failed";
    }
}

// Handle Grading by Teacher
if(isset($_POST['grade_submission']) && $role == 'teacher'){
    $sub_id = $_POST['sub_id'];
    $marks = $_POST['marks'];
    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']); 
    
    $stmt = $conn->prepare("UPDATE submissions SET marks = ?, feedback = ?, graded_at = NOW() WHERE id = ?");
    $stmt->bind_param("isi", $marks, $feedback, $sub_id); // i=int, s=string, i=int
    $stmt->execute();
    $success = "Assignment graded successfully";
    echo "<meta http-equiv='refresh' content='0'>"; // Page refresh for instant update
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Assignment Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body.dark-mode { background-color: #121212 !important; color: #e4e4e4 !important; }
        .dark-mode .card { background-color: #1e1e1e !important; border: 1px solid #333 !important; color: #e4e4e4 !important; }
        .dark-mode .navbar { background-color: #0a58ca !important; }
        .dark-mode .table { color: #e4e4e4 !important; }
        .dark-mode .form-control { background-color: #2d2d2d !important; color: #e4e4e4 !important; border: 1px solid #444 !important; }
        .dark-mode .text-muted { color: #a0a0a0 !important; }
        .dark-mode .card-body { background-color: #1e1e1e !important; }
        .dark-mode .table { 
            --bs-table-bg: #1e1e1e !important; 
            background-color: #1e1e1e !important; 
            color: #e4e4e4 !important; 
        }
        .dark-mode .table th, .dark-mode .table td { 
            background-color: #1e1e1e !important;
            border-color: #444 !important; 
            color: #e4e4e4 !important;
        }
        .dark-mode .table-striped > tbody > tr:nth-of-type(odd) > * {
            --bs-table-accent-bg: #2a2a2a !important;
            color: #e4e4e4 !important;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-primary">
    <div class="container-fluid">
        <span class="navbar-brand"><i class="fas fa-graduation-cap"></i> Assignment Portal</span>
        <div class="d-flex">
            <button class="btn btn-outline-light btn-sm me-3" id="darkModeToggle">
                <i class="fas fa-moon"></i>
            </button>
            <span class="navbar-text me-3">Welcome, <?php echo $name; ?> (<?php echo ucfirst($role); ?>)</span>
            <a href="logout.php" class="btn btn-light btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <!-- OASP LOGO WITH SPIN -->
     <div class="text-center mb-4">
    <img src="logo.png" alt="OASP Logo" style="height:80px; width:80px; border-radius:50%; box-shadow:0 4px 8px rgba(0,0,0,0.2); border:3px solid #0d6efd;">
</div>
    <style>
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    </style>
    <?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
    
    <!-- ADMIN PANEL -->
    <?php if($role == 'admin'): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header bg-dark text-white"><i class="fas fa-users"></i> Manage Users</div>
                <div class="card-body">
                    <a href="register.php" class="btn btn-success btn-sm mb-3"><i class="fas fa-user-plus"></i> Add New User</a>
                    <table class="table table-sm table-striped">
                        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php
                        $users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
                        while($u = mysqli_fetch_assoc($users)){
                            echo "<tr>
                                <td>{$u['name']}</td>
                                <td>{$u['email']}</td>
                                <td><span class='badge bg-secondary'>{$u['role']}</span></td>
                                <td>
                                    <a href='update_user.php?id={$u['id']}' class='btn btn-warning btn-sm'><i class='fas fa-edit'></i></a>
                                    <a href='update_user.php?delete={$u['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure?\")'><i class='fas fa-trash'></i></a>
                                </td>
                            </tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- TEACHER PANEL -->
<?php if($role == 'teacher'): ?>
<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-success text-white"><i class="fas fa-plus"></i> Create Assignment</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-2"><input type="text" name="title" class="form-control" placeholder="Assignment Title" required></div>
                    <div class="mb-2"><textarea name="description" class="form-control" placeholder="Description" rows="3"></textarea></div>
                    <div class="mb-2"><label>Due Date & Time</label><input type="datetime-local" name="due_date" class="form-control" required></div>
                    <button type="submit" name="create_assignment" class="btn btn-success w-100">Create</button>
                </form>
            </div>
        </div>
    </div>
    <!-- MY ASSIGNMENTS - TEACHER -->
<div class="card mb-4">
    <div class="card-header bg-warning text-dark"><i class="fas fa-list"></i> My Created Assignments</div>
    <div class="card-body">
        <?php
        $my_assignments = mysqli_query($conn, "SELECT * FROM assignments WHERE teacher_id = $user_id ORDER BY due_date DESC");
        if(mysqli_num_rows($my_assignments) > 0){
            echo "<div class='table-responsive'><table class='table table-sm table-hover'>
                    <thead><tr>
                        <th>Title</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Submissions</th>
                        <th>Action</th>
                    </tr></thead><tbody>";
            
            while($assign = mysqli_fetch_assoc($my_assignments)){
                $is_overdue = strtotime(date("Y-m-d H:i:s")) > strtotime($assign['due_date']);
                $status_badge = $is_overdue ? "<span class='badge bg-danger'>Closed</span>" : "<span class='badge bg-success'>Active</span>";
                
                // Count submissions for this assignment
                $sub_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM submissions WHERE assignment_id = {$assign['id']}"));
                
                echo "<tr>
                        <td><b>{$assign['title']}</b><br><small class='text-muted'>{$assign['description']}</small></td>
                        <td>{$assign['due_date']}</td>
                        <td>$status_badge</td>
                        <td><span class='badge bg-info'>{$sub_count['total']} Submitted</span></td>
                        <td>
                        <a href='edit_assignment.php?id={$assign['id']}' class='btn btn-sm btn-warning me-1'><i class='fas fa-edit'></i></a>
                        <a href='dashboard.php?delete_assignment={$assign['id']}' class='btn btn-sm btn-danger' onclick='return confirm (\"Delete this assignment? All submissions will also be deleted!\")'><i class='fas fa-trash'></i></a>
                        </td>
                      </tr>";
            }
            echo "</tbody></table></div>";
        } else {
            echo "<p class='text-muted'>You haven't created any assignments yet.</p>";
        }
        ?>
    </div>
</div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-info text-white"><i class="fas fa-file-alt"></i> Student Submissions</div>
            <div class="card-body">
                <?php
                // Handle Grading by Teacher - Updated for feedback column
                if(isset($_POST['grade_submission']) && $role == 'teacher'){
                    $sub_id = $_POST['sub_id'];
                    $marks = $_POST['marks'];
                    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);
                    
                    $stmt = $conn->prepare("UPDATE submissions SET marks = ?, feedback = ?, graded_at = NOW() WHERE id = ?");
                    $stmt->bind_param("isi", $marks, $feedback, $sub_id);
                    $stmt->execute();
                    $success = "Assignment graded successfully";
                    echo "<meta http-equiv='refresh' content='0'>"; // Page refresh for instant update
                }

                $subs = mysqli_query($conn, "SELECT s.*, a.title, u.name as student_name FROM submissions s 
                    JOIN assignments a ON s.assignment_id = a.id 
                    JOIN users u ON s.student_id = u.id 
                    WHERE a.teacher_id = $user_id ORDER BY s.submitted_at DESC");
                
                if(isset($success)) echo "<div class='alert alert-success'>$success</div>";
                
                if(mysqli_num_rows($subs) > 0){
                    while($sub = mysqli_fetch_assoc($subs)){
                        $status = $sub['is_late'] ? "<span class='badge bg-danger'>Late</span>" : "<span class='badge bg-success'>On Time</span>";
                        $graded = (!empty($sub['marks'])) ? "<span class='badge bg-primary'>Graded: {$sub['marks']}/10</span>" : "<span class='badge bg-warning'>Not Graded</span>";
                        
                        echo "<div class='border p-3 mb-3 rounded'>
                            <h6><b>{$sub['title']}</b> by {$sub['student_name']} $status $graded</h6>
                            <small>Submitted: {$sub['submitted_at']}</small><br>
                            <a href='uploads/{$sub['file_path']}' target='_blank' class='btn btn-sm btn-primary mt-1 mb-2'><i class='fas fa-download'></i> Download File</a>";
                        
                        if(!empty($sub['feedback'])){
                            echo "<div class='alert alert-secondary p-2 mt-1'><b>Feedback:</b> {$sub['feedback']}</div>";
                        }
                        
                        $marks_val = $sub['marks'] ?? '';
                        $feedback_val = $sub['feedback'] ?? '';
                        
                        echo "<form method='POST' class='mt-2'>
                            <input type='hidden' name='sub_id' value='{$sub['id']}'>
                            <div class='row g-2'>
                                <div class='col-md-3'><input type='number' name='marks' class='form-control form-control-sm' placeholder='Marks out of 10' value='$marks_val' max='10' min='0' required></div>
                                <div class='col-md-7'><input type='text' name='feedback' class='form-control form-control-sm' placeholder='Feedback' value='$feedback_val' required></div>
                                <div class='col-md-2'><button type='submit' name='grade_submission' class='btn btn-sm btn-success w-100'>Save</button></div>
                            </div>
                        </form>
                        </div>";
                    }
                } else {
                    echo "<p class='text-muted'>No submissions yet.</p>";
                }
                ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

    <!-- STUDENT PANEL -->
    <?php if($role == 'student'): ?>
    <div class="card">
        <div class="card-header bg-primary text-white"><i class="fas fa-book"></i> Available Assignments</div>
        <div class="card-body">
            <?php
            $assignments = mysqli_query($conn, "SELECT * FROM assignments ORDER BY due_date ASC");
            while($a = mysqli_fetch_assoc($assignments)){
                $check_sub = mysqli_query($conn, "SELECT * FROM submissions WHERE assignment_id = {$a['id']} AND student_id = $user_id");
                $submitted = mysqli_num_rows($check_sub) > 0;
                $is_overdue = strtotime(date("Y-m-d H:i:s")) > strtotime($a['due_date']);
                
                echo "<div class='border p-3 mb-3 rounded'>
                    <h5>{$a['title']}</h5>
                    <p>{$a['description']}</p>
                    <p><b>Due Date:</b> {$a['due_date']}";
                if($is_overdue) echo " <span class='badge bg-danger'>Overdue</span>";
                echo "</p>";
                
                if($submitted){
                    $sub_data = mysqli_fetch_assoc($check_sub);
                    $status = $sub_data['is_late'] ? "<span class='badge bg-danger'>Submitted Late</span>" : "<span class='badge bg-success'>Submitted On Time</span>";
                    $grade_info = !empty($sub_data['marks']) ? "<div class='alert alert-success mt-2'><b>Marks:</b> {$sub_data['marks']}/10 <br><b>Feedback:</b> {$sub_data['feedback']}</div>" : "<div class='alert alert-warning mt-2'>Not graded yet</div>";
                    
                    echo "<div class='alert alert-info'>Already Submitted $status on {$sub_data['submitted_at']}</div> $grade_info";
                } else {
                    echo "<form method='POST' enctype='multipart/form-data'>
                        <input type='hidden' name='assignment_id' value='{$a['id']}'>
                        <div class='input-group'>
                            <input type='file' name='file' class='form-control' required>
                            <button type='submit' name='submit_assignment' class='btn btn-primary'>Submit</button>
                        </div>
                    </form>";
                }
                echo "</div>";
            }
            ?>
        </div>
    <?php endif; ?>
</div>
<script>
    const toggleBtn = document.getElementById('darkModeToggle');
    const body = document.body;
    
    // Page load pe check karo - pehle se dark mode on tha kya
    if(localStorage.getItem('darkMode') === 'enabled') {
        body.classList.add('dark-mode');
        toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
    }
    
    // Button click pe dark mode toggle karo
    toggleBtn.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
        if(body.classList.contains('dark-mode')) {
            localStorage.setItem('darkMode', 'enabled');
            toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
        } else {
            localStorage.setItem('darkMode', 'disabled');
            toggleBtn.innerHTML = '<i class="fas fa-moon"></i>';
        }
    });
</script>
</body>
</html>






















