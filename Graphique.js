// JavaScript source code

var Recadrage = { X: 80, Y: 30, W: 0, H: 0 };

// data : tableau a 1 dimension de la courbe en données brute composé par element de {x:0,y:0}
// couleur_style : couleur du tracer
// force_y_zero : dans le cas ou origine et echelle ne sont pas spécifiés l'origine en ordonnée est à 0
// calc contient origine : x et y en données brutes
// calc contient echelle : x et y 
// repere : si à true dessine le repere orthonormé ainsi que les echelles
// log_y : specifie pour le dessin de repère que l'echelle des Y et en log10
// surface : couleur de la surface à dessiner
// RETURN : un tableau composé de 2 éléments de position origine et d'echelle du tracer.
//          Ces éléments sont suceptibles d'etre recalculé ou non suivant les parametres de lancement
function TraceCourbeSimple(canvas_id, data, couleur_style, force_y_zero, origine_echelle, repere, log_y, surface, epaisseur, cercles, rayon) {
    var canvas = document.getElementById(canvas_id);

    var echelle_x = 0;
    var echelle_y = 0;
    var pos_x = 0;
    var pos_y = 0;

    var borneY = new Borne();
    borneY.CompareData(data, 'y');
    var borneX = new Borne();
    borneX.CompareData(data, 'x');

    if (typeof origine_echelle == "undefined") {
        var WT = canvas.width;
        var HT = canvas.height;
        Recadrage.W = WT - Recadrage.X;
        Recadrage.H = HT - Recadrage.Y;

        echelle_x = Recadrage.W / (borneX.max - borneX.min); //data.length;
        echelle_y = (force_y_zero) ? Recadrage.H / Math.max(Math.abs(borneY.max), Math.abs(borneY.min)) : Recadrage.H / (borneY.max - borneY.min);

        pos_x = borneX.min * echelle_x;
        pos_y = (force_y_zero) ? (borneY.max < 0 ? Recadrage.H : 0) : echelle_y * borneY.min;

        var calc = new CalculsCoordonnes({ origine: { x: pos_x, y: pos_y }, echelle: { x: echelle_x, y: echelle_y } }, Recadrage);
    } else {
        echelle_x = origine_echelle.echelle.x;
        echelle_y = origine_echelle.echelle.y;

        pos_x = origine_echelle.origine.x;
        pos_y = origine_echelle.origine.y;

        var calc = origine_echelle;
    }

    var ctx = canvas.getContext("2d");

    ctx.save();

    ctx.beginPath();
    ctx.strokeStyle = "#00000000";
    ctx.rect(calc.Recadrage.X, 0, calc.Recadrage.X + calc.Recadrage.W, calc.Recadrage.H);
    ctx.stroke();
    ctx.clip();

    ctx.lineWidth = epaisseur == undefined ? 1 : epaisseur;
    if (couleur_style.hasOwnProperty("couleur")) ctx.strokeStyle = couleur_style.couleur;
    if (couleur_style.hasOwnProperty("dash")) ctx.setLineDash(couleur_style.dash);

    if (cercles == undefined) {
        ctx.beginPath();

        if (surface != undefined) {
            ctx.fillStyle = surface;
        }

        ctx.moveTo(calc.ValeurToX(data[0].x), calc.ValeurToY(data[0].y));

        for (i = 1; i < data.length; i++)
            ctx.lineTo(calc.ValeurToX(data[i].x), calc.ValeurToY(data[i].y));

        ctx.stroke();

        if (surface != undefined) {
            ctx.fill();
        }
    } else {
        for (i = 0; i < data.length; i++) {
            ctx.beginPath();
            ctx.arc(calc.ValeurToX(data[i].x), calc.ValeurToY(data[i].y), rayon == undefined ? 10 : rayon, 0, Math.PI * 2);
            ctx.stroke();
        }
    }

    ctx.setLineDash([]);
    ctx.restore();

    if (repere) {
        // dessine repere orthonormé
        ctx.beginPath();
        ctx.lineWidth = 2;
        ctx.strokeStyle = "#FF00FF";
        ctx.moveTo(calc.Recadrage.X, calc.Recadrage.H);
        ctx.lineTo(calc.Recadrage.X, 0);
        ctx.moveTo(calc.Recadrage.X, calc.Recadrage.H);
        ctx.lineTo(calc.Recadrage.X + calc.Recadrage.W, calc.Recadrage.H);
        ctx.stroke();

        // dessine les reperes
        // repere axe des X
        var Nombre_repere = 5;
        var distance_repere = calc.Recadrage.W / Nombre_repere;

        ctx.beginPath();
        ctx.lineWidth = 1;
        ctx.strokeStyle = "#FFFFFF";

        ctx.moveTo(0, calc.ValeurToY(0));
        ctx.lineTo(calc.Recadrage.W, calc.ValeurToY(0));

        ctx.stroke();

        ctx.beginPath();
        ctx.lineWidth = 1;
        ctx.strokeStyle = "#FFA0FF";
        for (i = 0; i < Nombre_repere; i++) {
            var id = Math.round(i * distance_repere) + calc.Recadrage.X;
            var xx = calc.XToValeur(id);

            I = data.findIndex(function(element, indice, tab) { return element['x'] >= xx; }, xx);

            if (0 <= I && I < data.length) {
                ctx.moveTo(calc.ValeurToX(data[I].x), calc.Recadrage.H); // - pos_x + id * echelle_x + Recadrage.X, Recadrage.H);
                ctx.lineTo(calc.ValeurToX(data[I].x), calc.Recadrage.H + 10);

                ctx.font = "12px Courier";
                var s = data[id].date_text;
                ctx.strokeText(s, calc.ValeurToX((i * distance_repere)), calc.Recadrage.H + 20);
            }
        }
        ctx.stroke();

        // repere axe des Y des prix
        var Nombre_repere = 5;
        var distance_repere = calc.Recadrage.H / Nombre_repere;

        ctx.beginPath();
        ctx.lineWidth = 1;
        ctx.strokeStyle = "#00FF00";
        for (i = 0; i < Nombre_repere; i++) {
            var py = i * distance_repere;
            ctx.moveTo(calc.Recadrage.X, calc.Recadrage.H - py);
            ctx.lineTo(calc.Recadrage.X - 20, calc.Recadrage.H - py);

            ctx.font = "12px Courier";
            var s = calc.YToValeur(calc.Recadrage.H - py);
            s = (typeof log_y == "undefined") ? s : (log_y ? Math.pow(10, s) : s);
            //p = Math.floor(Math.log10(s)) - 5;
            //p = Math.pow(10,p);
            //s = Math.floor(s / p) * p;
            ctx.strokeText(s.toPrecision(5), 10, calc.Recadrage.H - py - 3);
        }
        ctx.stroke();
    }

    return calc; //{origine:{x:pos_x,y:pos_y},echelle:{x:echelle_x,y:echelle_y},Recadrage{X:0,Y:0,W:0,H:0};
}

function TraceSupportResistance(svg_id, canvas_id, data, origine_echelle, log_y, epaisseur) {
    if (data == null)
        return;

    if (origine_echelle == undefined)
        return;

    var canvas = canvas_id != null ? document.getElementById(canvas_id) : null;
    var svg = svg_id != null ? document.getElementById(svg_id) : null;

    var ctx = canvas.getContext("2d");
    var calc = origine_echelle;

    ctx.save();

    ctx.beginPath();
    ctx.strokeStyle = "#00000000";
    ctx.rect(calc.Recadrage.X, 0, calc.Recadrage.X + calc.Recadrage.W, calc.Recadrage.H);
    ctx.stroke();
    ctx.clip();

    ctx.lineWidth = typeof epaisseur == "undefined" ? 1 : epaisseur;
    ctx.font = "10px Arial";

    var child = document.createElementNS("http://www.w3.org/2000/svg", 'g');
    var child1 = document.createElementNS("http://www.w3.org/2000/svg", 'clipPath');
    child1.setAttribute("id", "clipSVG");
    var child2 = document.createElementNS("http://www.w3.org/2000/svg", 'rect');
    child2.setAttribute("x", calc.Recadrage.X);
    child2.setAttribute("y", "0");
    child2.setAttribute("width", calc.Recadrage.W);
    child2.setAttribute("height", calc.Recadrage.H);
    child1.appendChild(child2);
    child.appendChild(child1);
    svg.appendChild(child);

    for (i = 0; i < data.length; i++)
        if (data[i].cryptomonaie == monaie) {

            var sr = data[i].support_resistance;

            for (j = 0; j < sr.length; j++) {
                var couleur;
                var couleur_extend;
                ctx.beginPath();

                var sr1 = sr[j];

                if (sr1.side == 0) {
                    couleur = "#FF000080";
                    ctx.strokeStyle = "#FF000080";
                } else {
                    couleur = "#00FF0080";
                    ctx.strokeStyle = "#00FF0080";
                }

                var x1 = calc.ValeurToX(sr1.equation_droite.p1.X / 1000);
                var x2 = calc.ValeurToX(sr1.equation_droite.p2.X / 1000);
                var y1 = sr1.log ? Math.pow(10, sr1.equation_droite.p1.Y) : sr1.equation_droite.p1.Y;
                var y2 = sr1.log ? Math.pow(10, sr1.equation_droite.p2.Y) : sr1.equation_droite.p2.Y;
                y1 = calc.ValeurToY(y1);
                y2 = calc.ValeurToY(y2);

                ctx.moveTo(x1, y1);
                ctx.lineTo(x2, y2);

                ctx.stroke();

                ctx.strokeStyle = "#FFFFFF";
                ctx.strokeText("A" + j, x1, y1);
                ctx.strokeText("B" + j, x2, y2);

                var x3 = calc.ValeurToX(sr1.timestamp_max / 1000);
                var y3 = calc.ValeurToY(Droite(sr1.equation_droite.A, sr1.equation_droite.B, sr1.timestamp_max));

                if (sr1.timestamp_max > 0) {
                    ctx.beginPath();
                    ctx.setLineDash([2, 2]);

                    if (sr1.side == 0) {
                        couleur_extend = "#FF808080";
                        ctx.strokeStyle = "#FF8080";
                    } else {
                        couleur_extend = "#80FF8080";
                        ctx.strokeStyle = "#80FF80";
                    }

                    ctx.moveTo(x2, y2);
                    ctx.lineTo(x3, y3);

                    ctx.stroke();
                    ctx.setLineDash([]);
                }

                var x = (x2 + x1) / 2;
                x = (x < calc.Recadrage.X + 50) ? calc.Recadrage.X + 50 :
                    (x > calc.Recadrage.W - calc.Recadrage.X + 50) ? calc.Recadrage.W - calc.Recadrage.X + 50 : x;
                x = Math.max(x, x1);
                x = Math.min(x, x3);

                ctx.strokeStyle = "#FFFFFF";
                ctx.strokeText(sr1.nom, x, calc.ValeurToY(Droite(sr1.equation_droite.A, sr1.equation_droite.B, calc.XToValeur(x) * 1000)));


                //html = '<line onmouseover="alert(i);" x1="' + calc.ValeurToX(data[i].x) + '" y1="' + calc.ValeurToY(data[i].y) + '" x2="' + calc.ValeurToX(data[i + 1].x) + '" y2="' + calc.ValeurToY(data[i+1].y) + '" style="stroke:rgb(255,255,255);stroke-width:' + epaisseur + '" />';
                var child = document.createElementNS("http://www.w3.org/2000/svg", 'line');

                var param_event_mouse = { indices: null, couleur: "#FF0000", child: "" };
                param_event_mouse.indices = [i, j, 0];
                param_event_mouse.couleur = couleur;
                param_event_mouse.child = "line" + j;

                child.setAttribute("id", "line" + j);
                child.setAttribute("onmouseover", "MouseOverSVG(" + JSON.stringify(param_event_mouse) + ")"); // var serializedArr = JSON.stringify( arr );   { indices : data[i].id , couleur: couleur }.PropertySerialize()
                child.setAttribute("onmouseout", "MouseOutSVG(" + JSON.stringify(param_event_mouse) + ")");
                child.setAttribute("onmousemove", "MouseMoveSVG(evt,this)");
                child.setAttribute("onmousewheel", "MouseWheelSVG(evt,this)");
                child.setAttribute("clip-path", "url(#clipSVG)");
                //child.setAttribute("contextmenu", "contextMenuSupportResistance");
                child.setAttribute("oncontextmenu", "contextMenuOpen()");
                child.setAttribute("x1", x1);
                child.setAttribute("y1", y1);
                child.setAttribute("x2", x2);
                child.setAttribute("y2", y2);
                child.setAttribute("class", sr1.side == 0 ? "support_main" : "resistance_main");
                child.style.stroke = "#FFFFFF00";
                child.style.strokeWidth = 8;
                svg.appendChild(child);

                if (sr1.timestamp_max > 0) {
                    var child = document.createElementNS("http://www.w3.org/2000/svg", 'line');

                    var param_event_mouse = { indices: null, couleur: "#FF0000", child: "" };
                    param_event_mouse.indices = [i, j, 1];
                    param_event_mouse.couleur = couleur_extend;
                    param_event_mouse.child = "line_extend" + j;

                    child.setAttribute("id", "line_extend" + j);
                    child.setAttribute("onmouseover", "MouseOverSVG(" + JSON.stringify(param_event_mouse) + ")"); // var serializedArr = JSON.stringify( arr );   { indices : data[i].id , couleur: couleur }.PropertySerialize()
                    child.setAttribute("onmouseout", "MouseOutSVG(" + JSON.stringify(param_event_mouse) + ")");
                    child.setAttribute("onmousemove", "MouseMoveSVG(evt,this)");
                    child.setAttribute("onmousewheel", "MouseWheelSVG(evt,this)");
                    child.setAttribute("clip-path", "url(#clipSVG)");
                    //child.setAttribute("contextmenu", "contextMenuSupportResistance");
                    child.setAttribute("oncontextmenu", "contextMenuOpen()");
                    child.setAttribute("x1", x2);
                    child.setAttribute("y1", y2);
                    child.setAttribute("x2", x3);
                    child.setAttribute("y2", y3);
                    child.style.stroke = "#FFFFFF00";
                    child.setAttribute("class", sr1.side == 0 ? "support_extend" : "resistance_extend");
                    child.style.strokeWidth = 8;
                    svg.appendChild(child);
                }
            }

        }

    ctx.restore();
}

function TraceAchatVente(svg_id, canvas_id, data, origine_echelle, log_y, epaisseur) {

    if (data == null)
        return;

    if (origine_echelle == undefined)
        return;

    var canvas = canvas_id != null ? document.getElementById(canvas_id) : null;
    var svg = svg_id != null ? document.getElementById(svg_id) : null;

    var ctx = canvas.getContext("2d");
    var calc = origine_echelle;

    ctx.save();

    ctx.beginPath();
    ctx.strokeStyle = "#00000000";
    ctx.rect(calc.Recadrage.X, 0, calc.Recadrage.X + calc.Recadrage.W, calc.Recadrage.H);
    ctx.stroke();
    ctx.clip();

    ctx.lineWidth = typeof epaisseur == "undefined" ? 1 : epaisseur;
    ctx.font = "10px Arial";

    var child = document.createElementNS("http://www.w3.org/2000/svg", 'g');
    var child1 = document.createElementNS("http://www.w3.org/2000/svg", 'clipPath');
    child1.setAttribute("id", "clipSVG");
    var child2 = document.createElementNS("http://www.w3.org/2000/svg", 'rect');
    child2.setAttribute("x", calc.Recadrage.X);
    child2.setAttribute("y", "0");
    child2.setAttribute("width", calc.Recadrage.W);
    child2.setAttribute("height", calc.Recadrage.H);
    child1.appendChild(child2);
    child.appendChild(child1);
    svg.appendChild(child);

    var id_cryptomonaie = 1;
    var id_sens = 13;
    var id_cacher = 14;
    var id_date = 6;
    var id_identifiant = 0;
    var id_prix = 3;
    var d = new Date();

    for (i = 0; i < data.length; i++)
        if (data[i][id_cryptomonaie] == monaie) {

            var av = data[i];
            var couleur;

            ctx.beginPath();

            if (av[id_sens] == 0) {
                couleur = "#FF000080";
                ctx.strokeStyle = "#FF000080";
            } else {
                couleur = "#00FF0080";
                ctx.strokeStyle = "#00FF0080";
            }

            var x1 = calc.ValeurToX(av[id_date] / 1000);
            var y1 = log_y ? Math.log(av[id_prix]) : av[id_prix];
            y1 = calc.ValeurToY(y1);

            ctx.moveTo(x1, y1);
            ctx.lineTo(calc.Recadrage.W, y1);
            ctx.arc(x1, y1, 5, 0, 2 * Math.PI);

            d.setDate(av[id_date] / 1000);
            ctx.strokeStyle = "#FFFFFF";
            ctx.strokeText(av[id_identifiant] + " " + d.format("Y-M-D H:m:s"), x1, y1);

            ctx.stroke();

            //html = '<line onmouseover="alert(i);" x1="' + calc.ValeurToX(data[i].x) + '" y1="' + calc.ValeurToY(data[i].y) + '" x2="' + calc.ValeurToX(data[i + 1].x) + '" y2="' + calc.ValeurToY(data[i+1].y) + '" style="stroke:rgb(255,255,255);stroke-width:' + epaisseur + '" />';
            var child = document.createElementNS("http://www.w3.org/2000/svg", 'line');

            var param_event_mouse = { indices: i, couleur: couleur, child: "" };
            param_event_mouse.indices = i;
            param_event_mouse.couleur = couleur;
            param_event_mouse.child = "line" + j;

            child.setAttribute("id", "line" + j);
            child.setAttribute("onmouseover", "MouseOverOrderSVG(" + JSON.stringify(param_event_mouse) + ")"); // var serializedArr = JSON.stringify( arr );   { indices : data[i].id , couleur: couleur }.PropertySerialize()
            child.setAttribute("onmouseout", "MouseOutOrderSVG(" + JSON.stringify(param_event_mouse) + ")");
            child.setAttribute("onmousemove", "MouseMoveOrderSVG(evt,this)");
            child.setAttribute("onmousewheel", "MouseWheelOrderSVG(evt,this)");
            child.setAttribute("clip-path", "url(#clipSVG)");
            //child.setAttribute("contextmenu", "contextMenuSupportResistance");
            child.setAttribute("oncontextmenu", "contextOrderMenuOpen()");
            child.setAttribute("x1", x1);
            child.setAttribute("y1", y1);
            child.setAttribute("x2", x2);
            child.setAttribute("y2", y2);
            child.setAttribute("class", sr1.side == 0 ? "support_main" : "resistance_main");
            child.style.stroke = "#FFFFFF00";
            child.style.strokeWidth = 8;
            svg.appendChild(child);
        }

    ctx.restore();


}

function Droite(A, B, X) {
    return X * A + B;
}

function TraceSegmentsCourbeSimple(canvas_id, segments, couleur, force_y_zero, origine_echelle, repere, log_y, surface, epaisseur) {

    if (segments == null)
        return;

    segments.forEach(function(segment) { TraceCourbeSimple(canvas_id, segment, couleur, force_y_zero, origine_echelle, repere, log_y, surface, epaisseur); });
}

function TraceSegmentsCourbeSimpleSVG(svg_id, segments, couleur, force_y_zero, origine_echelle, epaisseur) {

    if (segments == null)
        return;

    segments.forEach(function(segment) { TraceCourbeSimpleSVG(svg_id, segment, couleur, force_y_zero, origine_echelle, epaisseur); });
}

function Borne() {
    this.min = 1e30;
    this.max = -1e30;
    this.moy = 0;
    this.nombre = 0;

    Borne.prototype.Compare = function(v) {
        if (parseFloat(v) < this.min) this.min = parseFloat(v);
        if (parseFloat(v) > this.max) this.max = parseFloat(v);

        this.moy += parseFloat(v);
        this.nombre++;
    }

    Borne.prototype.CompareData = function(v, id1, id2) {
        if (id1 == undefined)
            for (var i = 0; i < v.length; i++) { this.Compare(v[i]); }
        else
        if (id2 == undefined)
            for (var i = 0; i < v.length; i++) { this.Compare(v[i][id1]); }
        else
            for (var i = 0; i < v.length; i++) {
                this.Compare(v[i][id1]);
                this.Compare(v[i][id2]);
            }
    }

    Borne.prototype.GetMoyenne = function() { return this.moy / this.nombre; }
}


function CalculsCoordonnes(pos_ech, recadrage) {
    this.origine = { x: 0, y: 0 };
    this.echelle = { x: 0, y: 0 };
    this.origine.x = pos_ech.origine.x;
    this.origine.y = pos_ech.origine.y;
    this.echelle.x = pos_ech.echelle.x;
    this.echelle.y = pos_ech.echelle.y;

    if (recadrage != undefined) {
        this.Recadrage = { X: 0, Y: 0, W: 0, H: 0 };
        this.Recadrage.X = recadrage.X;
        this.Recadrage.Y = recadrage.Y;
        this.Recadrage.W = recadrage.W;
        this.Recadrage.H = recadrage.H;
    } else {
        this.Recadrage = { X: 0, Y: 0, W: 0, H: 0 };
        this.Recadrage.X = 80;
        this.Recadrage.Y = 30;
        this.Recadrage.W = recadrage.W;
        this.Recadrage.H = recadrage.H;
    }


    CalculsCoordonnes.prototype.ValeurToX = function(v) {
        return v * this.echelle.x - this.origine.x + this.Recadrage.X;
    }

    CalculsCoordonnes.prototype.ValeurToY = function(v) {
        return this.Recadrage.H - this.echelle.y * v + this.origine.y; // - this.origine.y
    }

    CalculsCoordonnes.prototype.XToValeur = function(x) {
        return (x - this.Recadrage.X + this.origine.x) / this.echelle.x;
    }

    CalculsCoordonnes.prototype.YToValeur = function(y) {
        return -(y - this.Recadrage.H - this.origine.y) / this.echelle.y; // + this.origine.y
    }

    CalculsCoordonnes.prototype.XValeurToEchelleX = function(x, v) {
        return (x - this.Recadrage.X + this.origine.x) / v;
    }

    CalculsCoordonnes.prototype.YValeurToEchelleY = function(y, v) {
        return -(y - this.Recadrage.H - this.origine.y) / v;
    }

    CalculsCoordonnes.prototype.XValeurToOrigineX = function(x, v) {
        return -(x - this.Recadrage.X - v * this.echelle.x);
    }

    CalculsCoordonnes.prototype.YValeurToOrigineY = function(y, v) {
        return (y - this.Recadrage.H + this.echelle.y * v);
    }
}

function CanvasClear(canvas_id) {
    var canvas = document.getElementById(canvas_id);

    var ctx = canvas.getContext("2d");
    ctx.beginPath();
    ctx.fillStyle = "#000000FF";
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.stroke();


}

// data : tableau a 1 dimension de la courbe en données brute composé par element de {mts:0,start:0,end:0,higt:0,low:0,volume:0}
// couleur : couleur du tracer
// force_y_zero : dans le cas ou origine et echelle ne sont pas spécifiés l'origine en ordonnée est à 0
// origine : x et y en données brutes
// echelle : x et y 
// repere : si à true dessine le repere orthonormé ainsi que les echelles
// log_y : specifie pour le dessin de repère que l'echelle des Y et en log10
// surface : couleur de la surface à dessiner
function TraceBougies(canvas_id, data, force_y_zero, origine_echelle, repere, log_y) {
    var canvas = document.getElementById(canvas_id);

    var echelle_x = 0;
    var echelle_y = 0;
    var pos_x = 0;
    var pos_y = 0;

    var borneY = new Borne();
    borneY.CompareData(data, 'close', 'open');

    var borneY2 = new Borne();
    borneY2.CompareData(data, 'volume');
    var echelle_volume = canvas.height * 0.2 / borneY2.max;

    var borneX = new Borne();
    borneX.CompareData(data, 'mts');

    if (origine_echelle == undefined) {
        var WT = canvas.width;
        var HT = canvas.height;

        Recadrage.W = WT - Recadrage.X;
        Recadrage.H = HT - Recadrage.Y;

        echelle_x = Recadrage.W / (borneX.max - borneX.min); //data.length;
        echelle_y = (force_y_zero) ? Recadrage.H / Math.max(Math.abs(borneY.max), Math.abs(borneY.min)) : Recadrage.H / (borneY.max - borneY.min);

        pos_x = borneX.min * echelle_x;
        pos_y = (force_y_zero) ? (borneY.max < 0 ? Recadrage.H : 0) : echelle_y * borneY.min;

        var calc = new CalculsCoordonnes({ origine: { x: pos_x, y: pos_y }, echelle: { x: echelle_x, y: echelle_y } }, Recadrage);
    } else {
        echelle_x = origine_echelle.echelle.x;
        echelle_y = origine_echelle.echelle.y;

        pos_x = origine_echelle.origine.x;
        pos_y = origine_echelle.origine.y;

        var calc = origine_echelle;
    }

    var ctx = canvas.getContext("2d");

    ctx.save();

    ctx.beginPath();
    ctx.strokeStyle = "#00000000";
    ctx.rect(calc.Recadrage.X, 0, calc.Recadrage.X + calc.Recadrage.W, calc.Recadrage.H);
    ctx.stroke();
    ctx.clip();

    ctx.lineWidth = 1;

    for (i = 0; i < data.length; i++) {
        if (data[i].open > data[i].close)
            ctx.fillStyle = "#FF0000";
        else
            ctx.fillStyle = "#00FF00";

        if (i < data.length - 1) {
            var delta_x_mts = (data[i + 1].mts - data[i].mts) * calc.echelle.x - 1;
            var largeur_bougie = Math.max(1, delta_x_mts);
        } else {
            var delta_x_mts = (data[i].mts - data[i - 1].mts) * calc.echelle.x - 1;
            var largeur_bougie = Math.max(1, delta_x_mts);
        }

        var h = calc.echelle.y * (data[i].open - data[i].close);
        ctx.fillRect(calc.ValeurToX(data[i].mts), calc.ValeurToY(data[i].open),
            largeur_bougie, (h > 0 ? Math.max(1, h) : Math.min(-1, h)));

        ctx.fillStyle = "#FF80FF80";

        ctx.fillRect(calc.ValeurToX(data[i].mts), calc.Recadrage.H,
            largeur_bougie, -data[i].volume * echelle_volume);

        ctx.strokeStyle = "#FFFFFFA0";

        ctx.beginPath();

        ctx.moveTo(calc.ValeurToX(data[i].mts) + largeur_bougie / 2, calc.ValeurToY(data[i].low));
        ctx.lineTo(calc.ValeurToX(data[i].mts) + largeur_bougie / 2, calc.ValeurToY(data[i].high));

        ctx.stroke();

    }

    ctx.restore();

    if (repere) {
        // dessine repere orthonormé
        ctx.beginPath();
        ctx.lineWidth = 2;
        ctx.strokeStyle = "#FF00FF";
        ctx.moveTo(calc.Recadrage.X, calc.Recadrage.H);
        ctx.lineTo(calc.Recadrage.X, 0);
        ctx.moveTo(calc.Recadrage.X, calc.Recadrage.H);
        ctx.lineTo(calc.Recadrage.X + calc.Recadrage.W, calc.Recadrage.H);
        ctx.stroke();

        // dessine les reperes
        // repere axe des X
        var Nombre_repere = 5;
        var distance_repere = calc.Recadrage.W / Nombre_repere;

        ctx.beginPath();
        ctx.lineWidth = 1;
        ctx.strokeStyle = "#FFFFFF";

        ctx.moveTo(calc.Recadrage.X, calc.ValeurToY(0));
        ctx.lineTo(calc.Recadrage.X + calc.Recadrage.W, calc.ValeurToY(0));

        ctx.stroke();

        ctx.beginPath();
        ctx.lineWidth = 1;
        ctx.strokeStyle = "#FFA0FF";
        for (i = 0; i < Nombre_repere; i++) {
            var id = Math.round(i * distance_repere) + calc.Recadrage.X;
            var mts = calc.XToValeur(id);

            I = data.findIndex(function(element, indice, tab) { return element['mts'] >= this; }, mts);

            if (0 <= I && I < data.length) {
                ctx.moveTo(calc.ValeurToX(data[I].mts), calc.Recadrage.H);
                ctx.lineTo(calc.ValeurToX(data[I].mts), calc.Recadrage.H + 10);

                ctx.font = "12px Courier";
                var s = data[I].mts_text;
                ctx.strokeText(s, calc.ValeurToX(data[I].mts), calc.Recadrage.H + 20);
            }
        }
        ctx.stroke();

        // repere axe des Y des prix
        // 
        //  Recadrage.H - echelle_y * data[i] - pos_y = Recadrage.H - py 
        // - py + pos_y / -echelle_y = data[i]
        var Nombre_repere = 5;
        var distance_repere = calc.Recadrage.H / Nombre_repere;

        ctx.beginPath();
        ctx.lineWidth = 1;
        ctx.strokeStyle = "#00FF00";
        for (i = 0; i < Nombre_repere; i++) {
            var py = i * distance_repere;
            ctx.moveTo(calc.Recadrage.X, calc.Recadrage.H - py);
            ctx.lineTo(calc.Recadrage.X - 20, calc.Recadrage.H - py);

            ctx.font = "12px Courier";
            var s = calc.YToValeur(calc.Recadrage.H - py);
            s = (typeof log_y == "undefined") ? s : (log_y ? Math.pow(10, s) : s);
            //p = Math.floor(Math.log10(s)) - 5;
            //p = Math.pow(10,p);
            //s = Math.floor(s / p) * p;
            ctx.strokeText(s.toPrecision(5), 10, calc.Recadrage.H - py - 3);
        }
        ctx.stroke();
    }

    return calc;
}

function TraceBougiesZone(canvas_id1, canvas_id2, data1, data2, origine_echelle1, origine_echelle2, f_comp) {

    var canvas1 = document.getElementById(canvas_id1);
    var canvas2 = document.getElementById(canvas_id2);

    var ctx1 = canvas1.getContext("2d");
    var ctx2 = canvas2.getContext("2d");

    ctx1.save();
    ctx1.strokeStyle = "#00000000";
    ctx1.rect(origine_echelle1.Recadrage.X, 0, origine_echelle1.Recadrage.X + origine_echelle1.Recadrage.W, origine_echelle1.Recadrage.H);
    ctx1.stroke();
    ctx1.clip();
    ctx1.lineWidth = 1;

    ctx2.save();
    ctx2.strokeStyle = "#00000000";
    ctx2.rect(origine_echelle2.Recadrage.X, 0, origine_echelle2.Recadrage.X + origine_echelle2.Recadrage.W, origine_echelle2.Recadrage.H);
    ctx2.stroke();
    ctx2.clip();
    ctx2.lineWidth = 1;

    for (var i = 0; i < data1.length; i++) {

        var comp = f_comp(data1[i]);
        var I = data2.findIndex(function(element, indice, tab) { return f_comp(element) == comp; });
        //data2.find2(e => f_comp(e) == comp);

        if (I >= 0) {

            if (i < data1.length - 1) {
                var delta_x_mts = (data1[i + 1].mts - data1[i].mts) * origine_echelle1.echelle.x - 1;
                var largeur_bougie = Math.max(1, delta_x_mts);
            } else {
                var delta_x_mts = (data1[i].mts - data1[i - 1].mts) * origine_echelle1.echelle.x - 1;
                var largeur_bougie = Math.max(1, delta_x_mts);
            }

            ctx1.fillStyle = "#00FFFF10";
            ctx1.fillRect(origine_echelle1.ValeurToX(data1[i].mts), 0, largeur_bougie, canvas1.height);

            if (I < data2.length - 1) {
                var delta_x_mts = (data2[I + 1].mts - data2[I].mts) * origine_echelle2.echelle.x - 1;
                var largeur_bougie = Math.max(1, delta_x_mts);
            } else {
                var delta_x_mts = (data2[I].mts - data2[I - 1].mts) * origine_echelle2.echelle.x - 1;
                var largeur_bougie = Math.max(1, delta_x_mts);
            }

            ctx2.fillStyle = "#00FFFF10";
            ctx2.fillRect(origine_echelle2.ValeurToX(data2[I].mts), 0, largeur_bougie, canvas2.height);
        }
    }

    ctx1.restore();
    ctx2.restore();

}

function MyTest(data, condition) {
    var data_max, data_min;

    data_max = data.MyMax(function(e) { return e.mts; }).objet;
    data_min = data.MyMin(function(e) { return e.mts; }).objet;

    return data_max.mts - data_min.mts >= condition;
}

// f_max(e) { return e.close; }
Array.prototype.MyMax = function(Calc) {
    var i_max = 0;
    var v_max = Calc(this[0]);

    for (var i = 1; i < this.length; i++) {
        if (v_max < Calc(this[i])) {
            v_max = Calc(this[i]);
            i_max = i;
        }
    }

    return { objet: this[i_max], id: i_max, valeur: v_max };
}

// f_min(e) { return e.close; }
Array.prototype.MyMin = function(Calc) {
    var i_min = 0;
    var v_min = Calc(this[0]);

    for (var i = 1; i < this.length; i++) {
        if (v_min > Calc(this[i])) {
            v_min = Calc(this[i]);
            i_min = i;
        }
    }

    return { objet: this[i_min], id: i_min, valeur: v_min };
}

Array.prototype.MyMin2 = function(Calc) {
    var i_min = 0;
    var v_min = Calc(this[0], 0);

    for (var i = 1; i < this.length; i++) {
        if (v_min > Calc(this[i], i)) {
            v_min = Calc(this[i], i);
            i_min = i;
        }
    }

    return { objet: this[i_min], id: i_min, valeur: v_min };
}

Array.prototype.MyMax2 = function(Calc) {
    var i_max = 0;
    var v_max = Calc(this[0], 0);

    for (var i = 1; i < this.length; i++) {
        if (v_max < Calc(this[i], i)) {
            v_max = Calc(this[i], i);
            i_max = i;
        }
    }

    return { objet: this[i_max], id: i_max, valeur: v_max };
}

function MySupportHaut(data_, espace_min_mts) {
    var array_max = new Array();
    var data = JSON.parse(JSON.stringify(data_));

    do {
        var max = data.MyMax(function(e) { return Math.max(e.close, e.open); });
        data.splice(max.id, 1);
        array_max.push(max.objet);
    }

    while ((array_max.length < 3 || !MyTest(array_max, espace_min_mts)) && data.length > 0)

    return array_max;
}

function MySupportBas(data_, espace_min_mts) {
    var array_min = new Array();
    var data = JSON.parse(JSON.stringify(data_));

    do {
        var min = data.MyMin(function(e) { return Math.min(e.close, e.open); });
        data.splice(min.id, 1);
        array_min.push(min.objet);
    }
    while ((array_min.length < 3 || !MyTest(array_min, espace_min_mts)) && data.length > 0)

    return array_min;
}

// e = function (t) { return t.chose; }
Array.prototype.MyMoyenne = function(e) {
    var somme = 0;

    for (var i = 0; i < this.length; i++) {
        somme += e(this[i]);
    }

    somme /= this.length;

    return somme;
}

function TraceSupportHautBas(canvas_id, data, force_y_zero, origine_echelle, repere, log_y, surface, epaisseur) {

    if (data.length < 2) return;

    var mts_min = Math.abs(data[1].mts - data[0].mts) * 20;
    var data_Haut = MySupportHaut(data, mts_min);
    var data_Bas = MySupportBas(data, mts_min);

    var x1 = data_Bas.MyMin(function(e) { return e.mts; }).objet.mts;
    var x2 = data_Bas.MyMax(function(e) { return e.mts; }).objet.mts;
    var y_Bas = data_Bas.MyMoyenne(function(e) { return Math.min(e.close, e.open); });

    // genere segment
    var segment_bas = new Array();
    segment_bas.push({ x: x1, y: y_Bas });
    segment_bas.push({ x: x2, y: y_Bas });

    var x1 = data_Haut.MyMin(function(e) { return e.mts; }).objet.mts;
    var x2 = data_Haut.MyMax(function(e) { return e.mts; }).objet.mts;
    var y_Haut = data_Haut.MyMoyenne(function(e) { return Math.max(e.close, e.open); });

    var segment_haut = new Array();
    segment_haut.push({ x: x1, y: y_Haut });
    segment_haut.push({ x: x2, y: y_Haut });

    TraceCourbeSimple(canvas_id, segment_haut, { couleur: "#FFFF00" }, force_y_zero, origine_echelle, repere, log_y, surface, epaisseur);
    TraceCourbeSimple(canvas_id, segment_bas, { couleur: "#FFFF00" }, force_y_zero, origine_echelle, repere, log_y, surface, epaisseur);
}

var equation_droite_Haut = new Array(); // { a : 0 , b : 0 } , a*x + b = y
var equation_droite_Bas = new Array();

// Prend uniquement les points ou la bougie haute redescend
Array.prototype.DeriveNulleHaut = function(f) {

    var derive_nulle = new Array();
    var valeur2 = f(this[0]);
    var valeur1 = f(this[1]);
    var valeur = 0;

    for (var i = 2; i < this.length; i++) {

        valeur = f(this[i]);
        if (valeur2 < valeur1 && valeur1 >= valeur) {
            if (i == 2) derive_nulle.push({ objet: this[i - 2].hasOwnProperty('objet') ? this[i - 2].objet : this[i - 2], id: i - 2, pic: true });
            derive_nulle.push({ objet: this[i - 1].hasOwnProperty('objet') ? this[i - 1].objet : this[i - 1], id: i - 1, pic: true });
            if (i == this.length - 1) derive_nulle.push({ objet: this[i].hasOwnProperty('objet') ? this[i].objet : this[i], id: i, pic: true });
        }

        if (valeur2 > valeur1 && valeur1 <= valeur)
            derive_nulle.push({ objet: this[i - 1].hasOwnProperty('objet') ? this[i - 1].objet : this[i - 1], id: i - 1, pic: false });

        valeur2 = valeur1;
        valeur1 = valeur;
    }

    return derive_nulle;
}

// f_min_max = fonction definissant le min et le max
// f_normalize = f_normalize(min,max,e)
Array.prototype.Normalize = function(f_min_max, f_normalize) {

    var max = this.MyMax(function(e) { return f_min_max(e); }).valeur;
    var min = this.MyMin(function(e) { return f_min_max(e); }).valeur;

    for (var i = 0; i < this.length; i++)
        f_normalize(min, max, this[i]);
}

// renvoie l'index qui satisfait en premier la condition sinon renvoie index_defaut
Array.prototype.findIndexDefaut = function(f_condition, index_defaut, arg) {

    var index = this.findIndex(f_condition, arg);
    return index == -1 ? index_defaut : index;

}

Array.prototype.DeriveNulleBas = function(f) {

    var derive_nulle = new Array();
    var valeur2 = f(this[0]);
    var valeur1 = f(this[1]);
    var valeur = 0;

    for (var i = 2; i < this.length; i++) {

        valeur = f(this[i]);

        if (valeur2 > valeur1 && valeur1 <= valeur) {
            if (i == 2) derive_nulle.push({ objet: this[i - 2].hasOwnProperty('objet') ? this[i - 2].objet : this[i - 2], id: i - 2, pic: true });
            derive_nulle.push({ objet: this[i - 1].hasOwnProperty('objet') ? this[i - 1].objet : this[i - 1], id: i - 1, pic: true });
            if (i == this.length - 1) derive_nulle.push({ objet: this[i].hasOwnProperty('objet') ? this[i].objet : this[i], id: i, pic: true });
        }

        if (valeur2 < valeur1 && valeur1 >= valeur)
            derive_nulle.push({ objet: this[i - 1].hasOwnProperty('objet') ? this[i - 1].objet : this[i - 1], id: i - 1, pic: false });

        valeur2 = valeur1;
        valeur1 = valeur;
    }

    return derive_nulle;
}

function Calcule_A_B_Droite(y1, y2, x1, x2) {

    var A = (y2 - y1) / (x2 - x1);
    var B = y1 - A * x1;

    return { A: A, B: B };
}

function Calcule_AB_droite_Y(AB_droite, x) {
    return x * AB_droite.A + AB_droite.B;
}

function Distance2Points(x1, y1, x2, y2) {
    return Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
}

function LongueurDroite(AB_droite, x1, x2) {
    var y1 = Calcule_AB_droite_Y(AB_droite, x1);
    var y2 = Calcule_AB_droite_Y(AB_droite, x2);

    return Distance2Points(x1, y1, x2, y2);
}

// recupere l'ordonnée haute
function Bougie_To_Ymax(bougie) { return Math.max(bougie.close, bougie.open); }

// recupere l'ordonnée basse
function Bougie_To_Ymin(bougie) { return Math.min(bougie.close, bougie.open); }

//var F = function (e2,e1) { return e1.touche == e2.touche ? e1.p_touche - e2.p_touche : e1.touche - e2.touche ; };
var F = function(e2, e1) { return (e1.p_touche * coef_int + e1.touche * coef_touch + e1.longueur * coef_longueur) - (e2.p_touche * coef_int + e2.touche * coef_touch + e2.longueur * coef_longueur) };

// calcule 2 meilleures droites qui touchent le plus
function DetecteMeilleurSupportResistance(data) {

    var droites_bas = new Array();
    var droites_haut = new Array();

    var derive_nulle_haut = data.DeriveNulleHaut(function(e) { return Bougie_To_Ymax(e); });
    var derive_nulle_bas = data.DeriveNulleBas(function(e) { return Bougie_To_Ymin(e); });
    // deuxieme , 3ieme ...passe
    for (; 40 < derive_nulle_haut.length;) {
        var l = derive_nulle_haut.length;
        derive_nulle_haut = derive_nulle_haut.DeriveNulleHaut(function(e) { return Bougie_To_Ymax(e.objet); });
        if (derive_nulle_haut.length == l) {
            derive_nulle_haut = derive_nulle_haut.filter(function(e) { return e.pic; });
        }
    }

    derive_nulle_haut = derive_nulle_haut.filter(function(e) { return e.pic; });

    for (; 40 < derive_nulle_bas.length;) {
        var l = derive_nulle_bas.length;
        derive_nulle_bas = derive_nulle_bas.DeriveNulleBas(function(e) { return Bougie_To_Ymin(e.objet); });
        if (derive_nulle_bas.length == l) {
            derive_nulle_bas = derive_nulle_bas.filter(function(e) { return e.pic; });
        }
    }

    derive_nulle_bas = derive_nulle_bas.filter(function(e) { return e.pic; });

    /*
        {
            var cercles = new Array();
            //segmentBas.push({ x: objet2.objet.mts, y: A * objet2.objet.mts + B });
            //segmentBas.push({ x: this[this.length - 1].mts, y: A * this[this.length - 1].mts + B });
            derive_nulle_bas.forEach(function (element,indice,tab) { cercles.push({x : element.objet.mts , y : Bougie_To_Ymin(element.objet) }) } );
            TraceCourbeSimple("Graph", cercles, "#00FFFF80", false, calc_bougie, false, false, false, 4,true,4);
        }
    
        {
            var cercles = new Array();
            //segmentBas.push({ x: objet2.objet.mts, y: A * objet2.objet.mts + B });
            //segmentBas.push({ x: this[this.length - 1].mts, y: A * this[this.length - 1].mts + B });
            derive_nulle_haut.forEach(function (element,indice,tab) { cercles.push({x : element.objet.mts , y : Bougie_To_Ymax(element.objet) }) } );
            TraceCourbeSimple("Graph", cercles, "#FFFF0080", false, calc_bougie, false, false, false, 4,true,4);
        }
        */

    // combinaison de plusieurs droites possible C(2,40) = 40! / ((40-2)! * 2!) = 40*39/2 = 780 droites
    var derive_nulle_bas_data_moy = new Array();
    var moy_moy = 0;
    for (var k = 0; k < derive_nulle_bas.length; k++) {
        var I = data.findIndex(function(element, indice, tab) { return element['mts'] >= this; }, derive_nulle_bas[k].objet.mts);
        var t = data.slice(I - 3 > 0 ? I - 3 : 0, I + 3 > data.length ? data.length - 1 : I + 3);
        var moy = t.Moyenne(function(e) { return (e.open + e.close) / 2; });
        derive_nulle_bas_data_moy.push({ moy: moy, I: I });
        moy_moy += Math.abs(Bougie_To_Ymin(derive_nulle_bas[k].objet) / moy - 1);
    }
    moy_moy /= derive_nulle_haut.length;
    moy_moy *= coef_touch_aprox; // 0.8

    for (var i1 = 0; i1 < derive_nulle_bas.length - 1; i1++) {

        for (var i2 = i1 + 1; i2 < derive_nulle_bas.length; i2++) {
            var AB_bas = Calcule_A_B_Droite(Bougie_To_Ymin(derive_nulle_bas[i1].objet), Bougie_To_Ymin(derive_nulle_bas[i2].objet),
                derive_nulle_bas[i1].objet.mts, derive_nulle_bas[i2].objet.mts);

            var touche_bougie = new Array();
            var n_touche = 0;
            var sup_touche = 0;
            derive_nulle_bas.forEach(function(element, indice, tab) {

                if (Math.abs(Bougie_To_Ymin(element.objet) / Calcule_AB_droite_Y(AB_bas, element.objet.mts) - 1) < moy_moy) {
                    n_touche++;
                    touche_bougie.push(element.objet);
                }

                if (Calcule_AB_droite_Y(AB_bas, element.objet.mts) <= Bougie_To_Ymin(element.objet)) { sup_touche++; }

            });

            droites_bas.push({
                equation_droite: AB_bas,
                touche: n_touche,
                p_touche: sup_touche,
                touche_bougie: touche_bougie,
                longueur: LongueurDroite(AB_bas, touche_bougie.MyMin(function(e) { return e.mts; }).objet.mts, touche_bougie.MyMax(function(e) { return e.mts; }).objet.mts)
            });

            //if (droites_bas.length > 2) droites_bas.sort(F);
            //if (droites_bas.length > 10) droites_bas = droites_bas.slice(0,10);
        }
    }

    droites_bas.Normalize(function(e) { return e.longueur; }, function(min, max, e) { e.longueur /= max; });
    droites_bas.Normalize(function(e) { return Math.max(e.touche, e.p_touche); }, function(min, max, e) {
        e.touche /= max;
        e.p_touche /= max;
    });

    droites_bas.sort(F);

    // combinaison de plusieurs droites possible 
    var derive_nulle_haut_data_moy = new Array();
    var moy_moy = 0;
    for (var k = 0; k < derive_nulle_haut.length; k++) {
        var I = data.findIndex(function(element, indice, tab) { return element['mts'] >= this; }, derive_nulle_haut[k].objet.mts);
        var t = data.slice(I - 3 > 0 ? I - 3 : 0, I + 3 > data.length ? data.length - 1 : I + 3);
        var moy = t.Moyenne(function(e) { return (e.open + e.close) / 2; });
        derive_nulle_haut_data_moy.push({ moy: moy, I: I });
        moy_moy += Math.abs(Bougie_To_Ymax(derive_nulle_haut[k].objet) / moy - 1);
    }

    moy_moy /= derive_nulle_haut.length;
    moy_moy *= coef_touch_aprox;

    for (var i1 = 0; i1 < derive_nulle_haut.length - 1; i1++) {

        for (var i2 = i1 + 1; i2 < derive_nulle_haut.length; i2++) {
            var AB_haut = Calcule_A_B_Droite(Bougie_To_Ymax(derive_nulle_haut[i1].objet), Bougie_To_Ymax(derive_nulle_haut[i2].objet),
                derive_nulle_haut[i1].objet.mts, derive_nulle_haut[i2].objet.mts);

            var touche_bougie = new Array();
            var n_touche = 0;
            var inf_touche = 0;
            derive_nulle_haut.forEach(function(element, indice, tab) {

                if (Math.abs(Bougie_To_Ymax(element.objet) / Calcule_AB_droite_Y(AB_haut, element.objet.mts) - 1) < moy_moy) {
                    n_touche++;
                    touche_bougie.push(element.objet);
                }

                if (Calcule_AB_droite_Y(AB_haut, element.objet.mts) >= Bougie_To_Ymax(element.objet)) { inf_touche++; }

            });

            droites_haut.push({
                equation_droite: AB_haut,
                touche: n_touche,
                p_touche: inf_touche,
                touche_bougie: touche_bougie,
                longueur: LongueurDroite(AB_haut, touche_bougie.MyMin(function(e) { return e.mts; }).objet.mts, touche_bougie.MyMax(function(e) { return e.mts; }).objet.mts)
            });

            //if (droites_haut.length > 2) droites_haut.sort(F);
            //if (droites_haut.length > 10) droites_haut = droites_haut.slice(0,10);
        }
    }

    droites_haut.Normalize(function(e) { return e.longueur; }, function(min, max, e) { e.longueur /= max; });
    droites_haut.Normalize(function(e) { return Math.max(e.touche, e.p_touche); }, function(min, max, e) {
        e.touche /= max;
        e.p_touche /= max;
    });

    droites_haut.sort(F);

    return { droites_bas_trier: droites_bas, droites_haut_trier: droites_haut };
}

function TraceSupportObliqueHautBasToucheMax(canvas_id, data, force_y_zero, origine_echelle, repere, log_y, surface, epaisseur) {
    if (data.length < 10) return;

    var droites = DetecteMeilleurSupportResistance(data);

    var segmentBas = new Array();
    segmentBas.push({ x: data[0].mts, y: Calcule_AB_droite_Y(droites.droites_bas_trier[0].equation_droite, data[0].mts) });
    segmentBas.push({ x: data.MyLast().mts, y: Calcule_AB_droite_Y(droites.droites_bas_trier[0].equation_droite, data.MyLast().mts) });

    TraceCourbeSimple(canvas_id, segmentBas, { couleur: "#FFFFFF80" }, force_y_zero, origine_echelle, repere, log_y, surface, epaisseur);

    segmentBas = new Array();
    segmentBas.push({ x: data.MyLast().mts, y: Calcule_AB_droite_Y(droites.droites_bas_trier[0].equation_droite, data.MyLast().mts) });
    segmentBas.push({ x: trade.MyLast().mts, y: Calcule_AB_droite_Y(droites.droites_bas_trier[0].equation_droite, trade.MyLast().mts) });

    TraceCourbeSimple(canvas_id, segmentBas, { couleur: "#FFFFFF60", dash: [2, 2] }, force_y_zero, origine_echelle, repere, log_y, surface, epaisseur);

    var cercles = new Array();
    droites.droites_bas_trier[0].touche_bougie.forEach(function(element, indice, tab) {
        cercles.push({
            x: element.mts,
            y: droites.droites_bas_trier[0].equation_droite.A * element.mts + droites.droites_bas_trier[0].equation_droite.B
        })
    });
    TraceCourbeSimple(canvas_id, cercles, { couleur: "#FFFF0080" }, false, origine_echelle, false, false, false, 4, true, 3);


    var segmentHaut = new Array();
    segmentHaut.push({ x: data[0].mts, y: Calcule_AB_droite_Y(droites.droites_haut_trier[0].equation_droite, data[0].mts) });
    segmentHaut.push({ x: data.MyLast().mts, y: Calcule_AB_droite_Y(droites.droites_haut_trier[0].equation_droite, data.MyLast().mts) });

    TraceCourbeSimple(canvas_id, segmentHaut, { couleur: "#FFFFFF80" }, force_y_zero, origine_echelle, repere, log_y, surface, epaisseur);

    var segmentHaut = new Array();
    segmentHaut.push({ x: data.MyLast().mts, y: Calcule_AB_droite_Y(droites.droites_haut_trier[0].equation_droite, data.MyLast().mts) });
    segmentHaut.push({ x: trade.MyLast().mts, y: Calcule_AB_droite_Y(droites.droites_haut_trier[0].equation_droite, trade.MyLast().mts) });

    TraceCourbeSimple(canvas_id, segmentHaut, { couleur: "#FFFFFF60", dash: [2, 2] }, force_y_zero, origine_echelle, repere, log_y, surface, epaisseur);

    var cercles = new Array();
    droites.droites_haut_trier[0].touche_bougie.forEach(function(element, indice, tab) {
        cercles.push({
            x: element.mts,
            y: droites.droites_haut_trier[0].equation_droite.A * element.mts + droites.droites_haut_trier[0].equation_droite.B
        })
    });

    TraceCourbeSimple(canvas_id, cercles, { couleur: "#FFFF0080" }, false, origine_echelle, false, false, false, 4, true, 3);
}

// recupere le dernier objet du tableau
Array.prototype.MyLast = function() { return this.length > 0 ? this[this.length - 1] : null; }

// recupere le premier objet du tableau
Array.prototype.MyFirst = function() { return this.length > 0 ? this[0] : null; }

// f_x = function (e) { return e.mts; }
// f_y = function (e) { return Math.min(); }
Array.prototype.SupportBas = function(f_x, f_y) {

    // Y = A * X + B
    var I1 = 0;
    var I2 = this.length - 1;

    var objet1 = new Array();
    objet1.push({ objet: this[I1], id: I1 });
    var objet2 = new Array();
    objet2.push({ objet: this[I2], id: I2 });

    var A = (f_y(objet2.MyLast().objet) - f_y(objet1.MyLast().objet)) / (f_x(objet2.MyLast().objet) - f_x(objet1.MyLast().objet));
    var B = f_y(objet2.MyLast().objet) - A * f_x(objet2.MyLast().objet);

    do {
        I1++;
        I1 = I1 < I2 ? I1 : I1 - 1;

        if (A * f_x(this[I1]) + B > f_y(this[I1])) {
            objet1.push({ objet: this[I1], id: I1 });
            A = (f_y(objet2.MyLast().objet) - f_y(objet1.MyLast().objet)) / (f_x(objet2.MyLast().objet) - f_x(objet1.MyLast().objet));
            B = f_y(objet1.MyLast().objet) - A * f_x(objet1.MyLast().objet);

            objet2.forEach(function(e, id, t) {

                if (A * f_x(e.objet) + B > f_y(e.objet)) {
                    A = (f_y(e.objet) - f_y(objet1.MyLast().objet)) / (f_x(e.objet) - f_x(objet1.MyLast().objet));
                    B = f_y(e.objet) - A * f_x(e.objet);
                }

            });
        }

        /*
        TracerBougiesAchatVente("Graph_svg", "Graph", false, calc_bougie, true, false);
    
        var segmentBas = new Array();
        segmentBas.push({ x: this[0].mts, y: A * this[0].mts + B });
        segmentBas.push({ x: objet1.objet.mts, y: A * objet1.objet.mts + B });
        TraceCourbeSimple("Graph", segmentBas, "#00FFFF80", false, calc_bougie, false, false, false, 4);
    
        var segmentBas = new Array();
        segmentBas.push({ x: objet1.objet.mts, y: A * objet1.objet.mts + B });
        segmentBas.push({ x: objet2.objet.mts, y: A * objet2.objet.mts + B });
        TraceCourbeSimple("Graph", segmentBas, "#FF808080", false, calc_bougie, false, false, false, 4);
    
        var segmentBas = new Array();
        segmentBas.push({ x: objet2.objet.mts, y: A * objet2.objet.mts + B });
        segmentBas.push({ x: this[this.length - 1].mts, y: A * this[this.length - 1].mts + B });
        TraceCourbeSimple("Graph", segmentBas, "#00FFFF80", false, calc_bougie, false, false, false, 4);
        */

        I2--;
        I2 = I1 < I2 ? I2 : I2 + 1;

        if (A * f_x(this[I2]) + B > f_y(this[I2])) {
            objet2.push({ objet: this[I2], id: I2 });
            A = (f_y(objet2.MyLast().objet) - f_y(objet1.MyLast().objet)) / (f_x(objet2.MyLast().objet) - f_x(objet1.MyLast().objet));
            B = f_y(objet1.MyLast().objet) - A * f_x(objet1.MyLast().objet);

            objet1.forEach(function(e, id, t) {

                if (A * f_x(e.objet) + B > f_y(e.objet)) {
                    A = (f_y(objet2.MyLast().objet) - f_y(e.objet)) / (f_x(objet2.MyLast().objet) - f_x(e.objet));
                    B = f_y(e.objet) - A * f_x(e.objet);
                }

            });
        }

        /*
        TracerBougiesAchatVente("Graph_svg", "Graph", false, calc_bougie, true, false);
    
        var segmentBas = new Array();
        segmentBas.push({ x: this[0].mts, y: A * this[0].mts + B });
        segmentBas.push({ x: objet1.objet.mts, y: A * objet1.objet.mts + B });
        TraceCourbeSimple("Graph", segmentBas, "#00FFFF80", false, calc_bougie, false, false, false, 4);
    
        var segmentBas = new Array();
        segmentBas.push({ x: objet1.objet.mts, y: A * objet1.objet.mts + B });
        segmentBas.push({ x: objet2.objet.mts, y: A * objet2.objet.mts + B });
        TraceCourbeSimple("Graph", segmentBas, "#FF808080", false, calc_bougie, false, false, false, 4);
    
        var segmentBas = new Array();
        segmentBas.push({ x: objet2.objet.mts, y: A * objet2.objet.mts + B });
        segmentBas.push({ x: this[this.length - 1].mts, y: A * this[this.length - 1].mts + B });
        TraceCourbeSimple("Graph", segmentBas, "#00FFFF80", false, calc_bougie, false, false, false, 4);
        */


    }
    while (I1 + 1 < I2)

    return { 'A': A, 'B': B, 'objet1': objet1, 'objet2': objet2 };
}

// f_x = function (e) { return e.mts; }
// f_y = function (e) { return Math.min(); }
Array.prototype.SupportHaut = function(f_x, f_y) {

    // Y = A * X + B
    var I1 = 0;
    var I2 = this.length - 1;

    var objet1 = new Array();
    objet1.push({ objet: this[I1], id: I1 });
    var objet2 = new Array();
    objet2.push({ objet: this[I2], id: I2 });

    var A = (f_y(objet2.MyLast().objet) - f_y(objet1.MyLast().objet)) / (f_x(objet2.MyLast().objet) - f_x(objet1.MyLast().objet));
    var B = f_y(objet2.MyLast().objet) - A * f_x(objet2.MyLast().objet);

    do {
        I1++;
        I1 = I1 < I2 ? I1 : I1 - 1;

        if (A * f_x(this[I1]) + B < f_y(this[I1])) {

            objet1.push({ objet: this[I1], id: I1 });
            A = (f_y(objet2.MyLast().objet) - f_y(objet1.MyLast().objet)) / (f_x(objet2.MyLast().objet) - f_x(objet1.MyLast().objet));
            B = f_y(objet1.MyLast().objet) - A * f_x(objet1.MyLast().objet);

            objet2.forEach(function(e, id, t) {

                if (A * f_x(e.objet) + B < f_y(e.objet)) {
                    A = (f_y(e.objet) - f_y(objet1.MyLast().objet)) / (f_x(e.objet) - f_x(objet1.MyLast().objet));
                    B = f_y(e.objet) - A * f_x(e.objet);
                }

            });
        }

        I2--;
        I2 = I1 < I2 ? I2 : I2 + 1;

        if (A * f_x(this[I2]) + B < f_y(this[I2])) {

            objet2.push({ objet: this[I2], id: I2 });
            A = (f_y(objet2.MyLast().objet) - f_y(objet1.MyLast().objet)) / (f_x(objet2.MyLast().objet) - f_x(objet1.MyLast().objet));
            B = f_y(objet2.MyLast().objet) - A * f_x(objet2.MyLast().objet);

            objet1.forEach(function(e, id, t) {

                if (A * f_x(e.objet) + B < f_y(e.objet)) {
                    A = (f_y(objet2.MyLast().objet) - f_y(e.objet)) / (f_x(objet2.MyLast().objet) - f_x(e.objet));
                    B = f_y(e.objet) - A * f_x(e.objet);
                }

            });

        }

    }
    while (I1 + 1 < I2)

    return { 'A': A, 'B': B, 'objet1': objet1, 'objet2': objet2 };
}


function TraceSupportObliqueHautBas(canvas_id, data, force_y_zero, origine_echelle, repere, log_y, surface, epaisseur) {

    if (data.length < 2) return;

    var Bas = data.SupportBas(function(e) { return e.mts; }, function(e) { return Math.min(e.close, e.open); });
    var Haut = data.SupportHaut(function(e) { return e.mts; }, function(e) { return Math.max(e.close, e.open); });

    var segmentBas = new Array();

    segmentBas.push({ x: data[0].mts, y: Bas.A * data[0].mts + Bas.B });
    segmentBas.push({ x: data.MyLast().mts, y: Bas.A * data.MyLast().mts + Bas.B });

    var segmentHaut = new Array();

    segmentHaut.push({ x: data[0].mts, y: Haut.A * data[0].mts + Haut.B });
    segmentHaut.push({ x: data.MyLast().mts, y: Haut.A * data.MyLast().mts + Haut.B });

    var segmentMedian = new Array();

    segmentMedian.push({ x: segmentHaut[0].x, y: (segmentHaut[0].y + segmentBas[0].y) / 2 });
    segmentMedian.push({ x: segmentHaut[1].x, y: (segmentHaut[1].y + segmentBas[1].y) / 2 });

    TraceCourbeSimple(canvas_id, segmentBas, { couleur: "#00FFFF80" }, force_y_zero, origine_echelle, repere, log_y, surface, epaisseur);
    TraceCourbeSimple(canvas_id, segmentHaut, { couleur: "#00FFFF80" }, force_y_zero, origine_echelle, repere, log_y, surface, epaisseur);
    TraceCourbeSimple(canvas_id, segmentMedian, { couleur: "#00FFFF80" }, force_y_zero, origine_echelle, repere, log_y, surface, epaisseur);

    /*
        var derive_nulle_old;
        var derive_nulle = data.map(function (e) { return {objet : e , id : 0}; } ) ;
        do
        {
            var t = derive_nulle.map(function (e) { return e.objet; });
            derive_nulle = t.DeriveNulleBas(function (e) { return Math.min(e.close, e.open); });
            if (derive_nulle.length >= 2) derive_nulle_old = derive_nulle;
        }
        while (derive_nulle.length > 4)
    
        var courbe = new Array();
    
        for (var i = 0; i < derive_nulle_old.length; i++) {
            courbe.push({ x: derive_nulle_old[i].objet.mts, y: Math.min(derive_nulle_old[i].objet.close, derive_nulle_old[i].objet.open) });
        }
    
        TraceCourbeSimple(canvas_id, courbe, "#00FFFF80", force_y_zero, origine_echelle, repere, log_y, surface, epaisseur);
    
    
    
        var derive_nulle_old;
        var derive_nulle = data.map(function (e) { return { objet: e, id: 0 }; });
        do {
            var t = derive_nulle.map(function (e) { return e.objet; });
            derive_nulle = t.DeriveNulleHaut(function (e) { return Math.max(e.close, e.open); });
            if (derive_nulle.length >= 2) derive_nulle_old = derive_nulle;
        }
        while (derive_nulle.length > 4)
    
        var courbe = new Array();
    
        for (var i = 0; i < derive_nulle_old.length; i++) {
            courbe.push({ x: derive_nulle_old[i].objet.mts, y: Math.max(derive_nulle_old[i].objet.close, derive_nulle_old[i].objet.open) });
        }
    
        TraceCourbeSimple(canvas_id, courbe, "#00FFFF80", force_y_zero, origine_echelle, repere, log_y, surface, epaisseur);
        */

}

function TraceCourbeSimpleSVG(svg_id, data, couleur, force_y_zero, origine_echelle, epaisseur) {
    var svg = document.getElementById(svg_id);

    var echelle_x = 0;
    var echelle_y = 0;
    var pos_x = 0;
    var pos_y = 0;

    var borneY = new Borne();
    borneY.CompareData(data, 'y');
    var borneX = new Borne();
    borneX.CompareData(data, 'x');

    if (typeof origine_echelle == "undefined") {
        var WT = svg.width.animVal.value;
        var HT = svg.height.animVal.value;
        Recadrage.W = WT - Recadrage.X;
        Recadrage.H = HT - Recadrage.Y;

        echelle_x = Recadrage.W / (borneX.max - borneX.min); //data.length;
        echelle_y = (force_y_zero) ? Recadrage.H / Math.max(Math.abs(borneY.max), Math.abs(borneY.min)) : Recadrage.H / (borneY.max - borneY.min);

        pos_x = borneX.min * echelle_x;
        pos_y = (force_y_zero) ? (borneY.max < 0 ? Recadrage.H : 0) : echelle_y * borneY.min;

        var calc = new CalculsCoordonnes({ origine: { x: pos_x, y: pos_y }, echelle: { x: echelle_x, y: echelle_y } }, Recadrage);
    } else {
        echelle_x = origine_echelle.echelle.x;
        echelle_y = origine_echelle.echelle.y;

        pos_x = origine_echelle.origine.x;
        pos_y = origine_echelle.origine.y;

        var calc = origine_echelle;
    }

    epaisseur = typeof epaisseur == "undefined" ? 2 : epaisseur;

    for (i = 0; i < data.length - 1; i++) {
        //html = '<line onmouseover="alert(i);" x1="' + calc.ValeurToX(data[i].x) + '" y1="' + calc.ValeurToY(data[i].y) + '" x2="' + calc.ValeurToX(data[i + 1].x) + '" y2="' + calc.ValeurToY(data[i+1].y) + '" style="stroke:rgb(255,255,255);stroke-width:' + epaisseur + '" />';
        var child = document.createElementNS("http://www.w3.org/2000/svg", 'line');

        var param_event_mouse = { indices: null, couleur: "#FF0000" };
        param_event_mouse.indices = data[i].id;
        param_event_mouse.couleur = data[i].couleur;

        child.setAttribute("onmouseover", "MouseOverSVG1(" + JSON.stringify(param_event_mouse) + ")"); // var serializedArr = JSON.stringify( arr );   { indices : data[i].id , couleur: couleur }.PropertySerialize()
        child.setAttribute("onmouseout", "MouseOutSVG1(" + JSON.stringify(param_event_mouse) + ")");
        child.setAttribute("x1", calc.ValeurToX(data[i].x));
        child.setAttribute("y1", calc.ValeurToY(data[i].y));
        child.setAttribute("x2", calc.ValeurToX(data[i + 1].x));
        child.setAttribute("y2", calc.ValeurToY(data[i + 1].y));
        child.style.stroke = "#FFFFFF00";
        child.style.strokeWidth = epaisseur;
        svg.appendChild(child);
        /*
                    // <circle cx="50" cy="50" r="40" stroke="green" stroke-width="4" fill="yellow" />
                    var child = document.createElementNS("http://www.w3.org/2000/svg", 'circle');
                    //child.setAttribute("onmouseover", "");
                    //child.setAttribute("onmouseout", "");
                    child.setAttribute("id", "circle" + (i + 1));
                    child.setAttribute("cx", calc.ValeurToX(data[i].x));
                    child.setAttribute("cy", calc.ValeurToY(data[i].y));
                    child.setAttribute("r", "3");
                    child.style.stroke = "#FFFFFFFF";
                    child.style.strokeWidth = "1";
                    child.style.fill = "#FF00FFFF";
                    child.style.display = "block";
                    svg.appendChild(child);
            
                    if (i + 1 == data.length - 1) {
                        var child = document.createElementNS("http://www.w3.org/2000/svg", 'circle');
                        //child.setAttribute("onmouseover", "");
                        //child.setAttribute("onmouseout", "");
                        child.setAttribute("id", "circle"+(i+1));
                        child.setAttribute("cx", calc.ValeurToX(data[i+1].x));
                        child.setAttribute("cy", calc.ValeurToY(data[i+1].y));
                        child.setAttribute("r", "3");
                        child.style.stroke = "#FFFFFFFF";
                        child.style.strokeWidth = "1";
                        child.style.fill = "#FF00FFFF";
                        child.style.display = "block";
                        svg.appendChild(child);
                    }
        */

    }
    return calc; //{origine:{x:pos_x,y:pos_y},echelle:{x:echelle_x,y:echelle_y}};
}