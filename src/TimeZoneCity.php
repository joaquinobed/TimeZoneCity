<?php
/**
 * Time Zone City
 *
 * @version    3.0 (2018-06-06 18:08:00 GMT)
 * @author     Peter Kahl <https://github.com/peterkahl>
 * @copyright  2017-2018 Peter Kahl
 * @license    Apache License, Version 2.0
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      <http://www.apache.org/licenses/LICENSE-2.0>
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace peterkahl\TimeZoneCity;

use \DateTimeZone;
use \DateTime;
use \Exception;

class TimeZoneCity {

  /**
   * DB resource
   *
   */
  public $dbresource;

  #===================================================================

  /**
   * Returns an array of timezones according to specified criteria.
   *
   * @param string  $sortby ........ Admissible values:
   *          -- 'time_zone'
   *          -- 'offset'
   *          -- 'place_name'
   *          -- 'place_id'
   *          -- 'region_code'
   *          -- 'region_name'
   *          -- 'country_code'
   *          -- 'country_name'
   *          -- 'latitude'
   *          -- 'longitude'
   *      OR multiple criteria separated by comma, example:
   *          -- 'offset,place_name'
   *
   * @param string  $sortdir ....... Admissible values:
   *          -- 'asc'
   *          -- 'desc'
   *      OR multiple directions separated by comma, example:
   *          -- 'desc,asc'
   *
   * @param string  $onlycountry ... 2-letter country code, OR multiple
   *                                 codes separated by comma
   *      Examples:
   *          -- 'us'
   *          -- 'us,ca,mx'
   *          -- 'de,cz,sk,at,pl,fr,dk,be,nl,it,es,pt,ch,se,no,fi'
   *          -- '' (empty - no country limitation)
   *
   * @return mixed
   * @throws \Exception
   */
  public function GetAllZones($sortby = 'offset,place_name', $sortdir = 'asc,asc', $onlycountry = '') {

    $sortdir = strtoupper($sortdir);
    $validSortdir = array(
      'ASC',
      'DESC',
    );
    $sortdirArr = explode(',', $sortdir);
    foreach ($sortdirArr as $k => $v) {
      if (!in_array($v, $validSortdir)) {
        throw new Exception('Illegal value argument sortdir');
      }
    }

    $sortby = strtolower($sortby);
    $validSortby = array(
      'time_zone',
      'offset',
      'place_name',
      'place_id',
      'region_code',
      'region_name',
      'country_code',
      'country_name',
      'latitude',
      'longitude',
    );
    $sortbyArr = explode(',', $sortby);
    foreach ($sortbyArr as $k => $v) {
      if (!in_array($v, $validSortby)) {
        throw new Exception('Illegal value argument sortby');
      }
    }

    $sortbyStr = '';
    foreach ($sortbyArr as $k => $v) {
      if (!isset($sortdirArr[$k])) {
        $sortdirArr[$k] = 'ASC';
      }
      $sortbyStr .= ', `'. $v .'` '. $sortdirArr[$k];
    }
    $sortbyStr = trim($sortbyStr, ', ');

    if (!is_string($onlycountry)) {
      throw new Exception('Argument onlycountry must be a string');
    }

    if (!empty($onlycountry)) {
      $onlyArr = explode(',', strtoupper($onlycountry));
      foreach ($onlyArr as $k => $v) {
        if (strlen($v) != 2) {
          throw new Exception('Illegal value argument onlycountry');
        }
        $onlyArr[$k] = "`country_code`='". mysqli_real_escape_string($this->dbresource, $v) ."'";
      }
      $onlyStr = implode(' OR ', $onlyArr);
    }

    if (!empty($onlycountry)) {
      $sql = "SELECT * FROM `timezonecity` WHERE ". $onlyStr ." ORDER BY ". $sortbyStr .";";
    }
    else {
      $sql = "SELECT * FROM `timezonecity` ORDER BY ". $sortbyStr .";";
    }

    $result = mysqli_query($this->dbresource, $sql);

    if ($result === false) {
      throw new Exception('Error executing SQL query');
    }

    $arr = array();
    $n = 0;
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
      $arr[$n] = $row;
      $arr[$n]['offset_formatted'] = $this->ReadableOffset($arr[$n]['offset']);
      $n++;
    }
    return $arr;
  }

  #===================================================================

  /**
   * Validates a timezone.
   * @param  string  $zone
   * @return mixed
   * @throws \Exception
   */
  public function ValidZone($zone) {
    $sql = "SELECT 1 FROM `timezonecity` WHERE `time_zone`='". mysqli_real_escape_string($this->dbresource, $zone) ."' LIMIT 1;";
    $result = mysqli_query($this->dbresource, $sql);
    if ($result === false) {
      throw new Exception('Error executing SQL query');
    }
    if (mysqli_num_rows($result) > 0) {
      return true;
    }
    return false;
  }

  #===================================================================

  /**
   * Returns all information on requested timezone (the whole row).
   * @param  string  $zone
   * @return mixed
   * @throws \Exception
   */
  public function GetZoneInfo($zone) {
    $sql = "SELECT * FROM `timezonecity` WHERE `time_zone`='". mysqli_real_escape_string($this->dbresource, $zone) ."' LIMIT 1;";
    $result = mysqli_query($this->dbresource, $sql);
    if ($result === false) {
      throw new Exception('Error executing SQL query');
    }
    if (mysqli_num_rows($result) > 0) {
      $arr = mysqli_fetch_array($result, MYSQLI_ASSOC);
      $arr['offset_formatted'] = $this->ReadableOffset($arr['offset']);
      return $arr;
    }
    return array();
  }

  #===================================================================

  /**
   * Returns nearest timezone for given country, longitude, latitude.
   * @param  string  $country ... 2-letter country code
   * @param  float   $lat  $long
   * @return string
   * @throws \Exception
   */
  public function GetNearestZone($country, $lat, $long) {
    if (!empty($country)) {
      $sql = "SELECT `time_zone` FROM `timezonecity` WHERE `country_code`='". mysqli_real_escape_string($this->dbresource, strtoupper($country)) ."' AND ABS(`longitude` - '". mysqli_real_escape_string($this->dbresource, $long) ."')<'15' ORDER BY ABS(`longitude` - '". mysqli_real_escape_string($this->dbresource, $long) ."') LIMIT 1;";
      $result = mysqli_query($this->dbresource, $sql);
      if ($result === false) {
        throw new Exception('Error executing SQL query');
      }
      if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        return $row['time_zone'];
      }
    }
    # Something was wrong with the country code. Now, we use only coordinates.
    $sql = "SELECT `time_zone` FROM `timezonecity` ORDER BY ABS(`longitude` - '". mysqli_real_escape_string($this->dbresource, $long) ."'), ABS(`latitude` - '". mysqli_real_escape_string($this->dbresource, $lat) ."') LIMIT 1;";
    $result = mysqli_query($this->dbresource, $sql);
    if ($result === false) {
      throw new Exception('Error executing SQL query');
    }
    if (mysqli_num_rows($result) > 0) {
      $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
      return $row['time_zone'];
    }
    throw new Exception('Failed to determime nearest timezone');
  }

  #===================================================================

  /**
   * Calculates offset from GMT for given timezone.
   * This includes DST (if observed).
   * @param  string   $zone
   * @return integer
   */
  public function GetZoneOffset($zone) {
    $remote_dtz = new DateTimeZone($zone);
    $remote_dt = new DateTime('now', $remote_dtz);
    return $remote_dtz->getOffset($remote_dt);
  }

  #===================================================================

  /**
   * Returns zone abbreviation, e.g. GMT, BST, PDT, HKT.
   * @param  string   $zone
   * @param  boolean  $preventEmpty ... When abbreviation does not exist,
   *                                    offset will be returned instead,
   *                                    e.g. +0100
   * @return string
   * @throws \Exception
   */
  public function GetZoneAbbr($zone, $preventEmpty = true) {
    $tz = new DateTimeZone($zone);
    $date = new DateTime('now', $tz);
    $trans = $tz->getTransitions();
    foreach ($trans as $k => $t) {
      if ($t['ts'] > time()) {
        return $trans[$k-1]['abbr'];
      }
    }
    return $preventEmpty ? $this->Sec2Readable($this->GetZoneOffset($zone)) : '';
  }

  #===================================================================

  /**
   * Is daylight savings (+1 hour) observed right now?
   * @param  string $zone
   * @return boolean
   * @throws \Exception
   */
  public function ZoneDoesDST($zone) {
    $tz = new DateTimeZone($zone);
    $date = new DateTime('now', $tz);
    $trans = $tz->getTransitions();
    foreach ($trans as $k => $t) {
      if ($t['ts'] > $date->format('U')) {
        return $trans[$k-1]['isdst'];
      }
    }
    throw new Exception('Failed to locate key for zone '. $zone);
  }

  #===================================================================

  /**
   * Zone offset in seconds into readable format.
   * @param  integer $sec
   * @return string
   */
  public function Sec2Readable($sec) {
    $hours   = floor(abs($sec)/3600);
    $minutes = floor((abs($sec) - $hours*3600)/60);
    $sign = ($sec >= 0) ? '+' : '-';
    return $sign . str_pad($hours, 2, '0', STR_PAD_LEFT) . str_pad($minutes, 2, '0', STR_PAD_LEFT);
  }

  #===================================================================

  /**
   * Converts offset in hours and decimal fractions into 'H:m' format.
   * @param  string $offset
   * @return string
   */
  public function ReadableOffset($offset) {
    $offset = number_format($offset, 2, '.', '');
    list($hours, $decimal) = explode('.', $offset);
    $minutes = str_pad(substr(trim('.'. $decimal * 60, '.'), 0, 2), 2, '0', STR_PAD_RIGHT);
    $sign = ($hours >= 0) ? '+' : '-';
    return $sign . str_pad($hours, 2, '0', STR_PAD_LEFT) .':'. $minutes;
  }

  #===================================================================

  /**
   * Removes accents. Makes foreign words more fiendly.
   * @param  string $str
   * @return string
   */
  public function RemoveAccents($str) {
    $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
    $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
    return str_replace($a, $b, $str);
  }

  #===================================================================
}
