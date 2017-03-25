<?php
//ini_set( 'display_errors', 1 );
//ライブドアの天気予報を表示する関数
class liveWeather {

	//地域のID取得
	function getCityData($text) {
		$area_url = "http://weather.livedoor.com/forecast/rss/primary_area.xml";
		$xml = simplexml_load_file($area_url);
		$xml = $xml->channel->children('ldWeather', true)->source->children();
		foreach( $xml as $pref ) {
  			foreach( $pref->children() as $pchild ) {
    			if($pchild->getName() == 'city') {
					$city = (string) $pchild->attributes()->title;
					$id = (string) $pchild->attributes()->id;
					if (strstr($text, $city)){
						return ['city'=> $city, 'id' => $id];
					}
				}
			}
		}
	}

	//今日、明日、明後日の地域の天候データ取得
	function getWeatherData($city,$day){
		$base_url = "http://weather.livedoor.com/forecast/webservice/json/v1?city=$city";
		$json = file_get_contents($base_url);
		$json = mb_convert_encoding($json, 'UTF-8');
		$obj = json_decode($json, false);
		$telop = $obj->forecasts[$day]->telop;
		$maxTemp = $obj->forecasts[$day]->temperature->max->celsius;
		if (is_null($maxTemp)){
			$maxTemp = "不明";
		} else {$maxTemp = $maxTemp."度";}
		$minTemp = $obj->forecasts[$day]->temperature->min->celsius;
		if (is_null($minTemp)){
			$minTemp = "不明";
		} else {$minTemp = $minTemp."度";}
		return ['telop'=>$telop,'maxTemp'=>$maxTemp,'minTemp'=>$minTemp];
	}
	
}

//使用例
/*
$lWeather = new liveWeather();
$word = "東京だよ、おっかさん。";
$arrayCity = $lWeather->getCityData($word);
if(!is_null($arrayCity)){
	$result = $lWeather->getWeatherData($arrayCity['id'],0);
} else {
	$result = $lWeather->getWeatherData("130010",0); //東京のID
}

echo $result['telop'];
echo $result['maxTemp'];
echo $result['minTemp']
*/

//?>
