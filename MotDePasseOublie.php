<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/Methodes_Connexions.php';
require_once __DIR__ . '/PasswordReset_lib.php';
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
if (empty($_SESSION['coreen_reset_csrf'])) $_SESSION['coreen_reset_csrf'] = bin2hex(random_bytes(32));
$submitted = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['coreen_reset_csrf'], $token)) {
        http_response_code(403); exit('Jeton invalide, rechargez la page.');
    }
    $email = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $error = 'Saisissez une adresse e-mail valide.';
    } else {
        $submitted = true;
        try {
            $cfg = coreenResetSettings();
            if (!coreenResetIsSecureRequest($cfg)) {
                throw new RuntimeException('HTTPS obligatoire pour la réinitialisation.');
            }
            if (coreenResetRateLimit($bdd, $email)) {
                $q = $bdd->prepare('SELECT `User_id`, `Mail1`, `Valider` FROM `utilisateurs`
                    WHERE LOWER(TRIM(`Mail1`)) = :email OR LOWER(TRIM(`Mail2`)) = :email LIMIT 1');
                $q->execute([':email' => $email]);
                $user = $q->fetch(PDO::FETCH_ASSOC);
                if ($user) coreenResetIssue($bdd, $user, $email);
            }
        } catch (Throwable $e) {
            error_log('[coreen-reset] Demande de lien : ' . $e->getMessage());
            // Même réponse pour les comptes existants et inexistants.
        }
    }
}
?>
<!doctype html><html lang="fr"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Mot de passe oublié · Vocabulaire coréen</title>
<link rel="icon" href="image/logo.jpeg" type="image/jpeg"><link rel="stylesheet" href="connexion.css?v=3">
</head><body class="login-page"><div class="login-shell">
<header class="login-topbar"><a class="login-brand" href="Connexion.php"><span class="login-brand__icon">가</span><span>Vocabulaire <strong>coréen</strong></span></a><span class="login-topbar__note">Votre espace d'apprentissage</span></header>
<main class="login-layout">
<section class="login-story"><div class="login-story__inner"><div class="login-eyebrow"><span class="login-eyebrow__dot"></span> APPRENDRE LE CORÉEN</div><h1>On reprend<br>où vous en étiez<span class="login-title-dot">.</span></h1><p>Retrouvez l'accès à votre vocabulaire et à vos exercices en toute simplicité.</p><div class="login-story__tags"><span>한글 · Hangeul</span><span>Quiz interactif</span></div></div><div class="login-story__symbol" aria-hidden="true">가</div></section>
<section class="login-panel"><div class="login-panel__mark" aria-hidden="true">안녕!</div><div class="login-panel__eyebrow">RÉCUPÉRATION DU COMPTE</div><h2>Mot de passe oublié ?</h2>
<?php if ($submitted): ?>
<p class="login-panel__subtitle" role="status">Si un compte validé correspond à cette adresse, un e-mail contenant un lien valable 30 minutes a été envoyé. Vérifiez également les courriers indésirables.</p>
<?php else: ?>
<p class="login-panel__subtitle">Saisissez l’adresse e-mail enregistrée dans votre compte.</p>
<?php if ($error): ?><div class="login-feedback login-feedback--error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<form method="post" class="reset-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['coreen_reset_csrf'], ENT_QUOTES, 'UTF-8') ?>"><div class="login-field"><label for="email">Adresse e-mail</label><input id="email" type="email" name="email" autocomplete="email" required maxlength="255" placeholder="adresse@exemple.fr" autofocus></div><button class="login-submit" type="submit">Envoyer le lien <span aria-hidden="true">→</span></button></form>
<?php endif; ?>
<div class="login-panel__separator"><span>ou</span></div><p class="login-signup"><a href="Connexion.php">← Revenir à la connexion</a></p>
</section></main><footer class="login-footer">한국어 <span>·</span> Un peu de vocabulaire chaque jour fait toute la différence.</footer></div></body></html>
