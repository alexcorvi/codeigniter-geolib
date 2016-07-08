CodeIgniter GeoLib
===================


A CodeIgniter Geo library for displaying IP location data, currency conversion and user agent parsing.

----------


Features
-------------
1. Request API info from the [geoPlugin](http://www.geoplugin.com/) API.
2. When requesting data from remote API it uses both `CURL` and `file_get_contents` (whichever works on your host).
3. It Provides a wrapper function around core CodeIgniter's user-agent data, so you got all the info in one array and don't have to memorize more stuff.
4. Accurate, realtim-ish currency conversion using:
	- Yahoo Finance Query API.
	- Google's currency converter.
	- GeoPlugin currency data.
5. It will first fetch Yahoo's API (since it's an official and efficient API, not based on a hack), then, if failed, it will fallback to Google's currency converter by extracting the data out of an HTML page, then if both failed (highly improbable it will fallback to GeoPlugin's currency data.
6. You can pass either currency code (e.g. `GBP`) or country code (e.g. `GB`) or IP address (e.g. `1.2.3.4`) to the currency converter method.
7. You can pass `null` as the `$to` argument to the currency converter method, and it will take your IP and convert the currency from base to your local one. (base = `$from`).
7. It has been designed to fit on almost every web host.   

Installation
-------------

* Upload the files in the `src` directory to your `libraries` directory.
* Load the library: `$this->load->library("geolib/geolib");`

> **Note:**

> You can easily modify the code to make it work even outside CodeIgniter, just make sure that you poly-fill all the CodeIgniter methods that are used. Which are the ones that starts with `$ci`.

Usage and Examples
-------------
--------------------

**Load the library**
```php
// load the library
$this->load->library("geolib/geolib");
```
--------------------

**Get user agent data**
```
echo "<pre>";
$data = $this->geolib->user_agent();
print_r($data);
echo "</pre>";
```

- will output something similar to:

```
Array
(
    [is_robot] => 
    [is_mobile] => 
    [is_browser] => 1
    [is_referral] => 
    [browser] => Chrome
    [version] => 51.0.2704.103
    [mobile] => 
    [platform] => Windows 10
    [referrer] => 
    [accept_langs] => Array
        (
            [0] => en-us
            [1] => en
            [2] => ar
        )

    [accept_charsets] => Array
        (
            [0] => Undefined
        )

)
```

--------------------

**Get user IP data**

```
echo "<pre>";
$data = $this->geolib->ip_info();
// or $data = $this->geolib->ip_info("198.211.100.32");
print_r($data);
echo "</pre>";
```
- will output something similar to:
```

Array
(
    [geoplugin_request] => 198.211.100.32
    [geoplugin_status] => 200
    [geoplugin_credit] => Some of the returned data includes GeoLite data created by MaxMind, available from http://www.maxmind.com.
    [geoplugin_city] => New York
    [geoplugin_region] => NY
    [geoplugin_areaCode] => 212
    [geoplugin_dmaCode] => 501
    [geoplugin_countryCode] => US
    [geoplugin_countryName] => United Status
    [geoplugin_continentCode] => NA
    [geoplugin_latitude] => 40.7143
    [geoplugin_longitude] => -74.006
    [geoplugin_regionCode] => NY
    [geoplugin_regionName] => New YorK
    [geoplugin_currencyCode] => USD
    [geoplugin_currencySymbol] => &#36;
    [geoplugin_currencySymbol_UTF8] => $
    [geoplugin_currencyConverter] => 1
)
```

--------------------

**Currency Conversion**
As mentioned earlier, you can pass country two-letters ISO code (e.g. `GB`) or currency code (e.g. `GBP`). and as for the target currency (the `$to`), you can pass null, and the data will be filled according to visitor's IP address.

```
$data = $this->geolib->convert_currency("USD", "GBP", 800);
// convert 800 from USD to GBP
$data = $this->geolib->convert_currency("CA", "ME", 800);
// convert 800 from Canada's local currency to Montenegro local currency
$data = $this->geolib->convert_currency("TR", "GBP", 800);
// convert 800 from Turkey's local currency to GBP
```

> **Note:**

> You can also omit the number (e.g. `800`) and you'll just get the exchange rate from the first to the second currency, just as you've passed `1`.

------------

Credits
-------------

* Geo location: [GeoPlugin](http://www.geoplugin.com/).
* User-agent data: basic codeigniter's user-agent library.
* Currency Conversion:
	* [Yahoo Finance YQL](https://developer.yahoo.com/yql/console/) as the main method.
	* [Google finance converter](https://www.google.com/finance/converter) as a fallback.
	* [GeoPlugin](http://www.geoplugin.com/) as a second fallback.

------------

License
-------------

The MIT License (MIT)

Copyright (c) 2016 Alex Corvi

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
