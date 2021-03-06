<?php
    require_once "include.php";
    require_once "util.php";
    
    $plugins = array();
    $plugin_log = fopen("plugin.log", "a");
    
    function logmsg($plugin_name, $msg) {
        global $plugin_log;
        
        fwrite($plugin_log, "Message from plugin '" . $plugin_name . "': " . $msg . "\n");
    }
    
    // Load all plugins
    foreach (glob("../lib/plugins/*.php") as $filename) {
        include $filename;
    }
    
    $category_names = array(
        "test-category" => "Test",
        "amenities" => "Amenities",
        "schools" => "Schools",
        "transport" => "Transport",
    );
    
    function load_from_cache($db, $plugin, $location) {
        $postcode_encoded = $db->real_escape_string($location["postcode"]);
        $plugin_encoded = $db->real_escape_string($plugin->name);
        $res = $db->query("SELECT value FROM cache WHERE postcode = '$postcode_encoded' AND plugin = '$plugin_encoded'");
        if ($res === FALSE) {
            fwrite($plugin_log, "MySQL error: " . $db->error . "\n");
            return "ERROR";
        }
        
        if ($res->num_rows == 0) {
            return "NORESULT";
        }
        
        $row = $res->fetch_row();
        return $row[0];
    }
    
    function store_to_cache($db, $plugin, $location, $result) {
        $postcode_encoded = $db->real_escape_string($location["postcode"]);
        $plugin_encoded = $db->real_escape_string($plugin->name);
        $result_encoded = $db->real_escape_string($result);
        $res = $db->query("INSERT INTO cache VALUES ('$plugin_encoded', '$postcode_encoded', $result_encoded)");
        if ($res === FALSE) {
            fwrite($plugin_log, "MySQL error: " . $db->error . "\n");
            return "ERROR";
        }
    }
    
    function get_result($db, $plugin, $location) {
        $res = load_from_cache($db, $plugin, $location);
        if ($res === "ERROR") {
            return "ERROR";
        }
        
        if ($res === "NORESULT") {
            try {
                $res = $plugin->get_result($db, $location);
                store_to_cache($db, $plugin, $location, $res);
                return $res;
            }
            
            catch (Exception $e) {
                fwrite($plugin_log, "Error from plugin '" . $name . "': " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n");
                return "ERROR";
            }
        }
        
        return $res;
    }
    
    function search($postcode1, $postcode2) {
        global $plugins, $category_names, $plugin_log;
        
        // Remove spaces
        $postcode1 = str_replace(" ", "", $postcode1);
        $postcode2 = str_replace(" ", "", $postcode2);
       
        // Connect to the DB
        $db = new mysqli("localhost", "yrs2012app-user", "vOdQ04wDTtIS3GeylBER1nNrAo76ZLFJU9hzuxsKmCPi8WcHqbYfVpjXkMag", "yrs2012app");
        
        // Calculate latitude & longitude
        $location1 = postcode2location($db, $postcode1);
        $location2 = postcode2location($db, $postcode2);
        
        $breakdown = array();
        
        foreach ($plugins as $plugin) {
            $category = $plugin->category;
            $name = $plugin->name;
            $hrname = $plugin->hrname;
            $units = $plugin->units;
            $better = $plugin->better;
            
            if (!array_key_exists($category, $breakdown)) {
                $breakdown[$category] = array(
                    "_name" => $category_names[$category],
                    "_score1" => 0,
                    "_score2" => 0,
                );
            }
            
            $r1 = get_result($db, $plugin, $location1);
            $r2 = get_result($db, $plugin, $location2);
            
            echo $r1 . " " . $r2 . "\n";
            
            if ($r1 === "ERROR" || $r2 === "ERROR") {
                continue;
            }
            
            $winner1 = false;
            $winner2 = false;
            
            if ($r1 != $r2) {
                if ($better == LOWER_IS_BETTER) {
                    $winner1 = $r1 < $r2;
                }
                else {
                    $winner1 = $r1 > $r2;
                }
                
                $winner2 = !$winner1;
                
                if ($winner1) {
                    $breakdown[$category]["_score1"]++;
                }
                else {
                    $breakdown[$category]["_score2"]++;
                }
            }
            
            $breakdown[$category][$name] = array(
                "name" => $hrname,
                "units" => $units,
                "result1" => $r1,
                "result2" => $r2,
                "winner1" => $winner1,
                "winner2" => $winner2,
            );
        }
        
        return $breakdown;
    }
?>
