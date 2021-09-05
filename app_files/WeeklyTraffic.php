<?php
    namespace TVI\Flights;
    require_once "Connection.php";
    require_once "utilities.php";
    
    use TVI\Flights\Connection;    

    /*
        Note: Target AD is stored in the flightimports table.
    */

    class Flight{  // Not sure this works as intended result is array like anyways
        public $FlightId;
        public $DepAD;
        public $ArrAD;
        public $Callsign;
        public $STD;
        public $STA;
        public $Touch;
        public $Direction;
        public $OpDate;
        public $ImportId;
        public $Aircraft;
    }

    class WeeklyTraffic{

        private $traffic_list   = array();
        private $err_code       = 200;  // assume ok
        private $err_text       = "";
        private $flight_count   = 0;
        
        function __construct( $date ){

            // prepare time boundaries for query
            $dates = first_and_last_dates_in_week($date);

            // Make dates query friendly
            $first = ($dates["first"])->format("Y-m-d H:i:s"); // 00:00 is set            
            $last  = ($dates["last"])->format("Y-m-d H:i:s");  // 23:59 is set

            // TO DO targetAD hardcoded... need to change if want it to be parameterized
            $sql = "SELECT Movements.* FROM Movements INNER JOIN FlightImports ON FlightImports.id = Movements.ImportId" 
                        . " WHERE FlightImports.TargetAD='FRO' and Movements.Touch >= '$first'"   
                        . " AND Movements.Touch <= '$last' ORDER BY Touch";

            // prepare 7 arrays - one for each day             
            $day_walk = clone $dates["first"];
            for ($i = 0; $i < 7; $i++) {
                $this->traffic_list[$day_walk->format("Y-m-d")] = [];
                $day_walk->modify('+1 day');
            }

            $conn = null;

            try {
                $conn = Connection::get();
                $stmt = $conn->prepare($sql);
                $stmt->setFetchMode(\PDO::FETCH_CLASS, 'Flight'); 
                $stmt->execute();                
                
                while ( $flight = $stmt->fetch(\PDO::FETCH_ASSOC) )
                {                   
                    // TODO make flightId key? for detail lookup
                    $this->traffic_list[$flight['OpDate']][] = $flight;  
                    $this->flight_count++;       
                }              
              } 
              catch (\PDOException $e) {
                $this->err_code = 500;
                $this->err_text = $e->getMessage();                
              } 
        }

        // a null return signifies an error on connect and create
        public function get_traffic_list(){
            if ($this->err_code != 200) 
                return null;

            return $this->traffic_list; //  multidim - array with sorted traffic for each day of the week               
        }

        public function created_ok(){
            return $this->err_code == 200;
        }

        public function get_error_text(){
            return $this->err_text;
        }       
    }