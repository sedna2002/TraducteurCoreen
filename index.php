<?php
// On demarre la session avant tout affichage HTML.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/Methodes_Connexions.php';

// Le panneau d'accueil reste reserve aux utilisateurs connectes.
if (ConnexionUser() === UserStatut::NonConnecte) {
    header('Location: Connexion.php');
    exit;
}

// Les valeurs de session sont echappees a l'affichage (prevention XSS).
$compte = $_SESSION['Connexion_User'] ?? [];
$prenom = trim((string)($compte['Prenom'] ?? ''));
$nom = trim((string)($compte['Nom'] ?? ''));
$nomAffiche = trim($prenom . ' ' . $nom);
if ($nomAffiche === '') {
    $nomAffiche = (string)($compte['Login'] ?? 'Utilisateur');
}
$initiale = mb_strtoupper(mb_substr($prenom !== '' ? $prenom : $nomAffiche, 0, 1, 'UTF-8'), 'UTF-8');
$fonction = ConnexionUser();
$statutCompte = ($fonction === UserStatut::SAdministrateur) ? 'Super administrateur' :
               (($fonction === UserStatut::Administrateur) ? 'Administrateur' :
               (($fonction === UserStatut::NonValider) ? 'Compte en attente' : 'Compte connecté'));
$derniereConnexion = '';
if (!empty($compte['LastConnexion'])) {
    try {
        $derniereConnexion = (new DateTimeImmutable((string)$compte['LastConnexion']))->format('d/m/Y à H:i');
    } catch (Exception $e) {
        // Une date incorrecte ne doit pas empecher l'affichage de l'accueil.
    }
}
if (empty($_SESSION['coreen_logout_csrf'])) {
    $_SESSION['coreen_logout_csrf'] = bin2hex(random_bytes(24));
}
function echapperHtml($texte) {
    return htmlspecialchars((string)$texte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html id="htmlMain" lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="theme-color" content="#14213d" />
    <link rel="icon" type="image/jpeg" href="image/logo.jpeg" />
    <link rel="stylesheet" type="text/css" href="index.css?v=3" media="all" />
    <title>Vocabulaire coréen · Apprentissage</title>
</head>
<body id="bodyMain" data-view="themes">
    <div class="app-shell">
        <header class="hero">
            <div class="hero__content">
                <div class="hero__topbar">
                    <div class="hero__eyebrow"><span class="hero__eyebrow-dot"></span> APPRENDRE LE CORÉEN</div>
                    <div class="hero__account" aria-label="Utilisateur connecté">
                        <span class="hero__avatar" aria-hidden="true"><?= echapperHtml($initiale) ?></span>
                        <span class="hero__identity">
                            <strong><?= echapperHtml($nomAffiche) ?></strong>
                            <small><?= echapperHtml($statutCompte) ?></small>
                        </span>
                        <?php if (in_array($fonction, [UserStatut::Administrateur, UserStatut::SAdministrateur], true)): ?>
                            <a class="hero__admin-link" href="Administration.php" title="Gérer les utilisateurs et les connexions">Administration</a>
                        <?php endif; ?>
                        <form method="post" action="Deconnexion.php" class="hero__logout-form">
                            <input type="hidden" name="csrf_token" value="<?= echapperHtml($_SESSION['coreen_logout_csrf']) ?>">
                            <button type="submit" class="hero__logout" title="Se déconnecter de l'application">Déconnexion</button>
                        </form>
                    </div>
                </div>
                <h1>Le coréen, un mot à la fois<span class="hero__period">.</span></h1>
                <p>Explorez les thèmes, mémorisez le vocabulaire et testez vos connaissances à votre rythme.</p>
                <?php if ($derniereConnexion !== ''): ?>
                    <p class="hero__last-login">Dernière connexion : <?= echapperHtml($derniereConnexion) ?></p>
                <?php endif; ?>
                <div class="hero__tags" aria-label="Fonctionnalités">
                    <span>한글 · Hangeul</span>
                    <span>Écoute et prononciation</span>
                    <span>Quiz interactif</span>
                </div>
            </div>
            <div class="hero__symbol" aria-hidden="true">가</div>
        </header>

        <main>
            <section class="command-bar" aria-label="Actions principales">
                <div class="selection-summary" aria-live="polite">
                    <span class="selection-summary__icon" aria-hidden="true">✓</span>
                    <div>
                        <strong id="selectionCount">0 rubrique sélectionnée</strong>
                        <span id="selectionHint">Sélectionnez un ou plusieurs thèmes pour commencer.</span>
                    </div>
                </div>
                <table id="menu" aria-label="Menu d'apprentissage">
                    <tbody><tr><td>
                        <button type="button" id="retour_themes" class="action action--back">← Catégories</button>
                        <button type="button" id="ouvrirDictionnaire" class="action action--secondary">Dictionnaire</button>
                        <button type="button" id="lancer" class="action action--primary">Commencer la révision <span aria-hidden="true">→</span></button>
                        <button type="button" id="arreter" class="action action--danger" style="display: none">Arrêter la révision</button>
                    </td></tr></tbody>
                </table>
            </section>

            <section class="content-panel" aria-label="Vocabulaire et exercices">
                <div class="section-intro" id="themesIntro">
                    <div>
                        <div class="section-kicker">VOTRE BIBLIOTHÈQUE</div>
                        <h2>Explorer les catégories</h2>
                        <p>Cliquez sur une catégorie entière ou choisissez les rubriques qui vous intéressent.</p>
                    </div>
                    <span class="section-intro__aside">8 univers à découvrir</span>
                </div>

                <select id="voiceSelect" style="display: none" aria-label="Sélection de la voix de synthèse"></select>

                <table id="themes" aria-label="Catégories de vocabulaire"><tbody></tbody></table>

                <div class="view-heading" id="apprentissageHeading">
                    <span class="view-heading__eyebrow">ÉTAPE 1 · MÉMORISER</span>
                    <h2>Apprendre les mots</h2>
                    <p>Cliquez sur le mot coréen pour réécouter sa prononciation.</p>
                </div>
                <table id="apprentissage" style="display: none" aria-label="Apprentissage des mots">
                    <tbody>
                        <tr><td colspan="2"><button id="mot_coreen_lecon" class="c_mot_coreen" type="button" title="Écouter la prononciation"></button></td></tr>
                        <tr><td colspan="2" class="c_mot_francais" id="mot_francais"></td></tr>
                        <tr class="study-actions">
                            <td><button type="button" id="mot_retour" class="action action--secondary">← Précédent</button></td>
                            <td><button type="button" id="mot_suivant" class="action action--primary">Suivant →</button></td>
                        </tr>
                    </tbody>
                </table>

                <div class="view-heading" id="testHeading">
                    <span class="view-heading__eyebrow">ÉTAPE 2 · S'ENTRAÎNER</span>
                    <h2>À vous de jouer !</h2>
                    <p>Choisissez la bonne traduction pour chaque mot coréen.</p>
                </div>
                <table id="testCoreen1" style="display: none" aria-label="Quiz coréen">
                    <tbody>
                        <tr><td colspan="2"><button class="c_mot_coreen" id="mot_coreen_quiz" type="button" title="Écouter la prononciation"></button></td></tr>
                        <tr><td><button type="button" class="button_testCoreen1" id="mot_francais_0">?</button></td><td><button type="button" class="button_testCoreen1" id="mot_francais_1">?</button></td></tr>
                        <tr><td><button type="button" class="button_testCoreen1" id="mot_francais_2">?</button></td><td><button type="button" class="button_testCoreen1" id="mot_francais_3">?</button></td></tr>
                    </tbody>
                </table>

                <div class="view-heading" id="dictionaryHeading">
                    <span class="view-heading__eyebrow">TOUS LES MOTS</span>
                    <h2>Le dictionnaire</h2>
                    <p>Retrouvez tout le vocabulaire et cliquez sur une ligne pour écouter un mot coréen.</p>
                </div>
                <div class="dictionary-scroll">
                    <table id="dictionnaire" style="display: none" aria-label="Dictionnaire français coréen">
                        <thead><tr><th>Sous-thème</th><th>Français</th><th>Coréen</th><th>Vu</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>
        </main>
        <footer class="footer">한국어 <span>·</span> Un peu de vocabulaire chaque jour fait toute la différence.</footer>
    </div>
    <script src="jquery-3.6.0.js"></script>
    <script src="index.js?v=2"></script>
</body>
</html>
