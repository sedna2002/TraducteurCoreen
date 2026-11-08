function dateToString(date2, date1) {
    var diff = date2.getTime() - date1.getTime();

    var days = Math.floor(diff / (1000 * 60 * 60 * 24));
    diff -= days * (1000 * 60 * 60 * 24);

    var hours = Math.floor(diff / (1000 * 60 * 60));
    diff -= hours * (1000 * 60 * 60);

    var mins = Math.floor(diff / (1000 * 60));
    diff -= mins * (1000 * 60);

    var seconds = Math.floor(diff / (1000));
    diff -= seconds * (1000);

    return days + "j " + hours + "h " + mins + "min " + seconds + "s";
}

class MaRegExp extends RegExp {
    constructor(pattern, flags, count) {
            super(pattern, flags);
            this.count = count;
        }
        [Symbol.replace](str, replacement) {
            // Applique @@replace |count| fois.
            var result = str;
            for (var i = 0; i < this.count; i++) {
                result = RegExp.prototype[Symbol.replace].call(this, result, replacement);
            }
            return result;
        }
}


// Affiche sur un format personnalisé 
// Y = année
// M = mois
// D = jours du mois
// H = heure
// m = minutes
// s = secondes
// ms = milisecondes
Date.prototype.format = function(f) {

    f = f.replace(new MaRegExp('^MM[^>]|[^<]MM[^>]|[^<]MM$', '', 1), function(s) { return s.replace('MM', '<MM>'); });
    //f = f.replace('MM', '<MM>');
    f = f.replace(new MaRegExp('^Y[^>]|[^<]Y[^>]|[^<]Y$', '', 1), function(s) { return s.replace('Y', '<Y>'); });
    f = f.replace(new MaRegExp('^M[^>]|[^<]M[^>]|[^<]M$', '', 1), function(s) { return s.replace('M', '<M>'); });
    f = f.replace(new MaRegExp('^D[^>]|[^<]D[^>]|[^<]D$', '', 1), function(s) { return s.replace('D', '<D>'); });
    f = f.replace(new MaRegExp('^H[^>]|[^<]H[^>]|[^<]H$', '', 1), function(s) { return s.replace('H', '<H>'); });
    f = f.replace(new MaRegExp('^ms[^>]|[^<]ms[^>]|[^<]ms$', '', 1), function(s) { return s.replace('ms', '<ms>'); });
    f = f.replace(new MaRegExp('^m[^>]|[^<]m[^>]|[^<]m$', '', 1), function(s) { return s.replace('m', '<m>'); });
    f = f.replace(new MaRegExp('^s[^>]|[^<]s[^>]|[^<]s$', '', 1), function(s) { return s.replace('s', '<s>'); });
    f = f.replace(new MaRegExp('^l[^>]|[^<]l[^>]|[^<]l$', '', 1), function(s) { return s.replace('l', '<l>'); });

    f = f.replace('<MM>', Date.months[this.getMonth()]);
    f = f.replace('<Y>', this.getFullYear());
    f = f.replace('<M>', this.getMonth() + 1 < 10 ? "0" + (this.getMonth() + 1) : this.getMonth() + 1);
    f = f.replace('<D>', this.getDate() < 10 ? "0" + this.getDate() : this.getDate());
    f = f.replace('<H>', this.getHours() < 10 ? "0" + this.getHours() : this.getHours());
    f = f.replace('<ms>', this.getMilliseconds());
    f = f.replace('<m>', this.getMinutes() < 10 ? "0" + this.getMinutes() : this.getMinutes());
    f = f.replace('<s>', this.getSeconds() < 10 ? "0" + this.getSeconds() : this.getSeconds());
    f = f.replace('<l>', Date.week_days[this.getDay()]);

    return f;
};

Date.prototype.clone = function() {
    return new Date(this.getFullYear(), this.getMonth(), this.getDate(), this.getHours(), this.getMinutes(), this.getSeconds(), this.getMilliseconds());
};

Date.prototype.getAllHours = function() {
    var h = this.getHours() + this.getMinutes() / 60 + this.getSeconds() / 3600 + date.getMilliseconds() / 3600000;
    return h;
}

Date.prototype.getAllDate = function() {
    var h = this.getDate() + this.getHours() / 24 + this.getMinutes() / (60 * 24) + this.getSeconds() / (3600 * 24) + date.getMilliseconds() / (3600000 * 24);
}

Date.week_days = ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"];
Date.months = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Decembre"];


function TimeSpan(m) {
    this.hours = 0;
    this.days = 0;
    this.minutes = 0;
    this.seconds = 0;
    this.miliseconds = 0;
    this.miliseconds_o = m;

    this.days = Math.floor(m / (1000 * 60 * 60 * 24));
    m -= this.days * (1000 * 60 * 60 * 24);

    this.hours = Math.floor(m / (1000 * 60 * 60));
    m -= this.hours * (1000 * 60 * 60);

    this.minutes = Math.floor(m / (1000 * 60));
    m -= this.minutes * (1000 * 60);

    this.seconds = Math.floor(m / (1000));
    m -= this.seconds * (1000);

    this.miliseconds = m;

    TimeSpan.prototype.format = function(f) {

        f = f.replace(new MaRegExp('^D[^>]|[^<]D[^>]|[^<]D$', '', 1), function(s) { return s.replace('D', '<D>'); });
        f = f.replace(new MaRegExp('^H[^>]|[^<]H[^>]|[^<]H$', '', 1), function(s) { return s.replace('H', '<H>'); });
        f = f.replace(new MaRegExp('^ms[^>]|[^<]ms[^>]|[^<]ms$', '', 1), function(s) { return s.replace('ms', '<ms>'); });
        f = f.replace(new MaRegExp('^m[^>]|[^<]m[^>]|[^<]m$', '', 1), function(s) { return s.replace('m', '<m>'); });
        f = f.replace(new MaRegExp('^s[^>]|[^<]s[^>]|[^<]s$', '', 1), function(s) { return s.replace('s', '<s>'); });

        f = f.replace('<D>', this.days);
        f = f.replace('<H>', this.hours);
        f = f.replace('<ms>', this.miliseconds);
        f = f.replace('<m>', this.minutes);
        f = f.replace('<s>', this.seconds);

        return f;
    }

    TimeSpan.prototype.ToString = function() {

        f = "";
        f += this.days != 0 ? this.days + " j" : f;
        f += this.hours != 0 ? this.hours + " h" : f;
        //f = this.miliseconds != 0 ? f.replace('<ms>', this.miliseconds) + " ms" : f;
        f += this.minutes != 0 ? this.minutes + " min" : f;
        f += this.seconds != 0 ? this.seconds + " s" : f;

        return f;
    }

    return this;
}

// Serialization d'un objet
function PropertySerialize(o) {

    var serial = "{ ";
    var prop = o.getOwnPropertyNames(this);

    for (i = 0; i < prop.length; i++) {
        serial += '"' + prop[i].valueOf() + '"';
        serial += " : ";

        var o = this[prop[i].valueOf()];
        if (Array.isArray(o)) serial += "[" + this[prop[i].valueOf()] + "]";
        else
            switch (typeof o) {
                case "string":
                    serial += '"' + o + '"';
                    break;
                case "number":
                    serial += o;
                    break;
                default:
                    serial += o.PropertySerialize();
                    break;
            }

        if (i < prop.length - 1) serial += ' , ';
    }

    serial += " }";

    return serial;
}

//var serial = JSON.parse('{ "p": 5, "x": [10, 1, 3], "y": 11, "message": "hello" }'); //.PropertySerialize();
//var serial = null;


HTMLTableElement.prototype.InitHeadBodyScroll = function() {

    var table = this; //document.getElementById(id);
    table.style.visibility = "hidden";
    var thead = table.getElementsByTagName('thead')[0];
    var thead_tr = thead.getElementsByTagName('tr')[0];
    var thead_tr_th = thead_tr.getElementsByTagName('th');

    var tbody = table.getElementsByTagName('tbody')[0];
    var tbody_tr = table.getElementsByTagName('tr');
    var thead_width = 0;

    for (var i = 0; i < thead_tr_th.length; i++) {
        if (tbody_tr.length >= 2) {
            var tbody_tr_td = tbody_tr[1].getElementsByTagName('td');
            var width = Math.max(thead_tr_th[i].clientWidth, tbody_tr_td[i].clientWidth);
            thead_tr_th[i].style.width = width - 2; // 
            tbody_tr_td[i].style.width = width - 1; //+ thead_tr_th[i].style.borderWidth - tbody_tr_td[i].style.borderWidth;
            //alert( $('th').css('border-width') ) ;
            //alert( window.getComputedStyle(thead_tr_th[i]).getPropertyValue('border-width') );

            thead_width += width + 2;
        } else
            thead_width += thead_tr_th[i].clientWidth + 2;

    }

    thead.style.width = thead_width;
    tbody.style.width = thead_width + 16;
    table.style.width = thead_width + 16 + 2;
    table.style.visibility = "";
};

HTMLTableElement.prototype.ResizeHeadBodyScroll = function() {
    var width = document.body.clientWidth;
    var table = this; // document.getElementById(id);
    table.style['max-width'] = Math.min(parseInt(table.style.width, 10), width - 20);
};

// method POST , PUT
function Open(url, method, params) {

    var form = document.getElementById("formOpenWindowFC");
    var b = form == null;

    if (b)
        form = document.createElement("form");

    form.setAttribute("id", "formOpenWindowFC");
    form.setAttribute("method", method);
    form.setAttribute("action", url);
    form.setAttribute("target", "_blank");

    while (form.firstChild) { form.removeChild(form.firstChild); }

    Object.keys(params).map(function(key, value) {
        var input = document.createElement("input");
        input.setAttribute("type", "text");
        input.setAttribute("name", key);
        input.setAttribute("value", params[key]);
        form.appendChild(input);
    });

    if (b)
        document.body.appendChild(form);

    form.submit();

};