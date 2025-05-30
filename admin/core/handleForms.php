<?php  
require_once 'dbConfig.php';
require_once 'models.php';

if (isset($_POST['insertNewUserBtn'])) {
	$username = trim($_POST['username']);
	$first_name = trim($_POST['first_name']);
	$last_name = trim($_POST['last_name']);
	$password = trim($_POST['password']);
	$confirm_password = trim($_POST['confirm_password']);

	if (!empty($username) && !empty($first_name) && !empty($last_name) && !empty($password) && !empty($confirm_password)) {

		if ($password == $confirm_password) {

			$insertQuery = insertNewUser($pdo, $username, $first_name, $last_name, password_hash($password, PASSWORD_DEFAULT));
			$_SESSION['message'] = $insertQuery['message'];

			if ($insertQuery['status'] == '200') {
				$_SESSION['message'] = $insertQuery['message'];
				$_SESSION['status'] = $insertQuery['status'];
				header("Location: ../login.php");
			}

			else {
				$_SESSION['message'] = $insertQuery['message'];
				$_SESSION['status'] = $insertQuery['status'];
				header("Location: ../register.php");
			}

		}
		else {
			$_SESSION['message'] = "Please make sure both passwords are equal";
			$_SESSION['status'] = '400';
			header("Location: ../register.php");
		}

	}

	else {
		$_SESSION['message'] = "Please make sure there are no empty input fields";
		$_SESSION['status'] = '400';
		header("Location: ../register.php");
	}
}

if (isset($_POST['loginUserBtn'])) {
	$username = trim($_POST['username']);
	$password = trim($_POST['password']);

	if (!empty($username) && !empty($password)) {

		$loginQuery = checkIfUserExists($pdo, $username);
		
		if ($loginQuery['result']) {
			$userIDFromDB = $loginQuery['userInfoArray']['user_id'];
			$usernameFromDB = $loginQuery['userInfoArray']['username'];
			$passwordFromDB = $loginQuery['userInfoArray']['password'];
			$isAdminStatusFromDB = $loginQuery['userInfoArray']['is_admin'];

			// Check if the user is an admin
			if ($isAdminStatusFromDB == 0) {
				$_SESSION['message'] = "You cannot login as an employee in the admin portal";
				$_SESSION['status'] = "400";
				header("Location: ../login.php");
				exit;
			}

			if (password_verify($password, $passwordFromDB)) {
				$_SESSION['user_id'] = $userIDFromDB;
				$_SESSION['username'] = $usernameFromDB;
				$_SESSION['is_admin'] = $isAdminStatusFromDB;
				header("Location: ../index.php");
			}
			else {
				$_SESSION['message'] = "Username/password invalid";
				$_SESSION['status'] = "400";
				header("Location: ../login.php");
			}
		}
		else {
			$_SESSION['message'] = "Username/password invalid";
			$_SESSION['status'] = "400";
			header("Location: ../login.php");
		}
	}
	else {
		$_SESSION['message'] = "Please make sure there are no empty input fields";
		$_SESSION['status'] = '400';
		header("Location: ../login.php");
	}
}

if (isset($_GET['logoutUserBtn'])) {
	unset($_SESSION['username']);
	header("Location: ../login.php");
}

if (isset($_POST['updateLeaveStatus'])) {
	$leave_id = $_POST['leave_id'];
	$leave_status = $_POST['leave_status'];
	
	// Update leave status
	if (updateLeaveStatus($pdo, $leave_status, $leave_id)) {
		// Get the user_id of the employee who filed the leave
		$sql = "SELECT user_id FROM leaves WHERE leave_id = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$leave_id]);
		$result = $stmt->fetch();
		$employee_id = $result['user_id'];
		
		// Create notification for the employee
		createLeaveStatusNotification($pdo, $employee_id, $leave_id, $leave_status);
	}
}