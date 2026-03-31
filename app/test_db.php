
<?php
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=fgcl_db;charset=utf8mb4",
        "thiery",
        "costa12@#"
    );
    echo "✅ Connexion OK !";
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}