Init();
async function Init() {

    //document.body.style.zoom="150%";
    // Couleurs et pictogrammes des cartes, dans l'ordre des thèmes du CSV.
    var couleursTheme = ["e77d42", "dd5b95", "8657ce", "3aa9c8", "29aa8b", "79bd43", "e5a340", "5e8bdd"];
    var iconesTheme = ["🐾", "🍓", "🏠", "📚", "👥", "💚", "📅", "🌿"];

    function setView(view) {
        document.body.setAttribute('data-view', view);
    }

    function mettreAJourSelection() {
        var nb = document.querySelectorAll('#themes .sous_theme[selected="true"]').length;
        var compteur = document.getElementById('selectionCount');
        var conseil = document.getElementById('selectionHint');
        if (compteur) compteur.textContent = nb + (nb > 1 ? ' rubriques sélectionnées' : ' rubrique sélectionnée');
        if (conseil) conseil.textContent = nb ? 'Vous pouvez commencer votre révision.' : 'Sélectionnez un ou plusieurs thèmes pour commencer.';
        document.querySelectorAll('#themes tr').forEach(tr => {
            var items = Array.from(tr.querySelectorAll('.sous_theme'));
            var total = items.length;
            var actifs = items.filter(b => b.getAttribute('selected') === 'true').length;
            var categorie = tr.querySelector('.theme');
            if (categorie) {
                categorie.setAttribute('selected', total > 0 && actifs === total ? 'true' : 'false');
                categorie.setAttribute('data-partial', actifs > 0 && actifs < total ? 'true' : 'false');
                categorie.setAttribute('aria-pressed', String(total > 0 && actifs === total));
            }
        });
    }

    function SpeechCFG(volume, rate, voiceId, voiceX)
    {
        this.Volume = volume;
        this.Rate = rate;
        this.Voice = undefined;

        SpeechCFG.prototype.SelectVoice = function(voiceId, voiceX)
        {
            var voices = speechSynthesis.getVoices();
            var voice = voices.find(v => v.lang == voiceId && (voiceX != undefined ? v.name.includes(voiceX) : true));
            this.Voice = voice;

            return voice;
        }

        if (voiceId != undefined)
            this.SelectVoice(voiceId, voiceX);


        SpeechCFG.prototype.Talk = async function(text, f_end, ...params)
        {
            if (this.Voice == undefined)
                return;

            var utterThis = new SpeechSynthesisUtterance(text);
            utterThis.rate = this.Rate;
            utterThis.volume = this.Volume;
            utterThis.voice = this.Voice;
            speechSynthesis.speak(utterThis);
            if (f_end != undefined)
            {
                utterThis.addEventListener('end', function(event) {
                    Object.apply(this, f_end(event, ...params));
                });
            }
        }
    }

    var voiceFR;
    var voiceKR;
    setTimeout(() => {
        voiceFR = new SpeechCFG(1, 0.9, "fr-FR", "Julie");
        voiceKR = new SpeechCFG(1, 0.9, "ko-KR", "한국의");
    }, 1000);

    var testStarted = false;
    var dictionnaire = new Array();
    var apprentissage = new Array();
    var apprentissageEtapeN = 0;
    var dictionnaireCSV;

    var headers = new Headers();
    headers.append("Accept", "text/plain");
    var optionsFetch = { method: 'GET',
               headers: headers,
               mode: 'cors',
               cache: 'default' };
    await fetch('dictionnaire.csv', optionsFetch)
        .then( function(response) {
            //return response.blob();
            return response.text();
        })
        .then( function (response) {
            dictionnaireCSV = response;
        });

    // parser CSV
    var lines = dictionnaireCSV.replace(/^\uFEFF/, '').split(/\r?\n/);
    var colonnes = lines[0].split(';');
    var index_theme = colonnes.findIndex(e => e == 'theme');
    var index_sous_theme = colonnes.findIndex(e => e == 'sous-theme');
    var index_coreen = colonnes.findIndex(e => e == 'coreen');
    var index_francais = colonnes.findIndex(e => e == 'francais');

    for(var i = 1;i < lines.length;i++)
    {
        var line = lines[i].split(';');
        if (line.length < 4 || !line[index_theme] || !line[index_sous_theme]) continue;

        try
        {
        var themeNom = line[index_theme].nomPropre();
        var sous_themeNom = line[index_sous_theme].nomPropre();
        }
        catch(e)
        { }
        var coreen = line[index_coreen];
        var francais = line[index_francais]

        var theme = dictionnaire.find(e => e.theme == themeNom && e.sous_theme == sous_themeNom);
        if (theme == undefined)
        {
            theme = { theme: themeNom, sous_theme: sous_themeNom, liste: new Array() };
            dictionnaire.push(theme);
        }

        theme.liste.push({ coreen : coreen , francais : francais , image: "" });
    }

    // creation des boutons de themes
    var table = document.getElementById('themes');
    var tbody = table.getElementsByTagName('tbody')[0];
    var themes = dictionnaire.reduce((a, e) => {
        if (!a.includes(e.theme))
            a.push(e.theme);

        return a;
    }, new Array() );

    for(var i = 0;i < themes.length;i++)
    {
        var tr = document.createElement('tr');
        tr.className = "tr_theme";
        tr.style.setProperty('--accent', '#' + couleursTheme[i % couleursTheme.length]);
        var td = document.createElement('td');
        var button = document.createElement('button');
        button.type = "button";
        button.className = "theme";
        button.setAttribute('selected', 'false');
        button.setAttribute('aria-pressed', 'false');
        var icon = document.createElement('span');
        icon.className = 'theme-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = iconesTheme[i % iconesTheme.length];
        var label = document.createElement('span');
        label.className = 'theme-name';
        label.textContent = themes[i];
        var count = document.createElement('span');
        count.className = 'theme-count';
        button.append(icon, label, count);
        button.tag = { theme : themes[i] };
        td.appendChild(button);
        tr.appendChild(td);
        tbody.appendChild(tr);
    }

    var trs = tbody.getElementsByTagName('tr');
    for(var i = 0;i < themes.length;i++)
    {
        var sous_themes = dictionnaire.reduce((a, e) => {
            if (e.theme == themes[i] && !a.includes(e.sous_theme))
                a.push(e.sous_theme);
    
            return a;
        }, new Array() );

        var titreCompteur = trs[i].querySelector('.theme-count');
        if (titreCompteur) titreCompteur.textContent = sous_themes.length + (sous_themes.length > 1 ? ' rubriques' : ' rubrique');

        for(var j = 0;j < sous_themes.length;j++)
        {
            var tr = trs[i];
            var td = document.createElement('td');
            var button = document.createElement('button');
            button.type = "button";
            button.className = "sous_theme";
            button.textContent = sous_themes[j];
            button.setAttribute('selected', 'false');
            button.setAttribute('aria-pressed', 'false');
            button.tag = { theme : themes[i] , sous_theme : sous_themes[j] };
            td.appendChild(button);
            tr.appendChild(td);
        }
    }

    var table = document.getElementById('themes');
    var bs = table.querySelectorAll("tbody tr td button");
    Array.from(bs).forEach((b, i) => {
        b.addEventListener('click', function() { selectionTheme(this); });
    });

    mettreAJourSelection();
    document.querySelector('.section-intro__aside').textContent = themes.length + ' univers à découvrir';

    document.getElementById('retour_themes').addEventListener('click', function() {
        tablesAllInvisible();
        document.getElementById('themes').style.display = '';
        document.getElementById('arreter').style.display = 'none';
        document.getElementById('lancer').style.display = '';
        testStarted = false;
        setView('themes');
    });

    var button_suivant = document.getElementById('mot_suivant');
    button_suivant.addEventListener('click', function() {
        apprentissageEtapeN++;
        
        if (apprentissageEtapeN == apprentissage.length)
        {
            //demarrage du test
            tablesAllInvisible();
            setView('quiz');
            testCoreen1();
        }
        else
            apprentissageEtape(apprentissageEtapeN);
    });
    var button_retour = document.getElementById('mot_retour');
    button_retour.addEventListener('click', function() {
        apprentissageEtapeN--;
        if (apprentissageEtapeN < 0)
            apprentissageEtapeN = 0;

        apprentissageEtape(apprentissageEtapeN);
    });

    var b_dictionnaire = document.querySelector('button#ouvrirDictionnaire');
    b_dictionnaire.addEventListener('click', function() {

        tablesAllInvisible();
        var table = document.querySelector('table#dictionnaire');
        table.style.display = "";
        setView('dictionary');

        tbody = table.querySelector('tbody');
        tbody.innerHTML = "";

        var themeNom_old = "";
        var themeNom_I = 0;
        for(var i = 1;i < lines.length;i++)
        {
            var line = lines[i].split(';');
            if (line.length < 4 || !line[index_theme] || !line[index_sous_theme]) continue;

            try
            {
            var themeNom = line[index_theme].nomPropre();
            var sous_themeNom = line[index_sous_theme].nomPropre();
            }
            catch(e)
            { }
            var coreen = line[index_coreen];
            var francais = line[index_francais]

            var tr = document.createElement('tr');
            if (themeNom_old != themeNom)
            {
                themeNom_old = themeNom;
                themeNom_I++;

                var td = document.createElement('td');
                td.textContent = themeNom;
                td.colSpan = 4;
                td.style.backgroundColor = couleursTheme[themeNom_I % 6];
                td.setAttribute('theme', true);
                tr.setAttribute('theme', true);
                tr.appendChild(td);
                
            }
            else
            {
                var td = document.createElement('td');
                td.innerHTML = sous_themeNom;
                td.setAttribute('theme', false);
                tr.appendChild(td);
                var td = document.createElement('td');
                td.innerHTML = francais;
                td.setAttribute('theme', false);
                tr.appendChild(td);
                var td = document.createElement('td');
                td.innerHTML = coreen;
                td.setAttribute('theme', false);
                tr.appendChild(td);
                var td = document.createElement('td');
                td.innerHTML = 0;
                td.setAttribute('theme', false);
                tr.setAttribute('theme', false);
                tr.appendChild(td);
            }
            tr.addEventListener('click', async function() {
                var tr = this;
                var tds = tr.getElementsByTagName('td');
                var td = tds[2];
                if (!td || !voiceKR) return;
                tr.setAttribute('talk', false);
                tr.style.backgroundColor = '#e9edff';
                await voiceKR.Talk(td.textContent, (ev, This) => {
                    This.setAttribute('talk', false);
                    This.style.backgroundColor = '';
                }, tr);
        
            });
            tbody.appendChild(tr);

        }

        // Les colonnes utilisent désormais le tableau HTML natif, responsive.

    });

    var b_arreter = document.getElementById('arreter');
    b_arreter.addEventListener('click', function() {

        tablesAllInvisible();
        var table = document.getElementById('themes');
        table.style.display = "";
        setView('themes');
        var select = document.getElementById('voiceSelect');
        select.style.display = "";
        testStarted = false;
        var b_lancer = document.getElementById('lancer');
        b_lancer.style.display = "";

        this.style.display = "none";
    });

    var b_lancer = document.getElementById('lancer');
    b_lancer.addEventListener('click', function() {

        tablesAllInvisible();

        var table = document.getElementById('themes');
        var buttons = table.querySelectorAll('button.sous_theme[selected="true"]');

        if (buttons.length == 0)
        {
            alert('Veuillez sélectionner au moins une rubrique.');
            testStarted = false;
            table.style.display = '';
            setView('themes');
            return;
        }

        testStarted = true;

        this.style.display = "none";
        var b_arreter = document.getElementById('arreter');
        b_arreter.style.display = "";

        var table = document.getElementById('apprentissage');
        table.style.display = "";
        apprentissage = new Array();
        var apprentissageX = new Array();

        for(var i = 0;i < buttons.length;i++)
        {
            var theme = dictionnaire.find(t => t.theme == buttons[i].tag.theme && t.sous_theme == buttons[i].tag.sous_theme );
            apprentissageX = apprentissageX.concat(theme.liste);
        }
        
        if (apprentissageX.length < 4) {
            alert('Il faut au moins 4 mots pour lancer le quiz. Sélectionnez une autre rubrique.');
            tablesAllInvisible();
            document.getElementById('themes').style.display = '';
            document.getElementById('lancer').style.display = '';
            document.getElementById('arreter').style.display = 'none';
            setView('themes');
            testStarted = false;
            return;
        }
        setView('learning');
        var N = C(4, apprentissageX.length);
        for(var j = 0;j < 4;j++)
        {
            apprentissage.push(Object.assign(apprentissageX[N[j]], { success : 0 }));
        }

        apprentissageEtapeN = 0;
        apprentissageEtape(apprentissageEtapeN);

    });

    var buttons = document.querySelectorAll('.c_mot_coreen');
    Array.from(buttons).forEach(b => {
        b.addEventListener('click', function() {

            this.setAttribute('talk', false); // true
            voiceKR.Talk(this.innerText , (ev, This) => {
                This.setAttribute('talk', false);
            }, this);

        });
    });

    async function testCoreen1()
    {
        var table = document.getElementById('testCoreen1');
        table.style.display = "";

        var button = table.querySelector('.c_mot_coreen');
        var n = Math.floor(Math.random() * apprentissage.length);
        button.innerHTML = apprentissage[ n ].coreen;
        button.tag = apprentissage[ n ];

        button.setAttribute('talk', false); // true
        await voiceKR.Talk(apprentissage[n].coreen , (ev, This) => {
            This.setAttribute('talk', false);
        }, button);

        motFrancaisTestCoreen1();
    }
    
    async function apprentissageEtape(n)
    {
        var table = document.getElementById('apprentissage');
        var button = table.querySelector('.c_mot_coreen');
        var tdF = table.querySelector('#mot_francais');
        button.innerHTML = apprentissage[n].coreen;
        tdF.innerHTML = apprentissage[n].francais;

        button.setAttribute('talk', false); // true
        await voiceKR.Talk(apprentissage[n].coreen , (ev, This) => {
            This.setAttribute('talk', false);
        }, button);
        await voiceFR.Talk(apprentissage[n].francais);
    }

    function motFrancaisTestCoreen1()
    {
        var table = document.getElementById('testCoreen1');
        var N = C(4, 4);
        for(var i = 0;i < 4;i++)
        {
            var button = table.querySelector('#mot_francais_' + i);
            button.style.backgroundColor = '';
            button.innerHTML = apprentissage[N[i]].francais + ( apprentissage[N[i]].success > 0 ? " [" + apprentissage[N[i]].success + "]" : "");
            button.tag = apprentissage[N[i]];
            button.onclick = function() {
                var buttonK = table.querySelector('.c_mot_coreen');

                var p = apprentissage.find(e => e.francais == buttonK.tag.francais );
                //var p = apprentissage.find(e => e.francais == this.tag.francais );

                if (this.tag.francais == buttonK.tag.francais)
                {
                    p.success++;
                    this.style.backgroundColor = "green";

                    setTimeout(() => {
                        testCoreen1();
                    }, 1000);

                }
                else
                {
                    p.success = 0;
                    this.style.backgroundColor = "red";

                    var buttons = table.querySelectorAll('.button_testCoreen1');
                    var buttonX = Array.from(buttons).find(b => b.tag.francais == buttonK.tag.francais );
                    buttonX.style.backgroundColor = "green";

                    setTimeout(() => {
                        testCoreen1();
                    }, 2000);
                }

            };
        }
    }

    function populateVoiceList() {
        if(typeof speechSynthesis === 'undefined') {
            return;
        }
        
        var voices = speechSynthesis.getVoices();
        
        for(var i = 0; i < voices.length; i++) {
            var option = document.createElement('option');
            option.textContent = voices[i].name + ' (' + voices[i].lang + ')';
            option.tag = voices[i];
        
            if(voices[i].default) {
            option.textContent += ' -- DEFAULT';
            }
        
            option.setAttribute('data-lang', voices[i].lang);
            option.setAttribute('data-name', voices[i].name);
            document.getElementById("voiceSelect").appendChild(option);
        }
    }
    
    populateVoiceList();
    if (typeof speechSynthesis !== 'undefined') {
            speechSynthesis.addEventListener('voiceschanged', populateVoiceList);
    }
    document.getElementById("voiceSelect").addEventListener('change', function() {
        var utterThis = new SpeechSynthesisUtterance(this.value);
        var voice = this.selectedOptions[0].tag; // getAttribute('data-name');
        voiceSelected = voice;
        utterThis.voice = voice;
        speechSynthesis.speak(utterThis);
    });

    function selectionTheme(This)
    {
        var activer = This.getAttribute('selected') !== 'true';
        if (This.classList.contains('theme')) {
            var tr = This.closest('tr');
            tr.querySelectorAll('button.sous_theme').forEach(b => {
                b.setAttribute('selected', String(activer));
                b.setAttribute('aria-pressed', String(activer));
            });
        } else {
            This.setAttribute('selected', String(activer));
            This.setAttribute('aria-pressed', String(activer));
        }
        mettreAJourSelection();
    }

    function tablesAllInvisible()
    {
        ['themes', 'apprentissage', 'testCoreen1', 'dictionnaire'].forEach(id => {
            document.getElementById(id).style.display = 'none';
        });
        document.getElementById('voiceSelect').style.display = 'none';
    }

    function C(n,p)
    {
        var N = new Array();
        var M = new Array();

        for(var i = 0;i < p;i++) { N.push(i); }
        for(var i = 0;i < n;i++) 
        { 
            var x = Math.floor(Math.random() * N.length);
            M.push(N[x]);
            N.splice(x, 1);
        }

        return M;
    }
}

String.prototype.nomPropre = function()
{
    if (this.length == 0)
        return this;
    try
    {
        var M = this.substring(0, 1).toUpperCase();
        return M + (this.length > 1 ? this.substring(1, this.length ).toLowerCase() : "");
    }
    catch(e)
    {
        console.log(e);
    }
}