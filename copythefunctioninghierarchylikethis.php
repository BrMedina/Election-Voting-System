<?php
require "dbconnection.php";
session_start();

if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Administrator') {
    if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Librarian') {
        header('Location: librariandashboard.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

// Handle logout
if(isset($_GET['logout']) && $_GET['logout'] == '1') {
    writeLog($conn, 'Logged Out');
    session_destroy();
    header('Location: login.php');
    exit();
}

// Store which section to show after form submission
$activeSection = 'homeSection';
if(isset($_GET['section'])) {
    $section = $_GET['section'];
    if ($section === 'usersSection' || $section === 'booksSection' || $section === 'logsSection' || $section === 'homeSection') {
        $activeSection = $section;
    }
}

function writeLog($conn, $action) {
    $userId = isset($_SESSION['id']) ? intval($_SESSION['id']) : 'NULL';
    $safeAction = $conn->real_escape_string($action);
    $logSql = "INSERT INTO logs_table (user_id, action, datetime) VALUES ($userId, '$safeAction', NOW())";
    $conn->query($logSql);
}

// Handle Delete User
if(isset($_POST['deleteUser'])) {
    $user_id = intval($_POST['user_id']);
    
    // Delete from member_table first, then user_table
    $deleteMemberSql = "DELETE FROM member_table WHERE member_id = $user_id";
    $conn->query($deleteMemberSql);
    
    $deleteUserSql = "DELETE FROM user_table WHERE user_id = $user_id";
    $conn->query($deleteUserSql);
    
    writeLog($conn, "Deleted User ID: $user_id");
    
    echo "<script>Swal.fire('Success', 'User deleted successfully', 'success');</script>";
    $activeSection = 'usersSection';
}

// Handle Add/Edit User
if(isset($_POST['saveUser'])) {
    $user_id = isset($_POST['user_id']) && $_POST['user_id'] != '' ? intval($_POST['user_id']) : null;
    $fullname = htmlspecialchars($_POST['fullname']);
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'] != '' ? MD5($_POST['password']) : null;
    $contact = htmlspecialchars($_POST['contact']);
    $address = htmlspecialchars($_POST['address']);
    $role = htmlspecialchars($_POST['role']);
    $status = htmlspecialchars($_POST['status']);
    
    if($user_id) {
        // Update existing user
        $updateSql = "UPDATE user_table SET full_name='$fullname', username='$username', email='$email', user_tablecol='$contact', role='$role', status='$status'";
        if($password) {
            $updateSql .= ", password='$password'";
        }
        $updateSql .= " WHERE user_id=$user_id";
        $conn->query($updateSql);
        
        // Update member info
        $updateMemberSql = "UPDATE member_table SET member_name='$fullname', contact_information='$contact', address='$address' WHERE member_id=$user_id";
        $conn->query($updateMemberSql);
        
        echo "<script>Swal.fire('Success', 'User updated successfully', 'success');</script>";
        writeLog($conn, "Updated User ID: $user_id");
    } else {
        // Add new user
        $otp = rand(000000, 999999);
        $insertUserSql = "INSERT INTO user_table (full_name, role, username, password, email, user_tablecol, otp, status) 
        VALUES ('$fullname', '$role', '$username', '$password', '$email', '$contact', '$otp', '$status')";
        
        if($conn->query($insertUserSql)) {
            $user_id = $conn->insert_id;
            
            // Add to member_table
            $insertMemberSql = "INSERT INTO member_table (member_id, member_name, contact_information, address) 
            VALUES ($user_id, '$fullname', '$contact', '$address')";
            $conn->query($insertMemberSql);
            
            echo "<script>Swal.fire('Success', 'User added successfully', 'success');</script>";
            writeLog($conn, "Added User: $fullname");
        }
    }
    $activeSection = 'usersSection';
}

// Handle Delete Book
if(isset($_POST['deleteBook'])) {
    $book_id = intval($_POST['book_id']);
    $conn->query("DELETE FROM borrowing_record_table WHERE book_id = $book_id");
    $conn->query("DELETE FROM book_table WHERE book_id = $book_id");
    echo "<script>Swal.fire('Success', 'Book deleted successfully', 'success');</script>";
    writeLog($conn, "Deleted Book ID: $book_id");
    $activeSection = 'booksSection';
}

// Handle Add/Edit Book
if(isset($_POST['saveBook'])) {
    $book_id = isset($_POST['book_id']) && $_POST['book_id'] != '' ? intval($_POST['book_id']) : null;
    $title = $conn->real_escape_string($_POST['title']);
    $author = $conn->real_escape_string($_POST['author']);
    $isbn = $conn->real_escape_string($_POST['isbn']);
    $genre = $conn->real_escape_string($_POST['genre']);
    $publication_date = $conn->real_escape_string($_POST['publication_date']);
    $availability_status = $conn->real_escape_string($_POST['availability_status']);
    $category_id = trim($_POST['category_id']);
    $categorySql = $category_id === '' ? 'NULL' : intval($category_id);

    if($book_id) {
        $updateBookSql = "UPDATE book_table SET title='$title', author='$author', ISBN='$isbn', genre='$genre', publication_date='$publication_date', availability_status='$availability_status', category_id=$categorySql WHERE book_id=$book_id";
        $conn->query($updateBookSql);
        echo "<script>Swal.fire('Success', 'Book updated successfully', 'success');</script>";
        writeLog($conn, "Updated Book ID: $book_id");
    } else {
        $insertBookSql = "INSERT INTO book_table (title, author, ISBN, genre, publication_date, availability_status, category_id) VALUES ('$title', '$author', '$isbn', '$genre', '$publication_date', '$availability_status', $categorySql)";
        $conn->query($insertBookSql);
        echo "<script>Swal.fire('Success', 'Book added successfully', 'success');</script>";
        writeLog($conn, "Added Book: $title");
    }

    $activeSection = 'booksSection';
}

// Handle Delete Borrowing Record
if(isset($_POST['deleteBorrow'])) {
    $record_id = intval($_POST['record_id']);
    $borrowRes = $conn->query("SELECT book_id, status FROM borrowing_record_table WHERE record_id = $record_id");
    if($borrowRes && $borrowRes->num_rows == 1) {
        $borrowRow = $borrowRes->fetch_assoc();
        $conn->query("DELETE FROM borrowing_record_table WHERE record_id = $record_id");
        if($borrowRow['status'] == 'borrowed') {
            $conn->query("UPDATE book_table SET availability_status = 'Available' WHERE book_id = " . intval($borrowRow['book_id']));
        }
    }
    echo "<script>Swal.fire('Success', 'Borrowed book record deleted successfully', 'success');</script>";
    writeLog($conn, "Deleted Borrow Record ID: $record_id");
    $activeSection = 'booksSection';
}

// Handle Add/Edit Borrowing Record
if(isset($_POST['saveBorrow'])) {
    $record_id = isset($_POST['record_id']) && $_POST['record_id'] != '' ? intval($_POST['record_id']) : null;
    $book_id = intval($_POST['book_id']);
    $member_id = intval($_POST['member_id']);
    $borrow_date = $conn->real_escape_string($_POST['borrow_date']);
    $return_date = $conn->real_escape_string($_POST['return_date']);
    $status = $conn->real_escape_string($_POST['status']);

    if($record_id) {
        $updateBorrowSql = "UPDATE borrowing_record_table SET book_id=$book_id, member_id=$member_id, borrow_date='$borrow_date', return_date='$return_date', status='$status' WHERE record_id=$record_id";
        $conn->query($updateBorrowSql);
    } else {
        $insertBorrowSql = "INSERT INTO borrowing_record_table (book_id, member_id, borrow_date, return_date, status) VALUES ($book_id, $member_id, '$borrow_date', '$return_date', '$status')";
        $conn->query($insertBorrowSql);
    }

    if($status === 'borrowed') {
        $conn->query("UPDATE book_table SET availability_status = 'Borrowed' WHERE book_id = $book_id");
    } elseif($status === 'returned') {
        $conn->query("UPDATE book_table SET availability_status = 'Available' WHERE book_id = $book_id");
    }

    echo "<script>Swal.fire('Success', 'Borrow record saved successfully', 'success');</script>";
    writeLog($conn, $record_id ? "Updated Borrow Record ID: $record_id" : "Added Borrow Record for Book ID: $book_id");
    $activeSection = 'booksSection';
}

// Search functionality
$searchInput = '';
$displaySQL = "SELECT u.user_id, u.full_name, u.username, u.email, u.user_tablecol, u.role, u.status, m.contact_information, m.address FROM user_table u LEFT JOIN member_table m ON u.user_id = m.member_id";

if(isset($_POST['btnSearch'])) {
    $searchInput = $conn->real_escape_string($_POST['searchInput']);
    if ($searchInput != '') {
        $displaySQL = "SELECT u.user_id, u.full_name, u.username, u.email, u.user_tablecol, u.role, u.status, m.contact_information, m.address FROM user_table u LEFT JOIN member_table m ON u.user_id = m.member_id WHERE (
            u.user_id LIKE '%$searchInput%' OR
            u.full_name LIKE '%$searchInput%' OR
            u.username LIKE '%$searchInput%' OR
            u.email LIKE '%$searchInput%' OR
            u.user_tablecol LIKE '%$searchInput%' OR
            m.address LIKE '%$searchInput%'
        )";
        $activeSection = 'usersSection';
    }
}

$result = $conn->query($displaySQL);

// Books search
$bookSearchInput = '';
$bookDisplaySQL = "SELECT * FROM book_table";

if(isset($_POST['btnBookSearch'])) {
    $bookSearchInput = $conn->real_escape_string($_POST['bookSearchInput']);
    if ($bookSearchInput != '') {
        $bookDisplaySQL = "SELECT * FROM book_table WHERE (
            book_id LIKE '%$bookSearchInput%' OR
            title LIKE '%$bookSearchInput%' OR
            author LIKE '%$bookSearchInput%' OR
            ISBN LIKE '%$bookSearchInput%' OR
            genre LIKE '%$bookSearchInput%' OR
            publication_date LIKE '%$bookSearchInput%' OR
            availability_status LIKE '%$bookSearchInput%'
        )";
        $activeSection = 'booksSection';
    }
}

$bookResult = $conn->query($bookDisplaySQL);

// Borrow records search
$borrowSearchInput = '';
$borrowDisplaySQL = "SELECT br.record_id, br.book_id, br.member_id, br.borrow_date, br.return_date, br.status, b.title AS book_title, m.member_name 
FROM borrowing_record_table br 
LEFT JOIN book_table b ON br.book_id = b.book_id 
LEFT JOIN member_table m ON br.member_id = m.member_id";

if(isset($_POST['btnBorrowSearch'])) {
    $borrowSearchInput = $conn->real_escape_string($_POST['borrowSearchInput']);
    if ($borrowSearchInput != '') {
        $borrowDisplaySQL = "SELECT br.record_id, br.book_id, br.member_id, br.borrow_date, br.return_date, br.status, b.title AS book_title, m.member_name 
        FROM borrowing_record_table br 
        LEFT JOIN book_table b ON br.book_id = b.book_id 
        LEFT JOIN member_table m ON br.member_id = m.member_id 
        WHERE (
            br.record_id LIKE '%$borrowSearchInput%' OR
            b.title LIKE '%$borrowSearchInput%' OR
            m.member_name LIKE '%$borrowSearchInput%' OR
            br.status LIKE '%$borrowSearchInput%'
        )";
        $activeSection = 'booksSection';
    }
}

$borrowResult = $conn->query($borrowDisplaySQL);

$bookOptionsRes = $conn->query("SELECT book_id, title FROM book_table ORDER BY title");
$memberOptionsRes = $conn->query("SELECT member_id, member_name FROM member_table ORDER BY member_name");

$logsDisplaySQL = "SELECT l.logs_id, l.user_id, u.full_name, l.action, l.datetime 
FROM logs_table l 
LEFT JOIN user_table u ON l.user_id = u.user_id 
ORDER BY l.datetime DESC";
$logsResult = $conn->query($logsDisplaySQL);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <title>Admin Dashboard</title>
</head>
<body class="has-sidebar has-topbar" style="background-color: #f7f7f7;">
<!-- TOP NAVBAR -->
<nav class="navbar navbar-expand-lg top-navbar shadow-sm">
    <div class="container-fluid">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-light shadow-sm sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle navigation">
        <i class="bi bi-list"></i>
        </button>
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
        <img src="./assets/headerLogo.png" alt="Logo" width="220" height="45" class="d-inline-block align-text-top" draggable="false">
        </a>
    </div>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar" aria-controls="topNavbar" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    </div>
</nav>

<!-- NAVBAR -->
<nav class="navbar shadow-sm sidebar-navbar sidebar-collapsed" id="sidebar">
    <div class="container-fluid d-flex flex-column align-items-start">
    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        
    <h2>Welcome, <?php echo $_SESSION['user_type']; ?></h2>
        <li class="nav-item">
            <a class="nav-link" href="admindashboard.php?section=logsSection"><i class="bi bi-clock-history me-2"></i>Check Logs</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="admindashboard.php?section=usersSection"><i class="bi bi-people me-2"></i>Modify Users</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="admindashboard.php?section=booksSection"><i class="bi bi-book me-2"></i>Modify Books</a>
        </li>
        <hr>
        <li class="nav-item">
            <a class="nav-link" href="#"><i class="bi bi-gear me-2"></i>Settings</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="admindashboard.php?logout=1"><i class="bi bi-box-arrow-left me-2"></i>Logout</a>
        </li>
        </ul>
    </div>
</nav>
<!-- NAVBAR -->

<!-- MAIN SECTION -->
<main id="main" class="bg-secondary-subtle p-5 d-flex flex-column gap-5">
    <!-- HOME SECTION -->
    <div id="homeSection" class="p-5 shadow-sm rounded-4 bg-white" <?php echo $activeSection === 'homeSection' ? '' : 'style="display:none;"'; ?>>
        <h2>Welcome to Admin Dashboard</h2>
        <p class="mt-3">Select an option from the sidebar to get started.</p>
    </div>

    <!-- USERS MANAGEMENT SECTION -->
    <div id="usersSection" class="p-5 shadow-sm rounded-4 bg-white" <?php echo $activeSection === 'usersSection' ? '' : 'style="display:none;"'; ?>>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Users</h2>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-plus-circle me-2"></i>Add User
            </button>
        </div>
        <form action="admindashboard.php?section=usersSection" method="POST" class="mb-4">
            <div class="row g-3">
                <div class="col-md-9">
                    <input type="search" name="searchInput" placeholder="Search by ID, Name, Username, Email, Phone, or Address" class="form-control" value="<?php echo htmlspecialchars($searchInput); ?>">
                </div>
                <div class="col-md-3">
                    <input type="submit" name="btnSearch" value="Search" class="btn btn-primary w-100">
                </div>
            </div>
        </form>
        <?php if($result && $result->num_rows > 0) { ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $user) { ?>
                            <tr>
                                <td><?php echo ($user['user_id']); ?></td>
                                <td><?php echo ($user['full_name']); ?></td>
                                <td><?php echo ($user['username']); ?></td>
                                <td><?php echo ($user['email']); ?></td>
                                <td><?php echo ($user['user_tablecol']); ?></td>
                                <td><?php echo ($user['address'] ?? 'N/A'); ?></td>
                                <td><span class='badge bg-info'><?php echo ($user['role']); ?></span></td>
                                <td><span class='badge <?php echo $user['status'] == 'Active' ? 'bg-success' : 'bg-warning'; ?>'><?php echo htmlspecialchars($user['status']); ?></span></td>
                                <td>
                                    <button class='btn btn-sm btn-warning me-2' data-bs-toggle='modal' data-bs-target='#editUserModal' onclick='editUser(<?php echo json_encode($user); ?>)'>
                                        <i class='bi bi-pencil'></i> Edit
                                    </button>
                                    <button class='btn btn-sm btn-danger' onclick='deleteUser(<?php echo intval($user['user_id']); ?>)'>
                                        <i class='bi bi-trash'></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class='alert alert-info'>No users found.</div>
        <?php } ?>
    </div>

    <!-- BOOKS MANAGEMENT SECTION -->
    <div id="booksSection" class="p-5 shadow-sm rounded-4 bg-white" <?php echo $activeSection === 'booksSection' ? '' : 'style="display:none;"'; ?>>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Modify Books</h2>
            <div class="d-flex gap-2">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBookModal">
                    <i class="bi bi-plus-circle me-2"></i>Add Book
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBorrowModal">
                    <i class="bi bi-journal-plus me-2"></i>Add Borrowed Book
                </button>
            </div>
        </div>

        <form action="admindashboard.php?section=booksSection" method="POST" class="mb-4">
            <div class="row g-3">
                <div class="col-md-9">
                    <input type="search" name="bookSearchInput" placeholder="Search by ID, title, author, ISBN, genre, availability" class="form-control" value="<?php echo htmlspecialchars($bookSearchInput); ?>">
                </div>
                <div class="col-md-3">
                    <input type="submit" name="btnBookSearch" value="Search Books" class="btn btn-primary w-100">
                </div>
            </div>
        </form>

        <div class="table-responsive mb-5">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Genre</th>
                        <th>Publication Date</th>
                        <th>Availability</th>
                        <th>Category ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($bookResult && $bookResult->num_rows > 0) { foreach($bookResult as $book) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($book['book_id']); ?></td>
                            <td><?php echo htmlspecialchars($book['title']); ?></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo htmlspecialchars($book['ISBN']); ?></td>
                            <td><?php echo htmlspecialchars($book['genre']); ?></td>
                            <td><?php echo htmlspecialchars($book['publication_date']); ?></td>
                            <td><?php echo htmlspecialchars($book['availability_status']); ?></td>
                            <td><?php echo htmlspecialchars($book['category_id']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning me-2" data-bs-toggle="modal" data-bs-target="#editBookModal" onclick='editBook(<?php echo json_encode($book); ?>)'>
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteBook(<?php echo intval($book['book_id']); ?>)">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php } } else { ?>
                        <tr><td colspan="9" class="text-center">No books found.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <h2>Borrowed Books</h2>
        <form action="admindashboard.php?section=booksSection" method="POST" class="mb-4">
            <div class="row g-3">
                <div class="col-md-9">
                    <input type="search" name="borrowSearchInput" placeholder="Search borrowed books by record ID, book title, member name, or status" class="form-control" value="<?php echo htmlspecialchars($borrowSearchInput); ?>">
                </div>
                <div class="col-md-3">
                    <input type="submit" name="btnBorrowSearch" value="Search Borrowed Books" class="btn btn-primary w-100">
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Record ID</th>
                        <th>Book</th>
                        <th>Member</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($borrowResult && $borrowResult->num_rows > 0) { foreach($borrowResult as $borrow) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($borrow['record_id']); ?></td>
                            <td><?php echo htmlspecialchars($borrow['book_title']); ?></td>
                            <td><?php echo htmlspecialchars($borrow['member_name']); ?></td>
                            <td><?php echo htmlspecialchars($borrow['borrow_date']); ?></td>
                            <td><?php echo htmlspecialchars($borrow['return_date']); ?></td>
                            <td><?php echo htmlspecialchars($borrow['status']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning me-2" data-bs-toggle="modal" data-bs-target="#editBorrowModal" onclick='editBorrow(<?php echo json_encode($borrow); ?>)'>
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteBorrow(<?php echo intval($borrow['record_id']); ?>)">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php } } else { ?>
                        <tr><td colspan="7" class="text-center">No borrowed book records found.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- LOGS SECTION -->
    <div id="logsSection" class="p-5 shadow-sm rounded-4 bg-white" <?php echo $activeSection === 'logsSection' ? '' : 'style="display:none;"'; ?>>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Activity Logs</h2>
            <span class="text-muted">Recent dashboard actions</span>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Log ID</th>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Action</th>
                        <th>Date/Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($logsResult && $logsResult->num_rows > 0) { foreach($logsResult as $log) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['logs_id']); ?></td>
                            <td><?php echo htmlspecialchars($log['user_id'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($log['full_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td><?php echo htmlspecialchars($log['datetime']); ?></td>
                        </tr>
                    <?php } } else { ?>
                        <tr><td colspan="5" class="text-center">No logs found.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="admindashboard.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="fullname" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="contact" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-control" required>
                            <option value="">Select Role</option>
                            <option value="Administrator">Administrator</option>
                            <option value="Librarian">Librarian</option>
                            <option value="Borrower">Borrower</option>
                            <option value="member">Member</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" required>
                            <option value="Active">Active</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="saveUser" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="admindashboard.php">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="fullname" id="editFullname" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="editUsername" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="editEmail" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (leave blank to keep current)</label>
                        <input type="password" name="password" id="editPassword" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="contact" id="editContact" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" id="editAddress" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" id="editRole" class="form-control" required>
                            <option value="">Select Role</option>
                            <option value="Administrator">Administrator</option>
                            <option value="Librarian">Librarian</option>
                            <option value="Borrower">Borrower</option>
                            <option value="member">Member</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="editStatus" class="form-control" required>
                            <option value="Active">Active</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="saveUser" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Book Modal -->
<div class="modal fade" id="addBookModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="admindashboard.php?section=booksSection">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Author</label>
                        <input type="text" name="author" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ISBN</label>
                        <input type="text" name="isbn" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Genre</label>
                        <input type="text" name="genre" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Publication Date</label>
                        <input type="date" name="publication_date" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Availability</label>
                        <select name="availability_status" class="form-control">
                            <option value="Available">Available</option>
                            <option value="Borrowed">Borrowed</option>
                            <option value="Reserved">Reserved</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category ID</label>
                        <input type="number" name="category_id" class="form-control" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="saveBook" class="btn btn-primary">Save Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Book Modal -->
<div class="modal fade" id="editBookModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="admindashboard.php?section=booksSection">
                <div class="modal-body">
                    <input type="hidden" name="book_id" id="editBookId">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" id="editBookTitle" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Author</label>
                        <input type="text" name="author" id="editBookAuthor" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ISBN</label>
                        <input type="text" name="isbn" id="editBookISBN" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Genre</label>
                        <input type="text" name="genre" id="editBookGenre" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Publication Date</label>
                        <input type="date" name="publication_date" id="editBookPublicationDate" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Availability</label>
                        <select name="availability_status" id="editBookAvailability" class="form-control">
                            <option value="Available">Available</option>
                            <option value="Borrowed">Borrowed</option>
                            <option value="Reserved">Reserved</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category ID</label>
                        <input type="number" name="category_id" id="editBookCategory" class="form-control" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="saveBook" class="btn btn-primary">Update Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Borrowed Record Modal -->
<div class="modal fade" id="addBorrowModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Borrowed Book Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="admindashboard.php?section=booksSection">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Book</label>
                        <select name="book_id" class="form-control" required>
                            <option value="">Select Book</option>
                            <?php if($bookOptionsRes && $bookOptionsRes->num_rows > 0) { foreach($bookOptionsRes as $bookOption) { ?>
                                <option value="<?php echo intval($bookOption['book_id']); ?>"><?php echo htmlspecialchars($bookOption['title']); ?></option>
                            <?php } } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Member</label>
                        <select name="member_id" class="form-control" required>
                            <option value="">Select Member</option>
                            <?php if($memberOptionsRes && $memberOptionsRes->num_rows > 0) { foreach($memberOptionsRes as $memberOption) { ?>
                                <option value="<?php echo intval($memberOption['member_id']); ?>"><?php echo htmlspecialchars($memberOption['member_name']); ?></option>
                            <?php } } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Borrow Date</label>
                        <input type="date" name="borrow_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Return Date</label>
                        <input type="date" name="return_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" required>
                            <option value="borrowed">Borrowed</option>
                            <option value="returned">Returned</option>
                            <option value="reserved">Reserved</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="saveBorrow" class="btn btn-primary">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Borrowed Record Modal -->
<div class="modal fade" id="editBorrowModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Borrowed Book Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="admindashboard.php?section=booksSection">
                <div class="modal-body">
                    <input type="hidden" name="record_id" id="editBorrowRecordId">
                    <div class="mb-3">
                        <label class="form-label">Book</label>
                        <select name="book_id" id="editBorrowBookId" class="form-control" required>
                            <option value="">Select Book</option>
                            <?php if($bookOptionsRes && $bookOptionsRes->num_rows > 0) { foreach($bookOptionsRes as $bookOption) { ?>
                                <option value="<?php echo intval($bookOption['book_id']); ?>"><?php echo htmlspecialchars($bookOption['title']); ?></option>
                            <?php } } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Member</label>
                        <select name="member_id" id="editBorrowMemberId" class="form-control" required>
                            <option value="">Select Member</option>
                            <?php if($memberOptionsRes && $memberOptionsRes->num_rows > 0) { foreach($memberOptionsRes as $memberOption) { ?>
                                <option value="<?php echo intval($memberOption['member_id']); ?>"><?php echo htmlspecialchars($memberOption['member_name']); ?></option>
                            <?php } } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Borrow Date</label>
                        <input type="date" name="borrow_date" id="editBorrowDate" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Return Date</label>
                        <input type="date" name="return_date" id="editBorrowReturnDate" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="editBorrowStatus" class="form-control" required>
                            <option value="borrowed">Borrowed</option>
                            <option value="returned">Returned</option>
                            <option value="reserved">Reserved</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="saveBorrow" class="btn btn-primary">Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const body = document.body;
const desktopQuery = window.matchMedia('(min-width: 992px)');

function openSidebar() {
    sidebar.classList.remove('sidebar-collapsed');
    sidebar.classList.add('sidebar-open');
    body.classList.add('sidebar-open');
}

function closeSidebar() {
    sidebar.classList.add('sidebar-collapsed');
    sidebar.classList.remove('sidebar-open');
    body.classList.remove('sidebar-open');
}

function syncSidebarWithViewport() {
    if (desktopQuery.matches) {
        openSidebar();
    } else {
        closeSidebar();
    }
}

sidebarToggle.addEventListener('click', () => {
    const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
    if (isCollapsed) {
        openSidebar();
    } else {
        closeSidebar();
    }
});

desktopQuery.addEventListener('change', syncSidebarWithViewport);
syncSidebarWithViewport();

// Edit User Function
function editUser(user) {
    document.getElementById('editUserId').value = user.user_id;
    document.getElementById('editFullname').value = user.full_name;
    document.getElementById('editUsername').value = user.username;
    document.getElementById('editEmail').value = user.email;
    document.getElementById('editContact').value = user.user_tablecol;
    document.getElementById('editAddress').value = user.address || '';
    document.getElementById('editRole').value = user.role;
    document.getElementById('editStatus').value = user.status;
}

function editBook(book) {
    document.getElementById('editBookId').value = book.book_id;
    document.getElementById('editBookTitle').value = book.title || '';
    document.getElementById('editBookAuthor').value = book.author || '';
    document.getElementById('editBookISBN').value = book.ISBN || '';
    document.getElementById('editBookGenre').value = book.genre || '';
    document.getElementById('editBookPublicationDate').value = book.publication_date || '';
    document.getElementById('editBookAvailability').value = book.availability_status || 'Available';
    document.getElementById('editBookCategory').value = book.category_id || '';
}

function editBorrow(borrow) {
    document.getElementById('editBorrowRecordId').value = borrow.record_id;
    document.getElementById('editBorrowBookId').value = borrow.book_id;
    document.getElementById('editBorrowMemberId').value = borrow.member_id;
    document.getElementById('editBorrowDate').value = borrow.borrow_date || '';
    document.getElementById('editBorrowReturnDate').value = borrow.return_date || '';
    document.getElementById('editBorrowStatus').value = borrow.status || 'borrowed';
}

// Delete User Function
function deleteUser(userId) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'This user will be permanently deleted!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'admindashboard.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'user_id';
            input.value = userId;
            
            const submitInput = document.createElement('input');
            submitInput.type = 'hidden';
            submitInput.name = 'deleteUser';
            submitInput.value = '1';
            
            form.appendChild(input);
            form.appendChild(submitInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function deleteBook(bookId) {
    Swal.fire({
        title: 'Delete book?',
        text: 'This will also remove related borrowing records.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'admindashboard.php?section=booksSection';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'book_id';
            input.value = bookId;

            const submitInput = document.createElement('input');
            submitInput.type = 'hidden';
            submitInput.name = 'deleteBook';
            submitInput.value = '1';

            form.appendChild(input);
            form.appendChild(submitInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function deleteBorrow(recordId) {
    Swal.fire({
        title: 'Delete borrowed book record?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'admindashboard.php?section=booksSection';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'record_id';
            input.value = recordId;

            const submitInput = document.createElement('input');
            submitInput.type = 'hidden';
            submitInput.name = 'deleteBorrow';
            submitInput.value = '1';

            form.appendChild(input);
            form.appendChild(submitInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
</body>
</html>