<?php
session_start();
if (!isset($_SESSION["userId"]) || $_SESSION["role"] !== "Tour Guide") {
    header("Location: ../login.php");
    exit();
}

require_once "../../config/dbConnect.php";
require_once "../../models/usersModel.php";
$conn = dbConnection();
$guideId = $_SESSION["userId"];

@mysqli_query($conn, "ALTER TABLE `GUIDELINES` MODIFY `Notice_Date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");

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

    if ($action === "update_status") {
        $assignmentId = intval($_POST["assignment_id"]);
        $newStatus = $_POST["status"];
        $stmt = mysqli_prepare($conn, "UPDATE GUIDE_ASSIGNMENT SET Status = ? WHERE ID = ? AND Guide_ID = ?");
        mysqli_stmt_bind_param($stmt, 'sii', $newStatus, $assignmentId, $guideId);
        mysqli_stmt_execute($stmt);
        $msg = "Tour status updated successfully!";
        $view = 'status';
    } elseif ($action === "add_guideline") {
        $note = trim($_POST["note"]);
        $stmt = mysqli_prepare($conn, "INSERT INTO GUIDELINES (Guide_ID, Note, Notice_Date) VALUES (?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, 'is', $guideId, $note);
        mysqli_stmt_execute($stmt);
        $msg = "Guideline note added successfully!";
        $view = 'guidelines';
    } elseif ($action === "edit_guideline") {
        $noteId = intval($_POST["guideline_id"]);
        $note = trim($_POST["note"]);
        $stmt = mysqli_prepare($conn, "UPDATE GUIDELINES SET Note = ? WHERE ID = ? AND Guide_ID = ?");
        mysqli_stmt_bind_param($stmt, 'sii', $note, $noteId, $guideId);
        mysqli_stmt_execute($stmt);
        $msg = "Guideline note updated successfully!";
        $view = 'guidelines';
    } elseif ($action === "delete_guideline") {
        $noteId = intval($_POST["guideline_id"]);
        $stmt = mysqli_prepare($conn, "DELETE FROM GUIDELINES WHERE ID = ? AND Guide_ID = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $noteId, $guideId);
        mysqli_stmt_execute($stmt);
        $msg = "Guideline note deleted successfully!";
        $view = 'guidelines';
    } elseif ($action === "update_profile") {
        $name = trim($_POST["fullName"]);
        $phone = trim($_POST["phone"]);
        $stmt = mysqli_prepare($conn, "UPDATE `USER` SET `Name` = ?, `Phone` = ? WHERE `ID` = ?");
        mysqli_stmt_bind_param($stmt, 'ssi', $name, $phone, $guideId);
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
            $errorMsg = "New passwords do not match.";
        } elseif (changeUserPassword($guideId, $curr, $newP)) {
            $msg = "Password updated successfully!";
        } else {
            $errorMsg = "Current password is incorrect.";
        }
        $view = 'password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tour Guide Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/dashboard.css?v=<?php echo time(); ?>">
    <script src="../../assets/js/main.js" defer></script>
</head>
<body>

<div class="dashboard-container">
    <div class="top-nav">
        <h2>Tour Guide Dashboard</h2>
        <div style="display: flex; align-items: center; gap: 8px;">
            <span class="user-greeting">Hi, <?php echo htmlspecialchars($_SESSION["name"]); ?></span>
        </div>
    </div>

    <div class="main-body">
        <div class="sidebar">
            <ul>
                <li class="<?php echo ($view === 'dashboard') ? 'active' : ''; ?>">
                    <a href="guideDashboard.php?view=dashboard">Dashboard</a>
                </li>
                <li class="<?php echo ($view === 'status') ? 'active' : ''; ?>">
                    <a href="guideDashboard.php?view=status">Upload Status</a>
                </li>
                <li class="<?php echo ($view === 'guidelines') ? 'active' : ''; ?>">
                    <a href="guideDashboard.php?view=guidelines">Guidelines</a>
                </li>
                <li class="<?php echo ($view === 'profile') ? 'active' : ''; ?>">
                    <a href="guideDashboard.php?view=profile">Profile</a>
                </li>
                <li class="<?php echo ($view === 'password') ? 'active' : ''; ?>">
                    <a href="guideDashboard.php?view=password">Change Password</a>
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
                <div class="section-header">
                    <h3>My Assigned Schedule</h3>
                </div>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Tour</th>
                            <th>Package</th>
                            <th>Date</th>
                            <th>Tourists</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $assigns = mysqli_query($conn, "SELECT ga.ID, ga.Status AS guide_status, b.Status AS booking_status, b.Travel_Date, b.Persons, p.Package_Name, p.Image 
                            FROM GUIDE_ASSIGNMENT ga 
                            JOIN BOOKING b ON ga.Booking_ID = b.ID 
                            JOIN TOUR_PACKAGE p ON b.Tour_Package_ID = p.ID 
                            WHERE ga.Guide_ID = $guideId ORDER BY b.Travel_Date ASC");
                        if ($assigns && mysqli_num_rows($assigns) > 0):
                            while ($a = mysqli_fetch_assoc($assigns)):
                                $touristsCount = $a['Persons'] ?? 12;
                                $pImg = resolveTourImg($a['Image']);
                                $bStatus = !empty($a['booking_status']) ? $a['booking_status'] : 'Approved';
                                $gStatus = !empty($a['guide_status']) ? $a['guide_status'] : 'Scheduled';
                        ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($pImg); ?>" 
                                     style="width:40px; height:40px; object-fit:cover; border-radius:4px; display:block; background:#e2e8f0;" 
                                     alt="Tour"
                                     onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400';">
                            </td>
                            <td><strong><?php echo htmlspecialchars($a['Package_Name']); ?></strong></td>
                            <td><?php echo date("d M Y", strtotime($a['Travel_Date'])); ?></td>
                            <td><?php echo htmlspecialchars($touristsCount); ?></td>
                            <td>
                                <div style="display:inline-flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $bStatus)); ?>"><?php echo htmlspecialchars($bStatus); ?></span>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $gStatus)); ?>" style="border: 1px dashed #0284c7;"><?php echo htmlspecialchars($gStatus); ?></span>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr><td colspan="5" style="text-align:center; color:#888;">No assigned tours currently scheduled.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div style="max-width: 600px; margin-top: 24px;">
                    <div class="section-header">
                        <h3>Guidelines</h3>
                        <a href="guideDashboard.php?view=guidelines" class="btn-cyan" style="padding:4px 10px; font-size:12px; text-decoration:none;">Manage Notes</a>
                    </div>
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Note</th>
                                <th style="width: 150px;">Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $notes = mysqli_query($conn, "SELECT * FROM GUIDELINES WHERE Guide_ID = $guideId ORDER BY ID DESC LIMIT 3");
                            if ($notes && mysqli_num_rows($notes) > 0):
                                while ($n = mysqli_fetch_assoc($notes)):
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($n['Note']); ?></td>
                                <td><?php echo date("d M Y, h:i A", strtotime($n['Notice_Date'])); ?></td>
                            </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr><td colspan="2" style="text-align:center; color:#888;">No guidelines added.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div class="bordered-box" style="margin-top:16px;">
                        <h4 style="font-size:13px; margin-bottom:8px;">Tourists in Assigned Group</h4>
                        <ol style="font-size:12px; margin-left:18px; line-height:1.7;">
                            <li>Sayedur Rahman Leon</li>
                            <li>Fahim Ur Rahman</li>
                            <li>Chandrima Sharma Purba</li>
                            <li>Nafis Ul Hoque Bondhon</li>
                        </ol>
                    </div>
                </div>

            <?php elseif ($view === 'status'): ?>
                <div class="section-header">
                    <h3>Upload Tour Status</h3>
                </div>
                <div class="bordered-box" style="max-width:500px;">
                    <form action="guideDashboard.php?view=status" method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <div class="form-group">
                            <label>Select Assigned Tour</label>
                            <select name="assignment_id" required>
                                <?php
                                $assigns3 = mysqli_query($conn, "SELECT ga.ID, ga.Status AS curr_status, p.Package_Name FROM GUIDE_ASSIGNMENT ga JOIN BOOKING b ON ga.Booking_ID = b.ID JOIN TOUR_PACKAGE p ON b.Tour_Package_ID = p.ID WHERE ga.Guide_ID = $guideId");
                                while ($asRow = mysqli_fetch_assoc($assigns3)) {
                                    $curr = !empty($asRow['curr_status']) ? " (" . $asRow['curr_status'] . ")" : "";
                                    echo "<option value='{$asRow['ID']}'>{$asRow['Package_Name']}{$curr}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Select Updated Status</label>
                            <select name="status">
                                <option value="In Progress">In Progress</option>
                                <option value="Scheduled">Scheduled</option>
                                <option value="Completed">Completed</option>
                                <option value="Delayed">Delayed</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-cyan" style="width:100%;">Update Status</button>
                    </form>
                </div>

            <?php elseif ($view === 'guidelines'): 
                $editId = intval($_GET['edit_id'] ?? 0);
                $editNote = null;
                if ($editId > 0) {
                    $stmt = mysqli_prepare($conn, "SELECT * FROM GUIDELINES WHERE ID = ? AND Guide_ID = ?");
                    mysqli_stmt_bind_param($stmt, 'ii', $editId, $guideId);
                    mysqli_stmt_execute($stmt);
                    $res = mysqli_stmt_get_result($stmt);
                    $editNote = mysqli_fetch_assoc($res);
                }
            ?>
                <div class="section-header">
                    <h3>Operational Guidelines & Notices</h3>
                </div>
                <div class="bordered-box" style="margin-bottom:18px;">
                    <h4 style="font-size:14px; margin-bottom:10px;">
                        <?php echo $editNote ? 'Edit Guideline Note' : 'Post New Guideline'; ?>
                    </h4>
                    <form action="guideDashboard.php?view=guidelines" method="POST" style="display:flex; gap:12px; align-items:center;">
                        <input type="hidden" name="action" value="<?php echo $editNote ? 'edit_guideline' : 'add_guideline'; ?>">
                        <?php if ($editNote): ?>
                            <input type="hidden" name="guideline_id" value="<?php echo $editNote['ID']; ?>">
                        <?php endif; ?>
                        
                        <input type="text" name="note" value="<?php echo htmlspecialchars($editNote['Note'] ?? ''); ?>" placeholder="Write instructions..." required style="flex:1; padding:8px 12px; border:1px solid #ccc; border-radius:3px;">
                        
                        <button type="submit" class="btn-cyan">
                            <?php echo $editNote ? 'Update Note' : 'Add Note'; ?>
                        </button>
                        <?php if ($editNote): ?>
                            <a href="guideDashboard.php?view=guidelines" class="btn-sm" style="background:#64748b; color:#fff; text-decoration:none; padding:8px 12px; border-radius:3px;">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>

                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Notice / Instruction</th>
                            <th style="width: 180px;">Date & Time Posted</th>
                            <th style="width: 150px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $notesAll = mysqli_query($conn, "SELECT * FROM GUIDELINES WHERE Guide_ID = $guideId ORDER BY ID DESC");
                        if ($notesAll && mysqli_num_rows($notesAll) > 0):
                            while ($n = mysqli_fetch_assoc($notesAll)):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($n['Note']); ?></td>
                            <td><?php echo date("d M Y, h:i A", strtotime($n['Notice_Date'])); ?></td>
                            <td>
                                <div style="display:inline-flex; gap:6px; align-items:center;">
                                    <a href="guideDashboard.php?view=guidelines&edit_id=<?php echo $n['ID']; ?>" 
                                       class="btn-sm" 
                                       style="background:#0284c7; color:#fff; text-decoration:none; padding:4px 9px; border-radius:3px; font-size:12px; font-weight:500;">
                                       Edit
                                    </a>
                                    <form action="guideDashboard.php?view=guidelines" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this guideline note?');">
                                        <input type="hidden" name="action" value="delete_guideline">
                                        <input type="hidden" name="guideline_id" value="<?php echo $n['ID']; ?>">
                                        <button type="submit" class="btn-sm btn-reject" style="padding:4px 9px; font-size:12px;">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr><td colspan="3" style="text-align:center; color:#888;">No guidelines posted yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            <?php elseif ($view === 'profile'): 
                $gData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM `USER` WHERE ID = $guideId"));
            ?>
                <div class="section-header">
                    <h3>My Profile</h3>
                </div>
                <div class="bordered-box" style="max-width:480px;">
                    <form action="guideDashboard.php?view=profile" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="fullName" value="<?php echo htmlspecialchars($gData['Name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($gData['Email']); ?>" disabled style="background:#eaeaea;">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($gData['Phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" value="Tour Guide" disabled style="background:#eaeaea;">
                        </div>
                        <button type="submit" class="btn-cyan">Update Profile</button>
                    </form>
                </div>

            <?php elseif ($view === 'password'): ?>
                <div class="section-header">
                    <h3>Change Password</h3>
                </div>
                <div class="bordered-box" style="max-width:480px;">
                    <form action="guideDashboard.php?view=password" method="POST">
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