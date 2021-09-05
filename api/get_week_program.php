<?php

    /*
        Not in planned use yet - just playing around

        We are expecting to be called with url&date=2021-02-28 
        if no date supplied - we use today. If date supplied - check for validity

        Result is all traffic for the week - containing the date for target airport
        sorted by time touching the runway (departure / arival)
        an array of arrays per day with all the days flights

        For later: It should rather be called with target_ad, and requested timespan., 
        
    */

    namespace TVI\Flights;    

    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');

    require_once "../app_files/WeeklyTraffic.php";
    require_once "../app_files/utilities.php";    //

    $target_date = isset($_GET['date']) ? $_GET['date'] : (new \DateTime())->format("Y-m-d");
    
    $flights = null;

    if (is_valid_date_string($target_date)){
        $date = \DateTime::createFromFormat('Y-m-d', $target_date);
        $prg = new WeeklyTraffic($date);                        
        $flights = $prg->get_traffic_list(); // $flights will be null if error on connect/get 
        if ($flights)   {
            $json = json_encode($flights);                
            echo $json;
            return;
        }                           
    }     

    // $flights still null so something went wrong
    // Return 5xx something.. :)
    header("HTTP/1.1 500 SERVER ERROR");    
    echo json_encode(array("Message"=>"Something went wrong dude!"));                        