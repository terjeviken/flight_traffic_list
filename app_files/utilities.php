<?php
namespace TVI\Flights;


/*
    input: DateTime object
    output: DateTime objects monday and sunday of that week (first-last day)

*/
function first_and_last_dates_in_week( $date_time ){

    $first_day = clone $date_time;
    $last_day = clone $date_time;

    $day_of_week = intval($date_time->format('w'));
    if ($day_of_week == 0) $day_of_week = 7; // sunday!! hack

    $first_day_corr = $day_of_week-1;
    $first_day->modify("-$first_day_corr". " day");
    $first_day->setTime(0,0,0);

    $last_day_corr = $day_of_week = 7 - $day_of_week;
    $last_day->modify("+$last_day_corr" . " day");
    $last_day->setTime(23,59,59);
    return   array("first" => $first_day, "last" =>$last_day);        
}

// takes a DateTime and formats it to a string
function date_str($date, $format="Y-m-d"){
    return $date->format($format);
}

// Checks if a Date-string is a valid date
function is_valid_date_string($date_str, $format = 'Y-m-d')
{
    $d = \DateTime::createFromFormat($format, $date_str);    
    return $d && $d->format($format) === $date_str;
}

