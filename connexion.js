/* Page de connexion : préserve le protocole MD5 historique du serveur. */
(() => {
    'use strict';
    const form = document.getElementById('loginForm');
    if (!form) return;

    const login = document.getElementById('login');
    const password = document.getElementById('PW');
    const toggle = document.getElementById('togglePassword');
    const message = document.getElementById('loginFeedback');
    const submit = document.getElementById('connect');
    const submitLabel = document.getElementById('connectLabel');

    function error(text) {
        message.textContent = text;
        message.hidden = false;
    }
    function clearError() {
        message.textContent = '';
        message.hidden = true;
    }

    toggle.addEventListener('click', () => {
        const visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        toggle.textContent = visible ? 'Masquer' : 'Afficher';
        toggle.setAttribute('aria-pressed', String(visible));
        password.focus();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearError();
        const username = login.value.trim();
        if (!username || !password.value) {
            error('Renseignez votre identifiant et votre mot de passe.');
            (!username ? login : password).focus();
            return;
        }
        if (typeof window.hex_md5 !== 'function') {
            error('Le module d’authentification est indisponible. Rechargez la page.');
            return;
        }

        const params = new URLSearchParams({login: username, pw: window.hex_md5(password.value)});
        submit.disabled = true;
        submitLabel.textContent = 'Connexion en cours…';
        try {
            const response = await fetch('Connexion_Verification.php', {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
                body: params.toString()
            });
            const answer = (await response.text()).trim();
            if (response.ok && answer === 'OK') {
                window.location.assign('index.php');
                return;
            }
            if (response.status >= 500) {
                error('Le serveur est momentanément indisponible. Réessayez plus tard.');
            } else {
                error(answer === 'NON_VALIDE'
                    ? 'Votre compte n’est pas encore validé par un administrateur.'
                    : 'Identifiant ou mot de passe incorrect.');
            }
        } catch (_) {
            error('Connexion au serveur impossible. Vérifiez le réseau puis réessayez.');
        } finally {
            submit.disabled = false;
            submitLabel.textContent = 'Se connecter';
        }
    });
    login.addEventListener('input', clearError);
    password.addEventListener('input', clearError);
})();
