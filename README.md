# Time Zone City

[![Downloads](https://img.shields.io/packagist/dt/peterkahl/time-zone-city.svg)](https://packagist.org/packages/peterkahl/time-zone-city)
[![License](http://img.shields.io/:license-apache-blue.svg)](http://www.apache.org/licenses/LICENSE-2.0.html)
[![If this project has business value for you then don't hesitate to support me with a small donation.](https://img.shields.io/badge/Donations-via%20Paypal-blue.svg)](https://www.paypal.me/PeterK93)

Detects nearest timezone (and country etc.) for given coordinates and more!

This PHP timezone library --
* detects nearest timezone for given coordinates
* detects nearest country for given coordinates
* generates HTML code for timezone select with customizable configuration
* validates a timezone db name (e.g. 'Asia/Dubai')
* returns time offset in seconds for given timezone
* returns information for given timezone (see below 🎉)
* returns Google Maps API `place_id` for given timezone
* returns 3-5 character abbreviation for given timezone (CEST, BST, GMT, EST). This works where the native PHP DateTimeZone fails!

Each zone includes these details:
* timezone db name (e.g. 'Asia/Dubai')
* 3-5 character abbreviation (both standard and daylight time, e.g. 'PST' and 'PDT') 🎉
* full name of time zone (both standard and daylight time, e.g. 'Pacific Standard Time' and 'Pacific Daylight Time') 🎉
* offset in hours (both standard and daylight time; useful for sorting) 🎉
* place name (city)
* Google Maps API `place_id` (useful for translation of place name)
* region code
* region name
* country code
* country name
* latitude
* longitude

## Upgrading from versions older than 2019-02-05

The version released on 2019-02-05 is a major revamp with code improvements and database expansion. Make sure you import the database dump if your existing Time Zone City is older than 2019-02-05.

### Alternative time zone library available!

I have created an alternative library which is simpler, less versatile and does not use SQL database, but may do the job you need:

[https://github.com/peterkahl/time-zone-name](https://github.com/peterkahl/time-zone-name)

## Usage

Get name of timezone from country code, latitude and longitude:
```php
use peterkahl\TimeZoneCity\TimeZoneCity;

$link = mysqli_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, $DB_DBNAME);

mysqli_set_charset($link, "utf8mb4");

$zoneObj = new TimeZoneCity;
$zoneObj->dbresource = $link;

$country   = 'ru';
$latitude  = 55.61;
$longitude = 38.76;

echo $zoneObj->GetNearestZone($country, $latitude, $longitude);

/*
Array
(
    [time_zone] => Europe/Moscow
    [std_abbr] => MSK
    [dst_abbr] =>
    [std_offset] => 3
    [dst_offset] => 3
    [std_full] => Moscow Time
    [dst_full] =>
    [place_name] => Moscow
    [place_id] => ChIJybDUc_xKtUYRTM9XV8zWRD0
    [region_code] =>
    [region_name] => Moskva Oblast
    [country_code] => RU
    [country_name] => Russia
    [latitude] => 55.755826
    [longitude] => 37.6173
)
*/

#-----------------------------------------------------------------------
# GEOCODING
# If we don't know the country code (or the code is erorrneous),
# latitude and longitude will be used to determine the nearest timezone, country etc.

$country   = '';
$latitude  = 52.486;  # This is Birmingham, UK
$longitude = -1.89;

print_r($zoneObj->GetNearestZone($country, $latitude, $longitude), true);

/*
Array
(
    [time_zone] => Europe/Jersey
    [std_abbr] => GMT
    [dst_abbr] => BST
    [std_offset] => 0
    [dst_offset] => 1
    [std_full] => Greenwich Mean Time
    [dst_full] => British Summer Time
    [place_name] => Jersey
    [place_id] => ChIJM3WSjKRSDEgRw2waCqMjnFE
    [region_code] =>
    [region_name] =>
    [country_code] => JE
    [country_name] => Jersey
    [latitude] => 49.214439
    [longitude] => -2.13125
)

Well, it's not perfect, but close enough! At least the timezone is right.
*/

#-----------------------------------------------------------------------
# Example when country code is erorrneous.

$country   = 'DE';
$latitude  = 22.27; # Coordinates are near Macau; can't be Germany!
$longitude = 113.79;

print_r($zoneObj->GetNearestZone($country, $latitude, $longitude), true);

/*
Array
(
    [time_zone] => Asia/Macau
    [std_abbr] => CST
    [dst_abbr] =>
    [std_offset] => 8
    [dst_offset] => 8
    [std_full] => China Standard Time
    [dst_full] =>
    [place_name] => Macau
    [place_id] => ChIJ88g14uB6ATQR9qyFtCzje8Y
    [region_code] =>
    [region_name] =>
    [country_code] => MO
    [country_name] => Macau
    [latitude] => 22.198745
    [longitude] => 113.543873
)
*/

```

Generate HTML code for SELECT tag:
```php
use peterkahl\TimeZoneCity\TimeZoneCity;

$link = mysqli_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, $DB_DBNAME);

mysqli_set_charset($link, "utf8mb4");

$zoneObj = new TimeZoneCity;
$zoneObj->dbresource = $link;

#-----------------------------------------------------------------------
# All world timezones

$zones = $zoneObj->GetAllZones(); # Advanced sorting is possible!

$currentZone = 'America/Los_Angeles';

echo '<select>'."\n";

foreach ($zones as $key => $val) {
  $place = array();
  $place[] = $val['place_name'];
  $place[] = $val['region_code'];
  $place[] = $val['country_name'];
  $place = array_filter($place);
  $place = array_unique($place);
  $place = implode(', ', $place);
  echo '  <option value="'. $val['time_zone'] .'"';
  if ($currentZone == $val['time_zone']) {
    echo ' selected';
  }
  echo '>(UTC'. $val['std_offset_formatted'] .') '. $place .'</option>'."\n";
}

echo '</select>'."\n";

#-----------------------------------------------------------------------
# Only US timezones

$zones = $zoneObj->GetAllZones('std_offset,place_name', 'desc,asc', 'us');

$currentZone = 'America/Los_Angeles';

echo '<select>'."\n";

foreach ($zones as $key => $val) {
  $place = array();
  $place[] = $val['place_name'];
  $place[] = $val['region_code'];
  #$place[] = $val['country_name'];
  $place = array_filter($place);
  $place = array_unique($place);
  $place = implode(', ', $place);
  echo '  <option value="'. $val['time_zone'] .'"';
  if ($currentZone == $val['time_zone']) {
    echo ' selected';
  }
  echo '>(UTC'. $val['std_offset_formatted'] .') '. $place .'</option>'."\n";
}

echo '</select>'."\n";

/*
<select>
  <option value="Pacific/Wake">(UTC+12:00) Wake Island</option>
  <option value="America/Detroit">(UTC-05:00) Detroit, MI</option>
  <option value="America/Indiana/Indianapolis">(UTC-05:00) Indianapolis, IN</option>
  <option value="America/Kentucky/Louisville">(UTC-05:00) Louisville, KY</option>
  <option value="America/Indiana/Marengo">(UTC-05:00) Marengo, IN</option>
  <option value="America/Kentucky/Monticello">(UTC-05:00) Monticello, KY</option>
  <option value="America/New_York">(UTC-05:00) New York, NY</option>
  <option value="America/Indiana/Petersburg">(UTC-05:00) Petersburg, IN</option>
  <option value="America/Indiana/Vevay">(UTC-05:00) Vevay, IN</option>
  <option value="America/Indiana/Vincennes">(UTC-05:00) Vincennes, IN</option>
  <option value="America/Indiana/Winamac">(UTC-05:00) Winamac, IN</option>
  <option value="America/North_Dakota/Beulah">(UTC-06:00) Beulah, ND</option>
  <option value="America/North_Dakota/Center">(UTC-06:00) Center, ND</option>
  <option value="America/Chicago">(UTC-06:00) Chicago, IL</option>
  <option value="America/Indiana/Knox">(UTC-06:00) Knox, IN</option>
  <option value="America/Menominee">(UTC-06:00) Menominee, MI</option>
  <option value="America/North_Dakota/New_Salem">(UTC-06:00) New Salem, ND</option>
  <option value="America/Indiana/Tell_City">(UTC-06:00) Tell City, IN</option>
  <option value="America/Boise">(UTC-07:00) Boise, ID</option>
  <option value="America/Denver">(UTC-07:00) Denver, CO</option>
  <option value="America/Phoenix">(UTC-07:00) Phoenix, AZ</option>
  <option value="America/Los_Angeles" selected>(UTC-08:00) Los Angeles, CA</option>
  <option value="America/Metlakatla">(UTC-08:00) Metlakatla, AK</option>
  <option value="America/Anchorage">(UTC-09:00) Anchorage, AK</option>
  <option value="America/Juneau">(UTC-09:00) Juneau, AK</option>
  <option value="America/Nome">(UTC-09:00) Nome, AK</option>
  <option value="America/Sitka">(UTC-09:00) Sitka, AK</option>
  <option value="America/Yakutat">(UTC-09:00) Yakutat, AK</option>
  <option value="America/Adak">(UTC-10:00) Adak, AK</option>
  <option value="Pacific/Honolulu">(UTC-10:00) Honolulu, HI</option>
  <option value="Pacific/Johnston">(UTC-10:00) Johnston Atoll</option>
  <option value="Pacific/Midway">(UTC-11:00) Midway Atoll</option>
  <option value="Pacific/Pago_Pago">(UTC-11:00) Pago Pago</option>
</select>

*/

#-----------------------------------------------------------------------
# Only US and Canadian timezones

$zones = $zoneObj->GetAllZones('std_offset,place_name', 'desc,asc', 'us,ca');

$currentZone = 'America/Los_Angeles';

echo '<select>'."\n";

foreach ($zones as $key => $val) {
  $place = array();
  $place[] = $val['place_name'];
  $place[] = $val['region_code'];
  $place[] = $val['country_name'];
  $place = array_filter($place);
  $place = array_unique($place);
  $place = implode(', ', $place);
  echo '  <option value="'. $val['time_zone'] .'"';
  if ($currentZone == $val['time_zone']) {
    echo ' selected';
  }
  echo '>(UTC'. $val['std_offset_formatted'] .') '. $place .'</option>'."\n";
}

echo '</select>'."\n";

/*
<select>
  <option value="Pacific/Wake">(UTC+12:00) Wake Island, United States</option>
  <option value="America/St_Johns">(UTC-03:30) St. John's, NL, Canada</option>
  <option value="America/Blanc-Sablon">(UTC-04:00) Blanc-Sablon, QC, Canada</option>
  <option value="America/Glace_Bay">(UTC-04:00) Glace Bay, NC, Canada</option>
  <option value="America/Goose_Bay">(UTC-04:00) Goose Bay, NL, Canada</option>
  <option value="America/Halifax">(UTC-04:00) Halifax, NS, Canada</option>
  <option value="America/Moncton">(UTC-04:00) Moncton, NB, Canada</option>
  <option value="Atlantic/Stanley">(UTC-03:00) Stanley, NB, Canada</option>
  <option value="America/Atikokan">(UTC-05:00) Atikokan, ON, Canada</option>
  <option value="America/Detroit">(UTC-05:00) Detroit, MI, United States</option>
  <option value="America/Indiana/Indianapolis">(UTC-05:00) Indianapolis, IN, United States</option>
  <option value="America/Iqaluit">(UTC-05:00) Iqaluit, NU, Canada</option>
  <option value="America/Kentucky/Louisville">(UTC-05:00) Louisville, KY, United States</option>
  <option value="America/Indiana/Marengo">(UTC-05:00) Marengo, IN, United States</option>
  <option value="America/Kentucky/Monticello">(UTC-05:00) Monticello, KY, United States</option>
  <option value="America/New_York">(UTC-05:00) New York, NY, United States</option>
  <option value="America/Nipigon">(UTC-05:00) Nipigon, ON, Canada</option>
  <option value="America/Pangnirtung">(UTC-05:00) Pangnirtung, NU, Canada</option>
  <option value="America/Indiana/Petersburg">(UTC-05:00) Petersburg, IN, United States</option>
  <option value="America/Thunder_Bay">(UTC-05:00) Thunder Bay, ON, Canada</option>
  <option value="America/Toronto">(UTC-05:00) Toronto, ON, Canada</option>
  <option value="America/Indiana/Vevay">(UTC-05:00) Vevay, IN, United States</option>
  <option value="America/Indiana/Vincennes">(UTC-05:00) Vincennes, IN, United States</option>
  <option value="America/Indiana/Winamac">(UTC-05:00) Winamac, IN, United States</option>
  <option value="America/North_Dakota/Beulah">(UTC-06:00) Beulah, ND, United States</option>
  <option value="America/North_Dakota/Center">(UTC-06:00) Center, ND, United States</option>
  <option value="America/Chicago">(UTC-06:00) Chicago, IL, United States</option>
  <option value="America/Indiana/Knox">(UTC-06:00) Knox, IN, United States</option>
  <option value="America/Menominee">(UTC-06:00) Menominee, MI, United States</option>
  <option value="America/North_Dakota/New_Salem">(UTC-06:00) New Salem, ND, United States</option>
  <option value="America/Rainy_River">(UTC-06:00) Rainy River, ON, Canada</option>
  <option value="America/Rankin_Inlet">(UTC-06:00) Rankin Inlet, NU, Canada</option>
  <option value="America/Regina">(UTC-06:00) Regina, SK, Canada</option>
  <option value="America/Resolute">(UTC-06:00) Resolute, NU, Canada</option>
  <option value="America/Swift_Current">(UTC-06:00) Swift Current, SK, Canada</option>
  <option value="America/Indiana/Tell_City">(UTC-06:00) Tell City, IN, United States</option>
  <option value="America/Winnipeg">(UTC-06:00) Winnipeg, MB, Canada</option>
  <option value="America/Boise">(UTC-07:00) Boise, ID, United States</option>
  <option value="America/Cambridge_Bay">(UTC-07:00) Cambridge Bay, NU, Canada</option>
  <option value="America/Creston">(UTC-07:00) Creston, BC, Canada</option>
  <option value="America/Dawson_Creek">(UTC-07:00) Dawson Creek, BC, Canada</option>
  <option value="America/Denver">(UTC-07:00) Denver, CO, United States</option>
  <option value="America/Edmonton">(UTC-07:00) Edmonton, AB, Canada</option>
  <option value="America/Fort_Nelson">(UTC-07:00) Fort Nelson, BC, Canada</option>
  <option value="America/Inuvik">(UTC-07:00) Inuvik, NT, Canada</option>
  <option value="America/Phoenix">(UTC-07:00) Phoenix, AZ, United States</option>
  <option value="America/Yellowknife">(UTC-07:00) Yellowknife, NT, Canada</option>
  <option value="America/Dawson">(UTC-08:00) Dawson, YT, Canada</option>
  <option value="America/Los_Angeles" selected>(UTC-08:00) Los Angeles, CA, United States</option>
  <option value="America/Vancouver">(UTC-08:00) Vancouver, BC, Canada</option>
  <option value="America/Whitehorse">(UTC-08:00) Whitehorse, YT, Canada</option>
  <option value="America/Anchorage">(UTC-09:00) Anchorage, AK, United States</option>
  <option value="America/Juneau">(UTC-09:00) Juneau, AK, United States</option>
  <option value="America/Metlakatla">(UTC-08:00) Metlakatla, AK, United States</option>
  <option value="America/Nome">(UTC-09:00) Nome, AK, United States</option>
  <option value="America/Sitka">(UTC-09:00) Sitka, AK, United States</option>
  <option value="America/Yakutat">(UTC-09:00) Yakutat, AK, United States</option>
  <option value="America/Adak">(UTC-10:00) Adak, AK, United States</option>
  <option value="Pacific/Honolulu">(UTC-10:00) Honolulu, HI, United States</option>
  <option value="Pacific/Johnston">(UTC-10:00) Johnston Atoll, United States</option>
  <option value="Pacific/Midway">(UTC-11:00) Midway Atoll, United States</option>
  <option value="Pacific/Pago_Pago">(UTC-11:00) Pago Pago, United States</option>
</select>

Exec time = 3.18 msec

*/

```

Get details of given time zone:
```php
use peterkahl\TimeZoneCity\TimeZoneCity;

$link = mysqli_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, $DB_DBNAME);

mysqli_set_charset($link, "utf8mb4");

$zoneObj = new TimeZoneCity;
$zoneObj->dbresource = $link;

$info = $zoneObj->GetZoneInfo('Australia/Melbourne');

var_dump($info);

/*
array(15) {
  ["time_zone"]=>
  string(19) "Australia/Melbourne"
  ["std_abbr"]=>
  string(4) "AEST"
  ["dst_abbr"]=>
  string(4) "AEDT"
  ["std_offset"]=>
  string(2) "10"
  ["dst_offset"]=>
  string(2) "11"
  ["std_full"]=>
  string(32) "Australian Eastern Standard Time"
  ["dst_full"]=>
  string(40) "Australian Eastern Daylight Savings Time"
  ["place_name"]=>
  string(9) "Melbourne"
  ["place_id"]=>
  string(27) "ChIJ90260rVG1moRkM2MIXVWBAQ"
  ["region_code"]=>
  string(3) "VIC"
  ["region_name"]=>
  string(8) "Victoria"
  ["country_code"]=>
  string(2) "AU"
  ["country_name"]=>
  string(9) "Australia"
  ["latitude"]=>
  string(11) "-37.8136276"
  ["longitude"]=>
  string(11) "144.9630576"
}

Exec time = 463.96 μsec
*/

```

Get abbreviation of given time zone:
```php
use peterkahl\TimeZoneCity\TimeZoneCity;

$link = mysqli_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, $DB_DBNAME);

mysqli_set_charset($link, "utf8mb4");

$zoneObj = new TimeZoneCity;
$zoneObj->dbresource = $link;

$DateObj = new DateTime('now');
$DateObj->setTimeZone(new DateTimeZone('Asia/Dubai');

# Is DST in effect in the zone Asia/Dubai ?
$dst = $DateObj->format('I');

echo $zoneObj->GetZoneAbbr('Asia/Dubai', $dst); # GST

```
