<?php
if (!class_exists("dbWrapper")) {
    Class dbWrapper
    {
        protected $_mysqli;
        protected $_debug;

        public function __construct($host, $username, $password, $database, $debug)
        {
            $this->_mysqli = new mysqli($host, $username, $password, $database);
            $this->_debug = (bool)$debug;
            if (mysqli_connect_errno()) {
                if ($this->_debug) {
                    echo mysqli_connect_error();
                    debug_print_backtrace();
                }
                return false;
            }
            return true;
        }

        public function escape($query)
        {
            return $this->_mysqli->real_escape_string($query);
        }

        public function multi_query($query)
        {
            if (is_array($query)) {
                $exploded = implode(';', $query);
            } else {
                $exploded = $query;
            }

            if ($query = $this->_mysqli->multi_query($exploded)) {
                $i = 0;
                do {
                    $i++;
                } while ($this->_mysqli->more_results() && $this->_mysqli->next_result());
            }

            if ($this->_mysqli->errno) {
                if ($this->_debug) {
                    echo mysqli_error($this->_mysqli);
                    debug_print_backtrace();
                }
                return false;
            } else {
                return true;
            }
        }

        public function q($query)
        {
            if ($query = $this->_mysqli->prepare($query)) {
                if (func_num_args() > 1) {
                    $x = func_get_args(); //grab all of the arguments
                    $args = array_merge(array(func_get_arg(1)),
                        array_slice($x, 2)); //filter out the query part, leaving the type declaration and parameters
                    $args_ref = array();
                    foreach ($args as $k => &$arg) { //not sure what this step is doing
                        $args_ref[$k] = & $arg;
                    }
                    call_user_func_array(array($query, 'bind_param'), $args_ref); // bind each parameter in the form of: $query bind_param (param1, param2, etc.)
                }
                $query->execute();

                if ($query->errno) {
                    if ($this->_debug) {
                        echo mysqli_error($this->_mysqli);
                        debug_print_backtrace();
                    }
                    return false;
                }

                if ($query->affected_rows > -1) {
                    return $query->affected_rows;
                }
                $params = array();
                $meta = $query->result_metadata();
                while ($field = $meta->fetch_field()) {
                    $params[] = & $row[$field->name];
                }
                call_user_func_array(array($query, 'bind_result'), $params);

                $result = array();
                while ($query->fetch()) {
                    $r = array();
                    foreach ($row as $key => $val) {
                        $r[$key] = $val;
                    }
                    $result[] = $r;
                }
                $query->close();
                return $result;
            } else {
                if ($this->_debug) {
                    echo $this->_mysqli->error;
                    debug_print_backtrace();
                }
                return false;
            }
        }

        public function handle()
        {
            return $this->_mysqli;
        }

        public function last_index()
        {
            return $this->_mysqli->insert_id;
        }
    }
}

if (!function_exists("curl")) {
    function curl($link, $postfields = '', $cookie = '', $refer = '', $user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1')
    {
        $ch = curl_init($link);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        if ($refer) {
            curl_setopt($ch, CURLOPT_REFERER, $refer);
        }
        if ($postfields) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        }
        if ($cookie) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
        }
        $page = curl_exec($ch);
        curl_close($ch);
        return $page;
    }
}

if (!function_exists("cut_str")) {
    function cut_str($str, $left, $right)
    {
        $str = substr(stristr($str, $left), strlen($left));
        $leftLen = strlen(stristr($str, $right));
        $leftLen = $leftLen ? -($leftLen) : strlen($str);
        $str = substr($str, 0, $leftLen);

        return $str;
    }
}

//GIVEN A UNIX TIMESTAMP RETURNS A RELATIVE DISTANCE TO DATE (23.4 days ago)
//PUTTING ANY VALUE IN 2ND VARIABLE MAKES IT RETURN RAW HOURS APART
if (!function_exists('relative_time')) {
    function relative_time($time, $output = 'default')
    {
        if (!is_numeric($time)) {
            if (strtotime($time)) {
                $time = strtotime($time);
            } else {
                return FALSE;
            }
        }

        if ($output == 'default') {
            if ((time() - $time) >= 2592000) {
                $time_adj = round(((time() - $time) / 2592000), 1) . ' months ago';
            } else if ((time() - $time) >= 86400) {
                $time_adj = round(((time() - $time) / 86400), 1) . ' days ago';
            } else if ((time() - $time) >= 3600) {
                $time_adj = round(((time() - $time) / 3600), 1) . ' hours ago';
            } else {
                $time_adj = round(((time() - $time) / 60), 0) . ' mins ago';
            }
        } else {
            $time_adj = round(((time() - $time) / 3600), 1);
        }

        return $time_adj;
    }
}

if (!function_exists('LoadJPEG')) {
    function LoadJPEG($imgURL)
    {

        ##-- Get Image file from Port 80 --##
        $fp = fopen($imgURL, "r");
        $imageFile = fread($fp, 3000000);
        fclose($fp);

        ##-- Create a temporary file on disk --##
        $tmpfname = tempnam("/temp", "IMG");

        ##-- Put image data into the temp file --##
        $fp = fopen($tmpfname, "w");
        fwrite($fp, $imageFile);
        fclose($fp);

        ##-- Load Image from Disk with GD library --##
        $im = imagecreatefromjpeg($tmpfname);

        ##-- Delete Temporary File --##
        unlink($tmpfname);

        ##-- Check for errors --##
        if (!$im) {
            print "Could not create JPEG image $imgURL";
        }

        return $im;
    }
}

if (!function_exists("grab_image")) {
    function grab_image($img_url, $destination = './images/schema/')
    {
        if (!empty($img_url) && substr($img_url, (strlen($img_url) - 4)) == '.png') {
            $file = $destination . basename($img_url);

            if (!file_exists($file)) {
                ##-- Get Image file from Port 80 --##

                $handle = fopen($img_url, "rb");
                $imageFile = '';
                while (!feof($handle) && $handle) {
                    $imageFile .= fread($handle, 8192);
                }
                fclose($handle);

                ##-- Put image data into the temp file --##
                $fp = fopen($file, "w");
                fwrite($fp, $imageFile);
                fclose($fp);

                unset($imageFile);

                return $file;
            } else {
                return $file;
            }
        } else {
            return false;
        }
    }
}

if (!function_exists("get_d2_player_backpack")) {
    function get_d2_player_backpack($account_id = '76561197989020883', $flush = 0, $steam_api_key)
    {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        if ($flush == 1) {
            $memcache->delete("get_d2_player_backpack" . $account_id);
        }

        $url = 'http://api.steampowered.com/IEconItems_570/GetPlayerItems/v0001/?language=en&key=' . $steam_api_key . '&steamid=' . $account_id;

        $player_items = $memcache->get("get_d2_player_backpack" . $account_id);
        if (!$player_items) {
            $player_items = json_decode(curl($url), true);

            if (empty($player_items)) {
                sleep(1);
                $player_items = json_decode(curl($url), true);
            }

            $memcache->set("get_d2_player_backpack" . $account_id, $player_items, 0, 60 * 15);
        }

        $memcache->close();

        return $player_items;
    }
}

if (!function_exists("get_d2_schema")) {
    function get_d2_item_schema($flush = 0, $steam_api_key)
    {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        if ($flush == 1) {
            $memcache->delete("get_d2_item_schema");
        }

        $schema = $memcache->get("get_d2_item_schema");
        if (!$schema) {
            //INTERNAL
            //$schema = json_decode(file_get_contents('http://api.steampowered.com/IEconItems_816/GetSchema/v0001/?language=en&key='.$steam_api_key), true);

            //LIVE
            $schema = json_decode(curl('http://api.steampowered.com/IEconItems_570/GetSchema/v0001/?language=en&key=' . $steam_api_key), true);

            $memcache->set("get_d2_item_schema", $schema, 0, 60 * 60);
        }

        $memcache->close();

        return $schema;
    }
}

if (!function_exists("get_d2_rarities")) {
    function get_d2_rarities($flush = 0, $steam_api_key)
    {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        if ($flush == 1) {
            $memcache->delete("get_d2_rarities");
        }

        $schema = $memcache->get("get_d2_rarities");
        if (!$schema) {
            //INTERNAL
            //$schema = json_decode(file_get_contents('http://api.steampowered.com/IEconItems_816/GetSchema/v0001/?language=en&key='.$steam_api_key), true);

            //LIVE
            $schema = json_decode(curl('http://api.steampowered.com/IEconDOTA2_816/GetRarities/v1/?language=en&key=' . $steam_api_key), true);

            $memcache->set("get_d2_rarities", $schema, 0, 60 * 60);
        }

        $memcache->close();

        return $schema;
    }
}

if (!function_exists("GetMatchHistory")) {
    function GetMatchHistory($startinggame = NULL, $date_max = NULL, $debug = false, $num_games = NULL, $steam_api_key)
    {
        $parameters = NULL;

        if (!empty($startinggame)) {
            $parameters .= '&start_at_match_id=' . $startinggame;
        }
        if (!empty($num_games)) {
            $parameters .= '&matches_requested=' . $num_games;
        }
        if (!empty($date_max)) {
            $parameters .= '&date_max=' . $date_max;
        }

        $url = 'http://api.steampowered.com/IDOTA2Match_570/GetMatchHistory/v001/?language=en&min_players=10&key=' . $steam_api_key . $parameters;

        if ($debug === true) {
            echo $url;
        }

        $matches = json_decode(curl($url), true);

        if (empty($matches)) {
            sleep(1);
            $matches = json_decode(curl($url), true);
        }

        return $matches;
    }
}

if (!function_exists("GetMatchHistoryBySequenceNum")) {
    function GetMatchHistoryBySequenceNum($start_at_match_seq_num = NULL, $matches_requested = NULL, $debug = false, $steam_api_key)
    {
        $parameters = NULL;

        if (!empty($start_at_match_seq_num)) {
            $parameters .= '&start_at_match_seq_num=' . $start_at_match_seq_num;
        }
        if (!empty($matches_requested)) {
            $parameters .= '&matches_requested=' . $matches_requested;
        }

        $url = 'http://api.steampowered.com/IDOTA2Match_570/GetMatchHistoryBySequenceNum/v0001/?language=en&min_players=10&key=' . $steam_api_key . $parameters;

        if ($debug === true) {
            echo $url;
        }

        $matches = json_decode(curl($url), true);

        if (empty($matches)) {
            sleep(1);
            $matches = json_decode(curl($url), true);
        }

        return $matches;
    }
}

if (!function_exists("GetMatchDetails")) {
    function GetMatchDetails($match_id = NULL, $debug = false, $steam_api_key)
    {
        $parameters = NULL;

        if (!empty($match_id)) {
            $parameters .= '&match_id=' . $match_id;
        }

        $url = 'http://api.steampowered.com/IDOTA2Match_570/GetMatchDetails/V001/?language=en&min_players=10&key=' . $steam_api_key . $parameters;

        if ($debug === true) {
            echo $url;
        }

        $matches = json_decode(curl($url), true);

        if (empty($matches)) {
            sleep(1);
            $matches = json_decode(curl($url), true);
        }

        return $matches;
    }
}

if (!function_exists("convert_id")) {
    function convert_id($id)
    {
        if (empty($id)) return false;

        if (strlen($id) === 17) {
            $converted = substr($id, 3) - 61197960265728;
        } else {
            $converted = '765' . ($id + 61197960265728);
        }

        return (string)$converted;
    }
}

if (!function_exists("hex2rgb")) {
    function hex2rgb($hex)
    {
        $hex = str_replace("#", "", $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        $rgb = $r . ', ' . $g . ', ' . $b;
        //return implode(",", $rgb); // returns the rgb values separated by commas
        return $rgb; // returns an array with the rgb values
    }
}

if (!function_exists("hex2name")) {
    function hex2name($hex)
    {
        $colour_name = NULL;
        switch ($hex) {
            case 'FFFFFF':
                $colour_name = 'None';
                break;
            case 'B7CF33':
                $colour_name = 'Light Green';
                break;

            case '32A6CF':
                $colour_name = 'Light Blue';
                break;

            case 'D07733':
                $colour_name = 'Orange';
                break;

            case 'C439C6':
                $colour_name = 'Magenta';
                break;

            /*case '8232ED':
                $colour_name = 'Purple';
                break;*/

            case '2924CB':
                $colour_name = 'Persian';
                break;

            case '51B350':
                $colour_name = 'Green';
                break;

            case '3D68C4':
                $colour_name = 'Indigo';
                break;

            case '0097CE':
                $colour_name = 'Blue';
                break;

            case '8232CF':
                $colour_name = 'Violet';
                break;

            case 'CFAB31':
                $colour_name = 'Gold';
                break;

            case '4AB78D':
                $colour_name = 'Teal';
                break;

            case 'D03D33':
                $colour_name = 'Red';
                break;
        }
        return $colour_name;

        /*
            Light Green		183,207,51		B7CF33
            Light Blue		50,166,207		32A6CF
            Orange			208,119,51		D07733
            Magenta			196,57,198		C439C6
            Purple	 		130,50,237		8232ED
            Persian			41,36,203		2924CB
            Green			81,179,80		51B350
            Indigo			61,104,196		3D68C4
            Blue			0,151,206		0097CE
            Violet			130,50,207		8232CF
            Gold			207,171,49		CFAB31
            Teal			74,183,141		4AB78D
            Red				208,61,51		D03D33
        */
    }
}

if (!function_exists("quality2colour")) {
    function quality2colour($quality)
    {
        $colour = NULL;
        switch ($quality) {
            case 0: //base
                $colour = '#FFFFFF';
                break;
            case 1: //genuine
                $colour = '#4D7455';
                break;
            case 2: //vintage
                $colour = '#476291';
                break;
            case 3: //unusual
                $colour = '#8650AC';
                break;
            case 4: //standard
                $colour = '#FFFFFF';
                break;
            case 5: //community
                $colour = '#70B04A';
                break;
            case 7: //self-made
                $colour = '#70B04A';
                break;
            case 9: //strange
                $colour = '#CF6A32';
                break;
            case 12: //tournament
                $colour = '#D46FF9';
                break;
            case 13: //favoured
                $colour = '#FFFF01';
                break;
        }
        return $colour;
    }
}

if (!function_exists('sortCardsFromInventory')) {
    function sortCardsFromInventory($player_items, $cardLookup)
    {
        $final_array = array();
        $aggregate_count = array();

        if (isset($player_items['result']['status']) && $player_items['result']['status'] == '1' && !empty($player_items['result']['items'])) {
            foreach ($player_items['result']['items'] as $key => $value) {
                if (array_key_exists($value['defindex'], $cardLookup)) {
                    $item_id = $value['defindex'];

                    $final_array[$key]['item_id'] = $value['id'];
                    $final_array[$key]['original_id'] = $value['original_id'];
                    $final_array[$key]['defindex'] = $value['defindex'];
                    $final_array[$key]['quality'] = $value['quality'];
                    $final_array[$key]['slot'] = $value['inventory'] & 65535;

                    $final_array[$key]['item_name'] = $cardLookup[$item_id]['name'];
                    $final_array[$key]['image_url'] = $cardLookup[$item_id]['image_url'];
                    $final_array[$key]['image_url_large'] = $cardLookup[$item_id]['image_url_large'];


                    ////////////////////////
                    // FORMATTED VERSION
                    ////////////////////////
                    $team_name = $cardLookup[$item_id]['team'];
                    $card_count = isset($aggregate_count[$value['defindex']]['count'])
                        ? $aggregate_count[$value['defindex']]['count'] + 1
                        : 1;

                    $aggregate_count['teams'][$team_name][$value['defindex']]['name'] = $cardLookup[$item_id]['name'];
                    $aggregate_count['teams'][$team_name][$value['defindex']]['count'] = $card_count;

                    $aggregate_count[$value['defindex']]['name'] = $cardLookup[$item_id]['name'];
                    $aggregate_count[$value['defindex']]['team'] = $team_name;
                    $aggregate_count[$value['defindex']]['count'] = $card_count;

                } else {
                    $final_array['errors'][] = 'Couldn\'t add: ' . $value['defindex'];
                    $aggregate_count['errors'][] = 'Couldn\'t add: ' . $value['defindex'];
                }
            }
        } else {
            $final_array['errors'] = 'Nothing in inventory';
            $aggregate_count['errors'] = 'Nothing in inventory';
        }

        $final_array = json_encode($aggregate_count);

        return $final_array;
    }
}