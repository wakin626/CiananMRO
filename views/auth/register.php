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
    <form class="auth-card" method="post"
          action="/CIANANMRO/public/index.php?page=register">
        <div class="auth-brand">CIANAN MRO</div>
        <div class="auth-sub">Create Account</div>

        <?php if ($authError): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($authError) ?></div>
        <?php endif; ?>

        <div class="form-field">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" placeholder="Full Name" required autofocus>
        </div>
        <div class="form-field">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Username" required>
        </div>
        <div class="form-field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Password" required>
        </div>
        <div class="form-field">
            <label for="role">Role</label>
            <select id="role" name="role" required>
                <option value="cianan_user">Cianan User</option>
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
</body>
</html>
