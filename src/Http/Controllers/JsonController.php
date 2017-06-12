<?php 
namespace Ry\Socin\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Facebook\Facebook;
use Illuminate\Support\Facades\Log;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Ry\Socin\Models\Facebookpage;
use Ry\Socin\Models\Facebookuser;
use Ry\Socin\Models\Bot;
use Ry\Socin\Models\FacebooknodeBot;
use Ry\Socin\Models\Facebooknode;
use Ry\Socin\Exceptions\BotFormatException;

class JsonController extends Controller
{		
	public function getBot(Request $request) {
		$ar = $request->all();
		Log::info(print_r($ar, true));
		if($request->has("hub_mode")
				&& $ar["hub_mode"] == "subscribe"
				&& $request->has("hub_verify_token")
				//&& $ar["hub_verify_token"]=="8d0b6bc8-af23-11e6-bb07-08002777848f"
				&& $request->has("hub_challenge"))
		{
			echo $ar["hub_challenge"];
			exit;
		}
		return $ar;
	}
	
	public function postBot(Request $request) {
		$fb = new Facebook(app("gallery")->params["facebook"]);
	
		$ar = $request->all();
		
		Log::info(print_r($ar, true));
		
		if($request->has("object") && $ar["object"]==="page") {
			if($request->has("entry")) {
				foreach ($ar["entry"] as $entry) {
					$pages = Facebookpage::where("fbid", "=", $entry["id"]);
					if($pages->exists()) {
						$page = $pages->first();
						$fb->setDefaultAccessToken($page->access_token);
						foreach($entry["messaging"] as $message) {
							$psid = $message["sender"]["id"];
							
							$bots = $page->botusers()->where("psid", "=", $psid);
							if($bots->exists()) {
								$bot = $bots->first();
								if($bot->currentrequest) {
									if($bot->currentrequest->handler) {
										list($controller, $method) = explode("@", $bot->currentrequest->handler);
										try {
											app($controller)->$method($bot, $message);
											$bot->currentrequest->delete();
											$bot->botrequest_id = null;
											$bot->save();
										
											//next request
											if($bot->requests()->exists()) {
												$botrequest = $bot->requests()->first();
												$arrequest = json_decode($botrequest->payload, true);
												
													if(isset($arrequest["filedata"])) {
														$file = $arrequest["filedata"];
														unset($arrequest["filedata"]);
														$fbrequest = $fb->request('POST', '/me/messages', [
																//"message" => '{"attachment":{"type":"audio", "payload":{}}}',
																"message" => json_encode($arrequest),
																"filedata" => $fb->fileToUpload($file),
																"recipient" => '{"id":"'.$bot->psid.'"}'
														]);
													}
													else {
														$fbrequest = $fb->request('POST', '/me/messages', [
																"message" => $arrequest,
																"recipient" => [
																		"id" => $bot->psid
																]
														]);
													}
													$fb->getClient()->sendRequest($fbrequest);
													if($botrequest->handler!=null) {
														$bot->botrequest_id = $botrequest->id;
													}
													else {
														$botrequest->delete();
														$bot->botrequest_id = null;
													}
													$bot->save();
												
											}
										}
										catch(BotFormatException $e) {
											$original = json_decode($bot->currentrequest->payload, true);
											if(!isset($original["text"]))
												$original["text"] = "Error !!!";
											$original["text"] = "Erreur de format e : " . $original["text"];
											$fbrequest = $fb->request('POST', '/me/messages', [
													"message" => $original,
													"recipient" => [
															"id" => $bot->psid
													]
											]);
											$fb->getClient()->sendRequest($fbrequest);
										}
										catch(\Exception $e) {
											$fbrequest = $fb->request('POST', '/me/messages', [
													"message" => json_decode($bot->currentrequest->payload, true),
													"recipient" => [
															"id" => $bot->psid
													]
											]);
											$fb->getClient()->sendRequest($fbrequest);
										}	
									}
									else {
										$bot->currentrequest->delete();
										$bot->botrequest_id = null;
										$bot->save();
										
										//next request
										if($bot->requests()->exists()) {
											$botrequest = $bot->requests()->first();
											$arrequest = json_decode($botrequest->payload, true);
											try {
												if(isset($arrequest["filedata"])) {
													$file = $arrequest["filedata"];
													unset($arrequest["filedata"]);
													$fbrequest = $fb->request('POST', '/me/messages', [
															//"message" => '{"attachment":{"type":"audio", "payload":{}}}',
															"message" => json_encode($arrequest),
															"filedata" => $fb->fileToUpload($file),
															"recipient" => '{"id":"'.$bot->psid.'"}'
													]);
												}
												else {
													$fbrequest = $fb->request('POST', '/me/messages', [
															"message" => $arrequest,
															"recipient" => [
																	"id" => $bot->psid
															]
													]);
												}
												$fb->getClient()->sendRequest($fbrequest);
												
												if($botrequest->handler!=null) {
													$bot->botrequest_id = $botrequest->id;
												}
												else {
													$botrequest->delete();
													$bot->botrequest_id = null;
												}
												$bot->save();
											}
											catch(\Exception $e) {
												
											}
										}
									}
								}
								else {
									//next request
									if($bot->requests()->exists()) {
										$botrequest = $bot->requests()->first();
										$arrequest = json_decode($botrequest->payload, true);
											if(isset($arrequest["filedata"])) {
												$file = $arrequest["filedata"];
												unset($arrequest["filedata"]);
												$fbrequest = $fb->request('POST', '/me/messages', [
														//"message" => '{"attachment":{"type":"audio", "payload":{}}}',
														"message" => str_replace('"payload":[]', '"payload":{}', json_encode($arrequest)),
														"filedata" => $fb->fileToUpload($file),
														"recipient" => '{"id":"'.$bot->psid.'"}'
												]);
											}
											else {
												Log::info(print_r($arrequest, true));
												$fbrequest = $fb->request('POST', '/me/messages', [
														"message" => $arrequest,
														"recipient" => [
																"id" => $bot->psid
														]
												]);
											}
											$fb->getClient()->sendRequest($fbrequest);
											if($botrequest->handler!=null) {
												$bot->botrequest_id = $botrequest->id;
											}
											else {
												$botrequest->delete();
												$bot->botrequest_id = null;
											}
											$bot->save();
									}
								}
							}
							else {
								$bot = $page->botusers()->create([
									"psid" => $psid
								]);
							}		
											
							if(isset($message["postback"]["referral"]["ref"])) { //tsy voatery
								$fbid = $message["postback"]["referral"]["ref"];
								$nodes = Facebooknode::where("fbid", "=", $fbid);
								if($nodes->exists()) {
									$node = $nodes->first();
									$r = $node->bots()->where("bot_id", "=", $bot->id);
									if(!$r->exists()) {
										$node->bots()->attach($bot);
									}
								}
							}						
							//app("rysocin.bot")->handle($fb, $message);
						}	
					}
				}
			}
		}
		return $ar;
	}
	
	private function subscribe($params) {
		//token : EAAJ04ZAsKP8oBAFCb2hSkFQPdPzp8P8dCOHC4PFj1V3BQ7moExSR2ewrAQs0vc6fWdZBXSEk3QR3qdWrQYCzEOThuY2Xhu3Uw1mZBGlRCPgImVDD6R9Dpt3PYjWK1Qmkg6OKZBPs1ZAYoueeUZCZCW66SiF00rszL1zwfNUYt4VfAZDZD
		return response($params["hub_challenge"], 200);
	}
	
	public function getLinking(Request $request) {
		$bot = Bot::where("psid", "=", $request->get("psid"))->first();
		$bot->account_linking_token = $request->get("account_linking_token");
		$bot->save();
		return redirect($request->get("redirect_uri")."&authorization_code=12345678");
	}
}

?>