# Time Zone City

Everything you need for working with timezones and world time. Each zone includes these details:
* timezone
* offset in hours (useful for sorting)
* place name
* Google Maps API place_id (useful for translation of place name)
* region code
* region name
* country code
* country name
* latitude
* longitude

---

## Usage

Get name of timezone from country code and longitude:
```php
use peterkahl\TimeZoneCity\TimeZoneCity;

$link = mysqli_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, $DB_DBNAME);

mysqli_set_charset($link, "utf8mb4");

$zoneObj = new TimeZoneCity;
$zoneObj->dbresource = $link;

$country   = 'ru';
$latitude  = 55.61;
$longitude = 38.76;

echo $zoneObj->GetNearestZone($country, $latitude, $longitude); # Europe/Moscow

#-----------------------------------------------------------------------
# If we don't know the country code, or the code is errorneous,
# latitude and longitude will be used to determine the nearest timezone.

$country   = '';
$latitude  = 22.27;
$longitude = 113.79;

echo $zoneObj->GetNearestZone($country, $latitude, $longitude); # Asia/Macau

```

Generate HTML code for SELECT tag:
```php
use peterkahl\TimeZoneCity\TimeZoneCity;

$link = mysqli_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, $DB_DBNAME);

mysqli_set_charset($link, "utf8mb4");

$zoneObj = new TimeZoneCity;
$zoneObj->dbresource = $link;

echo '<select>';

$zones = $zoneObj->GetAllZones(); # Advanced sorting is possible!

foreach ($zones as $key => $val) {
  if ($val['offset'] >= 0) {
    $val['offset'] = '+'. $val['offset'];
  }
  echo '<option value="'. $val['time_zone'] .'">(UTC'. $val['offset'] .':00)'. $val['place_name'] .', '. $val['country_name'] .'</option>';
}

echo '</select>';

```

Get details of given time zone:
```php
use peterkahl\TimeZoneCity\TimeZoneCity;

$link = mysqli_connect($DB_HOSTNAME, $DB_USERNAME, $DB_PASSWORD, $DB_DBNAME);

mysqli_set_charset($link, "utf8mb4");

$zoneObj = new TimeZoneCity;
$zoneObj->dbresource = $link;

$info = $zoneObj->GetZoneInfo('Africa/Sao_Tome');

# Google Maps API place_id can be used to obtain translated name
echo $info['place_id'];   # ChIJidiaC_nscBAR6jB2VQwjUWI

echo $info['place_name']; # São Tomé and Príncipe

# Remove accents
$city = $zoneObj->RemoveAccents($info['place_name']); # Sao Tome and Principe

var_dump($info);

/*
array(10) {
  ["time_zone"]=>
  string(15) "Africa/Sao_Tome"
  ["offset"]=>
  string(1) "0"
  ["place_name"]=>
  string(24) "São Tomé and Príncipe"
  ["place_id"]=>
  string(27) "ChIJidiaC_nscBAR6jB2VQwjUWI"
  ["region_code"]=>
  string(0) ""
  ["region_name"]=>
  string(0) ""
  ["country_code"]=>
  string(2) "ST"
  ["country_name"]=>
  string(24) "São Tomé and Príncipe"
  ["latitude"]=>
  string(7) "0.18636"
  ["longitude"]=>
  string(17) "6.613080999999999"
}
*/

```
