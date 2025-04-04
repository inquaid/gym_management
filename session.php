<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role']);
}

// Function to check if user has admin role
function isAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'admin';
}

// Function to check if user has staff role
function isStaff() {
    return isLoggedIn() && $_SESSION['role'] === 'staff';
}

// Function to check if user has member role
function isMember() {
    return isLoggedIn() && $_SESSION['role'] === 'member';
}

// Function to require admin role
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: ../index.php");
        exit;
    }
}

// Function to require staff role
function requireStaff() {
    if (!isStaff()) {
        header("Location: ../index.php");
        exit;
    }
}

// Function to require member role
function requireMember() {
    if (!isMember()) {
        header("Location: ../index.php");
        exit;
    }
}

// Function to require any logged in user
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../index.php");
        exit;
    }
}

// Function to logout
function logout() {
    session_unset();
    session_destroy();
    header("Location: ../index.php");
    exit;
}

// Function to set user session
function setUserSession($user_id, $role, $username) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['role'] = $role;
    $_SESSION['username'] = $username;
    $_SESSION['last_activity'] = time();
    $_SESSION['last_regeneration'] = time();
}

// Function to get user role
function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

// Function to check session timeout (30 minutes) and redirect if needed
function checkSessionTimeout() {
    $timeout = 30 * 60; // 30 minutes
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_unset();
        session_destroy();
        
        // Only redirect if headers haven't been sent yet
        if (!headers_sent()) {
            header("Location: index.php?msg=timeout");
            exit();
        } else {
            echo "<script>window.location.href = 'index.php?msg=timeout';</script>";
            exit();
        }
    }
    
    $_SESSION['last_activity'] = time();
    return false;
}

// Function to regenerate session ID periodically
function regenerateSession() {
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    }
    
    $regeneration_time = 30 * 60; // 30 minutes
    
    if (time() - $_SESSION['last_regeneration'] > $regeneration_time) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Function to clear user session data
function clearUserSession() {
    session_unset();
    session_destroy();
}

// Function to check if user has required role
function requireRole($required_role) {
    if (!isLoggedIn() || getUserRole() !== $required_role) {
        if (!headers_sent()) {
            header("Location: index.php?msg=unauthorized");
        } else {
            echo "<script>window.location.href = 'index.php?msg=unauthorized';</script>";
        }
        exit();
    }
}

// Apply session security measures
regenerateSession();
checkSessionTimeout();
?> 