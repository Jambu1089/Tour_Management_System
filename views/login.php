<?php
// Auto-display register panel if a registration error was returned
$showRegister = isset($_GET["regErr"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tour Management System - Login</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        body.auth-body {
            background: linear-gradient(rgba(15, 23, 42, 0.45), rgba(15, 23, 42, 0.45)), 
                        url('https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=1600&auto=format&fit=crop&q=80') center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-wrapper {
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25) !important;
            border-radius: 6px;
            background: #ffffff;
        }
        .travel-brand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            background-color: #e0f2fe;
            border-radius: 50%;
            margin-bottom: 8px;
        }
    </style>
</head>
<body class="auth-body">

<div class="auth-wrapper">
    <div class="auth-header">
        <div class="travel-brand-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#00a8ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="2" y1="12" x2="22" y2="12"></line>
                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
            </svg>
        </div>
        <h1>Tour<br>Management<br>System</h1>
    </div>

    <?php if (isset($_GET["successMsg"])): ?>
        <span class="success-msg"><?php echo htmlspecialchars($_GET["successMsg"]); ?></span>
    <?php endif; ?>

    <!-- ================= LOGIN PANEL ================= -->
    <div class="auth-box" id="loginBox" style="<?php echo $showRegister ? 'display: none;' : ''; ?>">
        <h2>LOGIN</h2>
        <form action="../controllers/loginController.php" method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Enter email" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>

            <div style="text-align: right; margin-bottom: 16px;">
                <a href="#" style="font-size: 12px; color: #00a8ff; text-decoration: none;">Forgot Password?</a>
            </div>

            <!-- Login Submission Button -->
            <button type="submit" class="btn-action-blue">Login</button>

            <!-- Text redirect matching wireframe -->
            <div class="auth-redirect-text">
                Don't have an account? <span id="openRegisterBtn" class="link-blue">Register here</span>
            </div>

            <?php if (isset($_GET["loginErr"])): ?>
                <span class="error-msg" style="text-align: center; margin-top: 10px;"><?php echo htmlspecialchars($_GET["loginErr"]); ?></span>
            <?php endif; ?>
        </form>
    </div>

    <!-- ================= REGISTER PANEL (HIDDEN BY DEFAULT) ================= -->
    <div class="auth-box" id="registerBox" style="<?php echo $showRegister ? '' : 'display: none;'; ?>">
        <h2>REGISTER</h2>
        <form action="../controllers/registerController.php" method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullName" placeholder="Enter full name" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="Enter email" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirmPassword" placeholder="Confirm password" required>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="Tourist">Tourist</option>
                    <option value="Tour Guide">Tour Guide</option>
                    <option value="Agency Manager">Agency Manager</option>
                </select>
            </div>

            <!-- Register Submission Button -->
            <button type="submit" class="btn-action-blue">Register</button>

            <!-- Text redirect matching wireframe -->
            <div class="auth-redirect-text">
                Already have an account? <span id="backToLoginBtn" class="link-blue">Login Here</span>
            </div>

            <?php if (isset($_GET["regErr"])): ?>
                <span class="error-msg" style="text-align: center; margin-top: 10px;"><?php echo htmlspecialchars($_GET["regErr"]); ?></span>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
    const loginBox = document.getElementById("loginBox");
    const registerBox = document.getElementById("registerBox");
    const openRegisterBtn = document.getElementById("openRegisterBtn");
    const backToLoginBtn = document.getElementById("backToLoginBtn");

    openRegisterBtn.addEventListener("click", () => {
        loginBox.style.display = "none";
        registerBox.style.display = "block";
    });

    backToLoginBtn.addEventListener("click", () => {
        registerBox.style.display = "none";
        loginBox.style.display = "block";
    });
</script>

</body>
</html>