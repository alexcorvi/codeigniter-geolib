<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**=========================================================================
 * CodeIgniter Geolib
 * A class for getting user agent data
 * -------------------------------------------------------------------------
 * Main features:
 *      -   Compatible class for getting IP inforamtion with wide range of
 *          hostings
 *      -   Getting all user agent data as an array (a wrapper around CI
 *          core lib.)
 *      -   Converting Currencies, either based on user location or
 *          passed as an argument.
 * -------------------------------------------------------------------------
 * # Author:	Alex Corvi <alex@arrayy.com>
 * -------------------------------------------------------------------------
 * # Licence:	The MIT License (MIT)
 *				Copyright (c) <2016> <Alex Corvi>
 *				------------------------------------------------------------
 *				Permission is hereby granted, free of charge, to any person
 *				obtaining a copy of this software and associated
 *				documentation files (the "Software"), to deal in the
 *				Software without restriction, including without limitation
 *				the rights to use, copy, modify, merge, publish, distribute,
 *				sublicense, and/or sell copies of the Software, and to
 *				permit persons to whom the Software is furnished to do so,
 *				subject to the following conditions:
 *
 *					1.	The above copyright notice and this permission
 *						notice shall be included in all copies or
 *						substantial portions of the Software.
 *					2.	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY
 *						OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT
 *						LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *						FITNESS FOR A PARTICULAR PURPOSE AND
 *						NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 *						COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES
 *						OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 *						CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF
 *						OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 *						OTHER DEALINGS IN THE SOFTWARE.
 *
 * ------------------------------------------------------------------
**/

class Geolib {
    /**
     * Constructor function
     * @private
     * @author Alex Corvi
     */
    public function __construct(){
        // load the geoIP plugin
        require_once $this->path(APPPATH,"libraries","geolib","inc","geoip-plugin.php"); 
        require_once $this->path(APPPATH,"libraries","geolib","inc","iso.php"); 
        $this->iso = new ISO();
    }
    
    /**
     * A wrapper around CI user agent lib
     * @author Alex Corvi
     * @return array All user agent data
     */
    public function user_agent(){
        $data = array();
        $ci = &get_instance();
        $ci->load->library('user_agent');
        
        $data["is_robot"]           =       $ci->agent->is_robot();
        $data["is_mobile"]          =       $ci->agent->is_mobile();
        $data["is_browser"]         =       $ci->agent->is_browser();
        $data["is_referral"]        =       $ci->agent->is_referral();
        $data["browser"]            =       $ci->agent->browser();
        $data["version"]            =       $ci->agent->version();
        $data["mobile"]             =       $ci->agent->mobile();
        $data["platform"]           =       $ci->agent->platform();
        $data["referrer"]           =       $ci->agent->referrer();
        $data["accept_langs"]       =       $ci->agent->languages();
        $data["accept_charsets"]    =       $ci->agent->charsets();
        
        return $data;
    }
    
    /**
     * getting all IP info
     * @author Alex Corvi
     * @param  string [$ip=null] the IP
     * @return array  IP data
     */
    public function ip_info ($ip=null) {
        $ci = &get_instance();
        /**
         * NOTE:
         * To minimize number of API requests,
         * we're saving the IP data in the session storage
         * Whenever we make an API request for this current user
         * we're going to save the result in a the session.
         * So when this method is called again the IP data
         * will show up.
         * 
        **/
        if($ip===null) {
            if(session_id() == '') session_start();
            if(isset($_SESSION["IP_DATA"])) {
                return unserialize(base64_decode(urldecode($_SESSION["IP_DATA"])));
            }
        }
        $gP = new geoPlugin();
        $data = $gP->locate($ip);
        if($ip===null) {
            if(session_id() == '') session_start();
            $_SESION["IP_DATA"] = urlencode(base64_encode(serialize($data))); // valid for the next 5 hours
        }
        return $data;
        /**
         * 
         * TODO: find an alternative API service to use if this one fails
         * 
        **/
    }
    
    /**
     *
     * Converting currencies
     * @author Alex Corvi
     * @param  string  $from           can be ISO country code or currency code
     * @param  string  $to             can be ISO country code or currency code
     *                                 If left empty, it will be filled according
     *                                 to user's IP.
     * @param  integer [$amount=false] a number to calculate it directly, or false to get the rate
     * @return integer Either rate, or calculated value
     *                                 
     */
    public function convert_currency($from, $to=null, $amount=false){
        
        if($to===null) $to = $this->ip_info()["geoplugin_countryCode"] ? $this->ip_info()["geoplugin_countryCode"] : "USD" ;
        
        // if passed country code,
        // this will automatically convert them to currency codes
        $from_cur = $this->iso->iso2currency($from);
        if(!$from_cur) $from_cur = $from;
        
        $to_cur = $this->iso->iso2currency($to);
        if(!$to_cur) $to_cur = $to;
        
        //--------------------------------------------
        // we'll try yahoo first
        // very reliable, w'll probably stop here
        $ycur = $this->ycur($from_cur,$to_cur,$amount);
        if($ycur) {
            if($amount === false) return $ycur;
            else return $amount * $ycur;
        }
        
        //--------------------------------------------
        // then google
        // although google is known for being efficient,
        // we're putting it second, because this is not 
        // an official API
        $gcur = $this->gcur($from_cur,$to_cur,$amount);
        if($gcur) {
            if($amount === false) return $gcur;
            else return $amount * $gcur;
        }
        
        //--------------------------------------------
        // then, if both failed
        // we'll fallback to the GeoPlugin
        $geoCur = $this->geoCur($from_cur,$to_cur,$amount);
        if($geoCur) {
            if($amount === false) return $geoCur;
            else return $amount * $geoCur;
            
            // NOTE: this API is known to have issues
            // with curriencies, being inacurate, outdated
            // and messy. This why i placed it as last resort
            // in the first place.
            // however, I've kept it here solely for educational
            // purposes, and I'm very confident that one of
            // the methods above will actually give the result
            // and the code will NOT reach this point.
            // Nevertheless, if you felt like you're getting
            // inacurate results, do NOT hesistate to remove
            // this bit of code.
            
            // NOTE: the API actually doesn't directly provide
            // a currency conversion functionality, it's rather
            // hack; by converting the country ISO code to a
            // generic IP that belongs to that country.
        }
    }
    
    /**
     * Getting Exchange rate using Yahoo's query tool https://developer.yahoo.com/yql/console/
     * @author Alex Corvi
     * @param  string  [$from="USD"] Base currency
     * @param  string  [$to="GBP"]   Target currency
     * @return integer exchange rate
     */
    private function ycur($from="USD",$to="GBP") {
        $from = urlencode($from);
        $to = urlencode($to);
        $host = 'http://query.yahooapis.com/v1/public/yql?q=select%20%2a%20from%20yahoo.finance.xchange%20where%20pair%20in%20%28%22{-from}{-to}%22%29&format=json&env=store://datatables.org/alltableswithkeys';
        $host = str_replace('{-from}',  $from,  $host);
        $host = str_replace('{-to}',    $to,    $host);
        $response = $this->req($host);
        if(!$response) return false;
        
        
        $response = json_decode($response,true);
        if(!$response) return false;
        
        if(!isset($response["query"])) return false;
        if(!isset($response["query"]["results"])) return false;
        if(!isset($response["query"]["results"]["rate"])) return false;
        if(!isset($response["query"]["results"]["rate"]["Rate"])) return false;
        
        
        $rate = $response["query"]["results"]["rate"]["Rate"];
        
        if(!$rate) return false;
        else return $rate;
    }

    
    /**
     * Getting Exchange rate using Google's tool https://www.google.com/finance/converter
     * @author Alex Corvi
     * @param  string  [$from="USD"] Base currency
     * @param  string  [$to="GBP"]   Target currency
     * @return integer exchange rate
     */
    private function gcur($from="USD", $to="GBP") {
        $from = urlencode($from);
        $to = urlencode($to);
        $host = "https://www.google.com/finance/converter?a=1&from={-from}&to={-to}";
        $host = str_replace('{-from}',  $from,  $host);
        $host = str_replace('{-to}',    $to,    $host);
        $response = $this->req($host);
        if(!$response) return false;
        if(class_exists("DOMDocument") && class_exists("DOMXpath")){
            $doc = new DOMDocument;
            @$doc->loadHTML($response);
            $xpath = new DOMXpath($doc);
            $rate = $xpath->query('//*[@id="currency_converter_result"]/span')->item(0)->nodeValue;
            $rate = str_replace(' '.$to, '', $rate);
        } else {
            $response = explode("<span class=bld>",$response);
            $response = explode("</span>",$response[1]);
            $rate = preg_replace("/[^0-9\.]/", null, $response[0]);
        }
        if(!$rate) return false;
        else return $rate;
    }
    
    
    /**
     * Getting the currecny convertion from the geoPlugin
     * @author Alex Corvi
     * @param  string  [$from="USD"] Base currencey
     * @param  string  [$to="USD"]   Target currency
     * @return integer Exchange rate
     */
    private function geoCur($from="USD",$to="USD"){
        
        // getting ISO target
        $iso_tr = $this->iso->currency2iso($to);
        if(!$iso_tr) $iso_tr = $from;
        
        $cur_base = $this->iso->iso2currency($from);
        if(!$cur_base) $cur_base = $from;
        
        
        return $this->money4Country($iso_tr,$cur_base);
    }
    
    /**
     * Makes a request to a URL
     * @author Alex Corvi
     * @param  string $host The url
     * @return string Response
     */
    private function req($host){
        $response = "";
        if (function_exists('curl_init')) {
            //use cURL to fetch data
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $host);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close ($ch);
        }
        if ((!function_exists('curl_init')) || !$response){
            if(ini_get('allow_url_fopen')) $response = file_get_contents($host, 'r');
            else return false;
        }
        return $response;
    }

    /**
     * Converts money from currency to country
     * @author Alex Corvi
     * @param  string  [$country="US"] the target country
     * @param  string  [$base="USD"]   base currency
     * @param  integer [$amount=false] The amount to convert
     * @return integer the result
     */
    private function money4Country($country="US",$base="USD"){
        $ip = $this->iso->iso2ip($country);
        if(!$ip) return false;
        return $this->money4IP($ip,$base);
    }

    /**
     * convets money from base currency to IP
     * @author Alex Corvi
     * @param  string  [$ip=null]      IP of target country
     * @param  string  [$base="USD"]   base currency
     * @param  integer [$amount=false] amount
     * @return integer result
     */
    private function money4IP($ip=null,$base="USD"){
        $gP = new geoPlugin();
        $gP->currency = $base;
        $rate = $gP->locate($ip)["geoplugin_currencyConverter"];
        if(!$rate) return false;
        else return $rate;
    }

    /**
     * Return the arguments as a path seperated by the DIRECTORY_SEPARATOR
     * @author Alex Corvi
     * @return string : full path                
     */
    private function path() {
        return join(DIRECTORY_SEPARATOR, func_get_args());
    }

}
