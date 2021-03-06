<?php
namespace Lib;

class Steam {
	/**
	 * Steam 的语言
	 * @var string
	 */
	static protected $language;
	static protected $country;

	public function __construct() {

	}
	/**
	 * 设置为steam的语言
	 * @param $language
	 */
	static protected function setLanguage($language) {
		switch ($language) {
		case 'en':
			self::$language = 'english';
			break;
		case 'zh':
			self::$language = 'schinese';
			break;
		case 'ja':
			self::$language = 'japanese';
			break;
		case 'th':
			self::$language = 'thai';
			break;
		case 'zh-tw':
			self::$language = 'tchinese';
			break;
		case 'pt':
			self::$language = 'portuguese';
			break;
		case 'ru':
			self::$language = 'russian';
			break;
		case 'tr':
			self::$language = 'turkish';
			break;
		case 'it':
			self::$language = 'italian';
			break;
		case 'nl':
			self::$language = 'dutch';
			break;
		case 'fr':
			self::$language = 'french';
			break;
		case 'es':
			self::$language = 'spanish';
			break;
		case 'de':
			self::$language = 'german';
			break;
		case 'kr':
			self::$language = 'koreana';
			break;
		default:
			self::$language = 'english';
			break;
		}
	}

	/**
	 * 获取个人库存
	 *
	 * @param int $game_id
	 * @param string $language
	 * @return string 一个获取库存的连接
	 */
	static public function getInventory($steamid, $appid, $language = 'en') {
		//730->csgo  570->dota
		self::setLanguage($language);
		return $url = 'http://steamcommunity.com/inventory/' . $steamid . '/' . $appid . '/2?l=' . self::$language . '&count=75';
	}

	/**
	 * 获取交易url
	 * @param string $steamid
	 * @return string
	 */
	static public function getTradeUrl($steamid) {
		return $url = 'https://steamcommunity.com/profiles/'
		. $steamid . '/tradeoffers/privacy#trade_offer_access_url';
	}

	/**
	 * 发送交易报价
	 * @param  string $to_steamid
	 * @param  array  $trade_info
	 * @param  array  $from_assets
	 * @param  array  $to_assets
	 * @return string $tradeofferid
	 */
	static public function launchTransaction($to_steamid = '', $trade_info = [], $from_assets = [], $to_assets = []) {
		//机器人发送空的交易报价，相当于是客户赠送给机器人的礼物
		$sessionid = get_cookie_info('sessionid');
		$url = 'https://steamcommunity.com/tradeoffer/new/send';
		//$url = 'https://steamcommunity.com/tradeoffer/new/send';
		$data = [
			'sessionid' => $sessionid, //交易发起人的sessionid
			'serverid' => $trade_info['serverid'], //服务器ID
			"partner" => $to_steamid, //接受交易报价人的steamid
			"tradeoffermessage" => $trade_info['tradeoffermessage'], //交易留言
			'json_tradeoffer' => [
				'newversion' => $trade_info['newversion'], //测试都为 true
				'version' => $trade_info['version'], //一个未知的版本，目前有2、3两种
				"me" => [ //交易发起人
					"assets" => [],
					"currency" => $trade_info['me_currency'], //交易报价所用的货币，bot 发起的交易货币应该为空，用 kaleoz 的货币来交易
					"ready" => $trade_info['me_ready'], //false什么鬼 ,猜测是交易报价是否已经被读取的选项
				],
				"them" => [
					"assets" => [],
					"currency" => $trade_info['them_currency'],
					"ready" => $trade_info['them_ready'], //什么鬼？？？ ,猜测是交易报价是否已经被读取的选项
				],
			],
			"captcha" => "",
			"trade_offer_create_params" => [
				//trade url 中的 token 值 //TkzH4VYR
				"trade_offer_access_token" => $trade_info["trade_offer_access_token"],
			],
		];

		if (is_array($from_assets) && count($from_assets) > 0) {
			foreach ($from_assets as $item) {
				$data["json_tradeoffer"]["me"]["assets"][] = [
					'appid' => $item['appid'],
					'contextid' => $item['contextid'], //csgo和dota2的值为2
					'amount' => $item['amount'],
					'assetid' => $item['assetid'],
				];
			}
		}

		if (is_array($to_assets) && count($to_assets) > 0) {
			foreach ($to_assets as $item) {
				$data["json_tradeoffer"]["them"]["assets"][] = [
					'appid' => $item['appid'],
					'contextid' => $item['contextid'], //csgo和dota2的值为2
					'amount' => $item['amount'],
					'assetid' => $item['assetid'],
				];
			}
		}

		$partner = '139635891';
		$referer = "https://steamcommunity.com/tradeoffer/new/?partner=" . $partner;// trade url 中的 partner 值

		if ($trade_info["trade_offer_access_token"]) {
			$referer .= '&token=' . $trade_info["trade_offer_access_token"];
		}
       
		$headers = [];
        $headers[] = 'Method: post';
        $headers[] = 'Accept:application/json, text/javascript;q=0.9, */*;q=0.5';
		$headers[] = 'Accept-Encoding:gzip, deflate, br';
		$headers[] = 'Accept-Language: zh-Hans-CN, zh-Hans; q=0.8, en-US; q=0.5, en; q=0.3';
        $headers[] = 'User-Agent:Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36';
		$headers[] = 'Referer:"'. $referer .'"';
		$headers[] = 'Content-Type:application/x-www-form-urlencoded; charset=UTF-8';
        $headers[] = 'Connection:keep-alive';
        $headers[] = 'Timeout: 50000';

		return https_post($url, $data, true, $headers);
	}

	/**
	 * 取消已经发送的交易报价
	 * 每次应该用 bot 的sessionid 去取消交易报价，因为交易报价都是 bot 主动发起
	 * @param  string $tradeofferid
	 * @return string $tradeofferid
	 */
	public static function cancelTransaction($tradeofferid) {
		$sessionid = get_cookie_info('sessionid');
		$url = 'https://steamcommunity.com/tradeoffer/' . $tradeofferid . '/cancel';
		$data = [
			'sessionid' => $sessionid,
		];

		return $tradeofferid = https_post($url, $data, 1); //返回取消报价的 tradeofferid
	}

	//拒绝交易
	//$url = 'https://steamcommunity.com/tradeoffer/1973666829/decline'; post : sessionid

}
