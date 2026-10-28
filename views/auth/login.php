<?php $authError = $authError ?? ''; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CIANAN MRO - Login</title>
    <link rel="stylesheet" href="/CIANANMRO/public/css/style.css">
</head>
<body class="auth-body">
<div class="auth-wrapper">
    <form class="auth-card" method="post"
          action="/CIANANMRO/public/index.php?page=login">
        <div class="auth-brand">CIANAN MRO</div>
        <div class="auth-sub">Maintenance Inventory System</div>

        <?php if ($authError): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($authError) ?></div>
        <?php endif; ?>

        <div class="form-field">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Username" required autofocus>
        </div>
        <div class="form-field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Password" required>
        </div>
        <button type="submit" class="btn btn-primary auth-submit">Login</button>

        <div class="auth-footer">
            No account yet? <a href="/CIANANMRO/public/index.php?page=register">Create Account</a>
        </div>
    </form>
</div>
</body>
</html>
