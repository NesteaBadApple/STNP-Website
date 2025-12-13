<?php
session_start();
require_once 'config.php'; // must set $conn as a mysqli connection

// Helper: redirect and exit
function redirect($url) {
    header("Location: $url");
    exit();
}

// Clear previous errors (optional)
unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['active_form']);

// ---------- REGISTER ----------
if (isset($_POST['register'])) {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation
    if ($name === '' || $email === '' || $password === '') {
        $_SESSION['register_error'] = 'Please fill all required fields.';
        $_SESSION['active_form'] = 'register';
        redirect('login.php');
    }

    // Check if email exists (prepared statement)
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        $_SESSION['register_error'] = 'Database error (check prepare).';
        $_SESSION['active_form'] = 'register';
        redirect('login.php');
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        $_SESSION['register_error'] = 'Email is already registered!';
        $_SESSION['active_form'] = 'register';
        redirect('login.php');
    }
    $stmt->close();

    // Insert new user
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $ins = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    if (!$ins) {
        $_SESSION['register_error'] = 'Database error (insert prepare).';
        $_SESSION['active_form'] = 'register';
        redirect('login.php');
    }
    $ins->bind_param('sss', $name, $email, $hashed);

    if ($ins->execute()) {
        // Auto-login after registration: store id and username in session
        $new_user_id = $ins->insert_id;
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['username'] = $name; // matches home.php expectation

        $ins->close();
        redirect('home.php');
    } else {
        $ins->close();
        $_SESSION['register_error'] = 'Could not create account. Try again.';
        $_SESSION['active_form'] = 'register';
        redirect('login.php');
    }
}

// ---------- LOGIN ----------
if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $_SESSION['login_error'] = 'Please enter both email and password.';
        $_SESSION['active_form'] = 'login';
        redirect('login.php');
    }

    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    if (!$stmt) {
        $_SESSION['login_error'] = 'Database error (check prepare).';
        $_SESSION['active_form'] = 'login';
        redirect('login.php');
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Successful login: set the session keys home.php expects
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['name'];

            $stmt->close();
            redirect('home.php');
        } else {
            // wrong password
            $_SESSION['login_error'] = 'Incorrect email or password';
            $_SESSION['active_form'] = 'login';
            $stmt->close();
            redirect('login.php');
        }
    } else {
        // no user
        $_SESSION['login_error'] = 'Incorrect email or password';
        $_SESSION['active_form'] = 'login';
        $stmt->close();
        redirect('login.php');
    }
}

// If reached without POST, send to login
redirect('login.php');
