<?php 

include(ROOT_PATH . "/app/database/db.php");
include(ROOT_PATH . "/app/helpers/middleware.php"); 
include(ROOT_PATH . "/app/helpers/validateUser.php");
// include(ROOT_PATH . "/app/controllers/authors.php"); 

$table = 'users';
$tableComment = 'comment';

$admin_users = selectAll($table);
$comment = selectAll($tableComment);

$errors = array();
$id = '';
$username = '';
$admin = '';
$email = '';
$password = '';
$passwordConf = '';

function loginUser($user) 
{
    $_SESSION['id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['admin'] = $user['admin'];
    $_SESSION['message'] = 'You are now logged in';
    $_SESSION['type'] = 'success';
    
    if ($_SESSION['admin']) {
        header('location: ' . BASE_URL . '/admin/dashboard.php');
    } else {
        header('location: ' . BASE_URL . '/Index.php');
    }
    exit();
}


if (isset($_POST['register-btn']) || isset($_POST['create-admin'])) {
    $errors = validateUser($_POST);

    if (count($errors) === 0) {
        unset($_POST['register-btn'], $_POST['passwordConf'], $_POST['create-admin']);
        $_POST['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);

        if (isset($_POST['admin'])) {
            $_POST['admin'] = 1;
            $user_id = create($table, $_POST);
            $_SESSION['message'] = "Admin user created";
            $_SESSION['type'] = "success";
            header('location: ' . BASE_URL . '/admin/users/index.php');
            exit();
        } else {
            $_POST['admin'] = 0;
            $user_id = create($table, $_POST);
            $user = selectOne($table, ['id' => $user_id]);
            loginUser($user);
        }
    } else {
        $username = $_POST['username'];
        $admin = isset($_POST['admin']) ? 1 : 0;
        $email = $_POST['email'];
        $password = $_POST['password'];
        $passwordConf = $_POST['passwordConf'];
    }
}

if (isset($_POST['update-user'])) {
    adminOnly();
    $errors = validateUser($_POST);

    if (count($errors) === 0) {
        $id = $_POST['id'];
        unset($_POST['passwordConf'], $_POST['update-user'], $_POST['id']);
        $_POST['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $_POST['admin'] = isset($_POST['admin']) ? 1 : 0;
        $count = update($table, $id, $_POST);
        $_SESSION['message'] = "Admin user updated";
        $_SESSION['type'] = "success";
        header('location: ' . BASE_URL . '/admin/users/index.php');
        exit();
    } else {
        $username = $_POST['username'];
        $admin = isset($_POST['admin']) ? 1 : 0;
        $email = $_POST['email'];
        $password = $_POST['password'];
        $passwordConf = $_POST['passwordConf'];
    }
}

// if (isset($_GET['id'])) {
//     $user = selectOne($table, ['id' => $_GET['id']]);
//     $id = $user['id'];
//     $username = $user['username'];
//     $admin = $user['admin'] == 1 ? 1 : 0;
//     $email = $user['email'];
// }

if (isset($_POST['login-btn'])) {
    $errors = validateLogin($_POST);

    if (count($errors) === 0) {
        $user = selectOne($table, ['username' => $_POST['username']]);
        
        if ($user && password_verify($_POST['password'], $user['password'])) {
            loginUser($user);
        } else {
            array_push($errors, 'Wrong credentials');
        }
    }

    $username = $_POST['username'];
    $password = $_POST['password'];

}

if (isset($_GET['delete_id'])) {
    adminOnly();
    $count = delete($table, $_GET['delete_id']);
    $_SESSION['message'] = "Admin user deleted";
    $_SESSION['type'] = "success";
    header('location: ' . BASE_URL . '/admin/users/index.php');
    exit();
}

function validateSendMessage($data) {
    $errors = array();

    // Example validation, you can customize this based on your requirements
    if (empty($data['message'])) {
        $errors[] = 'Message is required';
    }

    return $errors;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn-send'])) {
    $errors = validateSendMessage($_POST);

    if (count($errors) === 0) {
        // Retrieve user_id from the session
        $user_id = isset($_SESSION['id']) ? $_SESSION['id'] : null;

        if ($user_id) {
            // Send the message
            $result = sendMessage($_POST['message'], $user_id);

            if ($result) {
                $_SESSION['message'] = "Message sent successfully";
                $_SESSION['type'] = "success";
                header('location: ' . BASE_URL . '/single.php');  // Redirect to a thank-you page or another appropriate page
                exit();
            } else {
                array_push($errors, 'Failed to send message. Please try again.');
            }
        } else {
            // Redirect to the login page if user_id is not set
            header('location: ' . BASE_URL . '/login.php');
            exit();
        }
    }
}

if (isset($_POST['update-btn'])) {
    $errors = validateUser($_POST);

    if (count($errors) === 0) {
        // Get the user ID from the session
        $id = $_SESSION['id'];

        // Remove unnecessary fields and hash the password if it's being updated
        unset($_POST['passwordConf'], $_POST['update-btn'], $_POST['id']);
        $_POST['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Set the admin flag based on whether it is checked in the form
        // $_POST['admin'] = isset($_POST['admin']) ? 1 : 0;

        // Update the user information
        $count = update($table, $id, $_POST);

        // Redirect with a success message
        $_SESSION['message'] = "Profile updated successfully";
        $_SESSION['type'] = "success";
        header('location: ' . BASE_URL . '/Index.php');  // Adjust the redirection URL as needed
        exit();
    } else {
        // Set the form values for repopulating the form in case of errors
        $username = $_POST['username'];
        // $admin = isset($_POST['admin']) ? 1 : 0;
        $email = $_POST['email'];
        $password = $_POST['password'];
        $passwordConf = $_POST['passwordConf'];
    }
}
