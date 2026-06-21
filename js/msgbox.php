<style>
    .msgBoxTitle {
        position: fixed;
        top: 10px;
        margin: 0 auto;
        padding: 0 8px;
    }
    .divMsgBoxBtn {
        display: flex;
        flex-direction: row;
        position: fixed;
        bottom: 20px;
    }
    .divMsgBoxBtn input {
        margin: 0 6px;
        min-width: 80px;
    }
    .msgBoxMess {
        width: 100%;
        overflow-x: auto;
        margin: 30px 6px 48px 6px;
        padding: 0 4px 0 4px;
        text-align: center;
    }
</style>
<!-- IF YOU WANT TO PREVENT DEFAULT(REDIRECT) -->
<?php if (isset($_POST["preventDef"]))
        if ($_POST["preventDef"] != "NO"){ ?>
    <script>
        $("#frm_msgbox").submit(function(e){
        e.preventDefault();
        });
    </script>
<?php } ?>

    <!-- IF "YES NO" BUTTONS ONLY -->
<?php if ($_POST["YESNO"] == 'true') { ?>
    <!-- THE FORM -->
    <?php if (isset($_POST["msgboxaction"])) { ?>

        <!-- IF IS PHP -->
        <form id="frm_msgbox" class="msgbox" method="POST" action="<?php echo substr($_POST["msgboxaction"], 1, -1); ?>">
            <h4 class="msgBoxTitle"><?php echo substr($_POST["msgboxtitle"], 1, -1); ?></h4>
            <p class="msgBoxMess"><?php echo substr($_POST["msgboxmess"], 1, -1); ?></p>
            <div class="divMsgBoxBtn">
                <input type="submit" name="btnMsgBoxYes" value="YES"/>
                <input type="submit" onclick="$('#div_msgbox').hide(); $('#div_msgbox').empty();" name="btnMsgBoxNo" value="NO"/>
            </div>
        </form>

    <?php } else { ?>

        <!-- IF IS JS -->
        <div id="frm_msgbox" class="msgbox">
            <h4 class="msgBoxTitle"><?php echo substr($_POST["msgboxtitle"], 1, -1); ?></h4>
            <p class="msgBoxMess"><?php echo substr($_POST["msgboxmess"], 1, -1); ?></p>
            <div class="divMsgBoxBtn">
                <input type="submit" id="btnMsgBoxYes" value="YES"/>
                <input type="submit" onclick="$('#div_msgbox').hide(); $('#div_msgbox').empty();" id="btnMsgBoxNo" name="btnMsgBoxNo" value="NO"/>
            </div>
        </div>

    <?php } ?>

    <!-- IF "OK" BUTTON ONLY -->
<?php }
    else if ($_POST["YESNO"] != 'true') { ?>
        <!-- IF "JS" OR PLAIN -->
<?php   if ($_POST["msgtype"] == 'plain') { ?>
    <!-- THE FORM -->
            <div id="div_msgbox" class="msgbox">
                <h4 class="msgBoxTitle"><?php echo substr($_POST["msgboxtitle"], 1, -1); ?></h4>
                <p class="msgBoxMess"><?php echo substr($_POST["msgboxmess"], 1, -1); ?></p>
                <div class="divMsgBoxBtn">
                    <input type="button" id="btnMsgBoxOk" value="OK"/>
                </div>
            </div>

    <!-- IF "PHP" -->
<?php } else if ($_POST["msgtype"] == 'php') {  ?>
    <?php if (isset($_POST["msgboxaction"])) ?>
            <form id="frm_msgbox" class="msgbox" method="POST" action="<?php echo substr($_POST["msgboxaction"], 1, -1); ?>">
                <h4 class="msgBoxTitle"><?php echo substr($_POST["msgboxtitle"], 1, -1); ?></h4>
                <p class="msgBoxMess"><?php echo substr($_POST["msgboxmess"], 1, -1); ?></p>
                <div class="divMsgBoxBtn">
                    <input type="button" onclick="$('#div_msgbox').hide(); $('#div_msgbox').empty();" id="btnMsgBoxOk" value="OK"/>
                </div>
            </form>
<?php } ?>
<?php } ?>