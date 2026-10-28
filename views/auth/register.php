<?php $authError = $authError ?? ''; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CIANAN MRO - Create Account</title>
    <link rel="stylesheet" href="/CIANANMRO/public/css/style.css">
</head>
<body class="auth-body">
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-hero">
            <div class="auth-hero-logo">
                <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </div>
            <div class="auth-hero-brand">CIANAN MRO</div>
            <div class="auth-hero-tagline">Maintenance System</div>
            <p class="auth-hero-note">Welcome, new user!</p>
            <div class="auth-hero-foot">Already have access? Head to the login page.</div>
        </div>

        <div class="auth-form-side">
            <form method="post"
                  action="/CIANANMRO/public/index.php?page=register">
                <?php if ($authError): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($authError) ?></div>
                <?php endif; ?>

                <div class="form-field">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required autofocus>
                </div>

                <div class="form-field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Choose a username" required>
                </div>

                <div class="form-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                </div>

                <div class="form-field">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="cianan_user">CIANAN User</option>
                        <option value="production_operator">Production Operator</option>
                        <option value="maintenance_technician">Maintenance Technician</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary auth-submit">Create Account</button>

                <div class="auth-footer">
                    Already have an account? <a href="/CIANANMRO/public/index.php?page=login">Login</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
