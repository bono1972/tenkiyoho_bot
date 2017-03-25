<?php
//ini_set( 'display_errors', 1 );
// OAuthライブラリの読み込み
require (__DIR__ ."/twitteroauth/autoload.php");
use Abraham\TwitterOAuth\TwitterOAuth;
//天候取得クラス読み込み
require (__DIR__ ."/cls_liveWeather.php");
//設定ファイル読み込み
require (__DIR__ ."/credentials.php");
//ユーザーID
$user = "your TwitterID";

//接続。それぞれのKEYを設定ファイルにdifineで定数化してある。
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

$sID = LoadID();//since_id読み込み

//自分宛てメンションのタイムライン取得
$mentions = $connection->get("statuses/mentions_timeline", array("count" => 20,"since_id" => $sID));

foreach ($mentions as $mention){
    $tx = null;
    $id = $mention->id_str; // 呟きのID。string型
    $screen_name = $mention->user->screen_name; // ユーザーID
    $name = $mention->user->name; // ユーザー名
    $in_reply_to_status_id_str = $mention->in_reply_to_status_id_str; //会話表示用
    // 呟き内容。余分なスペースを消して、半角カナを全角カナに、全角英数を半角英数に変換
    $text = mb_convert_kana(trim($mention->text),"rnKHV","utf-8");
    // Botが自分自身の呟き、RT、QTに反応しないようにする
    if($screen_name == $user || preg_match("/(R|Q)T( |:)/",$text)){continue;}
    
    $weather = new liveWeather();
    $arrayCity = $weather->getCityData($text);
    if(!is_null($arrayCity)){
        $cityID = $arrayCity['id'];
    } else { $cityID = "130010";} //東京のID

    if(preg_match('/(今日|明日|あした|明後日|あさって)/', $text,$match)) {
        if('今日' == $match[1]){
            $day = 0;
        } elseif (('明日'== $match[1]) || ('あした' == $match[1])){
            $day = 1;
        } elseif (('明後日'== $match[1]) || ('あさって' == $match[1])){
            $day = 2;
        }

        $result = $weather->getWeatherData($cityID, $day);
        if (!is_null($arrayCity)){
            $tx = "@".$screen_name." ".$name."さん、".$match[1]."の".$arrayCity['city']."の天気は".
            $result['telop']."、最高気温は".$result['maxTemp']."、最低気温は".
            $result['minTemp']."です。";
        } else {
            $tx = "@".$screen_name." ".$name."さん、申し訳ありません。お問い合わせの地域の天候データが".
            "データベースにございません。他の地域名で再度聞いて頂けますでしょうか。";
        }
    } else { $tx = "@".$screen_name." ".$name."さん、○○の(今日、明日、明後日)の天気、などと聞いて頂けますでしょうか。";}
    $param = ["status" => $tx, "in_reply_to_status_id_str" => $in_reply_to_status_id_str];
    if(isset($tx)) {
        $res = $connection->post("statuses/update",  $param);
        if($sID < $id) {$sID = $id;}

        $errCode = $res->errors[0]->code;
        if ($errCode == 187) {
            $errRes = $connection->post("statuses/update", array("status" => 
            "@".$screen_name." ".$name."さん、申し訳ありませんが、ツイッターの仕様上、同じ内容のツイートを連続して行うことができません。"));
         }
    SaveID($sID);
    }
    
}

function SaveID($sID){
  try{
  $savefile = __DIR__."/savefile.txt";
  $fileObj = new SplFileObject($savefile,"wb");//書込専用、上書き
  $fileObj->flock(LOCK_EX);
  $fileObj->fwrite($sID);
  $fileObj->flock(LOCK_UN);
  } catch (Exception $e){
    echo $e->getMessage();
  }
}
function LoadID(){
  try{
  $savefile = __DIR__."/savefile.txt";
  $fileObj = new SplFileObject($savefile,"rb");//読込専用
  $fileObj->flock(LOCK_SH);
  $sID = $fileObj->fread($fileObj->getSize());
  if ($sID ===FALSE){$sID ="1";} 
  $fileObj->flock(LOCK_UN);
  return $sID;
  } catch (Exception $e){
    echo $e->getMessage();
  }
}
