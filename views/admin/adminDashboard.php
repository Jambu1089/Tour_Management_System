<?php
session_start();
if (!isset($_SESSION["userId"]) || $_SESSION["role"] !== "Admin") {
    header("Location: ../login.php");
    exit();
}

require_once "../../config/dbConnect.php";
require_once "../../models/usersModel.php";
$conn = dbConnection();
$adminId = $_SESSION["userId"];

@mysqli_query($conn, "ALTER TABLE `DESTINATION` MODIFY `Image` TEXT");

$view = $_GET['view'] ?? 'dashboard';

$msg = "";
$errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "toggle_user") {
        $uId = intval($_POST["target_user_id"]);
        $newStat = $_POST["new_status"];
        mysqli_query($conn, "UPDATE `USER` SET `Status` = '$newStat' WHERE `ID` = $uId");
        $msg = "User status updated to $newStat!";
        $view = 'users';
    } elseif ($action === "delete_user") {
        $uId = intval($_POST["target_user_id"]);
        if ($uId !== $adminId) {
            mysqli_query($conn, "DELETE FROM `USER` WHERE `ID` = $uId");
            $msg = "User account removed successfully!";
        } else {
            $errorMsg = "Cannot delete the active administrator account.";
        }
        $view = 'users';
    } elseif ($action === "add_user") {
        $name  = trim($_POST["name"]);
        $email = trim($_POST["email"]);
        $pass  = trim($_POST["password"]);
        $role  = $_POST["role"];

        if (registerUser($name, $email, $pass, $role)) {
            $msg = "New user account created successfully!";
        } else {
            $errorMsg = "Registration failed. Email already exists!";
        }
        $view = 'users';
    } elseif ($action === "add_category") {
        $name = trim($_POST["name"]);
        $desc = trim($_POST["description"]);
        $stmt = mysqli_prepare($conn, "INSERT INTO `CATEGORY` (`Name`, `Description`) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, 'ss', $name, $desc);
        mysqli_stmt_execute($stmt);
        $msg = "Category added successfully!";
        $view = 'categories';
    } elseif ($action === "delete_category") {
        $cId = intval($_POST["category_id"]);
        mysqli_query($conn, "DELETE FROM `CATEGORY` WHERE `ID` = $cId");
        $msg = "Category deleted successfully!";
        $view = 'categories';
    } elseif ($action === "add_destination") {
        $catId = intval($_POST["category_id"]);
        $name  = trim($_POST["name"]);
        $loc   = trim($_POST["location"]);

        $savedFileName = "default_destination.jpg";
        $uploadDirectory = "../../assets/img/";

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        if (isset($_FILES["dest_image"]) && $_FILES["dest_image"]["error"] === UPLOAD_ERR_OK) {
            $originalName = basename($_FILES["dest_image"]["name"]);
            $cleanFileName = time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "", $originalName);
            $targetFilePath = $uploadDirectory . $cleanFileName;

            if (move_uploaded_file($_FILES["dest_image"]["tmp_name"], $targetFilePath)) {
                $savedFileName = $cleanFileName;
            } else {
                $errorMsg = "Failed to save the uploaded image.";
            }
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO `DESTINATION` (`Category_ID`, `Name`, `Location`, `Image`) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'isss', $catId, $name, $loc, $savedFileName);
        mysqli_stmt_execute($stmt);
        $msg = "Destination added successfully!";
        $view = 'destinations';
    } elseif ($action === "edit_destination") {
        $dId   = intval($_POST["destination_id"]);
        $catId = intval($_POST["category_id"]);
        $name  = trim($_POST["name"]);
        $loc   = trim($_POST["location"]);

        $uploadDirectory = "../../assets/img/";
        $hasNewImg = isset($_FILES["dest_image"]) && $_FILES["dest_image"]["error"] === UPLOAD_ERR_OK;

        if ($hasNewImg) {
            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0777, true);
            }
            $originalName = basename($_FILES["dest_image"]["name"]);
            $cleanFileName = time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "", $originalName);
            $targetFilePath = $uploadDirectory . $cleanFileName;

            if (move_uploaded_file($_FILES["dest_image"]["tmp_name"], $targetFilePath)) {
                $stmt = mysqli_prepare($conn, "UPDATE `DESTINATION` SET `Category_ID` = ?, `Name` = ?, `Location` = ?, `Image` = ? WHERE `ID` = ?");
                mysqli_stmt_bind_param($stmt, 'isssi', $catId, $name, $loc, $cleanFileName, $dId);
                mysqli_stmt_execute($stmt);
                $msg = "Destination and image updated successfully!";
            } else {
                $errorMsg = "Failed to upload new image.";
            }
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE `DESTINATION` SET `Category_ID` = ?, `Name` = ?, `Location` = ? WHERE `ID` = ?");
            mysqli_stmt_bind_param($stmt, 'issi', $catId, $name, $loc, $dId);
            mysqli_stmt_execute($stmt);
            $msg = "Destination details updated successfully!";
        }
        $view = 'destinations';
    } elseif ($action === "delete_destination") {
        $dId = intval($_POST["destination_id"]);
        mysqli_query($conn, "DELETE FROM `DESTINATION` WHERE `ID` = $dId");
        $msg = "Destination deleted successfully!";
        $view = 'destinations';
    } elseif ($action === "update_profile") {
        $name  = trim($_POST["name"]);
        $phone = trim($_POST["phone"]);
        $stmt = mysqli_prepare($conn, "UPDATE `USER` SET `Name` = ?, `Phone` = ? WHERE `ID` = ?");
        mysqli_stmt_bind_param($stmt, 'ssi', $name, $phone, $adminId);
        mysqli_stmt_execute($stmt);
        $_SESSION["name"] = $name;
        $msg = "Admin profile updated successfully!";
        $view = 'profile';
    } elseif ($action === "change_password") {
        $curr = $_POST["currentPassword"];
        $newP = $_POST["newPassword"];
        $conf = $_POST["confirmPassword"];

        if ($newP !== $conf) {
            $errorMsg = "New passwords do not match!";
        } elseif (changeUserPassword($adminId, $curr, $newP)) {
            $msg = "Password changed successfully!";
        } else {
            $errorMsg = "Current password incorrect!";
        }
        $view = 'password';
    }
}

$uCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `USER`"))['c'];
$bCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `BOOKING`"))['c'];
$pCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `TOUR_PACKAGE`"))['c'];
$cCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `CATEGORY`"))['c'];
$dCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `DESTINATION`"))['c'];

$apprCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `BOOKING` WHERE Status='Approved'"))['c'];
$pendCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `BOOKING` WHERE Status='Pending'"))['c'];
$compCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `BOOKING` WHERE Status='Completed'"))['c'];
$rejcCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM `BOOKING` WHERE Status='Rejected'"))['c'] ?? 0;

$pendPct = ($bCount > 0) ? round(($pendCount / $bCount) * 100, 1) : 0;
$apprPct = ($bCount > 0) ? round(($apprCount / $bCount) * 100, 1) : 0;
$compPct = ($bCount > 0) ? round(($compCount / $bCount) * 100, 1) : 0;
$rejcPct = ($bCount > 0) ? round(($rejcCount / $bCount) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/dashboard.css?v=<?php echo time(); ?>">
    <script src="../../assets/js/main.js" defer></script>
</head>
<body>

<div class="dashboard-container">
    <div class="top-nav">
        <h2>Admin Dashboard</h2>
        <div style="display: flex; align-items: center; gap: 8px;">
            <span class="user-greeting">Hi, Admin</span>
        </div>
    </div>

    <div class="main-body">
        <div class="sidebar">
            <ul>
                <li class="<?php echo ($view === 'dashboard') ? 'active' : ''; ?>">
                    <a href="adminDashboard.php?view=dashboard">Dashboard</a>
                </li>
                <li class="<?php echo ($view === 'users') ? 'active' : ''; ?>">
                    <a href="adminDashboard.php?view=users">Users</a>
                </li>
                <li class="<?php echo ($view === 'categories') ? 'active' : ''; ?>">
                    <a href="adminDashboard.php?view=categories">Categories</a>
                </li>
                <li class="<?php echo ($view === 'destinations') ? 'active' : ''; ?>">
                    <a href="adminDashboard.php?view=destinations">Destinations</a>
                </li>
                <li class="<?php echo ($view === 'analytics') ? 'active' : ''; ?>">
                    <a href="adminDashboard.php?view=analytics">Analytics</a>
                </li>
                <li class="<?php echo ($view === 'profile') ? 'active' : ''; ?>">
                    <a href="adminDashboard.php?view=profile">Profile</a>
                </li>
                <li class="<?php echo ($view === 'password') ? 'active' : ''; ?>">
                    <a href="adminDashboard.php?view=password">Change Password</a>
                </li>
                <li>
                    <button id="logoutBtn">Logout</button>
                </li>
            </ul>
        </div>

        <div class="content">
            <?php if (!empty($msg)): ?>
                <div class="success-msg"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMsg)): ?>
                <div class="error-banner"><?php echo htmlspecialchars($errorMsg); ?></div>
            <?php endif; ?>

            <?php if ($view === 'dashboard'): ?>
                <div class="metric-grid">
                    <div class="metric-card">
                        <span>Users</span>
                        <h1 class="color-blue"><?php echo $uCount; ?></h1>
                    </div>
                    <div class="metric-card">
                        <span>Bookings</span>
                        <h1 class="color-green"><?php echo $bCount; ?></h1>
                    </div>
                    <div class="metric-card">
                        <span>Packages</span>
                        <h1 class="color-yellow"><?php echo $pCount; ?></h1>
                    </div>
                    <div class="metric-card">
                        <span>Categories</span>
                        <h1 class="color-green"><?php echo $cCount; ?></h1>
                    </div>
                    <div class="metric-card">
                        <span>Destinations</span>
                        <h1 class="color-red"><?php echo $dCount; ?></h1>
                    </div>
                </div>

                <div class="two-col-layout">
                    <div class="bordered-box">
                        <h3 style="font-size:15px; margin-bottom:12px;">Bookings Overview</h3>
                        <p style="margin-bottom:8px; font-size:13px;">Total Bookings: <strong><?php echo $bCount; ?></strong></p>
                        <p style="margin-bottom:8px; font-size:13px;">Approved: <strong style="color:#2ed573;"><?php echo $apprCount; ?></strong></p>
                        <p style="margin-bottom:8px; font-size:13px;">Pending: <strong style="color:#ffa502;"><?php echo $pendCount; ?></strong></p>
                        <p style="margin-bottom:8px; font-size:13px;">Completed: <strong style="color:#2e66e7;"><?php echo $compCount; ?></strong></p>
                    </div>

                    <div class="bordered-box">
                        <h3 style="font-size:15px; margin-bottom:12px;">Top Destinations</h3>
                        <ol style="font-size:13px; line-height:1.8; margin-left:18px;">
                            <li>Cox's Bazar (200 bookings)</li>
                            <li>Sajek (156 bookings)</li>
                            <li>Sylhet (125 bookings)</li>
                        </ol>

                        <h4 style="font-size:14px; margin-top:15px; margin-bottom:8px;">Bookings by Status</h4>
                        <ul style="list-style:none; font-size:12px; line-height:1.8;">
                            <li><span style="color:#ffa502;">■</span> Pending (<?php echo $pendPct; ?>%)</li>
                            <li><span style="color:#2ed573;">■</span> Approved (<?php echo $apprPct; ?>%)</li>
                            <li><span style="color:#2e66e7;">■</span> Completed (<?php echo $compPct; ?>%)</li>
                            <li><span style="color:#ff4757;">■</span> Rejected (<?php echo $rejcPct; ?>%)</li>
                        </ul>
                    </div>
                </div>

            <?php elseif ($view === 'users'): ?>
                <div class="section-header">
                    <h3>Users Management</h3>
                </div>

                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $uList = mysqli_query($conn, "SELECT * FROM `USER` ORDER BY ID ASC");
                        while ($usr = mysqli_fetch_assoc($uList)):
                            $status = $usr['Status'] ?? 'Active';
                            $badgeClass = ($status === 'Active') ? 'status-approved' : 'status-pending';
                            $newStatus = ($status === 'Active') ? 'Inactive' : 'Active';
                            $btnText = ($status === 'Active') ? 'Deactivate' : 'Activate';
                        ?>
                        <tr>
                            <td><?php echo $usr['ID']; ?></td>
                            <td><strong><?php echo htmlspecialchars($usr['Name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($usr['Email']); ?></td>
                            <td><?php echo htmlspecialchars($usr['Role']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $badgeClass; ?>">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </td>
                            <td>
                                <form action="adminDashboard.php?view=users" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_user">
                                    <input type="hidden" name="target_user_id" value="<?php echo $usr['ID']; ?>">
                                    <input type="hidden" name="new_status" value="<?php echo $newStatus; ?>">
                                    <button type="submit" class="btn-sm" style="border:1px solid #ccc; background:#fff; cursor:pointer; padding:3px 8px; border-radius:3px;">
                                        <?php echo $btnText; ?>
                                    </button>
                                </form>
                                <?php if ($usr['ID'] !== $adminId): ?>
                                <form action="adminDashboard.php?view=users" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="target_user_id" value="<?php echo $usr['ID']; ?>">
                                    <button type="submit" class="btn-sm btn-reject">Delete</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <div class="bordered-box" style="margin-top:20px;">
                    <h4 style="font-size:14px; margin-bottom:12px;">Add New User</h4>
                    <form action="adminDashboard.php?view=users" method="POST">
                        <input type="hidden" name="action" value="add_user">
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="name" required placeholder="Enter full name">
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" required placeholder="Enter email">
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" required placeholder="Set password">
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role" required>
                                    <option value="Tourist">Tourist</option>
                                    <option value="Tour Guide">Tour Guide</option>
                                    <option value="Agency Manager">Agency Manager</option>
                                    <option value="Admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn-cyan" style="margin-top:6px;">Create User</button>
                    </form>
                </div>

            <?php elseif ($view === 'categories'): ?>
                <div class="section-header">
                    <h3>Category Management</h3>
                </div>

                <div class="bordered-box" style="margin-bottom:18px;">
                    <h4 style="font-size:14px; margin-bottom:10px;">Add Travel Category</h4>
                    <form action="adminDashboard.php?view=categories" method="POST" style="display:flex; gap:12px; align-items:flex-end;">
                        <input type="hidden" name="action" value="add_category">
                        <div class="form-group" style="flex:1; margin-bottom:0;">
                            <label>Category Name</label>
                            <input type="text" name="name" placeholder="e.g. Eco-Tour" required>
                        </div>
                        <div class="form-group" style="flex:2; margin-bottom:0;">
                            <label>Description</label>
                            <input type="text" name="description" placeholder="Brief category description" required>
                        </div>
                        <button type="submit" class="btn-cyan">Add Category</button>
                    </form>
                </div>

                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $cats = mysqli_query($conn, "SELECT * FROM `CATEGORY` ORDER BY ID ASC");
                        while ($c = mysqli_fetch_assoc($cats)):
                        ?>
                        <tr>
                            <td><?php echo $c['ID']; ?></td>
                            <td><strong><?php echo htmlspecialchars($c['Name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($c['Description']); ?></td>
                            <td>
                                <form action="adminDashboard.php?view=categories" method="POST" style="display:inline;" onsubmit="return confirm('Delete this category?');">
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="category_id" value="<?php echo $c['ID']; ?>">
                                    <button type="submit" class="btn-sm btn-reject">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php elseif ($view === 'destinations'): 
                $editId = intval($_GET['edit_id'] ?? 0);
                $editDest = null;
                if ($editId > 0) {
                    $eQ = mysqli_query($conn, "SELECT * FROM `DESTINATION` WHERE `ID` = $editId");
                    $editDest = mysqli_fetch_assoc($eQ);
                }
            ?>
                <div class="section-header">
                    <h3>Destination Management</h3>
                </div>

                <div class="bordered-box" style="margin-bottom:18px;">
                    <h4 style="font-size:14px; margin-bottom:10px;">
                        <?php echo $editDest ? 'Edit Destination (ID: ' . $editDest['ID'] . ')' : 'Add Destination'; ?>
                    </h4>
                    <form action="adminDashboard.php?view=destinations" method="POST" enctype="multipart/form-data" style="display:grid; grid-template-columns: 1fr 1fr 1fr 1.5fr auto; gap:10px; align-items:flex-end;">
                        <input type="hidden" name="action" value="<?php echo $editDest ? 'edit_destination' : 'add_destination'; ?>">
                        <?php if ($editDest): ?>
                            <input type="hidden" name="destination_id" value="<?php echo $editDest['ID']; ?>">
                        <?php endif; ?>

                        <div class="form-group" style="margin-bottom:0;">
                            <label>Category</label>
                            <select name="category_id" required>
                                <?php
                                $catsRes = mysqli_query($conn, "SELECT * FROM `CATEGORY`");
                                while ($cr = mysqli_fetch_assoc($catsRes)) {
                                    $sel = ($editDest && $editDest['Category_ID'] == $cr['ID']) ? 'selected' : '';
                                    echo "<option value='{$cr['ID']}' $sel>{$cr['Name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Destination Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($editDest['Name'] ?? ''); ?>" placeholder="e.g. Cox's Bazar" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Location</label>
                            <input type="text" name="location" value="<?php echo htmlspecialchars($editDest['Location'] ?? ''); ?>" placeholder="e.g. Chittagong, Bangladesh" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label><?php echo $editDest ? 'Change Image (Optional)' : 'Upload Image'; ?></label>
                            <input type="file" name="dest_image" accept="image/*" <?php echo $editDest ? '' : 'required'; ?> style="padding: 5px; background: #fff;">
                        </div>
                        
                        <div style="display:flex; gap:6px;">
                            <button type="submit" class="btn-cyan">
                                <?php echo $editDest ? 'Update' : 'Add Destination'; ?>
                            </button>
                            <?php if ($editDest): ?>
                                <a href="adminDashboard.php?view=destinations" class="btn-sm" style="background:#64748b; color:#fff; text-decoration:none; padding:8px 12px; border-radius:3px; display:inline-flex; align-items:center;">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>ID</th>
                            <th>Destination Name</th>
                            <th>Location</th>
                            <th>Category</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $fallbackImg = "https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=400";
                        $dests = mysqli_query($conn, "SELECT d.*, c.Name as CategoryName FROM `DESTINATION` d JOIN `CATEGORY` c ON d.Category_ID = c.ID ORDER BY d.ID ASC");
                        while ($d = mysqli_fetch_assoc($dests)):
                            $rawImg = trim($d['Image'] ?? '');
                            
                            if (preg_match('/^https?:\/\//i', $rawImg)) {
                                $destImg = $rawImg;
                            } elseif (!empty($rawImg)) {
                                $destImg = (strpos($rawImg, '/') !== false) ? $rawImg : "../../assets/img/" . $rawImg;
                            } else {
                                $destImg = $fallbackImg;
                            }
                        ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($destImg); ?>" 
                                     style="width:45px; height:45px; object-fit:cover; border-radius:4px; display:block; background:#e2e8f0;" 
                                     alt="Destination"
                                     onerror="this.onerror=null; this.src='<?php echo $fallbackImg; ?>';">
                            </td>
                            <td><?php echo $d['ID']; ?></td>
                            <td><strong><?php echo htmlspecialchars($d['Name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($d['Location']); ?></td>
                            <td><?php echo htmlspecialchars($d['CategoryName']); ?></td>
                            <td>
                                <div style="display:inline-flex; gap:5px; align-items:center;">
                                    <a href="adminDashboard.php?view=destinations&edit_id=<?php echo $d['ID']; ?>" 
                                       class="btn-sm" 
                                       style="background:#0284c7; color:#fff; text-decoration:none; padding:4px 9px; border-radius:3px; font-weight:500;">
                                       Edit
                                    </a>
                                    <form action="adminDashboard.php?view=destinations" method="POST" style="display:inline;" onsubmit="return confirm('Delete this destination?');">
                                        <input type="hidden" name="action" value="delete_destination">
                                        <input type="hidden" name="destination_id" value="<?php echo $d['ID']; ?>">
                                        <button type="submit" class="btn-sm btn-reject">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php elseif ($view === 'analytics'): ?>
                <div class="section-header">
                    <h3>Analytics & Reports</h3>
                </div>

                <div class="metric-grid">
                    <div class="metric-card">
                        <span>Total Users</span>
                        <h1 class="color-blue"><?php echo $uCount; ?></h1>
                    </div>
                    <div class="metric-card">
                        <span>Total Bookings</span>
                        <h1 class="color-green"><?php echo $bCount; ?></h1>
                    </div>
                    <div class="metric-card">
                        <span>Total Packages</span>
                        <h1 class="color-yellow"><?php echo $pCount; ?></h1>
                    </div>
                </div>

                <div class="bordered-box" style="max-width:550px; margin-top:20px;">
                    <h4 style="margin-bottom:12px;">Package Status Breakdown</h4>
                    <table class="custom-table" style="margin-bottom:0;">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Pending Bookings</td><td><?php echo $pendCount; ?></td></tr>
                            <tr><td>Approved Bookings</td><td><?php echo $apprCount; ?></td></tr>
                            <tr><td>Completed Bookings</td><td><?php echo $compCount; ?></td></tr>
                            <tr><td>Rejected Bookings</td><td><?php echo $rejcCount; ?></td></tr>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($view === 'profile'): 
                $aData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM `USER` WHERE ID = $adminId"));
            ?>
                <div class="section-header">
                    <h3>My Profile</h3>
                </div>

                <div class="bordered-box" style="max-width:460px;">
                    <form action="adminDashboard.php?view=profile" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($aData['Name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($aData['Email']); ?>" disabled style="background:#eaeaea;">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($aData['Phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" value="System Admin" disabled style="background:#eaeaea;">
                        </div>
                        <button type="submit" class="btn-cyan">Update Profile</button>
                    </form>
                </div>

            <?php elseif ($view === 'password'): ?>
                <div class="section-header">
                    <h3>Change Password</h3>
                </div>

                <div class="bordered-box" style="max-width:460px;">
                    <form action="adminDashboard.php?view=password" method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="currentPassword" required placeholder="Enter current password">
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="newPassword" required placeholder="Enter new password">
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirmPassword" required placeholder="Confirm new password">
                        </div>
                        <button type="submit" class="btn-cyan">Update Password</button>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>