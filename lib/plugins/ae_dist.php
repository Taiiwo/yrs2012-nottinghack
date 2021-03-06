<?php
	require_once "../lib/proximity.php";
    // Copy this file into lib/plugins/ and name it appropriately. Then follow the comments in this
    // file to fill in the gaps.
    
    // Change this name
    class AeDist{
        // The category identifier - should be lowercase and hyphen-separated e.g. "crime"
        public $category = "amenities";
        
        // The name identifier - should be lowercase and hyphen-separated e.g. "school-proximity"
        public $name = "hospital-proximity";
        
        // The human-readable name - this will be displayed in the results table e.g. "School proximity"
        public $hrname = "Hospital Proximity";
        
        // The units that the results are returned in.
        public $units = "km";
        
        // Should be either LOWER_IS_BETTER or HIGHER_IS_BETTER - determines which result wins.
        public $better = LOWER_IS_BETTER;
        
        // The get_result method should perform the searches and return the two results.
        // $db is a mysqli object connected to the database.
        // $location is an associative array which contain the following entries:
        //     "postcode" => the postcode
        //     "lat" => the latitude
        //     "lng" => the longitude
        public function get_result($db, $loc) {
            $criteria="emergency";
            return dist_to_result($loc["postcode"],$criteria,"hospital",$loc["lat"],$loc["lng"]) / 1000;
        }
    }
    
    // Update the name of the class here too.
    // This inserts the plugin into the plugin index.
    $plugins["aeproximity"] = new AeDist();
?>
