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

function resolveTourImg($rawImg) {
    $rawImg = trim($rawImg ?? '');
    $fallback = "https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400";
    if (empty($rawImg)) {
        return $fallback;
    }
    if (preg_match('/^https?:\/\//i', $rawImg)) {
        return $rawImg;
    }
    return (strpos($rawImg, '/') !== false) ? $rawImg : "../../assets/img/" . $rawImg;
}

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

        $dRes = mysqli_query($conn, "SELECT Image FROM `DESTINATION` WHERE ID = $destId");
        $dData = mysqli_fetch_assoc($dRes);
        $img = !empty($dData['Image']) ? $dData['Image'] : "https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400";

        $stmt = mysqli_prepare($conn, "INSERT INTO TOUR_PACKAGE (Destination_ID, Package_Name, Description, Price, Duration_Days, Capacity, Image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'issdiis', $destId, $title, $desc, $price, $duration, $capacity, $img);
        mysqli_stmt_execute($stmt);
        $msg = "New package added successfully with destination image!";
        $view = 'packages';
    } elseif ($action === "edit_package") {
        $pId = intval($_POST["package_id"]);
        $destId = intval($_POST["destination_id"]);
        $title = trim($_POST["title"]);
        $price = floatval($_POST["price"]);
        $duration = intval($_POST["duration"]);
        $capacity = intval($_POST["capacity"] ?? 20);
        $desc = trim($_POST["description"]);

        $dRes = mysqli_query($conn, "SELECT Image FROM `DESTINATION` WHERE ID = $destId");
        $dData = mysqli_fetch_assoc($dRes);
        $img = !empty($dData['Image']) ? $dData['Image'] : "https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400";

        $stmt = mysqli_prepare($conn, "UPDATE TOUR_PACKAGE SET Destination_ID = ?, Package_Name = ?, Description = ?, Price = ?, Duration_Days = ?, Capacity = ?, Image = ? WHERE ID = ?");
        mysqli_stmt_bind_param($stmt, 'issdiisi', $destId, $title, $desc, $price, $duration, $capacity, $img, $pId);
        mysqli_stmt_execute($stmt);
        $msg = "Tour package updated successfully!";
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
                    <a href="managerDashboard.php?view=reports">Reports & Reviews</a>
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
                            $rImg = resolveTourImg($r['Image'] ?? '');
                        ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($rImg); ?>" 
                                     style="width:38px; height:38px; object-fit:cover; border-radius:4px; display:block; background:#e2e8f0;" 
                                     alt="" 
                                     onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400';">
                            </td>
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
                    <a href="managerDashboard.php?view=reports" class="btn-outline" style="text-decoration:none; padding:10px 18px;">View Reports & Reviews</a>
                </div>

            <?php elseif ($view === 'packages'): 
                $editPkgId = intval($_GET['edit_id'] ?? 0);
                $editPkg = null;
                if ($editPkgId > 0) {
                    $epQ = mysqli_query($conn, "SELECT * FROM TOUR_PACKAGE WHERE ID = $editPkgId");
                    $editPkg = mysqli_fetch_assoc($epQ);
                }
            ?>
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
                            $pImg = resolveTourImg($pkg['Image'] ?? '');
                        ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($pImg); ?>" 
                                     style="width:45px; height:45px; object-fit:cover; border-radius:4px; display:block; background:#e2e8f0;" 
                                     alt="Package"
                                     onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400';">
                            </td>
                            <td><strong><?php echo htmlspecialchars($pkg['Package_Name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($pkg['Location']); ?></td>
                            <td>$<?php echo htmlspecialchars($pkg['Price']); ?></td>
                            <td><?php echo htmlspecialchars($pkg['Duration_Days']); ?> Days</td>
                            <td><?php echo htmlspecialchars($cap); ?></td>
                            <td>
                                <div style="display:inline-flex; gap:5px; align-items:center;">
                                    <a href="managerDashboard.php?view=packages&edit_id=<?php echo $pkg['ID']; ?>" 
                                       class="btn-sm" 
                                       style="background:#0284c7; color:#fff; text-decoration:none; padding:4px 9px; border-radius:3px; font-weight:500;">
                                       Edit
                                    </a>
                                    <form action="managerDashboard.php?view=packages" method="POST" onsubmit="return confirm('Delete this package?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_package">
                                        <input type="hidden" name="package_id" value="<?php echo $pkg['ID']; ?>">
                                        <button type="submit" class="btn-sm btn-reject">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <div class="bordered-box" style="margin-top:20px;">
                    <h4 style="font-size:14px; margin-bottom:12px;">
                        <?php echo $editPkg ? 'Edit Tour Package (ID: ' . $editPkg['ID'] . ')' : 'Add New Tour Package'; ?>
                    </h4>
                    <form action="managerDashboard.php?view=packages" method="POST">
                        <input type="hidden" name="action" value="<?php echo $editPkg ? 'edit_package' : 'add_package'; ?>">
                        <?php if ($editPkg): ?>
                            <input type="hidden" name="package_id" value="<?php echo $editPkg['ID']; ?>">
                        <?php endif; ?>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                            <div class="form-group">
                                <label>Package Title</label>
                                <input type="text" name="title" value="<?php echo htmlspecialchars($editPkg['Package_Name'] ?? ''); ?>" required placeholder="Enter package name">
                            </div>
                            <div class="form-group">
                                <label>Destination</label>
                                <select name="destination_id" id="packageDestSelect" required>
                                    <?php
                                    $dests = mysqli_query($conn, "SELECT * FROM DESTINATION");
                                    while ($d = mysqli_fetch_assoc($dests)) {
                                        $destImgUrl = resolveTourImg($d['Image'] ?? '');
                                        $sel = ($editPkg && $editPkg['Destination_ID'] == $d['ID']) ? 'selected' : '';
                                        echo "<option value='{$d['ID']}' data-image='" . htmlspecialchars($destImgUrl) . "' $sel>{$d['Name']} ({$d['Location']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Price ($)</label>
                                <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($editPkg['Price'] ?? ''); ?>" required placeholder="e.g. 499.00">
                            </div>
                            <div class="form-group">
                                <label>Duration (Days)</label>
                                <input type="number" name="duration" value="<?php echo htmlspecialchars($editPkg['Duration_Days'] ?? ''); ?>" required placeholder="e.g. 5">
                            </div>
                            <div class="form-group">
                                <label>Capacity</label>
                                <input type="number" name="capacity" value="<?php echo htmlspecialchars($editPkg['Capacity'] ?? 20); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Selected Destination Image</label>
                                <div style="display:flex; align-items:center; gap:10px; padding:6px 10px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:4px; min-height:42px;">
                                    <img id="packageDestPreviewImg" src="" alt="Selected Destination" style="width:40px; height:40px; object-fit:cover; border-radius:4px; display:none;">
                                    <span id="packageDestPreviewText" style="font-size:12px; color:#64748b;">Auto-inherited from destination</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Itinerary / Description</label>
                            <textarea name="description" rows="3" placeholder="Enter trip itinerary..."><?php echo htmlspecialchars($editPkg['Description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div style="display:flex; gap:8px;">
                            <button type="submit" class="btn-cyan">
                                <?php echo $editPkg ? 'Update Package' : 'Save Package'; ?>
                            </button>
                            <?php if ($editPkg): ?>
                                <a href="managerDashboard.php?view=packages" class="btn-sm" style="background:#64748b; color:#fff; text-decoration:none; padding:8px 14px; border-radius:3px; display:inline-flex; align-items:center;">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

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
                        $bks = mysqli_query($conn, "SELECT b.*, u.Name as CustName, p.Package_Name, p.Image, p.Capacity,
                            (SELECT IFNULL(SUM(Persons), 0) FROM BOOKING WHERE Tour_Package_ID = p.ID AND Status = 'Approved') AS Booked_Seats
                            FROM BOOKING b 
                            JOIN `USER` u ON b.User_ID = u.ID 
                            JOIN TOUR_PACKAGE p ON b.Tour_Package_ID = p.ID 
                            WHERE b.Status = 'Pending'");
                        if (mysqli_num_rows($bks) > 0):
                            while ($bRow = mysqli_fetch_assoc($bks)):
                                $bImg = resolveTourImg($bRow['Image'] ?? '');
                                $capacity = intval($bRow['Capacity'] ?? 20);
                                $booked = intval($bRow['Booked_Seats'] ?? 0);
                                $availableSeats = max(0, $capacity - $booked);
                        ?>
                        <tr class="booking-item-row" 
                            style="cursor:pointer;"
                            data-pkg-name="<?php echo htmlspecialchars($bRow['Package_Name']); ?>"
                            data-available="<?php echo $availableSeats; ?>"
                            data-capacity="<?php echo $capacity; ?>">
                            <td>
                                <img src="<?php echo htmlspecialchars($bImg); ?>" 
                                     style="width:38px; height:38px; object-fit:cover; border-radius:4px; display:block; background:#e2e8f0;" 
                                     alt=""
                                     onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400';">
                            </td>
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
                    <p id="seatAvailabilityNotice" style="font-size:13px; color:#555;">Click on any booking approval row above to check seat availability.</p>
                </div>

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

                <div class="section-header" style="margin-top: 32px;">
                    <h3>Customer Reviews & Ratings</h3>
                </div>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Tour Package</th>
                            <th>Customer</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th style="width: 140px;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $revQuery = mysqli_query($conn, "SELECT r.*, u.Name as TouristName, p.Package_Name, p.Image 
                            FROM REVIEW r 
                            JOIN `USER` u ON r.User_ID = u.ID 
                            JOIN TOUR_PACKAGE p ON r.Tour_Package_ID = p.ID 
                            ORDER BY r.ID DESC");
                        if ($revQuery && mysqli_num_rows($revQuery) > 0):
                            while ($rev = mysqli_fetch_assoc($revQuery)):
                                $ratingStars = intval($rev['Rating']);
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($rev['Package_Name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($rev['TouristName']); ?></td>
                            <td>
                                <span style="color: #f59e0b; font-size: 15px; letter-spacing: 2px;">
                                    <?php echo str_repeat('★', $ratingStars) . str_repeat('☆', 5 - $ratingStars); ?>
                                </span>
                                <span style="font-size: 12px; color: #64748b; margin-left: 4px;">(<?php echo $ratingStars; ?>/5)</span>
                            </td>
                            <td><?php echo htmlspecialchars($rev['Comment']); ?></td>
                            <td><?php echo date("d M Y", strtotime($rev['Review_Date'])); ?></td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr><td colspan="5" style="text-align:center; color:#888; padding: 20px;">No customer reviews submitted yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

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

<script>
document.addEventListener("DOMContentLoaded", function() {
    const destSelect = document.getElementById("packageDestSelect");
    const previewImg = document.getElementById("packageDestPreviewImg");
    const previewText = document.getElementById("packageDestPreviewText");

    function updatePreview() {
        if (!destSelect || !previewImg) return;
        const selectedOpt = destSelect.options[destSelect.selectedIndex];
        if (selectedOpt && selectedOpt.dataset.image) {
            previewImg.src = selectedOpt.dataset.image;
            previewImg.style.display = "block";
            previewText.textContent = "Auto-selected from " + selectedOpt.text.split('(')[0].trim();
        } else {
            previewImg.style.display = "none";
            previewText.textContent = "No image found for destination";
        }
    }

    if (destSelect) {
        destSelect.addEventListener("change", updatePreview);
        updatePreview();
    }

    const bookingRows = document.querySelectorAll(".booking-item-row");
    const seatNotice = document.getElementById("seatAvailabilityNotice");

    bookingRows.forEach(row => {
        row.addEventListener("click", function(e) {
            if (e.target.closest("button") || e.target.closest("form")) return;

            bookingRows.forEach(r => r.style.backgroundColor = "");
            this.style.backgroundColor = "#e0f2fe";

            const pkgName = this.getAttribute("data-pkg-name");
            const avail = this.getAttribute("data-available");
            const cap = this.getAttribute("data-capacity");

            if (seatNotice) {
                seatNotice.innerHTML = pkgName + ": <strong>Available Seats: " + avail + " / " + cap + "</strong>";
            }
        });
    });
});
</script>

</body>
</html>