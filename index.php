<?php 

session_start();


$redirected = false;
$import_ok = false;
$import_msg = "";

if(isset($_SESSION['importresult']))
{
	$redirected = true;
	$import_ok = $_SESSION['importresult'] == "ok";	
	$import_msg = $_SESSION['importresult_msg'];
}
	session_destroy();
 ?>
 
<!DOCTYPE html>
<html lang="en">
 <head>
  <meta charset="utf-8">
  <title>Flight cvs-import avitools</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
 </head>
 <body>    
    <br><br>
        <div class="container">
		
            <div class="row">

                <div class="col-md-3 hidden-phone"></div>
                <div class="col-md-6" id="form-login">
                    <form class="well" action="import.php" method="post" name="upload_excel" enctype="multipart/form-data">
                        <fieldset>
                            <legend>Importer LETIS .xls fil med planlagte flyvninger</legend>
							<?php
							if ($redirected)
							{ ?>
							   <div class="alert alert-<?php echo ($import_ok ? "success" : "danger"); ?>">
							      <strong><?php echo $import_msg; ?></strong>
							   </div>
							
							<?php 
							} ?>
                            <div class="control-group">
                                <div class="control-label">
                                    <label>CSV:</label>
                                </div>
                                <div class="controls form-group">
                                    <input type="file" name="file" id="file" class="input-large form-control">
                                </div>
                            </div>
                            
                            <div class="control-group">
                                <div class="controls">
                                <button type="submit" id="submit" name="Import" class="btn btn-success btn-flat btn-lg pull-right button-loading" data-loading-text="Loading...">Upload</button>
                                </div>
                            </div>					
                        </fieldset>                      
                    </form>
                </div>
                <div class="col-md-3 hidden-phone"></div>

 </body>
</html>