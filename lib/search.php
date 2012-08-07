<?php
    require_once "search-schools.php"
    
    function search($postcode) {
        $db = new mysqli("localhost", "yrs2012app-user", "vOdQ04wDTtIS3GeylBER1nNrAo76ZLFJU9hzuxsKmCPi8WcHqbYfVpjXkMag");
        
        $latlng_result = postcode2latlng($db, $postcode);
        $lat = $latlng_result["lat"]
        $lng = $latlng_result["lng"]
        
        $nearest_school = search_schools($db, $postcode, $lat, $lng);
        
        $result = array(
            "overall_score" => 0.0,
            
            "results_living" => array(
                
            ),
            
            "results_staying" => array(
                "nearest_school" => $nearest_school,
            ),
        );
        
        return $result;
    }
?>
