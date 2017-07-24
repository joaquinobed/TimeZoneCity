# Time Zone City

This PHP timezone library --
* generates HTML code for timezone select with customizable configuration
* detects nearest timezone for given coordinates
* validates a timezone
* returns time offset in seconds for given timezone
* returns information for given timezone
* tells whether DST is observed
* returns Google Maps API `place_id` for given timezone

Each zone includes these details:
* timezone
* offset in hours (useful for sorting)
* place name
* Google Maps API `place_id` (useful for translation of place name)
* region code
* region name
* country code
* country name
* latitude
* longitude

---

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

echo $zoneObj->GetNearestZone($country, $latitude, $longitude); # Europe/Moscow

#-----------------------------------------------------------------------
# If we don't know the country code, or the code is erorrneous,
# latitude and longitude will be used to determine the nearest timezone.

$country   = '';
$latitude  = 22.27;
$longitude = 113.79;

echo $zoneObj->GetNearestZone($country, $latitude, $longitude); # Asia/Macau

#-----------------------------------------------------------------------
# Example when country code is erorrneous.

$country   = 'DE';
$latitude  = 22.27; # Coordintaes are near Macau; can't be Germany!
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

#-----------------------------------------------------------------------
# All world timezones

echo '<select>';

$zones = $zoneObj->GetAllZones(); # Advanced sorting is possible!

foreach ($zones as $key => $val) {
  if ($val['offset'] >= 0) {
    $val['offset'] = '+'. str_pad($val['offset'], 2, '0', STR_PAD_LEFT);
  }
  else {
    $val['offset'] = '-'. str_pad(trim($val['offset'], '-'), 2, '0', STR_PAD_LEFT);
  }
  echo '<option value="'. $val['time_zone'] .'">(UTC'. $val['offset'] .':00)'. $val['place_name'] .', '. $val['country_name'] .'</option>';
}

echo '</select>';

#-----------------------------------------------------------------------
# Only US timezones

echo '<select>';

$zones = $zoneObj->GetAllZones('offset,place_name', 'asc', 'us');

foreach ($zones as $key => $val) {
  if ($val['offset'] >= 0) {
    $val['offset'] = '+'. str_pad($val['offset'], 2, '0', STR_PAD_LEFT);
  }
  else {
    $val['offset'] = '-'. str_pad(trim($val['offset'], '-'), 2, '0', STR_PAD_LEFT);
  }
  echo '<option value="'. $val['time_zone'] .'">(UTC'. $val['offset'] .':00)'. $val['place_name'] .', '. $val['country_name'] .'</option>';
}

echo '</select>';

#-----------------------------------------------------------------------
# Only US and Canadian timezones

echo '<select>'."\n";

$zones = $zoneObj->GetAllZones('offset,place_name', 'asc', 'us,ca');

foreach ($zones as $key => $val) {
  if ($val['offset'] >= 0) {
    $val['offset'] = '+'. str_pad($val['offset'], 2, '0', STR_PAD_LEFT);
  }
  else {
    $val['offset'] = '-'. str_pad(trim($val['offset'], '-'), 2, '0', STR_PAD_LEFT);
  }
  echo '  <option value="'. $val['time_zone'] .'">(UTC'. $val['offset'] .':00) '. $val['place_name'] .', '. $val['country_name'] .'</option>'."\n";
}

echo '</select>'."\n";

/*
<select>
  <option value="Pacific/Midway">(UTC-11:00) Midway Atoll, US</option>
  <option value="Pacific/Pago_Pago">(UTC-11:00) Pago Pago, US</option>
  <option value="America/Adak">(UTC-10:00) Adak, US</option>
  <option value="Pacific/Honolulu">(UTC-10:00) Honolulu, US</option>
  <option value="Pacific/Johnston">(UTC-10:00) Johnston Atoll, US</option>
  <option value="America/Anchorage">(UTC-09:00) Anchorage, US</option>
  <option value="America/Juneau">(UTC-09:00) Juneau, US</option>
  <option value="America/Nome">(UTC-09:00) Nome, US</option>
  <option value="America/Sitka">(UTC-09:00) Sitka, US</option>
  <option value="America/Yakutat">(UTC-09:00) Yakutat, US</option>
  <option value="America/Dawson">(UTC-08:00) Dawson, CA</option>
  <option value="America/Los_Angeles">(UTC-08:00) Los Angeles, US</option>
  <option value="America/Metlakatla">(UTC-08:00) Metlakatla, US</option>
  <option value="America/Vancouver">(UTC-08:00) Vancouver, CA</option>
  <option value="America/Whitehorse">(UTC-08:00) Whitehorse, CA</option>
  <option value="America/Boise">(UTC-07:00) Boise, US</option>
  <option value="America/Cambridge_Bay">(UTC-07:00) Cambridge Bay, CA</option>
  <option value="America/Creston">(UTC-07:00) Creston, CA</option>
  <option value="America/Dawson_Creek">(UTC-07:00) Dawson Creek, CA</option>
  <option value="America/Denver">(UTC-07:00) Denver, US</option>
  <option value="America/Edmonton">(UTC-07:00) Edmonton, CA</option>
  <option value="America/Fort_Nelson">(UTC-07:00) Fort Nelson, CA</option>
  <option value="America/Inuvik">(UTC-07:00) Inuvik, CA</option>
  <option value="America/Phoenix">(UTC-07:00) Phoenix, US</option>
  <option value="America/Yellowknife">(UTC-07:00) Yellowknife, CA</option>
  <option value="America/North_Dakota/Beulah">(UTC-06:00) Beulah, US</option>
  <option value="America/North_Dakota/Center">(UTC-06:00) Center, US</option>
  <option value="America/Chicago">(UTC-06:00) Chicago, US</option>
  <option value="America/Indiana/Knox">(UTC-06:00) Knox, US</option>
  <option value="America/Menominee">(UTC-06:00) Menominee, US</option>
  <option value="America/North_Dakota/New_Salem">(UTC-06:00) New Salem, US</option>
  <option value="America/Rainy_River">(UTC-06:00) Rainy River, CA</option>
  <option value="America/Rankin_Inlet">(UTC-06:00) Rankin Inlet, CA</option>
  <option value="America/Regina">(UTC-06:00) Regina, CA</option>
  <option value="America/Resolute">(UTC-06:00) Resolute, CA</option>
  <option value="America/Swift_Current">(UTC-06:00) Swift Current, CA</option>
  <option value="America/Indiana/Tell_City">(UTC-06:00) Tell City, US</option>
  <option value="America/Winnipeg">(UTC-06:00) Winnipeg, CA</option>
  <option value="America/Atikokan">(UTC-05:00) Atikokan, CA</option>
  <option value="America/Detroit">(UTC-05:00) Detroit, US</option>
  <option value="America/Indiana/Indianapolis">(UTC-05:00) Indianapolis, US</option>
  <option value="America/Iqaluit">(UTC-05:00) Iqaluit, CA</option>
  <option value="America/Kentucky/Louisville">(UTC-05:00) Louisville, US</option>
  <option value="America/Indiana/Marengo">(UTC-05:00) Marengo, US</option>
  <option value="America/Kentucky/Monticello">(UTC-05:00) Monticello, US</option>
  <option value="America/New_York">(UTC-05:00) New York, US</option>
  <option value="America/Nipigon">(UTC-05:00) Nipigon, CA</option>
  <option value="America/Pangnirtung">(UTC-05:00) Pangnirtung, CA</option>
  <option value="America/Indiana/Petersburg">(UTC-05:00) Petersburg, US</option>
  <option value="America/Thunder_Bay">(UTC-05:00) Thunder Bay, CA</option>
  <option value="America/Toronto">(UTC-05:00) Toronto, CA</option>
  <option value="America/Indiana/Vevay">(UTC-05:00) Vevay, US</option>
  <option value="America/Indiana/Vincennes">(UTC-05:00) Vincennes, US</option>
  <option value="America/Indiana/Winamac">(UTC-05:00) Winamac, US</option>
  <option value="America/Blanc-Sablon">(UTC-04:00) Blanc-Sablon, CA</option>
  <option value="America/Glace_Bay">(UTC-04:00) Glace Bay, CA</option>
  <option value="America/Goose_Bay">(UTC-04:00) Goose Bay, CA</option>
  <option value="America/Halifax">(UTC-04:00) Halifax, CA</option>
  <option value="America/Moncton">(UTC-04:00) Moncton, CA</option>
  <option value="America/St_Johns">(UTC-03:00) St. John's, CA</option>
  <option value="Atlantic/Stanley">(UTC-03:00) Stanley, CA</option>
  <option value="Pacific/Wake">(UTC+12:00) Wake Island, US</option>
</select>

*/

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
