<?php
/**
 * Administration du Vocabulaire coréen.
 * Aucun secret ni information personnelle sensible n'est envoyé en dehors du site.
 * Rôles relus dans MariaDB pour chaque requête, indépendamment de la session.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/Methodes_Connexions.php';
require_once __DIR__ . '/PasswordReset_lib.php';

header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function adminHtml($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function adminForbidden(): void {
    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="fr"><meta charset="utf-8"><title>Accès refusé</title>';
    echo '<body style="font:16px system-ui;padding:3rem;background:#f5f7fc;color:#172340">';
    echo '<h1>Accès réservé aux administrateurs</h1><p>Votre compte ne dispose pas des droits nécessaires.</p>';
    echo '<p><a href="index.php">Retour à l’accueil</a></p></body></html>';
    exit;
}

$sessionId = filter_var($_SESSION['Connexion_User']['User_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if (!$sessionId) {
    header('Location: Connexion.php', true, 302);
    exit;
}

// Ne pas se fier aux droits enregistrés dans la session : ils peuvent avoir changé.
$actorQuery = $bdd->prepare('SELECT `User_id`, `Login`, `Prenom`, `Nom`, `Valider`, `Administrateur`, `SAdministrateur` FROM `utilisateurs` WHERE `User_id` = :id LIMIT 1');
$actorQuery->execute([':id' => $sessionId]);
$actor = $actorQuery->fetch(PDO::FETCH_ASSOC);
if (!$actor || !(int)$actor['Valider'] || (!((int)$actor['Administrateur']) && !((int)$actor['SAdministrateur']))) {
    adminForbidden();
}
$isSuper = (bool)(int)$actor['SAdministrateur'];

if (empty($_SESSION['coreen_admin_csrf'])) {
    $_SESSION['coreen_admin_csrf'] = bin2hex(random_bytes(32));
}
if (empty($_SESSION['coreen_logout_csrf'])) {
    $_SESSION['coreen_logout_csrf'] = bin2hex(random_bytes(24));
}

// Modifications via POST seulement, avec CSRF, vérification des droits et requêtes préparées.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!is_string($token) || !hash_equals($_SESSION['coreen_admin_csrf'], $token)) {
        http_response_code(403);
        exit('Jeton de sécurité invalide. Rechargez la page.');
    }

    $targetId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $authorizedActions = ['validate', 'suspend', 'promote', 'demote', 'reset_mail'];
    $notice = 'Action refusée.';
    $noticeType = 'error';
    try {
        if (!$targetId || $targetId === (int)$actor['User_id'] || !in_array($action, $authorizedActions, true)) {
            throw new RuntimeException('Cette modification n’est pas autorisée.');
        }

        $bdd->beginTransaction();
        // Revérification des privilèges sous verrou avant toute écriture.
        $checkActor = $bdd->prepare('SELECT `Valider`, `Administrateur`, `SAdministrateur` FROM `utilisateurs` WHERE `User_id` = :id FOR UPDATE');
        $checkActor->execute([':id' => $sessionId]);
        $currentActor = $checkActor->fetch(PDO::FETCH_ASSOC);
        if (!$currentActor || !(int)$currentActor['Valider'] || (!((int)$currentActor['Administrateur']) && !((int)$currentActor['SAdministrateur']))) {
            throw new RuntimeException('Vos droits administrateur ont changé. Reconnectez-vous.');
        }
        $isSuperNow = (bool)(int)$currentActor['SAdministrateur'];

        $checkTarget = $bdd->prepare('SELECT `User_id`, `Login`, `Mail1`, `Valider`, `Administrateur`, `SAdministrateur` FROM `utilisateurs` WHERE `User_id` = :id FOR UPDATE');
        $checkTarget->execute([':id' => $targetId]);
        $target = $checkTarget->fetch(PDO::FETCH_ASSOC);
        if (!$target) {
            throw new RuntimeException('Compte introuvable.');
        }
        // Un super-administrateur ne peut pas être modifié depuis l'interface.
        if ((int)$target['SAdministrateur']) {
            throw new RuntimeException('Les comptes super-administrateurs sont protégés.');
        }
        // Un administrateur standard ne peut pas modifier les comptes administrateurs.
        if (!$isSuperNow && (int)$target['Administrateur']) {
            throw new RuntimeException('Seul un super-administrateur peut modifier un administrateur.');
        }

        if ($action === 'reset_mail') {
            if (!(int)$target['Valider'] || !filter_var((string)($target['Mail1'] ?? ''), FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Le compte doit être validé et disposer d’une adresse e-mail principale.');
            }
            $bdd->commit();
            $sent = coreenResetIssue($bdd, $target);
            if (!$sent) throw new RuntimeException('Un lien a déjà été demandé récemment. Réessayez après 5 minutes.');
            $notice = 'Lien de réinitialisation envoyé à l’adresse e-mail du compte (30 minutes).';
            $noticeType = 'success';
        } else {
        switch ($action) {
            case 'validate':
                $sql = 'UPDATE `utilisateurs` SET `Valider` = 1, `Date_upd` = NOW() WHERE `User_id` = :id';
                $notice = 'Compte validé.';
                break;
            case 'suspend':
                $sql = 'UPDATE `utilisateurs` SET `Valider` = 0, `Date_upd` = NOW() WHERE `User_id` = :id';
                $notice = 'Compte désactivé. Ses prochaines connexions seront refusées.';
                break;
            case 'promote':
                if (!$isSuperNow || !(int)$target['Valider']) {
                    throw new RuntimeException('Seul un super-administrateur peut nommer administrateur un compte déjà validé.');
                }
                $sql = 'UPDATE `utilisateurs` SET `Administrateur` = 1, `Date_upd` = NOW() WHERE `User_id` = :id';
                $notice = 'Droits administrateur attribués.';
                break;
            case 'demote':
                if (!$isSuperNow) {
                    throw new RuntimeException('Seul un super-administrateur peut retirer les droits administrateur.');
                }
                $sql = 'UPDATE `utilisateurs` SET `Administrateur` = 0, `Date_upd` = NOW() WHERE `User_id` = :id';
                $notice = 'Droits administrateur retirés.';
                break;
        }

        $statement = $bdd->prepare($sql);
        $statement->execute([':id' => $targetId]);
        $bdd->commit();
        $noticeType = 'success';
        }
    } catch (Throwable $e) {
        if ($bdd->inTransaction()) {
            $bdd->rollBack();
        }
        $notice = $e instanceof RuntimeException ? $e->getMessage() : 'La modification a échoué. Consultez les journaux du serveur.';
        if (!$e instanceof RuntimeException) {
            error_log('[coreen-admin] Échec de modification utilisateur : ' . $e->getMessage());
        }
    }
    $_SESSION['coreen_admin_notice'] = [$noticeType, $notice];
    header('Location: Administration.php#utilisateurs', true, 303);
    exit;
}

$flash = $_SESSION['coreen_admin_notice'] ?? null;
unset($_SESSION['coreen_admin_notice']);

$statistics = $bdd->query('SELECT COUNT(*) AS total,
  COALESCE(SUM(CASE WHEN `Valider` = 1 THEN 1 ELSE 0 END), 0) AS validated,
  COALESCE(SUM(CASE WHEN `Valider` = 0 THEN 1 ELSE 0 END), 0) AS pending,
  COALESCE(SUM(CASE WHEN `Administrateur` = 1 OR `SAdministrateur` = 1 THEN 1 ELSE 0 END), 0) AS admins
  FROM `utilisateurs`')->fetch(PDO::FETCH_ASSOC);
$recentLoginCount = (int)$bdd->query('SELECT COUNT(*) FROM `historique` WHERE `Date` >= NOW() - INTERVAL 30 DAY')->fetchColumn();
$users = $bdd->query('SELECT `User_id`, `Login`, `Prenom`, `Nom`, `Mail1`, `Date_cre`, `Valider`, `Administrateur`, `SAdministrateur` FROM `utilisateurs` ORDER BY `Valider` ASC, `Date_cre` DESC, `User_id` DESC')->fetchAll(PDO::FETCH_ASSOC);

$filterId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($filterId) {
    $historyQuery = $bdd->prepare('SELECT h.`Date`, h.`AdresseIp`, h.`Host`, u.`Login` FROM `historique` h LEFT JOIN `utilisateurs` u ON u.`User_id` = h.`User_id` WHERE h.`User_id` = :id ORDER BY h.`Date` DESC LIMIT 50');
    $historyQuery->execute([':id' => $filterId]);
} else {
    $historyQuery = $bdd->query('SELECT h.`Date`, h.`AdresseIp`, h.`Host`, u.`Login` FROM `historique` h LEFT JOIN `utilisateurs` u ON u.`User_id` = h.`User_id` ORDER BY h.`Date` DESC LIMIT 50');
}
$history = $historyQuery->fetchAll(PDO::FETCH_ASSOC);

$actorName = trim((string)$actor['Prenom'] . ' ' . (string)$actor['Nom']);
if ($actorName === '') {
    $actorName = (string)$actor['Login'];
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#14213d">
    <meta name="robots" content="noindex, nofollow">
    <title>Administration · Vocabulaire coréen</title>
    <link rel="icon" href="image/logo.jpeg" type="image/jpeg">
    <link rel="stylesheet" href="index.css?v=4">
    <link rel="stylesheet" href="administration.css?v=1">
</head>
<body>
<div class="app-shell admin-shell">
    <header class="hero admin-hero">
        <div class="hero__content">
            <div class="hero__topbar">
                <div class="hero__eyebrow"><span class="hero__eyebrow-dot"></span> ESPACE ADMINISTRATION</div>
                <div class="hero__account">
                    <span class="hero__avatar" aria-hidden="true">★</span>
                    <span class="hero__identity"><strong><?= adminHtml($actorName) ?></strong><small><?= $isSuper ? 'Super-administrateur' : 'Administrateur' ?></small></span>
                    <form method="post" action="Deconnexion.php" class="hero__logout-form">
                        <input type="hidden" name="csrf_token" value="<?= adminHtml($_SESSION['coreen_logout_csrf']) ?>">
                        <button type="submit" class="hero__logout">Déconnexion</button>
                    </form>
                </div>
            </div>
            <h1>Gérer les utilisateurs<span class="hero__period">.</span></h1>
            <p>Validez les inscriptions, gérez les accès et consultez l’activité du site.</p>
            <div class="hero__tags"><span>Gestion des comptes</span><span>Connexions récentes</span><span>Accès protégés</span></div>
        </div>
        <div class="hero__symbol" aria-hidden="true">관</div>
    </header>

    <nav class="admin-nav" aria-label="Navigation administration">
        <a class="admin-home-link" href="index.php">← Retour au vocabulaire</a>
        <div><a href="#utilisateurs">Utilisateurs</a><a href="#historique">Historique</a></div>
    </nav>

    <?php if ($flash && is_array($flash)): ?>
        <div role="status" class="admin-notice admin-notice--<?= ($flash[0] ?? '') === 'success' ? 'success' : 'error' ?>"><?= adminHtml($flash[1] ?? '') ?></div>
    <?php endif; ?>

    <section class="admin-stat-grid" aria-label="Statistiques des comptes">
        <div class="admin-stat"><span>Comptes inscrits</span><strong><?= (int)$statistics['total'] ?></strong><small>Tous les utilisateurs</small></div>
        <div class="admin-stat"><span>Comptes validés</span><strong><?= (int)$statistics['validated'] ?></strong><small>Autorisés à se connecter</small></div>
        <div class="admin-stat admin-stat--focus"><span>À valider</span><strong><?= (int)$statistics['pending'] ?></strong><small>Inscriptions en attente</small></div>
        <div class="admin-stat"><span>Connexions (30 jours)</span><strong><?= $recentLoginCount ?></strong><small>Nombre de connexions</small></div>
    </section>

    <section class="admin-panel" id="utilisateurs" aria-labelledby="users-heading">
        <div class="admin-panel__heading"><div><span class="section-kicker">GESTION DES ACCÈS</span><h2 id="users-heading">Utilisateurs</h2><p>Les changements prennent effet lors de la prochaine connexion. Un compte désactivé ne pourra plus se reconnecter.</p></div><span class="admin-count"><?= (int)$statistics['admins'] ?> administrateur(s)</span></div>
        <div class="admin-table-scroll">
            <table class="admin-table">
                <thead><tr><th>Utilisateur</th><th>Identifiant</th><th>Inscription</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <?php
                    $self = (int)$user['User_id'] === (int)$actor['User_id'];
                    $superTarget = (bool)(int)$user['SAdministrateur'];
                    $adminTarget = (bool)(int)$user['Administrateur'];
                    $validated = (bool)(int)$user['Valider'];
                    $canManage = !$self && !$superTarget && ($isSuper || !$adminTarget);
                    $completeName = trim((string)$user['Prenom'] . ' ' . (string)$user['Nom']);
                    ?>
                    <tr>
                        <td><strong><?= adminHtml($completeName !== '' ? $completeName : $user['Login']) ?></strong><?php if (!empty($user['Mail1'])): ?><small><?= adminHtml($user['Mail1']) ?></small><?php endif; ?></td>
                        <td><?= adminHtml($user['Login']) ?><?= $self ? ' <span class="admin-yourself">(vous)</span>' : '' ?></td>
                        <td><?= adminHtml($user['Date_cre'] ? substr((string)$user['Date_cre'], 0, 10) : '—') ?></td>
                        <td>
                            <span class="admin-pill <?= $validated ? 'admin-pill--ok' : 'admin-pill--pending' ?>"><?= $validated ? 'Validé' : 'En attente / désactivé' ?></span>
                            <?php if ($superTarget): ?><span class="admin-pill admin-pill--role">Super-admin</span><?php elseif ($adminTarget): ?><span class="admin-pill admin-pill--role">Admin</span><?php endif; ?>
                        </td>
                        <td class="admin-actions">
                            <a class="admin-mini-link" href="Administration.php?user_id=<?= (int)$user['User_id'] ?>#historique">Historique</a>
                            <?php if ($canManage): ?>
                                <form method="post" class="admin-inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= adminHtml($_SESSION['coreen_admin_csrf']) ?>">
                                    <input type="hidden" name="user_id" value="<?= (int)$user['User_id'] ?>">
                                    <?php if ($validated): ?>
                                        <button class="admin-mini-btn admin-mini-btn--danger" name="action" value="suspend" type="submit" data-confirm="Désactiver ce compte ?">Désactiver</button>
                                    <?php else: ?>
                                        <button class="admin-mini-btn admin-mini-btn--positive" name="action" value="validate" type="submit">Valider</button>
                                    <?php endif; ?>
                                    <?php if ($isSuper && !$adminTarget && $validated): ?>
                                        <button class="admin-mini-btn" name="action" value="promote" type="submit" data-confirm="Donner les droits administrateur à ce compte ?">Nommer admin</button>
                                    <?php elseif ($isSuper && $adminTarget): ?>
                                        <button class="admin-mini-btn" name="action" value="demote" type="submit" data-confirm="Retirer les droits administrateur ?">Retirer admin</button>
                                    <?php endif; ?>
                                    <?php if ($validated && !empty($user['Mail1'])): ?>
                                        <button class="admin-mini-btn" name="action" value="reset_mail" type="submit" data-confirm="Envoyer un lien de réinitialisation à l’adresse e-mail de ce compte ?">Réinitialiser le mot de passe</button>
                                    <?php endif; ?>
                                </form>
                            <?php elseif ($self): ?>
                                <span class="admin-action-note">Votre compte est protégé</span>
                            <?php elseif ($superTarget): ?>
                                <span class="admin-action-note">Compte protégé</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-panel" id="historique" aria-labelledby="history-heading">
        <div class="admin-panel__heading"><div><span class="section-kicker">JOURNAL D’ACTIVITÉ</span><h2 id="history-heading">Dernières connexions</h2><p><?= $filterId ? 'Historique filtré pour un utilisateur (50 dernières connexions).' : '50 dernières connexions, tous utilisateurs confondus.' ?></p></div><?php if ($filterId): ?><a class="admin-mini-link" href="Administration.php#historique">Tout afficher</a><?php endif; ?></div>
        <div class="admin-table-scroll">
            <table class="admin-table admin-history-table">
                <thead><tr><th>Date</th><th>Utilisateur</th><th>Adresse IP</th><th>Navigateur / appareil</th></tr></thead>
                <tbody>
                <?php foreach ($history as $entry): ?>
                    <tr><td><?= adminHtml($entry['Date']) ?></td><td><?= adminHtml($entry['Login'] ?: 'Compte supprimé') ?></td><td><?= adminHtml($entry['AdresseIp'] ?: '—') ?></td><td class="admin-agent" title="<?= adminHtml($entry['Host'] ?? '') ?>"><?= adminHtml(mb_strimwidth((string)($entry['Host'] ?? ''), 0, 95, '…', 'UTF-8')) ?></td></tr>
                <?php endforeach; ?>
                <?php if (!$history): ?><tr><td colspan="4" class="admin-empty">Aucune connexion enregistrée.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <footer class="footer">한국어 <span>·</span> Administration du vocabulaire coréen</footer>
</div>
<script>
document.querySelectorAll('button[data-confirm]').forEach(function(button) {
    button.addEventListener('click', function(event) {
        if (!confirm(button.dataset.confirm)) event.preventDefault();
    });
});
</script>
</body>
</html>
