<?php
session_start();
include "php/config/config.php";

if(isset($_POST['login-btn'])) {
    function validate($data){
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    $uname = validate($_POST['uname']);
    $pass = validate($_POST['pass']);

    if(empty($uname)) {
        header("Location: login.php?error=User Name is required");
        exit();
    } else if(empty($pass)) {
        header("Location: login.php?error=Password is required");
        exit();
    } else {
        // 1️⃣ Check in user table first
        $sql = "SELECT * FROM user WHERE user_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $uname);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $hashedPassword = $row['user_pass'];

            if($row['user_accountStat'] === "Pending") {
                header("Location: login.php?error=Account is still pending approval");
                exit();
            }

            if(password_verify($pass, $hashedPassword)) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['user_type'] = $row['user_type'];

                if($row['user_type'] === "Admin"){
                    header("Location: dashboard");
                } else if($row['user_type'] === "Dispatcher"){
                    header("Location: dispatch-dashboard");
                } else if($row['user_type'] === "Shop"){
                    header("Location: shop-dashboard");
                } else if($row['user_type'] === "User"){
                    header("Location: hr-dashboard");
                } else if($row['user_type'] === "Rescue"){
                    header("Location: shop-dashboard");
                } else if($row['user_type'] === "HR-Admin"){
                    header("Location: hra-dashboard");
                } else if($row['user_type'] === "Visual"){
                    header("Location: visual-dashboard");
                } else if($row['user_type'] === "Gate-Guard"){
                    header("Location: gate-dashboard");
                } else if($row['user_type'] === "Maintenance"){
                    header("Location: maintenance-dashboard");
                }
                exit();
            } else {
                header("Location: login.php?error=Incorrect username or password");
                exit();
            }
        } else {
            // 2️⃣ If not found in user table, check drivers table
            $sqlDriver = "SELECT * FROM drivers WHERE driver_uname = ?";
            $stmtDriver = $conn->prepare($sqlDriver);
            $stmtDriver->bind_param("s", $uname);
            $stmtDriver->execute();
            $resultDriver = $stmtDriver->get_result();

            if($resultDriver->num_rows === 1) {
                $driver = $resultDriver->fetch_assoc();
                $hashedPassword = $driver['driver_pass']; // must be hashed like in user table

                if($driver['driver_account_status'] === "Pending") {
                    header("Location: login.php?error=Driver account is still pending approval");
                    exit();
                }

                if(password_verify($pass, $hashedPassword)) {
                    $_SESSION['user_type'] = "Driver";
                    $_SESSION['user_id'] = $driver['driver_id'];
                    $_SESSION['user_name'] = $driver['driver_uname'];
                    $_SESSION['user_rfid'] = $driver['driver_rfid'];

                    // Redirect driver to their dashboard
                    header("Location: driver-dashboard");
                    exit();
                } else {
                    header("Location: login.php?error=Incorrect username or password");
                    exit();
                }
            } else {
                // Not found in either table
                header("Location: login.php?error=Incorrect username or password");
                exit();
            }
        }
    }
}
?>
