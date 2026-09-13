<?php
session_start();
if (!isset($_SESSION["userId"]) || $_SESSION["role"] !== "Agency Manager") {
    header("Location: ../login.php");
    exit();
}

require_once "../../config/dbConnect.php";
require_once "../../models/usersModel.php";
$conn = dbConnection();
$managerId = $_SESSION["userId"];

$view = $_GET['view'] ?? 'dashboard';
$msg = "";
$errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "approve_booking") {
        $bId = intval($_POST["booking_id"]);
        mysqli_query($conn, "UPDATE BOOKING SET Status = 'Approved' WHERE ID = $bId");
        $msg = "Booking #$bId Approved successfully!";
        $view = 'bookings';
    } elseif ($action === "reject_booking") {
        $bId = intval($_POST["booking_id"]);
        mysqli_query($conn, "UPDATE BOOKING SET Status = 'Rejected' WHERE ID = $bId");
        $msg = "Booking #$bId Rejected!";
        $view = 'bookings';
    } elseif ($action === "add_package") {
        $destId = intval($_POST["destination_id"]);
        $title = trim($_POST["title"]);
        $price = floatval($_POST["price"]);
        $duration = intval($_POST["duration"]);
        $capacity = intval($_POST["capacity"] ?? 20);
        $desc = trim($_POST["description"]);
        $img = trim($_POST["image_url"]);
        if (empty($img)) {
            $img = "https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400";
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO TOUR_PACKAGE (Destination_ID, Package_Name, Description, Price, Duration_Days, Capacity, Image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'issdiis', $destId, $title, $desc, $price, $duration, $capacity, $img);
        mysqli_stmt_execute($stmt);
        $msg = "New package added successfully!";
        $view = 'packages';
    } elseif ($action === "delete_package") {
        $pId = intval($_POST["package_id"]);
        mysqli_query($conn, "DELETE FROM TOUR_PACKAGE WHERE ID = $pId");
        $msg = "Tour package removed successfully!";
        $view = 'packages';
    } elseif ($action === "assign_guide") {
        $bookId = intval($_POST["booking_id"]);
        $guideId = intval($_POST["guide_id"]);
        $date = $_POST["assign_date"];

        $stmt = mysqli_prepare($conn, "INSERT INTO GUIDE_ASSIGNMENT (Booking_ID, Guide_ID, Assignment_Date, Status) VALUES (?, ?, ?, 'Scheduled')");
        mysqli_stmt_bind_param($stmt, 'iis', $bookId, $guideId, $date);
        mysqli_stmt_execute($stmt);
        $msg = "Tour guide assigned successfully!";
        $view = 'assign';
    } elseif ($action === "update_profile") {
        $name = trim($_POST["fullName"]);
        $phone = trim($_POST["phone"]);
        $stmt = mysqli_prepare($conn, "UPDATE `USER` SET `Name` = ?, `Phone` = ? WHERE `ID` = ?");
        mysqli_stmt_bind_param($stmt, 'ssi', $name, $phone, $managerId);
        mysqli_stmt_execute($stmt);
        $_SESSION["name"] = $name;
        $_SESSION["phone"] = $phone;
        $msg = "Profile updated successfully!";
        $view = 'profile';
    } elseif ($action === "change_password") {
        $curr = $_POST["currentPassword"];
        $newP = $_POST["newPassword"];
        $conf = $_POST["confirmPassword"];
        if ($newP !== $conf) {
            $errorMsg = "New passwords do not match!";
        } elseif (changeUserPassword($managerId, $curr, $newP)) {
            $msg = "Password changed successfully!";
        } else {
            $errorMsg = "Current password incorrect!";
        }
        $view = 'password';
    }
}

$pkgCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM TOUR_PACKAGE"))['total'];
$bookingCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM BOOKING"))['total'];
$pendingCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM BOOKING WHERE Status='Pending'"))['total'];
$guideCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM `USER` WHERE Role='Tour Guide'"))['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Agency Manager Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/dashboard.css?v=<?php echo time(); ?>">
    <script src="../../assets/js/main.js" defer></script>
</head>
<body>

<div class="dashboard-container">
    <div class="top-nav">
        <h2>Agency Manager Dashboard</h2>
        <div style="display: flex; align-items: center; gap: 8px;">
            <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=100&auto=format&fit=crop&q=80" alt="Manager" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid #cbd5e1;">
            <span class="user-greeting">Hi, <?php echo htmlspecialchars($_SESSION["name"]); ?></span>
        </div>
    </div>

    <div class="main-body">
        <div class="sidebar">
            <ul>
                <li class="<?php echo ($view === 'dashboard') ? 'active' : ''; ?>">
                    <a href="managerDashboard.php?view=dashboard">Dashboard</a>
                </li>
                <li class="<?php echo ($view === 'packages') ? 'active' : ''; ?>">
                    <a href="managerDashboard.php?view=packages">Manage Packages</a>
                </li>
                <li class="<?php echo ($view === 'bookings') ? 'active' : ''; ?>">
                    <a href="managerDashboard.php?view=bookings">Bookings</a>
                </li>
                <li class="<?php echo ($view === 'assign') ? 'active' : ''; ?>">
                    <a href="managerDashboard.php?view=assign">Assign Guide</a>
                </li>
                <li class="<?php echo ($view === 'reports') ? 'active' : ''; ?>">
                    <a href="managerDashboard.php?view=reports">Reports</a>
                </li>
                <li class="<?php echo ($view === 'profile') ? 'active' : ''; ?>">
                    <a href="managerDashboard.php?view=profile">Profile</a>
                </li>
                <li class="<?php echo ($view === 'password') ? 'active' : ''; ?>">
                    <a href="managerDashboard.php?view=password">Change Password</a>
                </li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </div>

        <div class="content">
            <?php if (!empty($msg)): ?>
                <div class="success-msg"><?php echo htmlspecialchars($msg); ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMsg)): ?>
                <div class="error-banner"><?php echo htmlspecialchars($errorMsg); ?></div>
            <?php endif; ?>

            <!-- DASHBOARD HOME (Fig 04) -->
            <?php if ($view === 'dashboard'): ?>
                <div class="metric-grid">
                    <div class="metric-card">
                        <span>Packages</span>
                        <h1 class="color-blue"><?php echo $pkgCount; ?></h1>
                    </div>
                    <div class="metric-card">
                        <span>Bookings</span>
                        <h1 class="color-green"><?php echo $bookingCount; ?></h1>
                    </div>
                    <div class="metric-card">
                        <span>Pending</span>
                        <h1 class="color-red"><?php echo $pendingCount; ?></h1>
                    </div>
                    <div class="metric-card">
                        <span>Guides</span>
                        <h1 class="color-yellow"><?php echo $guideCount; ?></h1>
                    </div>
                </div>

                <div class="section-header">
                    <h3>Recent Bookings</h3>
                    <a href="managerDashboard.php?view=bookings">View All</a>
                </div>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Tour</th>
                            <th>Customer</th>
                            <th>Package</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recentBk = mysqli_query($conn, "SELECT b.*, u.Name as Customer, p.Package_Name, p.Image 
                            FROM BOOKING b 
                            JOIN `USER` u ON b.User_ID = u.ID 
                            JOIN TOUR_PACKAGE p ON b.Tour_Package_ID = p.ID 
                            ORDER BY b.ID DESC LIMIT 4");
                        while ($r = mysqli_fetch_assoc($recentBk)):
                            $rImg = !empty($r['Image']) ? $r['Image'] : 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400';
                        ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars($rImg); ?>" style="width:38px; height:38px; object-fit:cover; border-radius:4px;" alt=""></td>
                            <td><?php echo htmlspecialchars($r["Customer"]); ?></td>
                            <td><strong><?php echo htmlspecialchars($r["Package_Name"]); ?></strong></td>
                            <td><?php echo date("d M Y", strtotime($r["Travel_Date"])); ?></td>
                            <td>$<?php echo htmlspecialchars($r["Total_Price"]); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($r['Status']); ?>">
                                    <?php echo htmlspecialchars($r["Status"]); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <div class="action-buttons-row">
                    <a href="managerDashboard.php?view=packages" class="btn-cyan" style="text-decoration:none; padding:10px 18px;">Add New Package</a>
                    <a href="managerDashboard.php?view=assign" class="btn-lime" style="text-decoration:none; padding:10px 18px;">Assign Tour Guide</a>
                    <a href="managerDashboard.php?view=reports" class="btn-outline" style="text-decoration:none; padding:10px 18px;">View Reports</a>
                </div>

            <!-- MANAGE PACKAGES -->
            <?php elseif ($view === 'packages'): ?>
                <div class="section-header">
                    <h3>Manage Tour Packages</h3>
                </div>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Destination</th>
                            <th>Price</th>
                            <th>Duration</th>
                            <th>Capacity</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $pkList = mysqli_query($conn, "SELECT p.*, d.Location FROM TOUR_PACKAGE p JOIN DESTINATION d ON p.Destination_ID = d.ID");
                        while ($pkg = mysqli_fetch_assoc($pkList)):
                            $cap = $pkg['Capacity'] ?? 20;
                            $pImg = !empty($pkg['Image']) ? $pkg['Image'] : 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400';
                        ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars($pImg); ?>" style="width:45px; height:45px; object-fit:cover; border-radius:4px;" alt="Package"></td>
                            <td><strong><?php echo htmlspecialchars($pkg['Package_Name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($pkg['Location']); ?></td>
                            <td>$<?php echo htmlspecialchars($pkg['Price']); ?></td>
                            <td><?php echo htmlspecialchars($pkg['Duration_Days']); ?> Days</td>
                            <td><?php echo htmlspecialchars($cap); ?></td>
                            <td>
                                <form action="managerDashboard.php?view=packages" method="POST" onsubmit="return confirm('Delete this package?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_package">
                                    <input type="hidden" name="package_id" value="<?php echo $pkg['ID']; ?>">
                                    <button type="submit" class="btn-sm btn-reject">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <div class="bordered-box" style="margin-top:20px;">
                    <h4 style="font-size:14px; margin-bottom:12px;">Add New Tour Package</h4>
                    <form action="managerDashboard.php?view=packages" method="POST">
                        <input type="hidden" name="action" value="add_package">
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                            <div class="form-group">
                                <label>Package Title</label>
                                <input type="text" name="title" required placeholder="Enter package name">
                            </div>
                            <div class="form-group">
                                <label>Destination</label>
                                <select name="destination_id" required>
                                    <?php
                                    $dests = mysqli_query($conn, "SELECT * FROM DESTINATION");
                                    while ($d = mysqli_fetch_assoc($dests)) {
                                        echo "<option value='{$d['ID']}'>{$d['Location']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Price ($)</label>
                                <input type="number" step="0.01" name="price" required placeholder="e.g. 499.00">
                            </div>
                            <div class="form-group">
                                <label>Duration (Days)</label>
                                <input type="number" name="duration" required placeholder="e.g. 5">
                            </div>
                            <div class="form-group">
                                <label>Capacity</label>
                                <input type="number" name="capacity" value="20" required>
                            </div>
                            <div class="form-group">
                                <label>Image URL</label>
                                <input type="text" name="image_url" placeholder="Paste image web address">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Itinerary / Description</label>
                            <textarea name="description" rows="3" placeholder="Enter trip itinerary..."></textarea>
                        </div>
                        <button type="submit" class="btn-cyan">Save Package</button>
                    </form>
                </div>

            <!-- BOOKING APPROVALS -->
            <?php elseif ($view === 'bookings'): ?>
                <div class="section-header">
                    <h3>Booking Approvals</h3>
                </div>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Tour</th>
                            <th>Customer</th>
                            <th>Package</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $bks = mysqli_query($conn, "SELECT b.*, u.Name as CustName, p.Package_Name, p.Image 
                            FROM BOOKING b 
                            JOIN `USER` u ON b.User_ID = u.ID 
                            JOIN TOUR_PACKAGE p ON b.Tour_Package_ID = p.ID 
                            WHERE b.Status = 'Pending'");
                        if (mysqli_num_rows($bks) > 0):
                            while ($bRow = mysqli_fetch_assoc($bks)):
                                $bImg = !empty($bRow['Image']) ? $bRow['Image'] : 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400';
                        ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars($bImg); ?>" style="width:38px; height:38px; object-fit:cover; border-radius:4px;" alt=""></td>
                            <td><?php echo htmlspecialchars($bRow['CustName']); ?></td>
                            <td><strong><?php echo htmlspecialchars($bRow['Package_Name']); ?></strong></td>
                            <td><?php echo date("d M Y", strtotime($bRow['Travel_Date'])); ?></td>
                            <td>$<?php echo htmlspecialchars($bRow['Total_Price']); ?></td>
                            <td>
                                <form action="managerDashboard.php?view=bookings" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="approve_booking">
                                    <input type="hidden" name="booking_id" value="<?php echo $bRow['ID']; ?>">
                                    <button type="submit" class="btn-sm btn-approve">Approve</button>
                                </form>
                                <form action="managerDashboard.php?view=bookings" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="reject_booking">
                                    <input type="hidden" name="booking_id" value="<?php echo $bRow['ID']; ?>">
                                    <button type="submit" class="btn-sm btn-reject">Reject</button>
                                </form>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr><td colspan="6" style="text-align:center; color:#888;">No pending bookings waiting for approval.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="bordered-box" style="margin-top:20px;">
                    <h4 style="font-size:14px; margin-bottom:8px;">Seat Availability Check</h4>
                    <p style="font-size:13px; color:#555;">Bali Adventure: <strong>Available Seats: 8 / 20</strong></p>
                </div>

            <!-- ASSIGN TOUR GUIDE -->
            <?php elseif ($view === 'assign'): ?>
                <div class="section-header">
                    <h3>Assign Tour Guide</h3>
                </div>
                <div class="two-col-layout">
                    <div class="bordered-box">
                        <form action="managerDashboard.php?view=assign" method="POST">
                            <input type="hidden" name="action" value="assign_guide">
                            <div class="form-group">
                                <label>Select Confirmed Booking</label>
                                <select name="booking_id" required>
                                    <?php
                                    $cnf = mysqli_query($conn, "SELECT b.ID, p.Package_Name, b.Travel_Date FROM BOOKING b JOIN TOUR_PACKAGE p ON b.Tour_Package_ID = p.ID WHERE b.Status = 'Approved'");
                                    while ($cRow = mysqli_fetch_assoc($cnf)) {
                                        echo "<option value='{$cRow['ID']}'>{$cRow['Package_Name']} ({$cRow['Travel_Date']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Select Tour Guide</label>
                                <select name="guide_id" required>
                                    <?php
                                    $gList = mysqli_query($conn, "SELECT ID, Name FROM `USER` WHERE Role = 'Tour Guide'");
                                    while ($g = mysqli_fetch_assoc($gList)) {
                                        echo "<option value='{$g['ID']}'>{$g['Name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Assignment Date</label>
                                <input type="date" name="assign_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <button type="submit" class="btn-cyan" style="width:100%;">Assign Guide</button>
                        </form>
                    </div>

                    <div class="bordered-box">
                        <h4 style="font-size:14px; margin-bottom:12px;">Guide Workload Overview</h4>
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Tour Guide</th>
                                    <th>Assigned Tours</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $gWorkload = mysqli_query($conn, "SELECT u.Name, COUNT(ga.ID) as total FROM `USER` u LEFT JOIN GUIDE_ASSIGNMENT ga ON u.ID = ga.Guide_ID WHERE u.Role = 'Tour Guide' GROUP BY u.ID");
                                while ($gw = mysqli_fetch_assoc($gWorkload)):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($gw['Name']); ?></td>
                                    <td><?php echo $gw['total']; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- REPORTS -->
            <?php elseif ($view === 'reports'): ?>
                <div class="section-header">
                    <h3>Tour Booking Reports</h3>
                </div>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Package</th>
                            <th>Total Bookings</th>
                            <th>Revenue ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $repQ = mysqli_query($conn, "SELECT p.Package_Name, COUNT(b.ID) as cnt, SUM(b.Total_Price) as rev FROM TOUR_PACKAGE p LEFT JOIN BOOKING b ON p.ID = b.Tour_Package_ID GROUP BY p.ID");
                        while ($rq = mysqli_fetch_assoc($repQ)):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($rq['Package_Name']); ?></strong></td>
                            <td><?php echo $rq['cnt']; ?></td>
                            <td>$<?php echo number_format($rq['rev'] ?? 0, 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <!-- PROFILE -->
            <?php elseif ($view === 'profile'): 
                $mgrData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM `USER` WHERE ID = $managerId"));
            ?>
                <div class="section-header">
                    <h3>My Profile</h3>
                </div>
                <div class="bordered-box" style="max-width:480px;">
                    <form action="managerDashboard.php?view=profile" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="fullName" value="<?php echo htmlspecialchars($mgrData['Name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($mgrData['Email']); ?>" disabled style="background:#eaeaea;">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($mgrData['Phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" value="Agency Manager" disabled style="background:#eaeaea;">
                        </div>
                        <button type="submit" class="btn-cyan">Update Profile</button>
                    </form>
                </div>

            <!-- PASSWORD -->
            <?php elseif ($view === 'password'): ?>
                <div class="section-header">
                    <h3>Change Password</h3>
                </div>
                <div class="bordered-box" style="max-width:480px;">
                    <form action="managerDashboard.php?view=password" method="POST">
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
                        <button type="submit" class="btn-cyan">Change Password</button>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>