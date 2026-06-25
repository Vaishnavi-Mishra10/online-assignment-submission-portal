<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
include 'db.php';
if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$name = $_SESSION['name'];
// USER MANAGEMENT - ADMIN
// DELETE USER
if(isset($_GET['delete_user']) && $role == 'admin'){
    $id = $_GET['delete_user'];
    if($id != $_SESSION['user_id']){
        mysqli_query($conn, "DELETE FROM users WHERE id='$id'");
        header("Location: dashboard.php?msg=User+Deleted");
        exit();
    }
}

// ADD NEW USER
if(isset($_POST['add_new_user']) && $role == 'admin'){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; 
    $user_role = $_POST['role'];
    mysqli_query($conn, "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$user_role')");
    header("Location: dashboard.php?msg=User+Added");
    exit();
}

// UPDATE USER
if(isset($_POST['update_user']) && $role == 'admin'){
    $id = $_POST['user_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $user_role = $_POST['role'];
    mysqli_query($conn, "UPDATE users SET name='$name', email='$email', role='$user_role' WHERE id='$id'");
    header("Location: dashboard.php?msg=User+Updated");
    exit();
}
// DOWNLOAD SAMPLE CSV
if(isset($_GET['download_sample']) && $role == 'admin'){
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sample_students.csv"');
    echo "name,email,password,role\n";
    echo "Rahul Kumar,rahul@portal.com,12345,student\n";
    echo "Priya Sharma,priya@portal.com,12345,student\n";
    exit();
}

// BULK UPLOAD STUDENTS
if(isset($_POST['bulk_upload']) && $role == 'admin'){
    if($_FILES['csv_file']['name']){
        $filename = $_FILES['csv_file']['tmp_name'];
        $file = fopen($filename, "r");
        $count = 0;
        fgetcsv($file); // Skip header row

        while(($row = fgetcsv($file, 1000, ","))!== FALSE){
            if(count($row) == 4){
                $name = mysqli_real_escape_string($conn, $row[0]);
                $email = mysqli_real_escape_string($conn, $row[1]);
                $password = $row[2];
                $user_role = mysqli_real_escape_string($conn, $row[3]);

                // Duplicate check
                $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
                if(mysqli_num_rows($check) == 0){
                    mysqli_query($conn, "INSERT INTO users (name, email, password, role) VALUES ('$name', '$email', '$password', '$user_role')");
                    $count++;
                }
            }
        }
        fclose($file);
        header("Location: dashboard.php?msg=".$count."+Students+Uploaded+Successfully");
        exit();
    }
}

// EDIT USER DATA FETCH
$edit_user = null;
if(isset($_GET['edit_user']) && $role == 'admin'){
    $id = $_GET['edit_user'];
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id='$id'");
    $edit_user = mysqli_fetch_assoc($result);
}

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
    $stmt->bind_param("isi", $marks, $feedback, $sub_id);
    $stmt->execute();
    $success = "Assignment graded successfully";
    echo "<meta http-equiv='refresh' content='0'>";
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
            <a href="profile.php" class="btn btn-light btn-sm me-2"><i class="fas fa-user"></i> My Profile</a>
            <a href="logout.php" class="btn btn-light btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
     <div class="text-center mb-4">
    <img src="logo.png" alt="OASP Logo" style="height:80px; width:80px; border-radius:50%; box-shadow:0 4px 8px rgba(0,0,0,0.2); border:3px solid #0d6efd;">
</div>
    <?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
    
    <!-- ADMIN PANEL -->
<?php if($role == 'admin'): ?>
<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header bg-dark text-white"><i class="fas fa-users"></i> Manage Users</div>
            <div class="card-body">
                <?php if(isset($_GET['msg'])) echo "<div class='alert alert-success'>".str_replace('+',' ',$_GET['msg'])."</div>"; ?>
                
                <?php if(!isset($_GET['edit_user'])){ ?>
                <div class="mb-3">
    <a href="add_users.php" class="btn btn-success">
        <i class="fas fa-user-plus"></i> Add New Users
    </a>
</div>
                <?php } ?>

                <?php if(isset($_GET['edit_user']) && $edit_user){ ?>
                <form method="POST" class="mb-3">
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                    <div class="row g-2">
                        <div class="col-md-3"><input type="text" name="name" class="form-control" value="<?php echo $edit_user['name']; ?>" required></div>
                        <div class="col-md-3"><input type="email" name="email" class="form-control" value="<?php echo $edit_user['email']; ?>" required></div>
                        <div class="col-md-3">
                            <select name="role" class="form-control" required>
                                <option value="student" <?php if($edit_user['role']=='student') echo 'selected'; ?>>Student</option>
                                <option value="teacher" <?php if($edit_user['role']=='teacher') echo 'selected'; ?>>Teacher</option>
                                <option value="admin" <?php if($edit_user['role']=='admin') echo 'selected'; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" name="update_user" class="btn btn-warning">Update</button>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
                <?php } ?>
                <!-- YAHAN SEARCH FORM PASTE KAR  -->
<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control" 
               placeholder="Search Name or Email" 
               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
    </div>
    <div class="col-md-3">
        <select name="role" class="form-select">
            <option value="">All Roles</option>
            <option value="admin" <?php if(($_GET['role'] ?? '')=='admin') echo 'selected'; ?>>Admin</option>
            <option value="student" <?php if(($_GET['role'] ?? '')=='student') echo 'selected'; ?>>Student</option>
            <option value="teacher" <?php if(($_GET['role'] ?? '')=='teacher') echo 'selected'; ?>>Teacher</option>
        </select>
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="dashboard.php" class="btn btn-secondary">Reset</a>
    </div>
</form>

                <table class="table table-sm table-striped">
                    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php
                    // SEARCH LOGIC - Line 265 ko replace kar
$where = "WHERE 1=1";
if(!empty($_GET['search'])){
    $s = mysqli_real_escape_string($conn, $_GET['search']);
    $where .= " AND (name LIKE '%$s%' OR email LIKE '%$s%')";
}
if(!empty($_GET['role'])){
    $r = mysqli_real_escape_string($conn, $_GET['role']);
    $where .= " AND role='$r'";
}
$users = mysqli_query($conn, "SELECT * FROM users $where ORDER BY id DESC");
                    while($user = mysqli_fetch_assoc($users)){
                        echo "<tr>";
                        echo "<td>".$user['name']."</td>";
                        echo "<td>".$user['email']."</td>";
                        echo "<td>".ucfirst($user['role'])."</td>";
                        echo "<td>
                            <a href='dashboard.php?edit_user=".$user['id']."' class='btn btn-sm btn-warning'>Edit</a>
                            <a href='dashboard.php?delete_user=".$user['id']."' class='btn btn-sm btn-danger' onclick='return confirm(\"Delete?\")'>Delete</a>
                        </td>";
                        echo "</tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

    <!-- TEACHER PANEL -->
<?php if($role == 'teacher'): ?>
    <!-- TOTAL STUDENTS CARD - TEACHER -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title">
                        <?php 
                            $student_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='student'");
                            $count = mysqli_fetch_assoc($student_count);
                            echo $count['total'];
                        ?>
                        </h4>
                        <p class="card-text">Total Students</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user-graduate fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="card-title">
                        <?php 
                            $assignment_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM assignments WHERE teacher_id=".$_SESSION['user_id']);
                            $count = mysqli_fetch_assoc($assignment_count);
                            echo $count['total'];
                        ?>
                        </h4>
                        <p class="card-text">My Assignments</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-book fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- STUDENTS CARD - TEACHER -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card p-3 text-center bg-dark text-white">
            <?php 
            $total_students_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role='student'");
            $total_students = mysqli_fetch_assoc($total_students_query)['total'];
            ?>
            <h3><?php echo $total_students; ?></h3>
            <p>Total Students</p>
            <a href="students_list.php" class="btn btn-info btn-sm mt-2">
                <i class="fas fa-users"></i> View All Students
            </a>
        </div>
    </div>
</div>
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
<div class="card mb-4">
    <div class="card-header bg-warning text-dark"><i class="fas fa-list"></i> My Created Assignments</div>
    <div class="card-body">
        <?php
           $my_assignments = mysqli_query($conn, "SELECT a.*, u.name AS teacher_name FROM assignments a LEFT JOIN users u ON a.teacher_id = u.id WHERE a.teacher_id = $user_id ORDER BY a.due_date DESC");
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
                
                $sub_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM submissions WHERE assignment_id = {$assign['id']}"));
                
                    echo "<tr>
                        <td><b>{$assign['title']}</b><br>
                        <small class='text-muted'>{$assign['description']}</small><br>
                        <small style='color:#94a3b8;'><b>Teacher:</b> " . htmlspecialchars($assign['teacher_name'] ?? 'Admin') . "</small></td>
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
            $assignments = mysqli_query($conn, "SELECT a.*, u.name AS teacher_name FROM assignments a LEFT JOIN users u ON a.teacher_id = u.id ORDER BY a.due_date ASC");
            while($a = mysqli_fetch_assoc($assignments)){
                $check_sub = mysqli_query($conn, "SELECT * FROM submissions WHERE assignment_id = {$a['id']} AND student_id = $user_id");
                $submitted = mysqli_num_rows($check_sub) > 0;
                $is_overdue = strtotime(date("Y-m-d H:i:s")) > strtotime($a['due_date']);
                
                echo "<div class='border p-3 mb-3 rounded'><h5>{$a['title']}</h5><p>{$a['description']}</p>";
                    echo "<p style='color:#94a3b8; font-size:14px; margin:5px 0;'><b>Teacher:</b> " . htmlspecialchars($a['teacher_name'] ?? 'Admin') . "</p>";
                echo "<p><b>Due Date:</b> {$a['due_date']}";
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
    
    if(localStorage.getItem('darkMode') === 'enabled') {
        body.classList.add('dark-mode');
        toggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
    }
    
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






