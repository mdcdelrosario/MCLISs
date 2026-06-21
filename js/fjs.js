var addtag = 'N';

function navdrop(navid) {
    var x = document.getElementById(navid);
    if (x.className.indexOf("w3-show") == -1) {
      x.className += " w3-show";
      x.previousElementSibling.className += " w3-green";
    } else { 
      x.className = x.className.replace(" w3-show", "");
      x.previousElementSibling.className = 
      x.previousElementSibling.className.replace(" w3-green", "");
    }
}

function jdphp(ulink, formid = "", adddata = "")
{
    var rest = "";
    $.ajax({
        type : "POST",
        url : ulink,
        async: false,
        data : $('#' + formid ).serialize() + adddata,
        success: function(data){
            rest = data;
        }
    });
    return JSON.parse(rest);
}

function PageLink(ulink, udiv = "", formid = "", adddata = "")
{
  if (udiv) {
    $('#' + udiv).html('<div style="text-align:center;padding:30px;"><i class="fa fa-spinner fa-spin fa-2x"></i> Loading...</div>');
  }

  if(formid == ""){
    $.ajax({
      url : ulink,
      data : adddata,
      success: function(data){
            $('#'+udiv).html(data);
      } 
    });
  }
  else {
    $.ajax({
        type : "POST",
        url : ulink,
        data : $('#' + formid ).serialize() + adddata,
        success: function(data){
            $('#'+udiv).html(data);
        }
    });
  }
}

function SaveData(ulink, formid, adddata = "" , pulink="", pudiv="", pformid="")
{
  $.ajax({
        type : "POST",
        url : ulink,
        data : $('#' + formid ).serialize() + adddata,
        success: function(data){
            showmsgboxJS('MESSAGE', data);
            // alert(data);
            //$('#main').html(data);
            if(pulink != ""){
                PageLink(pulink,pudiv,pformid);
            }

        }
  });
}

function addDashes(txtID){
    var value = $("#"+txtID).val();
    if(value.length == 2 || value.length == 7)
    {
        $("#"+txtID).val(value+"-");
    }
}

function popdiv(divid,ulink,formdata="",adddata = "",div_dentryID=""){
    PageLink(ulink,divid,formdata,adddata);
    (div_dentryID != "") ? document.getElementById(div_dentryID).style.display = 'block' : document.getElementById('div_dentry').style.display = 'block';
}

//FOR MESSAGE BOX THIS IS PHP ACTION "YES/NO"
function showmsgboxYN(title = '', message = '', action = '', preventDef = ''){
  if (preventDef === "")
    preventDef = 'NO';
    $.ajax({
        type : "POST",
        url : './js/msgbox.php',
        data : 'YESNO=true&msgboxtitle="' + title + '"&msgboxmess="' + message + '"&msgboxaction="' + action + '"&preventDef=' + preventDef,
        success: function(data){
            $('#div_msgbox').html(data);
        }
    });
    $('#div_msgbox').show();
}

//FOR MESSAGE BOX THIS IS JUST "OK"
function showmsgbox(title = '', message = ''){
    $.ajax({
        type : "POST",
        url : './js/msgbox.php',
        data : 'YESNO=false&msgboxtitle="' + title + '"&msgboxmess="' + message + '"&msgtype=plain',
        success: function(data){
            $('#div_msgbox').html(data);
            window.setTimeout(function() {
                $('#btnMsgBoxOk').focus();
                $('#btnMsgBoxOk').select();
                $('#btnMsgBoxOk').on('click', function()
                {
                    $('#div_msgbox').hide();
                    $('#div_msgbox').empty();
                });
            }, 500);
        }
    });
    $('#div_msgbox').show();
}

//FOR MESSAGE BOX THIS IS JUST "OK" WITH JS ACTION
function showmsgboxJS(title = '', message = '', OKaction = function () {}){
    $.ajax({
        type : "POST",
        url : './js/msgbox.php',
        data : 'YESNO=false&msgboxtitle="' + title + '"&msgboxmess="' + message + '"&msgtype=plain',
        success: function(data) {
            $('#div_msgbox').html(data);
            window.setTimeout(function() {
                $('#btnMsgBoxOk').focus();
                $('#btnMsgBoxOk').select();
                $('#btnMsgBoxOk').on('submit', function(e)
                {
                    e.preventDefault();
                });
                $('#btnMsgBoxOk').on('click', function()
                {
                    $('#div_msgbox').hide();
                    $('#div_msgbox').empty();
                    //EXECUTE IF YES IS PRESSED
                    OKaction();
                });
            }, 500);
        }
    });
    $('#div_msgbox').show();
}

//FOR MESSAGE BOX THIS IS JUST "OK" WITH PHP ACTION
function showmsgbox(title = '', message = '', action = '', preventDef = ''){
    $.ajax({
        type : "POST",
        url : './js/msgbox.php',
        data : 'YESNO=false&msgboxtitle="' + title + '"&msgboxmess="' + message + '"&msgtype=php&msgboxaction="' + action + '"&preventDef=' + preventDef,
        success: function(data){
            $('#div_msgbox').html(data);
            $('#btnMsgBoxOk').focus();
            $('#btnMsgBoxOk').select();
        }
    });
    $('#div_msgbox').show();
}

//FOR MESSAGE BOX THIS IS JS ACTION "YES/NO"
function showmsgboxJSYN(title = '', message = '', YESaction = function () {}){
    $.ajax({
        type : "POST",
        url : './js/msgbox.php',
        data : 'YESNO=true&msgboxtitle="' + title + '"&msgboxmess="' + message + '"',
        success: function(data){
            $('#div_msgbox').html(data);
            window.setTimeout(function(){
                $('#btnMsgBoxYes').on('click', function()
                {
                    $('#div_msgbox').hide();
                    $('#div_msgbox').empty();
                    //EXECUTE IF YES IS PRESSED
                    YESaction();
                });
            }, 500);
            //PUT TIMEOUT TO READ THE FUNCTION
        }
    });
    $('#div_msgbox').show();
}

function dateToString(d, xformat=""){
    var xrtrn = "";

    if(xformat=="DTP"){
        const ye = new Intl.DateTimeFormat('en', { year: 'numeric' }).format(d)
        const mo = new Intl.DateTimeFormat('en', { month: '2-digit' }).format(d)
        const da = new Intl.DateTimeFormat('en', { day: '2-digit' }).format(d)
        const hr = new Intl.DateTimeFormat('en', { hour: '2-digit',hour12: false }).format(d)
        const mi = new Intl.DateTimeFormat('en', { minute: '2-digit' }).format(d)
        const se = new Intl.DateTimeFormat('en', { second: '2-digit' }).format(d)
        
        xrtrn =  ye+"-"+mo+"-"+da+"T"+hr+ ":"+mi+":"+se ;
    }
    else
    {
        const ye = new Intl.DateTimeFormat('en', { year: 'numeric' }).format(d)
        const mo = new Intl.DateTimeFormat('en', { month: '2-digit' }).format(d)
        const da = new Intl.DateTimeFormat('en', { day: '2-digit' }).format(d)
        const hr = new Intl.DateTimeFormat('en', { hour: '2-digit',hour12: false }).format(d)
        const mi = new Intl.DateTimeFormat('en', { minute: '2-digit' }).format(d)
        const se = new Intl.DateTimeFormat('en', { second: '2-digit' }).format(d)
        
        xrtrn =  ye+"-"+mo+"-"+da+"T"+hr+ ":"+mi+":"+se ;  
    }

    return xrtrn;

}

function printDivLong(headerDiv, tableDiv) {
    let mywindow = window.open(
        '',
        'PRINT',
        'width=' + screen.availWidth + ' ,height=' + screen.availHeight
    );
    
    //ALAEM DYAY HEIGHT TI DIV TAPNU AG LOOP KA, IN ORDER TO MAKE OTHER PAGES SYEMPRE SIKA MET, AYNA APO AYA HAHAHAHA
    // var divHeight = $('#' + divId).height() + 100;
    // var pageCount = divHeight / (height - 300);
    // pageCount = Math.ceil(pageCount);
    
    //for (var i = 0; i < pageCount; i++) {
    mywindow.document.write(
        `<html>
            <head>
                <style>
                    @media screen, print {
                        * { font-size: 12px; }
                        .` + headerDiv + ` { font-size: 18px !important; }
                    }
                    @page {
                        size: landscape;
                    }
                    .content { height: 200px !important; }
                    table {border-collapse: collapse;}
                    th, td { padding: 1px 8px;}
                    th {background: #CCC; text-align: left;}
                    td {border-bottom: 1px solid;}
                    thead {display: table-header-group;}
                    tfoot {display: table-footer-group;}
                    tbody {display: table-row-group;}
                </style>
                <head></head><body>`);
            mywindow.document.write($('#' + headerDiv).html());
            mywindow.document.write($('#' + tableDiv).html());
            mywindow.document.write('</body></html>');
    //}
    //var pageHeight = mywindow.document.body.scrollHeight;
    
    mywindow.document.close(); // necessary for IE >= 10
    mywindow.focus(); // necessary for IE >= 10*/
   // mywindow.print();
}

function showLoader() {
    var loader = `
    <style>
    .loader {
        border: 12px solid #FAFAFA;
        border-radius: 50%;
        border-top: 12px solid #2E7D32;
        width: 120px;
        height: 120px;
        -webkit-animation: spin 1s linear infinite; /* Safari */
        animation: spin 1s linear infinite;
        position: fixed;
        top: calc(50% - 60px);
        left: calc(50% - 60px);
        transform: translate(-50%, -50%);
    }
    
    /* Safari */
    @-webkit-keyframes spin {
        0% { -webkit-transform: rotate(0deg); }
        100% { -webkit-transform: rotate(360deg); }
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    </style>
    <div class="loader"></div>`;
    $('#div_loader').show();
    $('#div_loader').html(loader);
}

function hideLoader() {
    $('#div_loader').hide();
    $('#div_loader').empty();
}

// oN Data Process
function formatNumber(num,dec,sep) {
    return  num.toFixed(dec).replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1'+sep) //@JerichoSamson0706 -> Added dollar symbol at number 1 beside separator to avoid format being limited to 1000
}

// Data already at element and cange format of element
function displayFormattedNumberTo(elementID,dec = 2,comma = ","){
    var formatted = formatNumber(parseFloat($('#'+elementID).val()),dec,comma);
    $('#'+elementID).val(formatted);
}

// Remove Comma and $- for Saving or etc...
function sanitizeCurrency(num){
    var noCommas = num.toString().replace(/,/g, ''),
    asANumber = +noCommas;
    return noCommas;
}

//Copy to Clipboard
function copyClip(str) {
    var copyText = str;
    copyText.select();
    copyText.setSelectionRange(0, 99999)
    document.execCommand("copy");
    alert("Copied the text: " + copyText.value);
  }