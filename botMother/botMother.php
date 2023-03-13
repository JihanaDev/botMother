<?php
// This script (botMother bot Blocker) was codded by J33h4n
// This script is not for illegal use
// Contact me via telegram:  https://t.me/j33h4n
// Respect Copyright <3 ASk me for permission before you change any part of this script :)
// Feel free to contact me for updates or custom scripts just for you <3

require 'config.php';




class botMother{

    // Don't touch these if you don't know what are you doing. OK?
    // public $COUNTRIES_FILTER            =   false;
    // public $TEST_MODE                   =   true;
    public $IP_API                      =   "http://ip-api.com/json/";
    public $TEST_MODE_IPS               =   array("::1", "0.0.0.0", "127.0.0.1");
    public $AGENTS_BLACKLIST_FILE       =   "data/AGENTS.jhn";
    public $IPS_BLACKLIST_FILE          =   "data/IPS.jhn";
    public $IPS_RANGE_BLACKLIST_FILE    =   "data/IPS_RANGE.jhn";
    public $HUMAN_LOGS_FILE             =   "log/human_log.txt";
    public $BOTS_LOGS_FILE              =   "log/bots_log.txt";


    function getTime(){
        return date("d M, Y h:i:sa");
    }

    function getLicenseKy(){
        global $LICENSE_KEY;
        return $LICENSE_KEY;
    }

    function getIp(){
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // CHECK IF IT'S A TEST MODE
        if(in_array($ip, $this->TEST_MODE_IPS)){
            $this->TEST_MODE = true;
            $ip = "1.1.1.1";
        }

        return $ip;
    }

    function getUserAgent(){
        return $_SERVER["HTTP_USER_AGENT"];
    }

    function getJsonData($link){
        $c = curl_init($link);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($c);
        curl_close($c);
        $data = json_decode($res);
        return $data;
    }

    function getIpInfo($param){
        $FULL_API_LINK = $this->IP_API . $this->getIp();
        $DATA = $this->getJsonData($FULL_API_LINK);
        return $DATA->$param;
    }

    function fileToArray($filename){
        $file_content = file_get_contents($filename);
        $agents_arr = explode(",", $file_content);
        return $agents_arr;
    }

    function blockBotsByAgent(){
       $agents = $this->fileToArray($this->AGENTS_BLACKLIST_FILE);
       foreach($agents as $agent){
            if(stripos($this->getUserAgent(), $agent) !== false OR trim($this->getUserAgent())==""){
                $this->saveBotIp();
                $this->killBot();
            }
       }

    }

    function blockBotsByIps(){
        $ips = $this->fileToArray($this->IPS_BLACKLIST_FILE);
        foreach($ips as $ip){
            if($this->getIp() == $ip){
                $this->killBot();
            }
        }
    }

    function blockBotsByIpsRange(){
        $ips_range = $this->fileToArray($this->IPS_RANGE_BLACKLIST_FILE);
        foreach($ips_range as $ip_range){
            if(strpos($this->getIp(), $ip_range) !== false){
                $this->saveBotIp();
                $this->killBot();
            }
        }
    }


    function addToFile($file, $text){
        $fp = fopen($file, "a");
        fwrite($fp, $text);
        fclose($fp);
    }


    function blockCountries(){
        global $FILTER_COUNTRIES;
        global $WHITELIST_COUNTRIES;
        if(strtolower($FILTER_COUNTRIES)=="yes" AND !in_array($this->getIpInfo("countryCode"), $WHITELIST_COUNTRIES)){
             $this->saveBotIp();
            $this->killBot();
        }
    }

    function saveBotIp(){
        $isBotExists = false;
        $ips = $this->fileToArray($this->IPS_BLACKLIST_FILE);
        foreach($ips as $ip){
            if($this->getIp() == $ip){
                $isBotExists = true;
                return;
            }
        }

        if(!$isBotExists){
            $this->addToFile($this->IPS_BLACKLIST_FILE, ",".$this->getIp());
        }

    }
   
    function logHuman(){
        global $logs;
        if(strtolower($logs)=="yes"){
            $fp = fopen($this->HUMAN_LOGS_FILE, "a");
            fwrite($fp,  "HUMAN VISIT :  [ ".$this->getIp()." - ".$this->getIpInfo("country")." ] ". $this->getTime()."\n");
            fclose($fp);
        }
    }

    function logBot(){
        global $logs;
        if(strtolower($logs)=="yes"){
            $fp = fopen($this->BOTS_LOGS_FILE, "a");
            fwrite($fp,  "BOT DETECTED:  [ IP: ".$this->getIp()."] [USER-AGENT: ".$this->getUserAgent()."] ". $this->getTime()."\n");
            fclose($fp);
        }
    }

    function killBot(){
        global $REDIRECTION;
        $this->logBot();
        exit(header("location: $REDIRECTION"));
    }

     function Run(){
        global $test_mode;
        if(strtolower($test_mode)=="no"){
            $this->blockCountries();
            $this->blockBotsByAgent();
            $this->blockBotsByIps();
            $this->blockBotsByIpsRange();
            $this->logHuman();
        }
    }


}




$jbot = new botMother;
$jbot->Run();



 
?>