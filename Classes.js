Array.prototype.Sum = function(f) {

    var sum = 0;
    for (var i = 0; i < this.length; i++)
        sum += f(this[i]);

    return sum;
}

// Fournit la moyenne avec un callback donné
Array.prototype.Moyenne = function(f) {

    var somme = this.Sum(f);

    moy = somme / this.length;
    return moy;
}

// Fournit l'ecartype avec un callback donné
Array.prototype.ecartType = function(f, coef) {

    if (coef == undefined)
        coef = 1;

    var moy = this.Moyenne(f);
    var ecart = 0;

    for (var i = 0; i < this.length; i++)
        ecart += Math.pow(f(this[i]) - moy, 2);

    ecart = Math.sqrt(ecart);
    ecart /= this.length;
    ecart *= coef;

    return ecart;
}

Array.prototype.Min = function(f) {

    if (this.length == 0)
        return 0;

    var min = f(this[0]);

    for (var i = 1; i < this.length; i++) {
        var t = f(this[i]);
        if (min > t) min = t;
    };

    return min;
}

Array.prototype.Max = function(f) {

    if (this.length == 0)
        return 0;

    var max = f(this[0]);

    for (var i = 1; i < this.length; i++) {
        var t = f(this[i]);
        if (max < t) max = t;
    };

    return max;
}

Array.prototype.Borne = function(f) {

    if (this.length == 0)
        return { min: 0, max: 0 };

    var max = f(this[0]);
    var min = max;

    for (var i = 1; i < this.length; i++) {
        var t = f(this[i]);
        if (max < t) max = t;
        if (min > t) min = t;
    };

    return { min: min, max: max };
}

Array.prototype.Borne2 = function(f_max, f_min) {

    if (this.length == 0)
        return { min: 0, max: 0 };

    var max = this.Max(f_max);
    var min = this.Min(f_min);

    return { min: min, max: max };
}

Array.prototype.ToArray = function(f) {

    var a = new Array();

    this.forEach(function(element, indice, tab) {

        var t = f(element, indice, tab);

        if (t != null)
            a.push(t);

    });

    return a;
}

Array.prototype.Where = function(f) {

    var a = new Array();

    this.forEach(function(element, indice, tab) {

        if (f(element))
            a.push(element);

    });

    return a;
}

Array.prototype.ecartType2Courbes_Absolu = function(f_Y, f_X, array2, f_T = null) {

    var sum = 0;
    var cmpt = 0;

    //this.forEach(function(element, indice, tab) {
    for (var i = 0; i < this.length; i++) {

        var x = f_X(this[i]);
        var e = array2.find2(function(h) { return f_X(h) == x; }, f_T);
        if (e != null) {
            sum += Math.abs(f_Y(this[i]) - f_Y(e));
            cmpt++;
        }

    };

    if (cmpt == 0)
        return { coef: 1e30, cmpt: 0 };

    return { coef: sum / cmpt, cmpt: cmpt / this.length };
}

Array.prototype.ecartType2Courbes_Relatif = function(f_Y, f_X, array2, f_T = null) {

    var sum = 0;
    var cmpt = 0;

    var array1_bis = new Array();
    var array2_bis = new Array();

    for (var i = 0; i < this.length; i++) {

        var x = f_X(this[i]);
        var e = array2.find2(function(h) { return f_X(h) == x; }, f_T);
        if (e != null) {
            array1_bis.push(this[i]);
            array2_bis.push(e);
        }

    }

    if (array1_bis.length == 0)
        return { coef: 1e30, cmpt: 0 };

    var b1 = array1_bis.Borne(z => f_Y(z));
    var b2 = array2_bis.Borne(z => f_Y(z));

    var KY1 = 1 / (b1.max - b1.min);
    var KY2 = 1 / (b2.max - b2.min);

    cmpt = array1_bis.length;
    //this.forEach(function(element, indice, tab) {
    for (var i = 0; i < cmpt; i++) {

        sum += Math.abs((f_Y(array1_bis[i]) - b1.min) * KY1 - (f_Y(array2_bis[i]) - b2.min) * KY2);

    };

    return { coef: sum / cmpt, cmpt: cmpt / this.length };
}

// f = condition du predicat
// g = condition pour sortir de la boucle
Array.prototype.find2 = function(f, g = null) {

    if (g == null) {

        //this.forEach(function(e, i, t) {
        for (var i = 0, e = this[i]; i < this.length; i++, e = this[i]) {
            if (f(e))
                return e;
        };

    } else {

        //this.forEach(function(e, i, t) {
        for (var i = 0, e = this[i]; i < this.length; i++, e = this[i]) {
            if (g(e))
                return null;

            if (f(e))
                return e;
        };

    }

    return null;
}


function Image() {
    this.x = 0;
    this.y = 0;
    this.w = 0;
    this.h = 0;
    this.image_data = undefined;
    this.ctx = undefined;

    Image.prototype.putImageData = function() {
        if (this.ctx != undefined && this.image_data != undefined)
            this.ctx.putImageData(this.image_data, this.x, this.y);
    };

    Image.prototype.putImageData_Ctx = function(ctx) {
        if (ctx != undefined && this.image_data != undefined)
            ctx.putImageData(this.image_data, this.x, this.y);
    };

    Image.prototype.getImageData = function(ctx, x, y, w, h) {
        this.ctx = ctx;
        this.x = x;
        this.y = y;
        this.w = w;
        this.h = h;
        this.image_data = this.ctx.getImageData(x, y, w, h);
    };

    Image.prototype.Clear = function() {
        this.x = 0;
        this.y = 0;
        this.w = 0;
        this.h = 0;
        this.image_data = undefined;
        this.ctx = undefined;
    };

}