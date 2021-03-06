<?php 

    namespace TVI\Flights;

    require_once "app_files/utilities.php";
    require_once "app_files/WeeklyTraffic.php";
    session_start();

    $target_date = new \DateTime(); // default to today

    // check if a date was given in the url. Format "YYYY-MM-DD and if valid use that
    if (isset($_GET['date'])){
        $date = $_GET['date'];
        if (is_valid_date_string($date)){
            $target_date = \DateTime::createFromFormat('Y-m-d', $date);            
        }
    }

    $prev_week = clone $target_date; $prev_week->modify('-7 day');
    $next_week = clone $target_date; $next_week->modify('+7 day');

    $result = first_and_last_dates_in_week($target_date);
    $monday = $result["first"];
    $sunday = $result["last"];
    
    //TODO: better control of db-errors and user input errors... 
    // and put in a meaningful message
    $program = new WeeklyTraffic($target_date);
    $flights = $program->get_traffic_list();

    $jscript_flights = [];    
   
    function hr_sec($date_str){
        // 2021-08-30 07:00:00
        return substr($date_str,11,5);
    }
    
 ?>
 
<!DOCTYPE html>
<html lang="en">
 <head>
 <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ukeprogram - flybevegelser</title>


    <link href="Content/bootstrap.css" rel="stylesheet"/>
    <link href="Content/bootstrap.css" rel="stylesheet"/>
    <link href="Content/bootstrap-datepicker.css" rel="stylesheet"/>
    <link href="Content/mysite.css" rel="stylesheet"/>


    <script src="Scripts/jquery-1.10.2.min.js"></script>
    <script src="Scripts/jquery.unobtrusive-ajax.min.js"></script>
    <script src="Scripts/jquery.validate.min.js"></script>
    <script src="Scripts/jquery.validate.unobtrusive.min.js"></script>
    <script src="Scripts/jquery-ui-1.11.4.min.js"></script>
    <script src="Scripts/bootstrap.min.js"></script>
    <script src="Scripts/bootstrap-datepicker.js"></script>
    <script src="Scripts/locales/bootstrap-datepicker.no.min.js"></script>     
  
 </head>
 <body>  
 <div class="container body-content">        
    <div id="toolbar" >

    <h3><strong>FRO</strong><?php echo "&nbsp " . date_str($monday, "d.m") . "  -  " . date_str($sunday, "d.m (Y)"); ?></h3>                   
    </div>
    <div class="row">
    <div class="col-md-3"><text>Velg dato: </text><input class="datepicker btn-lg" data-val="true" data-val-date="The field M??ldato must be a date." data-val-required="Feltet M??ldato er obligatorisk." id="targetdate" name="InputDate" readonly="True" type="text" value="<?php echo date_str($target_date,"d-m-Y"); ?>" /></div>
        <div class="col-md-3">
            <a href="flights.php?date=<?php echo date_str($prev_week); ?>" class="btn btn-primary btn-lg">&laquo; Uken foran</a>
            <a href="flights.php?date=<?php echo date_str($next_week); ?>" class="btn btn-primary btn-lg">Uken etter &raquo;</a>
        </div>
        <div class="col-md-3"><input type="checkbox" class="checkbox-primary" id="chkShowFlightAirports" checked>Vis til-/fra lufthavner</div>

    </div>

    <p class="snusfornuftig-info floatclear">D=Dep eller A=Arr. Tider er UTC.</p>
<div id="week-program-display">
    <?php
    $ukedager = ["Mandag", "Tirsdag", "Onsdag", "Torsdag", "Fredag", "L??rdag", "S??ndag"];

    $walker = $monday;
    for ($i = 0; $i < 7; $i++){                

        // Get array of the days traffic. May be empty
        $daily_tfc = $flights[date_str($walker)];
        ?>

        <div class="day-container">
        <div class="txt-weekday day-container-text">
            <h5 class="weekday"><?php echo $ukedager[$i]; ?></h5>
            <h6><?php echo date_str($walker, "d-m-Y"); ?></h6>
        </div>
        <table class="day-program-list" id="day-program-list">
            <?php            
            foreach($daily_tfc as $flight)
            {
                // Fill in array to be used by JavaScript
                $jscript_flights[$flight["FlightId"]] = $flight;

                ?>
                <tr>
                    <td ><button data-id="<?php echo $flight["FlightId"] ?>" class="callsign btn btn-link btn-sm"><?php echo $flight["Callsign"]; ?> </button> </td>                     
                    <td class="flight_airport_detail"> <?php echo $flight["DepAD"] ."-". $flight["ArrAD"] . ":"; ?></td> 
                    <td><?php echo hr_sec($flight["Touch"]) ."-" . $flight["Direction"]; ?> </td>
                </tr>
            <?php
            } //   end loop tfc for the day             
        ?> 
    </table>
    </div>
    <?php
    $walker->modify("+ 1 Day");
    } // end for 1-7 (days)
    ?>

    <div class="floatclear"></div>    
</div>

<div class="div-messages" background-color:#ffcc00>
    <label id="flightdetails" class="center-text">(Click call sign for details)</label>
</div>

<div id="met-disclaimer">
    DEMO!!! . Trafikkdata importeres kun sporadisk 
        <a href="http://avinor.no" target="_blank">Avinor's </a> LETIS system    
</div>

</div> <! ?????? body-container -->

<script type="text/javascript">   

    // php left a json encoded list of flights. First element is FlightID
    const flights = JSON.parse('<?php echo json_encode($jscript_flights); ?>');

    var showFlightAirports = true;

    function get_hours_minutes(date_str){
            // date_str is in format "2021-09-01 hh:mm:ss"
            return date_str.substr(11,5);
    }

    $(document).ready(function () {
        
        $(".callsign").click(function () {

            let flightId = $(this).data("id");
            let f = flights[flightId];

            ukedag = $(this).parents(".day-container").find(".weekday").text();          

            if (typeof(f) != "undefined"){
              
                $(this).effect( "shake", {}, 250, function(){
                    $(this).blur();
                } );

            
                let STA = get_hours_minutes(f.STA);
                let STD = get_hours_minutes(f.STD);

                desc = "[" + ukedag + "] - C/S: " + f.Callsign + " Type: " + f.Aircraft + " Dep: " + f.DepAD + " " + STD 
                    + " -> Arr: " + f.ArrAD + " " + STA;
                $("#flightdetails").text(desc);
            }

            
            
        });

        $("#chkShowFlightAirports").click(function () {
            if ($(this).prop("checked") != showFlightAirports) {
                showFlightAirports = !showFlightAirports;
                if (showFlightAirports) {
                    $(".flight_airport_detail").removeClass("hidden_element").fadeIn("200");
                }
                else {
                    $(".flight_airport_detail").fadeOut(300, function () {
                        $(".flight_airport_detail").addClass("hidden_element");
                    });
                }
            }


        });
    });

    $('.datepicker').datepicker(
        {
            format: 'dd.mm.yyyy',
            autoclose: true,            
            language: 'no',
            weekStart: 1
        })   

    //Listen for the change even on the input
    
    .on('changeDate', datePickerDateChanged);    

    function datePickerDateChanged(ev) {
        var tmpDate = $('#targetdate').val();        
        var agnosticDate = tmpDate.slice(6) + "-" + tmpDate.slice(3, 5) + "-" + tmpDate.slice(0, 2);        
        window.location.href = "flights.php?date=" + agnosticDate;
    }
   

</script>

 </body>

</html>