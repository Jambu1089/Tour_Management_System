<?php
session_start();
if (!isset($_SESSION["userId"]) || $_SESSION["role"] !== "Tourist") {
    header("Location: ../login.php");
    exit();
}

require_once "../../config/dbConnect.php";
require_once "../../models/usersModel.php";
$conn = dbConnection();
$userId = $_SESSION["userId"];

$view = $_GET['view'] ?? 'dashboard';
$feedback = "";
$errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "book_tour") {
        $pkgId = intval($_POST["package_id"]);
        $travelDate = $_POST["travel_date"];
        $persons = intval($_POST["persons"] ?? 1);
        $totalPrice = floatval($_POST["total_cost"]);

        $stmt = mysqli_prepare($conn, "INSERT INTO BOOKING (User_ID, Tour_Package_ID, Booking_Date, Travel_Date, Persons, Total_Price, Status) VALUES (?, ?, CURDATE(), ?, ?, ?, 'Pending')");
        mysqli_stmt_bind_param($stmt, 'iisid', $userId, $pkgId, $travelDate, $persons, $totalPrice);
        if (mysqli_stmt_execute($stmt)) {
            $feedback = "Booking request submitted successfully!";
            $view = 'bookings';
        }
    } elseif ($action === "submit_review") {
        $pkgId = intval($_POST["package_id"]);
        $rating = intval($_POST["rating"]);
        $comment = trim($_POST["comment"]);

        $stmt = mysqli_prepare($conn, "INSERT INTO REVIEW (Tour_Package_ID, User_ID, Rating, Comment, Review_Date) VALUES (?, ?, ?, ?, CURDATE())");
        mysqli_stmt_bind_param($stmt, 'iiis', $pkgId, $userId, $rating, $comment);
        if (mysqli_stmt_execute($stmt)) {
            $feedback = "Thank you! Your review has been submitted.";
            $view = 'bookings';
        }
    } elseif ($action === "update_profile") {
        $name = trim($_POST["fullName"]);
        $phone = trim($_POST["phone"]);
        $stmt = mysqli_prepare($conn, "UPDATE `USER` SET `Name` = ?, `Phone` = ? WHERE `ID` = ?");
        mysqli_stmt_bind_param($stmt, 'ssi', $name, $phone, $userId);
        mysqli_stmt_execute($stmt);
        $_SESSION["name"] = $name;
        $_SESSION["phone"] = $phone;
        $feedback = "Profile updated successfully!";
        $view = 'profile';
    } elseif ($action === "change_password") {
        $current = $_POST["currentPassword"];
        $newPass = $_POST["newPassword"];
        $confirm = $_POST["confirmPassword"];

        if ($newPass !== $confirm) {
            $errorMsg = "New passwords do not match.";
        } elseif (changeUserPassword($userId, $current, $newPass)) {
            $feedback = "Password changed successfully!";
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
    <title>Tourist Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/dashboard.css?v=<?php echo time(); ?>">
    <script src="../../assets/js/main.js" defer></script>
</head>
<body>

<div class="dashboard-container">
    <div class="top-nav">
        <h2>Tourist Dashboard</h2>
        <div style="display: flex; align-items: center; gap: 8px;">
            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&auto=format&fit=crop&q=80" alt="Tourist" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid #cbd5e1;">
            <span class="user-greeting">Hi, <?php echo htmlspecialchars($_SESSION["name"]); ?></span>
        </div>
    </div>

    <div class="main-body">
        <div class="sidebar">
            <ul>
                <li class="<?php echo ($view === 'dashboard') ? 'active' : ''; ?>">
                    <a href="touristDashboard.php?view=dashboard">Dashboard</a>
                </li>
                <li class="<?php echo ($view === 'search') ? 'active' : ''; ?>">
                    <a href="touristDashboard.php?view=search">Search Tours</a>
                </li>
                <li class="<?php echo ($view === 'bookings') ? 'active' : ''; ?>">
                    <a href="touristDashboard.php?view=bookings">My Bookings</a>
                </li>
                <li class="<?php echo ($view === 'reviews') ? 'active' : ''; ?>">
                    <a href="touristDashboard.php?view=reviews">Reviews</a>
                </li>
                <li class="<?php echo ($view === 'profile') ? 'active' : ''; ?>">
                    <a href="touristDashboard.php?view=profile">Profile</a>
                </li>
                <li class="<?php echo ($view === 'password') ? 'active' : ''; ?>">
                    <a href="touristDashboard.php?view=password">Change Password</a>
                </li>
                <li><button id="logoutBtn">Logout</button></li>
            </ul>
        </div>

        <div class="content">
            <?php if (!empty($feedback)): ?>
                <div class="success-msg"><?php echo htmlspecialchars($feedback); ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMsg)): ?>
                <div class="error-banner"><?php echo htmlspecialchars($errorMsg); ?></div>
            <?php endif; ?>

            <!-- DASHBOARD & SEARCH TOURS -->
            <?php if ($view === 'dashboard' || $view === 'search'): ?>
                <div class="search-card">
                    <h3>Search Tours</h3>
                    <div class="search-controls">
                        <div class="control-item">
                            <label>Destination</label>
                            <select id="filterDestination">
                                <option value="ALL">All Destinations</option>
                                <option value="Bali">Bali, Indonesia</option>
                                <option value="Switzerland">Switzerland</option>
                                <option value="Thailand">Thailand</option>
                                <option value="Maldives">Maldives</option>
                            </select>
                        </div>
                        <div class="control-item">
                            <label>Max Budget</label>
                            <select id="filterBudget">
                                <option value="ALL">Any Budget</option>
                                <option value="500">Up to $500</option>
                                <option value="700">Up to $700</option>
                                <option value="1000">Up to $1000</option>
                            </select>
                        </div>
                        <button type="button" class="btn-cyan" id="searchBtn">Search</button>
                    </div>
                </div>

                <div class="section-header">
                    <h3>Popular Tours</h3>
                    <a href="touristDashboard.php?view=search">View All</a>
                </div>
                <table class="custom-table" id="popularToursTable">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Package</th>
                            <th>Destination</th>
                            <th>Price</th>
                            <th>Duration</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $pkgs = mysqli_query($conn, "SELECT p.*, d.Location FROM TOUR_PACKAGE p JOIN DESTINATION d ON p.Destination_ID = d.ID");
                        while ($row = mysqli_fetch_assoc($pkgs)):
                            $pkgImg = !empty($row['Image']) ? $row['Image'] : 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400';
                        ?>
                        <tr data-destination="<?php echo htmlspecialchars($row['Location']); ?>" data-price="<?php echo $row['Price']; ?>">
                            <td><img src="<?php echo htmlspecialchars($pkgImg); ?>" style="width:45px; height:45px; object-fit:cover; border-radius:4px;" alt="Tour"></td>
                            <td><strong><?php echo htmlspecialchars($row['Package_Name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['Location']); ?></td>
                            <td>$<?php echo htmlspecialchars($row['Price']); ?></td>
                            <td><?php echo htmlspecialchars($row['Duration_Days']); ?> Days</td>
                            <td>
                                <a href="touristDashboard.php?view=book&pkg_id=<?php echo $row['ID']; ?>" class="btn-sm" style="background:#0084ff; color:#fff; text-decoration:none; padding:4px 10px; border-radius:3px;">Book</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <?php if ($view === 'dashboard'): ?>
                <div class="section-header">
                    <h3>My Recent Bookings</h3>
                </div>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Tour</th>
                            <th>Package</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recentB = mysqli_query($conn, "SELECT b.*, p.Package_Name, p.Image FROM BOOKING b JOIN TOUR_PACKAGE p ON b.Tour_Package_ID = p.ID WHERE b.User_ID = $userId ORDER BY b.ID DESC LIMIT 3");
                        if ($recentB && mysqli_num_rows($recentB) > 0):
                            while ($rb = mysqli_fetch_assoc($recentB)):
                                $rbImg = !empty($rb['Image']) ? $rb['Image'] : 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400';
                        ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars($rbImg); ?>" style="width:38px; height:38px; object-fit:cover; border-radius:4px;" alt=""></td>
                            <td><?php echo htmlspecialchars($rb['Package_Name']); ?></td>
                            <td><?php echo date("d M Y", strtotime($rb['Travel_Date'])); ?></td>
                            <td><span class="status-badge status-<?php echo strtolower($rb['Status']); ?>"><?php echo $rb['Status']; ?></span></td>
                            <td>$<?php echo htmlspecialchars($rb['Total_Price']); ?></td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr><td colspan="5" style="text-align:center; color:#888;">No recent bookings found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php endif; ?>

            <!-- BOOK TOUR VIEW -->
            <?php elseif ($view === 'book'): 
                $pkgId = intval($_GET["pkg_id"] ?? 1);
                $pQ = mysqli_query($conn, "SELECT p.*, d.Location FROM TOUR_PACKAGE p JOIN DESTINATION d ON p.Destination_ID = d.ID WHERE p.ID = $pkgId");
                $targetPkg = mysqli_fetch_assoc($pQ);
                $bImg = !empty($targetPkg['Image']) ? $targetPkg['Image'] : 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400';
            ?>
                <div class="section-header">
                    <h3>Book Tour</h3>
                </div>
                <div class="two-col-layout">
                    <div class="bordered-box">
                        <form action="touristDashboard.php?view=book" method="POST">
                            <input type="hidden" name="action" value="book_tour">
                            <input type="hidden" name="package_id" value="<?php echo $targetPkg['ID']; ?>">
                            <input type="hidden" name="total_cost" id="hiddenTotalCost" value="<?php echo $targetPkg['Price'] * 2; ?>">

                            <h4 style="margin-bottom: 12px;">Booking Information</h4>
                            
                            <div class="form-group">
                                <label>Travel Date</label>
                                <input type="date" name="travel_date" required value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                            </div>

                            <div class="form-group">
                                <label>Number of Persons</label>
                                <input type="number" id="bookingPersons" name="persons" min="1" max="20" value="2" required>
                            </div>

                            <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:12px; border-radius:4px; margin: 14px 0;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:13px;">
                                    <span>Price per Person:</span>
                                    <strong>$<span id="unitPrice"><?php echo $targetPkg['Price']; ?></span></strong>
                                </div>
                                <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:13px;">
                                    <span>Total Persons:</span>
                                    <strong><span id="totalPersonsCount">2</span></strong>
                                </div>
                                <div style="display:flex; justify-content:space-between; font-size:15px; border-top:1px solid #e2e8f0; padding-top:6px;">
                                    <span>Total Cost:</span>
                                    <strong style="color:#0084ff;" id="totalCostDisplay">$<?php echo $targetPkg['Price'] * 2; ?></strong>
                                </div>
                            </div>

                            <button type="submit" class="btn-primary-blue">Submit Booking</button>
                        </form>
                    </div>

                    <div class="bordered-box">
                        <img src="<?php echo htmlspecialchars($bImg); ?>" alt="Package" style="width:100%; height:160px; object-fit:cover; border-radius:4px; margin-bottom:10px;">
                        <h4 style="font-size:16px;"><?php echo htmlspecialchars($targetPkg['Package_Name']); ?></h4>
                        <p style="font-size:13px; color:#64748b; margin-bottom:6px;"><?php echo htmlspecialchars($targetPkg['Location']); ?></p>
                        <h4 style="color:#0084ff; font-size:15px; margin-bottom:8px;">$<?php echo $targetPkg['Price']; ?> / Person</h4>
                        <p style="font-size:12px; color:#475569; line-height:1.4;"><?php echo htmlspecialchars($targetPkg['Description']); ?></p>
                    </div>
                </div>

            <!-- MY BOOKINGS VIEW -->
            <?php elseif ($view === 'bookings'): ?>
                <div class="section-header">
                    <h3>My Bookings</h3>
                </div>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Tour</th>
                            <th>Package</th>
                            <th>Travel Date</th>
                            <th>Persons</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $myBookings = mysqli_query($conn, "SELECT b.*, p.Package_Name, p.Image, p.ID as pkgId FROM BOOKING b JOIN TOUR_PACKAGE p ON b.Tour_Package_ID = p.ID WHERE b.User_ID = $userId ORDER BY b.ID DESC");
                        if ($myBookings && mysqli_num_rows($myBookings) > 0):
                            while ($mb = mysqli_fetch_assoc($myBookings)):
                                $personsCount = $mb['Persons'] ?? 1;
                                $mbImg = !empty($mb['Image']) ? $mb['Image'] : 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400';
                        ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars($mbImg); ?>" style="width:40px; height:40px; object-fit:cover; border-radius:4px;" alt=""></td>
                            <td><strong><?php echo htmlspecialchars($mb['Package_Name']); ?></strong></td>
                            <td><?php echo date("d M Y", strtotime($mb['Travel_Date'])); ?></td>
                            <td><?php echo htmlspecialchars($personsCount); ?></td>
                            <td>$<?php echo htmlspecialchars($mb['Total_Price']); ?></td>
                            <td><span class="status-badge status-<?php echo strtolower($mb['Status']); ?>"><?php echo $mb['Status']; ?></span></td>
                            <td>
                                <a href="touristDashboard.php?view=reviews&pkg_id=<?php echo $mb['pkgId']; ?>" class="btn-sm" style="background:#f1f5f9; color:#0f172a; text-decoration:none; border:1px solid #ccc; padding:4px 8px; border-radius:3px;">Rate Tour</a>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr><td colspan="7" style="text-align:center; color:#888;">You have not made any bookings yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            <!-- REVIEWS VIEW -->
            <?php elseif ($view === 'reviews'): 
                $pkgId = intval($_GET["pkg_id"] ?? 1);
                $pQ = mysqli_query($conn, "SELECT p.*, d.Location FROM TOUR_PACKAGE p JOIN DESTINATION d ON p.Destination_ID = d.ID WHERE p.ID = $pkgId");
                $targetPkg = mysqli_fetch_assoc($pQ);
                $revImg = !empty($targetPkg['Image']) ? $targetPkg['Image'] : 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=400';
            ?>
                <div class="section-header">
                    <h3>Write a Review</h3>
                </div>
                <div class="two-col-layout">
                    <div class="bordered-box">
                        <form action="touristDashboard.php?view=reviews" method="POST">
                            <input type="hidden" name="action" value="submit_review">
                            <input type="hidden" name="package_id" value="<?php echo $targetPkg['ID']; ?>">
                            <input type="hidden" name="rating" id="ratingValue" value="5">

                            <div class="form-group">
                                <label>Your Rating</label>
                                <div class="star-group">
                                    <span class="star active" data-val="1">★</span>
                                    <span class="star active" data-val="2">★</span>
                                    <span class="star active" data-val="3">★</span>
                                    <span class="star active" data-val="4">★</span>
                                    <span class="star active" data-val="5">★</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Your Review</label>
                                <textarea name="comment" rows="4" placeholder="Write your experience about this tour..." required></textarea>
                            </div>

                            <button type="submit" class="btn-primary-blue">Submit Review</button>
                        </form>
                    </div>

                    <div class="bordered-box">
                        <img src="<?php echo htmlspecialchars($revImg); ?>" alt="Package" style="width:100%; height:160px; object-fit:cover; border-radius:4px; margin-bottom:10px;">
                        <h4><?php echo htmlspecialchars($targetPkg['Package_Name']); ?></h4>
                        <p style="font-size:13px; color:#64748b;"><?php echo htmlspecialchars($targetPkg['Location']); ?></p>
                    </div>
                </div>

            <!-- PROFILE VIEW -->
            <?php elseif ($view === 'profile'): 
                $tData = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM `USER` WHERE ID = $userId"));
            ?>
                <div class="section-header">
                    <h3>My Profile</h3>
                </div>
                <div class="bordered-box" style="max-width: 480px;">
                    <form action="touristDashboard.php?view=profile" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="fullName" value="<?php echo htmlspecialchars($tData['Name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($tData['Email']); ?>" disabled style="background:#f1f5f9;">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($tData['Phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" value="Tourist" disabled style="background:#f1f5f9;">
                        </div>
                        <button type="submit" class="btn-cyan">Update Profile</button>
                    </form>
                </div>

            <!-- PASSWORD VIEW -->
            <?php elseif ($view === 'password'): ?>
                <div class="section-header">
                    <h3>Change Password</h3>
                </div>
                <div class="bordered-box" style="max-width: 480px;">
                    <form action="touristDashboard.php?view=password" method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="currentPassword" placeholder="Enter current password" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="newPassword" placeholder="Enter new password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirmPassword" placeholder="Confirm new password" required>
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