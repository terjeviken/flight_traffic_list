<?php

namespace TVI\Flights;
require_once __DIR__ . "/vendor/autoload.php";
require_once  __DIR__ ."/app_files/Connection.php";

session_start();

use \PhpOffice\PhpSpreadsheet\Reader\Xls;
use \PhpOffice\PhpSpreadsheet\IOFactory;
use \PhpOffice\PhpSpreadsheet\Shared;

////////////////////////////////////////////
//
// DRIVER/LOGIC
// if verify file data
// if verify connection
// registert new import id
// start import...
// delete older imports for the same days for the target airport(s)
//
// if supporting import of several airports at once need dictionary
// one record from the Excel-file could affect two target airports. 
// but NOT yet
//
////////////////////////////////////////////

$g_target_airport      = "FRO"; // hardcoded - but should be read from submit form if more airports
$g_error_text          = "";
$g_sql_conn            = null;
$g_sheet               = null;
$g_workbook            = null;
$g_reader              = null;

$_SESSION['importresult']     = "ok";
$_SESSION['importresult_msg'] = "";


if (!get_valid_spread_sheet())
  msg_error_redirect("Unable to use/open this file: ");

$g_sql_conn =  Connection::get();
if (!$g_sql_conn)
  msg_error_redirect("Could not connect to database... try later.");

$g_import_id = get_next_import_id($g_sql_conn, $g_target_airport, "Just another import");

if ($g_import_id < 0)
  msg_error_redirect("Error while registering import in database.");

$result = import_into_flights_table($g_sql_conn, $g_import_id);

if (!$result) {
  msg_error_redirect("Error: import_flight_records");
} 
else {
  $_SESSION['importresult_msg'] = $result . " flighter importert!";
  header('Location: index.php');
}


////////////////////////////////////////////
// Simple message and die...
// Maybe redirect back to import
// and display a better err_msg???
////////////////////////////////////////////

function msg_error_redirect($msg)
{
  global $g_error_text;

  $msg = $msg . ": " . $g_error_text;
  $_SESSION['importresult']     = "error";
  $_SESSION['importresult_msg'] = $msg;

  header('Location: index.php');
  exit();
}


////////////////////////////////////////////
// Create a new import registration for airport
// return new id for use in flight reg.
////////////////////////////////////////////

function  get_next_import_id( $conn, $targetAirport, $comment="" )
{
  global $g_error_text;

  try {
    $conn->beginTransaction();
    $stmt   = $conn->prepare("INSERT INTO FlightImports (TargetAD, comments) VALUES(:airport, :comment)");
    $values = array("airport" => $targetAirport, "comment" => $comment);
    $stmt->execute($values);
    $result =  $conn->lastInsertId();
    $conn->commit();    
    return $result; // OK
  } 
  catch (\PDOException $e) {
    $conn->rollback();
    $g_error_text = $e->getMessage();
    return -1;
  }
  catch (\Exception $e) {
    $g_error_text = $e->getMessage();
    return -1;
  }  
}

function excel_to_php_date($cellValue)
{
  return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($cellValue);
}

// Note the change of order row-col which is how I think about a table
function cellValue($row, $col)
{
  global $g_sheet;
  return $g_sheet->getCellByColumnAndRow($col, $row)->getValue();
}

////////////////////////////////////////////
// Loop through data records of cvs
// and import into database
// returns number of rows handled
////////////////////////////////////////////

function import_into_flights_table($conn, $_id)
{
  global $g_target_airport, $g_error_text, $g_sheet;

  // Header row has been reasonably read and confirmed. File-handle now at first row.
  // we read first ro to get a date to work from also ensuring that we have at least one row in the import


  $numRecords         = $g_sheet->getHighestRow();
  $highestColumnIdx   = 16; // hard coded (there is a getHighestColumn also which returns "P")

  if ($numRecords <= 1) {
    $g_error_text = "No records";
    return 0;
  }


  // vi trenger en dato i settet for å bygge min/max datoer
  $minDate = $maxDate =  excel_to_php_date(cellValue(2, 4));

//  msg_error_redirect(cellValue(2, 6));

try{
  $conn->beginTransaction();	

  $stmt = $conn->prepare( "INSERT INTO Flights (DepAD, ArrAD, CallSign, Aircraft, STA, STD, ImportId) VALUES (:dep, :arr, :cs, :acft, :sta, :std, :i_id)" );          
    
      
    //msg_error_redirect($numRecords);
    for ($curRow = 2; $curRow < $numRecords-1; $curRow++)
    {
      $arrDate = $depDate = excel_to_php_date(cellValue($curRow, 4));

      // update max/min dates in the import
      $minDate = min($depDate, $minDate);
      $maxDate = max($depDate, $maxDate);

      $std        = substr(cellValue($curRow, 6),0,2) . ":" . substr(cellValue($curRow, 6),2,2) . ":00"; // HH:MM:SS
      $sta        = substr(cellValue($curRow, 9),0,2) . ":" . substr(cellValue($curRow, 9),2,2) . ":00";

      // if arrival is smaller than departure-time, then we assume next day
      if ($sta < $std)
        $arrDate->modify('+1 day');      

      $depStr = $depDate->Format("Y-m-d") . " " . $std;
      $arrStr = $arrDate->Format("Y-m-d") . " " . $sta;
      
      $values = array(
        "dep" 	=> cellValue($curRow, 5),
        "arr" 	=> cellValue($curRow, 8),
        "cs"  	=> cellValue($curRow, 1) .  cellValue($curRow, 2),
        "acft"	=> substr( cellValue($curRow, 13),0,4),
        "sta" 	=> $arrStr,
        "std" 	=> $depStr,
        "i_id" 	=> $_id		
      );

      $stmt->execute($values);  
    }

	// DELETE OLDER IMPORTS before we commit
		// only delete traffic on days covered from this import.
		// - TO DO: Better to mark record as old for history. Will require quite a bit change in .NET project

		$oldest = $minDate->Format("Y-m-d") . " 00.00.00";
		$newest = $maxDate->Format("Y-m-d") . " 23:59:59";
		
		$sql = "DELETE FROM Flights WHERE STD >=:oldest AND STA <= :newest AND ImportId  IN (SELECT id FROM FlightImports WHERE id != :id AND TargetAD = :target)";
		error_log($sql,0);
		
		$stmt = $conn->prepare( $sql );
			 
		$values = array( "oldest" => $oldest, "newest" => $newest,  "id" =>$_id, "target" => $g_target_airport);
		$stmt->execute($values);
		
	
  		// Try save everything....
	    $conn->commit(); 
      return $numRecords-1;	
    }
    catch(\PDOException $e)
    {
	    $conn->rollback(); 	
        msg_error_redirect($e->getMessage());	
    	$g_error_text = $e->getMessage();	

    	return 0;
    }    
  
  
  // should be unreachable?

}



////////////////////////////////////////////
// locate uploaded file, open and check.
// if ok - return file-handle
////////////////////////////////////////////

function get_valid_spread_sheet()
{

  global $g_error_text, $g_workbook, $g_reader, $g_sheet;

  $filename = $_FILES['file']['name'];

  if ($_FILES["file"]["name"] != '' && $_FILES["file"]["size"] > 0) {
    $allowed_extension = array('xls', 'xlsx');
    $file_array = explode(".", $_FILES['file']['name']);
    $file_extension = end($file_array);

    if (in_array($file_extension, $allowed_extension)) {
      $g_reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
      $g_reader->setReadDataOnly(true);
      $g_workbook = $g_reader->load($_FILES['file']['tmp_name']);
      $g_sheet = $g_workbook->getActiveSheet();

      if ($g_sheet->getCell('A1')->getValue() == "Airline Designator") {
        return true;
      }

      $g_error_text = "Xls file does not seem to be correct format";
    } else {
      $g_error_text = 'Only .xls or .xlsx file allowed';
    }
  } else {
    $g_error_text = 'Empty or no file provided!';
  }

  return false;
}
