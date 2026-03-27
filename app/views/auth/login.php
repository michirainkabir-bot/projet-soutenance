<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/connexion.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>Connexion — <?= APP_NAME ?></title>
</head>
<body>

<div class="login-container">

    <div class="logo">
        <img src="<?= BASE_URL ?>/public/images/logo.png" alt="logo" width="170px" height="150px">
    </div>

    <!-- Card -->
    <div class="login-card">
        <h2>Se connecter</h2>

        <?php if (!empty($error)): ?>
            <div style="background:#ff4d4d;color:white;padding:10px;border-radius:6px;margin-bottom:14px;font-size:14px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire connecté au backend PHP -->
        <form action="<?= BASE_URL ?>/app/controllers/AuthController.php?action=login" method="POST">

            <div class="input-group">
                <input type="email" name="email" placeholder="Adresse Email"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="input-group">
                <input type="password" name="password" id="password" placeholder="Mot de passe" required>
                <i class="fa-solid fa-eye eye" onclick="togglePassword()"></i>
            </div>

            <div class="links">
                <a href="#">Mot de passe oublié ?</a>
            </div>

            <button type="submit">Connexion</button>
        </form>
    </div>

</div>

<script>
function togglePassword() {
    const input = document.getElementById("password");
    const icon  = document.querySelector(".eye");
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
}
</script>

</body>
</html>
