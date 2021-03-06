<?php
	//gets the proximity to the nearest A&E department using Google places API
	require_once "include.php";
	function get_all_results($postcode,$criteria,$type,$lat,$lng,$radius){
	    $c = curl_init();
		$url="https://maps.googleapis.com/maps/api/place/textsearch/json";
		$argstr="?query=".$criteria."+near+".$postcode."&types=".$type."&sensor=false&key=".GOOGLE_API_KEY;
		$argstr.="&location=".$lat.",".$lng."&radius=".$radius;
        curl_setopt($c, CURLOPT_URL, $url . $argstr);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
        $data = curl_exec($c);
        curl_close($c);
        $d = json_decode($data);
		
        //var_dump($d);
        return $d->results;
	}
	function get_nearest_result($postcode,$criteria,$type,$lat,$lng){
        $d=get_all_results($postcode,$criteria,$type,$lat,$lng,"20000");
		//gets the lat and long of the first result
		$endloclat=$d[0]->geometry->location->lat;
		$endloclng=$d[0]->geometry->location->lng;
		return array("geo"=>array($endloclat,$endloclng),"name"=>$d[0]->name,"data"=>json_encode($d));
	}
	function dist_to_result($postcode,$criteria,$type,$lat,$lng){
		$nearest_of_type=get_nearest_result($postcode,$criteria,$type,$lat,$lng);
		$nearest_lat_lng=$nearest_of_type["geo"];
		//do google dist calc using API, like below but changed arguments
		
	    $c = curl_init();
		$url="https://maps.googleapis.com/maps/api/directions/json";
		$destlat=$nearest_lat_lng[0];
		$destlng=$nearest_lat_lng[1];
		$argstr="?sensor=false&origin=".$lat.",".$lng."&destination=".$destlat.",".$destlng;
        curl_setopt($c, CURLOPT_URL, $url . $argstr);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
        $data = curl_exec($c);
        curl_close($c);
        $d = json_decode($data);
        
        logmsg("proximity", $data);
        logmsg("proximity", $argstr);
        
        if ($d->status != "OK") {
            throw new Exception("API call to Google Maps Directions returned: " . $d->status);
        }
        
        $no_of_legs = count($d->routes[0]->legs);
		//the distance to the nearest place in miles
        return $d->routes[0]->legs[$no_of_legs-1]->distance->value;
	}
?>
