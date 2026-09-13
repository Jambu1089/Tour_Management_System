<?php
require_once "../models/usersModel.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? "");
    $pass  = trim($_POST["password"] ?? "");

    $hasErr   = false;
    $emailErr = "";
    $passErr  = "";

    if (empty($email)) {
        $hasErr = true;
        $emailErr = "Email is required";
    }
    if (empty($pass)) {
        $hasErr = true;
        $passErr = "Password is required";
    }

    if ($hasErr) {
        header("Location: ../views/login.php?emailErr=" . urlencode($emailErr) . "&passErr=" . urlencode($passErr));
        exit();
    }

    $user = authenticateUser($email, $pass);
    if ($user) {
        session_start();
        $_SESSION["userId"] = $user["ID"];
        $_SESSION["name"]   = $user["Name"];
        $_SESSION["role"]   = $user["Role"];

        switch ($user["Role"]) {
            case "Admin":
                header("Location: ../views/admin/adminDashboard.php");
                break;
            case "Agency Manager":
                header("Location: ../views/manager/managerDashboard.php");
                break;
            case "Tour Guide":
                header("Location: ../views/guide/guideDashboard.php");
                break;
            case "Tourist":
                header("Location: ../views/tourist/touristDashboard.php");
                break;
            default:
                header("Location: ../views/login.php?notFoundErr=Invalid Role");
        }
        exit();
    } else {
        header("Location: ../views/login.php?notFoundErr=Invalid Email or Password");
        exit();
    }
}
?>