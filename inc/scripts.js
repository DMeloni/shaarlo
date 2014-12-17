function getMy(){
    document.forms["loginform"].action = "https://www.shaarli.fr/my/" + document.getElementById('pseudo').value + "/";
    document.forms["loginform"].submit();
}       
function showDashboard(){
    document.getElementById('content').className = 'dashboarded';
    addClass(document.getElementById('panel-best'), 'dashboarded');
    document.getElementById("dashboard_icon").style.display="none";
    document.getElementById("dashboard").style.display="block";
}
function hideDashboard(){
    document.getElementById('content').className = '';
    removeClass(document.getElementById('panel-best'), 'dashboarded');
    document.getElementById("dashboard_icon").style.display="block";
    document.getElementById("dashboard").style.display="none";
}                    
function extend(him) {
    removeClass(him.parentNode.parentNode.childNodes[2], 'extended');
    him.innerHTML = '-';
    him.onclick =  function(){ shorten(him); } ;
}
function shorten(him) {
    addClass(him.parentNode.parentNode.childNodes[2], 'extended');
    him.innerHTML = '+';
    him.onclick =  function(){ extend(him); } ;
}
function option_extend(him) {
    removeClass(document.getElementById('bloc-filtre'), 'hidden');
    addClass(document.getElementById('searchform'), 'hidden');
    addClass(him, 'hidden');
}
function removeClass(el, name)
{
    if (hasClass(el, name)) {
        el.className=el.className.replace(new RegExp('(\\s|^)'+name+'(\\s|$)'),' ').replace(/^\s+|\s+$/g, '');
    }
}
function hasClass(el, name) {
    return new RegExp('(\\s|^)'+name+'(\\s|$)').test(el.className);
}

function addClass(el, name)
{
    if (!hasClass(el, name)) { el.className += (el.className ? ' ' : '') +name; }
}

function getChar(event) {
    if (event.which == null) {
        return event.keyCode;
    } else if (event.which!=0 && event.charCode!=0) {
        return event.which;
    } else {
        return null;
    }
}

function ireadit(him, id) 
{
    var r = new XMLHttpRequest(); 
    var params = "do=ireadit&amp;id=" + id;
    r.open("POST", "add.php", true); 
    r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    r.onreadystatechange = function () {
        if (r.readyState == 4) {
            if(r.status == 200){
                var blocArticle = him.parentNode.parentNode;
                removeClass(blocArticle, 'not-read');
                addClass(blocArticle, 'read');
            }
            return true; 
        }
    };
    r.send(params);
}

function save_lock(state) 
{
    var r = new XMLHttpRequest(); 
    var params = "do=lock&amp;state=" + state;
    r.open("POST", "add.php", true); 
    r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    r.onreadystatechange = function () {
        if (r.readyState == 4) {
            return true; 
        }
    };
    r.send(params);
}

function lock_menu(him, elementId)
{
    addClass(document.getElementById('header'), 'add-padding-top-8');
    addClass(document.getElementById(elementId), 'position-fixed');
    addClass(him, 'icon-lock');
    removeClass(him, 'icon-open');
    document.getElementById(elementId).onclick = function () {scroll(0, 0);};
    him.onclick = function () {open_menu(him, elementId);};
    save_lock('lock');
}

function open_menu(him, elementId)
{
    removeClass(document.getElementById('header'), 'add-padding-top-8');
    removeClass(document.getElementById(elementId), 'position-fixed');
    removeClass(him, 'icon-lock');
    addClass(him, 'icon-open');
    document.getElementById(elementId).onclick = function () {return true;};
    him.onclick = function () {lock_menu(him, elementId);};
    save_lock('open');
}   

document.onkeypress = function(event) {
    var char = getChar(event);
    if(char == '339') {
        var els = document.getElementsByClassName("button-extend");
        Array.prototype.forEach.call(els, function(el) {
            extend(el);
        });
    }
    return true;
}
