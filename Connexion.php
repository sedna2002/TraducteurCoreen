<?php
// Connexion : présentation harmonisée avec l'accueil Vocabulaire coréen.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/Methodes_Connexions.php';

$statut = ConnexionUser();
if ($statut !== UserStatut::NonConnecte && $statut !== UserStatut::NonValider) {
    header('Location: index.php', true, 302);
    exit;
}
$enAttente = ($statut === UserStatut::NonValider);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#14213d">
    <title>Se connecter · Vocabulaire coréen</title>
    <link rel="icon" href="image/logo.jpeg" type="image/jpeg">
    <link rel="stylesheet" href="connexion.css?v=2">
</head>
<body class="login-page">
    <div class="login-shell">
        <header class="login-topbar">
            <a class="login-brand" href="Connexion.php" aria-label="Vocabulaire coréen, accueil de connexion">
                <span class="login-brand__icon" aria-hidden="true">가</span>
                <span>Vocabulaire <strong>coréen</strong></span>
            </a>
            <span class="login-topbar__note"><span aria-hidden="true" class="login-online-dot"></span> Votre espace d'apprentissage</span>
        </header>

        <main class="login-layout">
            <section class="login-story" aria-labelledby="welcome-title">
                <div class="login-story__inner">
                    <div class="login-eyebrow"><span class="login-eyebrow__dot"></span> APPRENDRE LE CORÉEN</div>
                    <h1 id="welcome-title">Le coréen,<br>un mot à la fois<span class="login-title-dot">.</span></h1>
                    <p>Retrouvez votre vocabulaire, progressez chaque jour et entraînez-vous à votre rythme.</p>
                    <div class="login-story__tags" aria-label="Fonctionnalités">
                        <span>한글 · Hangeul</span>
                        <span>Écoute et prononciation</span>
                        <span>Quiz interactif</span>
                    </div>
                </div>
                <div class="login-story__symbol" aria-hidden="true">가</div>
                <div class="login-story__footer"><span aria-hidden="true">✦</span> Quelques minutes par jour, beaucoup de progrès.</div>
            </section>

            <section class="login-panel" aria-labelledby="signin-title">
                <div class="login-panel__mark" aria-hidden="true">안녕!</div>
                <div class="login-panel__eyebrow">BON RETOUR PARMI NOUS</div>
                <h2 id="signin-title">Heureux de vous retrouver</h2>
                <p class="login-panel__subtitle">Connectez-vous pour reprendre votre apprentissage.</p>

                <?php if ($enAttente): ?>
                    <div class="login-feedback login-feedback--warning" role="status">
                        Votre compte est en attente de validation par un administrateur.
                    </div>
                <?php endif; ?>

                <form id="loginForm" method="post" action="Connexion_Verification.php" novalidate>
                    <div class="login-field">
                        <label for="login">Identifiant</label>
                        <input id="login" name="login" type="text" autocomplete="username" required maxlength="100" placeholder="Votre identifiant" autofocus>
                    </div>

                    <div class="login-field">
                        <div class="login-field__label-row">
                            <label for="PW">Mot de passe</label>
                            <button class="login-show-password" type="button" id="togglePassword" aria-controls="PW" aria-pressed="false">Afficher</button>
                        </div>
                        <input id="PW" name="password" type="password" autocomplete="current-password" required placeholder="Votre mot de passe">
                    </div>

                    <div id="loginFeedback" class="login-feedback login-feedback--error" role="alert" aria-live="polite" hidden></div>

                    <button id="connect" type="submit" class="login-submit">
                        <span id="connectLabel">Se connecter</span><span aria-hidden="true">→</span>
                    </button>
                </form>

                <p class="reset-forgot-link"><a href="MotDePasseOublie.php">Mot de passe oublié ?</a></p>

                <div class="login-panel__separator"><span>ou</span></div>
                <p class="login-signup">Vous n'avez pas encore de compte ?<br>
                    <a href="CreationUser.php">Créer mon compte <span aria-hidden="true">↗</span></a>
                </p>
            </section>
        </main>

        <footer class="login-footer">한국어 <span aria-hidden="true">·</span> Un peu de vocabulaire chaque jour fait toute la différence.</footer>
    </div>
    <script src="md5.js"></script>
    <script src="connexion.js?v=2"></script>
</body>
</html>
