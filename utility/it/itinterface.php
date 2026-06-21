<script src="../../js/jquery.min.js"></script>

<div class="container">
  <div class="row">
    <div class="col-md-12">
	    <p>&nbsp;</p>
		<p>&nbsp;</p>
		<button type="button" id="button1"  class="btn btn-success btn-block">Click Here To Start Progress Bar</button>
		<p>&nbsp;<p>
		<button type="button" id="button2"  class="btn btn-danger btn-block">Click Here To Stop Progress Bar</button>
    </div>
    <div class="col-md-12">
		<p>&nbsp;</p>
	    <p>&nbsp;</p>
		<div id="progressbar" style="border:1px solid #ccc; border-radius: 5px; "></div>
  
		<!-- Progress information -->
		<br>
		<div id="information" ></div>
	</div>
  </div>
</div>

<iframe id="loadarea" style="display:none;"></iframe><br />

<script >
	$("#button1").click(function(){
		document.getElementById('loadarea').src = 'updatetbl.php';
	});
	$("#button2").click(function(){
		document.getElementById('loadarea').src = '';
	});
</script>