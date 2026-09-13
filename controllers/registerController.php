<?php
require_once "../models/usersModel.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST["fullName"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $pass     = trim($_POST["password"] ?? "");
    $confirm  = trim($_POST["confirmPassword"] ?? "");
    $role     = trim($_POST["role"] ?? "");

    $hasErr = false;
    $regErr = "";

    if (empty($name) || empty($email) || empty($pass) || empty($role)) {
        $hasErr = true;
        $regErr = "All fields are required";
    } elseif ($pass !== $confirm) {
        $hasErr = true;
        $regErr = "Passwords do not match";
    }

    if ($hasErr) {
        header("Location: ../views/login.php?regErr=" . urlencode($regErr));
        exit();
    }

    $status = registerUser($name, $email, $pass, $role);
    if ($status) {
        header("Location: ../views/login.php?successMsg=Account registered successfully. Please login.");
    } else {
        header("Location: ../views/login.php?regErr=Registration failed. Email might already exist.");
    }
    exit();
}
?>