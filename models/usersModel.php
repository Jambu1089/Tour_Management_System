<?php
require_once __DIR__ . "/../config/dbConnect.php";

function authenticateUser($email, $password) {
    $conn = dbConnection();
    $sql = "SELECT * FROM `USER` WHERE `Email` = ? AND `Password` = ? AND `Status` = 'Active'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $email, $password);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
    mysqli_close($conn);
    return $user;
}

function registerUser($name, $email, $password, $role) {
    $conn = dbConnection();
    $checkSql = "SELECT ID FROM `USER` WHERE `Email` = ?";
    $stmtCheck = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($stmtCheck, 's', $email);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    if (mysqli_num_rows($resCheck) > 0) {
        mysqli_close($conn);
        return false;
    }

    $sql = "INSERT INTO `USER` (`Name`, `Email`, `Password`, `Role`, `Status`) VALUES (?, ?, ?, ?, 'Active')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $password, $role);
    $success = mysqli_stmt_execute($stmt);
    mysqli_close($conn);
    return $success;
}

function updateUserProfile($id, $name, $phone) {
    $conn = dbConnection();
    $sql = "UPDATE `USER` SET `Name` = ?, `Phone` = ? WHERE `ID` = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssi', $name, $phone, $id);
    $res = mysqli_stmt_execute($stmt);
    mysqli_close($conn);
    return $res;
}

function changeUserPassword($id, $oldPass, $newPass) {
    $conn = dbConnection();
    $sqlCheck = "SELECT `Password` FROM `USER` WHERE `ID` = ?";
    $stmtCheck = mysqli_prepare($conn, $sqlCheck);
    mysqli_stmt_bind_param($stmtCheck, 'i', $id);
    mysqli_stmt_execute($stmtCheck);
    $res = mysqli_stmt_get_result($stmtCheck);
    $user = mysqli_fetch_assoc($res);
    
    if ($user && $user['Password'] === $oldPass) {
        $sqlUp = "UPDATE `USER` SET `Password` = ? WHERE `ID` = ?";
        $stmtUp = mysqli_prepare($conn, $sqlUp);
        mysqli_stmt_bind_param($stmtUp, 'si', $newPass, $id);
        $done = mysqli_stmt_execute($stmtUp);
        mysqli_close($conn);
        return $done;
    }
    mysqli_close($conn);
    return false;
}
?>