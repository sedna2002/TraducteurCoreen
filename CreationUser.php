<?php
session_start();
require_once __DIR__ . '/Methodes_Connexions.php';
if (empty($_SESSION['coreen_account_csrf'])) $_SESSION['coreen_account_csrf'] = bin2hex(random_bytes(32));

$modification = ConnexionUser() != UserStatut::NonConnecte;
$user = $modification ? ($_SESSION['Connexion_User'] ?? []) : [];
function account_value($user, $name) {
    return htmlspecialchars((string)($user[$name] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function account_checked($user, $name) {
    return !empty($user[$name]) ? 'checked' : '';
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#14213e">
    <title><?= $modification ? 'Mon compte' : 'Créer un compte' ?> · Vocabulaire coréen</title>
    <link rel="icon" type="image/jpeg" href="image/logo.jpeg">
    <link rel="stylesheet" href="creation-user.css?v=1">
</head>
<body class="account-page">
<div class="account-shell">
    <header class="account-header">
        <a class="account-brand" href="<?= $modification ? 'index.php' : 'Connexion.php' ?>">
            <span class="account-brand__icon" aria-hidden="true">가</span>
            <span>Vocabulaire <strong>coréen</strong></span>
        </a>
        <a class="account-return" href="<?= $modification ? 'index.php' : 'Connexion.php' ?>">
            <span aria-hidden="true">←</span> <?= $modification ? 'Retour à l’accueil' : 'Se connecter' ?>
        </a>
    </header>

    <main class="account-layout">
        <section class="account-intro" aria-labelledby="page-title">
            <span class="account-kicker"><span class="account-kicker__dot"></span> APPRENDRE LE CORÉEN</span>
            <h1 id="page-title"><?= $modification ? 'Votre espace personnel' : 'Bienvenue dans votre apprentissage' ?><span class="account-accent">.</span></h1>
            <p><?= $modification
                ? 'Mettez à jour vos informations et gérez vos options de contact.'
                : 'Créez votre compte pour retrouver vos exercices, explorer les thèmes et enrichir votre vocabulaire.' ?></p>
            <div class="account-illustration" aria-hidden="true">한<br>글</div>
            <div class="account-intro__footnote">À votre rythme, un mot après l’autre.</div>
        </section>

        <section class="account-panel" aria-labelledby="form-title">
            <div class="account-panel__head">
                <span class="account-eyebrow"><?= $modification ? 'PARAMÈTRES' : 'INSCRIPTION' ?></span>
                <h2 id="form-title"><?= $modification ? 'Mettre à jour mon compte' : 'Créer mon compte' ?></h2>
                <p><?= $modification ? 'Modifiez les informations nécessaires.' : 'Quelques informations suffisent pour commencer.' ?></p>
            </div>

            <?php if ($modification): ?>
                <div class="account-existing-session">
                    <?php ConnexionUser_Detail(); ?>
                </div>
            <?php endif; ?>

            <form id="form1" data-mode="<?= $modification ? 'update' : 'create' ?>" novalidate>
                <input type="hidden" id="account_csrf_token" value="<?= htmlspecialchars($_SESSION['coreen_account_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="account-fields">
                    <div class="account-field account-field--wide">
                        <label for="login">Identifiant <span class="required">*</span></label>
                        <input id="login" name="login" type="text" autocomplete="username" maxlength="100" required
                            value="<?= account_value($user, 'Login') ?>" <?= $modification ? 'readonly' : '' ?>
                            placeholder="Choisissez un identifiant">
                    </div>

                    <?php if (!$modification): ?>
                        <div class="account-field account-field--wide">
                            <label for="PW">Mot de passe <span class="required">*</span></label>
                            <input id="PW" name="password" type="password" autocomplete="new-password" minlength="8" required placeholder="Au moins 8 caractères">
                        </div>
                        <div class="account-field account-field--wide">
                            <label for="PW_confirme">Confirmer le mot de passe <span class="required">*</span></label>
                            <input id="PW_confirme" name="password-confirm" type="password" autocomplete="new-password" minlength="8" required placeholder="Saisissez à nouveau le mot de passe">
                            <span class="account-field__hint" id="PW_correspondent" aria-live="polite"></span>
                        </div>
                        <label class="account-check account-field--wide" for="PW_afficher_id">
                            <input type="checkbox" id="PW_afficher_id">
                            <span>Afficher les mots de passe</span>
                        </label>
                    <?php else: ?>
                        <input type="hidden" id="PW" value="">
                        <input type="hidden" id="PW_confirme" value="">
                    <?php endif; ?>

                    <div class="account-field">
                        <label for="prenom">Prénom <span class="required">*</span></label>
                        <input id="prenom" name="prenom" type="text" autocomplete="given-name" maxlength="150" required
                            value="<?= account_value($user, 'Prenom') ?>" placeholder="Votre prénom">
                    </div>
                    <div class="account-field">
                        <label for="nom">Nom <span class="required">*</span></label>
                        <input id="nom" name="nom" type="text" autocomplete="family-name" maxlength="150" required
                            value="<?= account_value($user, 'Nom') ?>" placeholder="Votre nom">
                    </div>
                </div>

                <details class="account-advanced">
                    <summary>
                        <span class="account-advanced__symbol" aria-hidden="true">⚙</span>
                        <span class="account-advanced__title">Options avancées <small>Facultatif · e-mail, SMS et API</small></span>
                        <span class="account-advanced__chevron" aria-hidden="true">⌄</span>
                    </summary>
                    <div class="account-advanced__inner">
                        <p class="account-help">Vous pouvez remplir ces paramètres plus tard depuis votre compte.</p>
                        <div class="account-fields">
                            <div class="account-field account-field--wide">
                                <label for="mail1">Adresse e-mail principale</label>
                                <div class="account-with-action">
                                    <input id="mail1" type="email" autocomplete="email" value="<?= account_value($user, 'Mail1') ?>" placeholder="adresse@exemple.fr">
                                    <?php if ($modification): ?><button type="button" class="account-test" data-test="mail1">Tester</button><?php endif; ?>
                                </div>
                                <label class="account-check" for="mail1_check"><input type="checkbox" id="mail1_check" <?= account_checked($user, 'mail1Enable') ?>><span>Activer les notifications sur cette adresse</span></label>
                            </div>
                            <div class="account-field account-field--wide">
                                <label for="mail2">Deuxième adresse e-mail</label>
                                <div class="account-with-action">
                                    <input id="mail2" type="email" value="<?= account_value($user, 'Mail2') ?>" placeholder="autre@exemple.fr">
                                    <?php if ($modification): ?><button type="button" class="account-test" data-test="mail2">Tester</button><?php endif; ?>
                                </div>
                                <label class="account-check" for="mail2_check"><input type="checkbox" id="mail2_check" <?= account_checked($user, 'mail2Enable') ?>><span>Activer les notifications sur cette adresse</span></label>
                            </div>
                            <div class="account-field account-field--wide">
                                <label for="telephone">Téléphone · SMS Free</label>
                                <div class="account-with-action">
                                    <input id="telephone" type="tel" autocomplete="tel" value="<?= account_value($user, 'Telephone') ?>" placeholder="Numéro de téléphone">
                                    <?php if ($modification): ?><button type="button" class="account-test" data-test="SMS">Tester</button><?php endif; ?>
                                </div>
                                <label class="account-check" for="telephone_check"><input type="checkbox" id="telephone_check" <?= account_checked($user, 'ServiceSMSFreeEnable') ?>><span>Activer les alertes SMS</span></label>
                            </div>
                            <div class="account-field">
                                <label for="telephone_login">Identifiant du service SMS Free</label>
                                <input id="telephone_login" type="text" value="<?= account_value($user, 'loginServiceSMSFree') ?>" autocomplete="off">
                            </div>
                            <div class="account-field">
                                <label for="telephone_pw">Clé du service SMS Free</label>
                                <input id="telephone_pw" type="password" value="<?= account_value($user, 'clefServiceSMSFree') ?>" autocomplete="off">
                            </div>
                            <div class="account-field">
                                <label for="key_api">Clé API</label>
                                <input id="key_api" type="password" value="<?= account_value($user, 'KeyAPI') ?>" autocomplete="off">
                            </div>
                            <div class="account-field">
                                <label for="secret_api">Secret API</label>
                                <input id="secret_api" type="password" value="<?= account_value($user, 'SecretAPI') ?>" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </details>

                <div class="account-feedback" id="formFeedback" role="status" aria-live="polite" hidden></div>
                <button id="submitAccount" type="submit" class="account-submit">
                    <?= $modification ? 'Enregistrer les modifications' : 'Créer mon compte' ?>
                    <span aria-hidden="true">→</span>
                </button>
                <p class="account-required"><span class="required">*</span> Champs obligatoires. Pour récupérer votre mot de passe, renseignez votre e-mail dans les options avancées.</p>
                <?php if (!$modification): ?>
                    <p class="account-signin">Déjà inscrit ? <a href="Connexion.php">Se connecter</a></p>
                <?php endif; ?>
            </form>
        </section>
    </main>
    <footer class="account-footer">한국어 · Progressez à votre rythme</footer>
</div>
<script src="md5.js"></script>
<script src="creation-user.js?v=1"></script>
</body>
</html>
