/* Conserve les identifiants de champs et l'API de CreationUser_Verification.php. */
(function () {
  "use strict";
  const form = document.getElementById("form1");
  if (!form) return;
  const create = form.dataset.mode === "create";
  const feedback = document.getElementById("formFeedback");
  const submit = document.getElementById("submitAccount");
  const field = (id) => document.getElementById(id);
  const value = (id) => field(id)?.value?.trim() ?? "";
  const checked = (id) => field(id)?.checked ?? false;

  function showFeedback(message, isError) {
    feedback.textContent = message;
    feedback.classList.toggle("account-feedback--error", !!isError);
    feedback.classList.toggle("account-feedback--success", !isError);
    feedback.hidden = false;
    if (isError) feedback.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  function checkPassword() {
    if (!create) return true;
    const password = field("PW").value;
    const confirmation = field("PW_confirme").value;
    const hint = field("PW_correspondent");
    const enough = password.length >= 8;
    const matching = password === confirmation;
    hint.textContent = !enough ? "Au moins 8 caractères sont nécessaires." : (!matching ? "Les mots de passe ne correspondent pas." : "");
    field("PW").classList.toggle("is-invalid", !enough);
    field("PW_confirme").classList.toggle("is-invalid", !matching || confirmation.length < 8);
    return enough && matching;
  }

  function validate() {
    if (!value("login")) { showFeedback("Renseignez votre identifiant.", true); field("login").focus(); return false; }
    if (!checkPassword()) { showFeedback("Vérifiez les deux champs de mot de passe.", true); field("PW").focus(); return false; }
    for (const [id, label] of [["prenom", "prénom"], ["nom", "nom"]]) {
      if (value(id).length < 2) { showFeedback("Renseignez votre " + label + " (au moins 2 caractères).", true); field(id).focus(); return false; }
    }
    for (const id of ["mail1", "mail2"]) {
      if (value(id) && !field(id).checkValidity()) { form.querySelector("details").open = true; field(id).focus(); showFeedback("Vérifiez le format des adresses e-mail.", true); return false; }
    }
    return true;
  }

  async function saveAccount() {
    if (!validate()) return;
    const parameters = new URLSearchParams({
      login: value("login"),
      pw: create ? hex_md5(field("PW").value) : "",
      nom: value("nom"),
      prenom: value("prenom"),
      mail1: value("mail1"),
      mail2: value("mail2"),
      telephone: value("telephone"),
      telephone_login: value("telephone_login"),
      telephone_pw: field("telephone_pw").value,
      telephone_check: checked("telephone_check") ? "1" : "0",
      mail1_check: checked("mail1_check") ? "1" : "0",
      mail2_check: checked("mail2_check") ? "1" : "0",
      key_api: field("key_api").value,
      secret_api: field("secret_api").value
    });
    submit.disabled = true;
    feedback.hidden = true;
    try {
      const url = "CreationUser_Verification.php" + (create ? "" : "?update=1");
      const response = await fetch(url, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
        body: parameters.toString()
      });
      const answer = (await response.text()).trim();
      if (!response.ok || answer !== "OK") throw new Error(answer && answer.length < 350 ? answer : "Impossible d'enregistrer le compte.");
      if (create) {
        showFeedback("Votre compte a été créé. Il doit maintenant être validé avant de bénéficier de l'accès complet. Vous pouvez vous connecter depuis la page de connexion.", false);
        form.reset();
      } else {
        showFeedback("Vos modifications ont bien été enregistrées.", false);
      }
    } catch (error) {
      showFeedback(error.message || "Erreur de communication avec le serveur.", true);
    } finally {
      submit.disabled = false;
    }
  }

  form.addEventListener("submit", (event) => { event.preventDefault(); saveAccount(); });
  if (create) {
    field("PW_afficher_id").addEventListener("change", (event) => {
      const type = event.target.checked ? "text" : "password";
      field("PW").type = type;
      field("PW_confirme").type = type;
    });
    for (const id of ["PW", "PW_confirme"]) field(id).addEventListener("input", () => {
      if (field("PW").value || field("PW_confirme").value) checkPassword();
    });
  }
  for (const button of form.querySelectorAll("[data-test]")) {
    button.addEventListener("click", async () => {
      button.disabled = true;
      try {
        const response = await fetch("SendMail.php", {
          method: "POST", credentials: "same-origin",
          headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8" },
          body: new URLSearchParams({ mail: button.dataset.test }).toString()
        });
        if (!response.ok) throw new Error("Envoi du test impossible.");
        showFeedback("Le test a été transmis au serveur. " + (await response.text()), false);
      } catch (e) { showFeedback(e.message, true); }
      finally { button.disabled = false; }
    });
  }
})();
