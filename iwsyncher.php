<?php
/*
Plugin Name: InsideWordSyncher
Plugin URI: http://wordpress.org/extend/plugins/insidewordsyncher/
Description: Shares all your blog posts with InsideWord. Note: the plugin may take several minutes after activation to sync all your posts.
Version: 0.6.0
Author: InsideWord
Author URI: http://www.insideword.com
License: GPL
*/
/*  Copyright 2011  InsideWord  (support@insideword.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once('InsideWordApi/InsideWordPressApi.php');
require_once('InsideWordApi/IWSettings.php');
require_once('InsideWordApi/IWUtility.php');

class IWSyncher
{
	//=============================================
	// Hook functions
	//=============================================
	function install()
	{
		IWOptions::create_options();
		$latencyCap = 7;
		// Find the highest latency value to the InsideWord servers using statistical sampling and then use that as the minimum time out value
		$latency = IWUtility::calculate_maximum_latency($latencyCap);
		if($latency === false) {
			IWSyncher::remove();
			wp_die("Failed to contact the InsideWord.com servers after ".$latencyCap." seconds. Our servers might be down. Try again in an hour. If you still see this message then contact us at support@insideword.com.",
				   "Installation Canceled",
				   array ( 'back_link' => true )
				   );
		} else {
			
			IWOptions::set_MinTimeOut($latency);
			
			IWSyncher::set_uri_point_of_contact();
			
			$api = IWUtility::get_IWApi();
			IWSyncher::static_data_request($api);
			IWSyncher::identification_request($api);
			
			IWOptions::set_Status("Starting server identification process.");
			IWUtility::schedule_event(0, 'iwsyncher_identification_attempt_event', array(true, time()));
			IWSyncher::visit_request($api, 5);
		}
	}

	function remove()
	{
		$api = new InsideWordPressApi;
		$api->delete_Cookie();
		IWOptions::delete_options();
		IWUtility::unschedule_event('iwsyncher_identification_attempt_event');
		IWUtility::unschedule_event('iwsyncher_get_account_info_event');
		IWUtility::unschedule_event('iwsyncher_synch_posts_event');
		IWUtility::unschedule_event('iwsyncher_get_InsideWordFooter_event');
		IWUtility::unschedule_event('iwsyncher_resynch_request_event');
		
		remove_action('wp_head', array('IWSyncher','insert_identification'));
		remove_action('the_content', array('IWSyncher', 'content_footer'));
		remove_action('transition_post_status', array('IWSyncher', 'transition_post_status'));
		remove_action('iwsyncher_identification_attempt_event', array('IWSyncher','identification_attempt'));
		remove_action('iwsyncher_get_account_info_event', array('IWSyncher','get_account_info'));
		remove_action('iwsyncher_synch_posts_event', array('IWSyncher','synch_posts'));
		remove_action('iwsyncher_get_InsideWordFooter_event', array('IWSyncher', 'get_InsideWordFooter'));
		remove_action('iwsyncher_resynch_request_event', array('IWSyncher', 'resynch_request'));
		remove_action('admin_menu', array('IWOptions', 'options_menu'));
		remove_action('admin_init', array('IWOptions', 'options_page_init'));
		
		// do the slowest task last in case the user becomes impatient and aborts
		IWUtility::clear_IWIDsAndFooters();
	}

	static function insert_identification()
	{
		$nonceKey = IWOptions::get_IdentificationKey();
		if (!empty($nonceKey))
		{
			IWUtility::log("inserting identification");
			echo '<input type="hidden" id="' . $nonceKey . '" />';
		}
	}
	
	static function identification_attempt($continueSync, $time)
	{
		// there can only be one!
		IWUtility::unschedule_event('iwsyncher_identification_attempt_event');
		
		$retryCount = IWOptions::get_RetryCount();
		$maxRetry = IWOptions::get_MaxRetry();
		if($retryCount > $maxRetry) {
			IWUtility::status("Tried and failed ".$maxRetry." times to identify with the InsideWord server. Please contact support@insideword.com.");
			IWUtility::disable_plugin();
		} else {
			IWUtility::log("beginning identification attempt");
			set_time_limit ( 31 );
			$api = IWUtility::get_IWApi();
			IWOptions::set_RetryCount($retryCount+1);
			$subFolder = IWOptions::get_ContactUriSubFolder();
			$response = $api->domainIdentification(site_url(), $subFolder);
			IWUtility::log("domainIdentification - \n".print_r( $response, true ));
			if( empty($response) || is_wp_error($response) ) {
				$retryDelay = IWOptions::get_RetryDelay();
				IWUtility::status("Failed to contact the InsideWord servers. Reattempting in ".$retryDelay." sec ... ");
				IWUtility::schedule_event($retryDelay, 'iwsyncher_resynch_request_event', array($continueSync, time()));
				IWSyncher::visit_request($api, $retryDelay+5);// add seconds to be safe.
			} else if( $response->StatusCode != 0 ) {
				IWUtility::status("Identification of your server failed: "+$response->StatusMessage);
				IWUtility::disable_plugin();
			} else {
				IWOptions::set_ThisMonthKey( $response->ThisMonth );
				IWOptions::set_NextMonthKey( $response->NextMonth );
				IWOptions::delete_IdentificationKey();
				IWOptions::set_RetryCount(1);
				if($continueSync === true) {
					IWOptions::set_IsSynching(true);
					IWUtility::schedule_event(0, 'iwsyncher_get_account_info_event', array(true, time()));
					IWSyncher::visit_request($api, 5);
				}
				IWUtility::status("Server identification successful.");
			}
			IWUtility::log("ending identification request");
			// set the time back to normal
			set_time_limit ( 30 );
		}
	}
	
	static function get_account_info($continueSync, $time)
	{
		// there can only be one!
		IWUtility::unschedule_event('iwsyncher_get_account_info_event');
		IWUtility::status("Downloading and setting up account data.");
		$retryCount = IWOptions::get_RetryCount();
		$maxRetry = IWOptions::get_MaxRetry();
		if($retryCount > $maxRetry) {
			IWUtility::status("Tried and failed ".$maxRetry." times to download data from the InsideWord server. Please contact support@insideword.com.");
			IWUtility::disable_plugin();
		} else if (IWOptions::has_IssuedKey() == false) {
			$reschedule = IWOptions::get_RescheduleDelay();
			IWUtility::status("Keys are currently unavailable for the data download. Retrying in ".$reschedule." sec ...");	
			IWUtility::schedule_event($reschedule, 'iwsyncher_get_account_info_event', array(false, time()));
		} else {
			try {
				set_time_limit ( 32 );
				IWOptions::set_RetryCount($retryCount+1);
				$api = IWUtility::get_IWApi();
				$loginResult = IWSyncher::login($api);
				$retryDelay = IWOptions::get_RetryDelay();
				
				if ( $loginResult == InsideWordPressApi::login_failed ) {
					IWUtility::status("Failed to login to InsideWord. Retrying in ".$retryDelay." sec ...");
					IWUtility::schedule_event($retryDelay, 'iwsyncher_get_account_info_event', array($continueSync, time()));
				} else if( $loginResult == InsideWordPressApi::login_success ) {
					
					if(IWSyncher::SyncAccountData($api) === false) {
						IWUtility::status("Failed to synch your InsideWord Account. Retrying in ".$retryDelay." sec ...");
						IWUtility::schedule_event($retryDelay, 'iwsyncher_get_account_info_event', array($continueSync, time()));
						IWSyncher::visit_request($api, $retryDelay+5);
					} else {
						// everything passed so we're good. Reschedule this event.
						$reschedule = IWOptions::get_RescheduleDelay();
						IWUtility::schedule_event($reschedule, 'iwsyncher_get_account_info_event', array(false, time()));
						IWOptions::set_RetryCount(1);
						
						if($continueSync) {
							IWUtility::schedule_event(0, 'iwsyncher_synch_posts_event', array(time()));
							IWSyncher::visit_request($api, 5);
						}
					}
				}
				
			} catch(Exception $e) {
				IWUtility::status("Encountered a critical error while downloading from InsideWord: ". $e->getMessage() .". Please contact us at support@insideword.com. Retrying in ".$retryDelay." sec ...");
				IWUtility::schedule_event($retryDelay, 'iwsyncher_get_account_info_event', array($continueSync, time()));
			}
		}
	}
	
	static function synch_posts($time)
	{
		// There can only be one!
		IWUtility::unschedule_event('iwsyncher_synch_posts_event');
	
		$start = IWOptions::get_SynchProgress();
		$end = $start + IWOptions::get_PublishIncrement();
		IWUtility::status("Synching posts ".$start." to ".$end );
		
		$api = IWUtility::get_IWApi();
		$loginResult = IWSyncher::login($api);
		if($loginResult == InsideWordPressApi::login_success) {
			$args = array(
				"offset" => $start,
				"numberposts" => IWOptions::get_PublishIncrement(),
				'order' => 'ASC'
			);
			
			$postList = get_posts($args);
			if(empty($postList)) {
				IWOptions::set_IsSynching(false);
				IWUtility::status("Install complete, all posts synched.");
			} else {
				// give this thing a minute to resolve
				set_time_limit ( 53 );
				foreach($postList as $aPost) {
					IWUtility::publish_post($aPost, $api);
				}
				// set the time back to normal
				set_time_limit ( 30 );
				IWOptions::set_SynchProgress($end);
				IWUtility::schedule_event(0, 'iwsyncher_synch_posts_event', array(time()));
				IWSyncher::visit_request($api, 5);
				IWUtility::log("finished batch");
			}
		}
	}
	
	static function content_footer($content)
	{
		$footer = "";
		if(IWOptions::has_IssuedKey() === true && IWOptions::get_IsSynching() != true) {
			global $post;
			if(IWUtility::has_footer($post->ID) === true)
			{
				$footer = IWUtility::get_footer($post->ID);
			}
			else
			{
				$args = array($post->ID);
				IWUtility::schedule_event(0, 'iwsyncher_get_InsideWordFooter_event', $args);
			}
		}
		return $content . " " . $footer;
	}
	
	static function transition_post_status($newstatus, $oldstatus, $post)
	{
		// abort if we get any of these
		if( IWOptions::has_IssuedKey() == false || IWOptions::get_IsSynching() === true || 
			( ($oldstatus === "new" || $oldstatus === "inherit") && ($newstatus === "auto-draft" || $newstatus === "inherit") )) {
			return;
		}
	
		$api = IWUtility::get_IWApi();
		
		// Ping the server to see if it is up and responding. If it is then proceed with the update.
		if($api->ping() == false) {
			IWUtility::log("Ping: Server is down");
		} else {
			$insideWordId = IWUtility::get_InsideWordId($post->ID);
			IWUtility::log("post status change - " .$oldstatus . " to " . $newstatus . ":\n". print_r( $post, true ));
			
			// if a password was added then delete it
			if(!empty($post->post_password)) {
				if(!empty($insideWordId) && IWSyncher::login($api) == InsideWordPressApi::login_success)
				{
					$api->change_article_state($insideWordId, InsideWordArticle::state_delete());
				}
			} else {
				switch($newstatus)
				{
					case 'publish':
						if(IWSyncher::login($api) == InsideWordPressApi::login_success)
						{
							IWUtility::publish_post($post, $api);
						}
						break;
					
					case 'pending':
					case 'draft':
					case 'future':
					case 'private':
						if(!empty($insideWordId) && IWSyncher::login($api) == InsideWordPressApi::login_success)
						{
							IWSyncher::change_post_state($insideWordId, InsideWordArticle::state_draft(), $api);
						}
						break;
						
					case 'trash':
						if(!empty($insideWordId) && IWSyncher::login($api) == InsideWordPressApi::login_success)
						{
							$api->change_article_state($insideWordId, InsideWordArticle::state_delete());
						}
						break;
						
					// leave these blank for now
					case 'auto-draft':
					case 'inherit':
					case 'new':
					default:
						break;
				}
			}
		}
	}
	
	//=============================================
	// Utility functions
	//=============================================
	
	static function static_data_request($insideWordApi)
	{
		
	}
	
	static function set_uri_point_of_contact()
	{
		// use the smallest post as the point of contact
		$smallestPostId = IWUtility::find_smallest_old_post();
		IWUtility::log("contact post is ". $smallestPostId ." at ". get_permalink($smallestPostId));
		if(empty($smallestPostId)) {
			IWSyncher::remove();
			wp_die("Could not find any posts on your server, aborting installation. Contact support@insideword.com if you have posts and keep getting this error message.",
				   "Installation Error",
				   array (
						'back_link' => true
					));
		} else { 
			$permaLink = get_permalink($smallestPostId);
			$subFolder = IWUtility::get_sub_folder(site_url(), $permaLink);
			IWUtility::log("subFolder = ". $subFolder);
			IWOptions::set_ContactPostId($smallestPostId);
			IWOptions::set_ContactUri($permaLink);
			IWOptions::set_ContactUriSubfolder($subFolder);
		}
	}
	
	static function identification_request($insideWordApi)
	{
		$response = $insideWordApi->domainIdentificationRequest(site_url());
		IWUtility::log("domainIdentificationRequest -\n" . print_r($response, true));
		if( empty($response) ) {
			IWSyncher::remove();
			wp_die("Installation Error: the InsideWord server stopped responding\n",
				   "Installation Error",
				   array (
						'back_link' => true
					));
		} else if (is_wp_error( $response )) {
			IWSyncher::remove();
			wp_die("Installation Error:\n" . $response->get_error_message(),
				   "Installation Error",
				   array (
						'back_link' => true
					));
		} else if($response->StatusCode != 0) {
			IWSyncher::remove();
			wp_die("Installation Error:\n" . $response->StatusMessage,
				   "Installation Error",
				   array (
						'back_link' => true
					));
		} else {
			IWOptions::set_IdentificationKey($response->Content);
		}
	}
	
	static function visit_request($insideWordApi, $sec)
	{
		$url = site_url()."/wp-cron.php";
		IWUtility::log("delayed_visit_request - " . $url . ", ". $sec . "sec");
		$insideWordApi->delayed_visit_request($url, $sec);
		return true;
	}
	
	static function login($insideWordApi)
	{
		//check with this month's key
		$response = $insideWordApi->login(IWOptions::get_ThisMonthKey(), 
										  IWOptions::get_NextMonthKey(),
										  IWOptions::get_LoginSessionTime());
		
		IWUtility::log("login - " . print_r($response, true));
		if(empty($response) || is_wp_error( $response )) {
			$returnValue = InsideWordPressApi::login_failed;
		} else {
			$returnValue = $response->StatusCode;
			switch($returnValue) {
				case InsideWordPressApi::login_failed:
					IWUtility::status("The keys are out of date. Attempting a complete key resynch.");
					IWUtility::disable_plugin();
					IWUtility::schedule_event(0, 'iwsyncher_resynch_request_event', array(false, time()));
					break;
				case InsideWordPressApi::login_new_key:
					IWUtility::log("successfully updated the keys.");
					IWOptions::set_ThisMonthKey( $response->ThisMonth );
					IWOptions::set_NextMonthKey( $response->NextMonth );
					$returnValue = InsideWordPressApi::login_success;
					break;
				case InsideWordPressApi::login_banned:
					IWUtility::status("Your account has been disabled. Contact us at support@insideword.com for more information.");
					IWUtility::disable_plugin();
					break;
			}
		}
		
		return $returnValue;
	}
	
	static function get_InsideWordRank($postId)
	{
		$rank = "";
		if(IWUtility::has_InsideWordId($postId)) {
			$api = IWUtility::get_IWApi();
			$response = $api->article_rank(IWUtility::get_InsideWordId($postId));
			IWUtility::log("get_InsideWordRank -\n" . print_r( $response, true ));
			if( !empty($response) && !is_wp_error($response) && $response->StatusCode === 0 ) {
				$rank = (int)$response->Content;
			}
		}
		return $rank;
	}
	
	static function get_InsideWordFooter($postId)
	{
		set_time_limit ( 14 );
		$rank = IWSyncher::get_InsideWordRank($postId);
		IWUtility::set_footer($postId, $rank);
		// set time back to normal
		set_time_limit ( 30 );
	}
	
	static function change_post_state($insideWordId, $state, $insideWordApi)
	{
		IWUtility::log("change_article_state - ". $insideWordId .", ". $state);
		$insideWordApi->change_article_state($insideWordId, $state);
	}
	
	static function SyncAccountData($insideWordApi)
	{
		// Create the categoryMap
		$args = array('hide_empty' => 0);
		$categoryList = get_categories($args);
		IWUtility::log("wordPress categories:\n".print_r($categoryList, true));
		$categoryMap = array();
		foreach($categoryList as $category) {
			$catId = $category->cat_ID;
			$IWCatId = null;
			if( IWOptions::has_CategoryMap($catId) ) {
				$IWCatId = IWOptions::get_CategoryMap($catId);
			}
			$categoryMap[] = array($category->name, $category->cat_ID, $IWCatId);
		}
		
		// get the admin e-mail
		$admin_email = get_option('admin_email', null);
	
		$response = $insideWordApi->account_sync($admin_email, $categoryMap);
		$returnValue = !empty($response) && !is_wp_error($response) && $response->StatusCode == 0;
		if( $returnValue ) {
			IWUtility::log("account_sync response:\n" . print_r( $response, true ));
			
			// pull out the profile and account link
			IWOptions::set_IWProfile($response->ProfileLink);
			IWOptions::set_IWAccount($response->AccountLink);
			
			// flatten the categories
			$flatarray = array();
			$childrenArray = array();
			$childrenArray = $response->CategoryTree->Children;
			$index = 0;
			while(count($childrenArray) > $index) {
				$temp = $childrenArray[$index];
				$flatarray[$index]->value = $temp->Id;
				$flatarray[$index]->text = $temp->Title;
				if( is_array($temp->Children) && !empty($temp->Children) ) {
					$childrenArray = array_merge( $childrenArray, $temp->Children );
				}
				$index++;
			}
			IWOptions::set_IWCategoryArray($flatarray);
			
			// flip and strip the categories to just a mappings array
			foreach($response->Map as $IWToWordPress) {
				IWOptions::set_CategoryMap($IWToWordPress->MapId, $IWToWordPress->AlternateId);
			}
		}
		
		return $returnValue;
 	}
	
	static function resynch_request($continueSync, $time)
	{
		// there can be only one!
		IWUtility::unschedule_event('iwsyncher_resynch_request_event');
	
		IWUtility::log("beginning resynch request");
		IWUtility::disable_plugin();
		$api = IWUtility::get_IWApi();
		if($api->ping() == false) {
			//Server is down so reschedule this for tomorrow
			IWUtility::schedule_event(60*60*24, 'iwsyncher_resynch_request_event', array($continueSync, time()));
		} else {
			$response = $api->domainIdentificationRequest(site_url());
			IWUtility::log("domainIdentificationRequest -\n" . print_r($response, true));
			if (empty($response) || is_wp_error( $response )) {
				IWUtility::status("Failed to contact the InsideWord servers. Reattempting in 24 hours.");
				IWUtility::schedule_event(60*60*24, 'iwsyncher_resynch_request_event', array($continueSync, time()));
			} else if($response->StatusCode != 0) {
				IWUtility::status("Encountered an issue with the InsideWord servers. Please contact us at support@insideword.com.");
			} else {
				IWOptions::set_IdentificationKey($response->Content);
				IWUtility::schedule_event(0, 'iwsyncher_identification_attempt_event', array($continueSync, time()));
				IWSyncher::visit_request($api, 5);
			}
		}
	}
}

$_insideWordSyncher = new IWSyncher;
register_activation_hook(__FILE__, array($_insideWordSyncher, 'install'));
register_deactivation_hook(__FILE__, array($_insideWordSyncher, 'remove'));
add_action('iwsyncher_get_account_info_event', array('IWSyncher','get_account_info'), 10, 2);
add_action('iwsyncher_synch_posts_event', array('IWSyncher','synch_posts'));
add_action('iwsyncher_identification_attempt_event', array('IWSyncher','identification_attempt'), 10, 2);
add_action('iwsyncher_get_InsideWordFooter_event', array('IWSyncher', 'get_InsideWordFooter'));
add_action('iwsyncher_resynch_request_event', array('IWSyncher', 'resynch_request'), 10, 2);

IWOptions::load_options();

if(is_admin()) {
	add_action('admin_menu', array('IWOptions', 'options_menu'));
	add_action('admin_init', array('IWOptions', 'options_page_init'));
}

if(IWOptions::has_IdentificationKey() === true) {
	add_action('wp_head', array('IWSyncher','insert_identification'));
}

if(IWOptions::has_IssuedKey() === true && IWOptions::get_IsSynching() == false) {
	// don't start the normal hooks until we're done synching.
	add_action('the_content', array('IWSyncher', 'content_footer'));

	add_action('transition_post_status', array('IWSyncher', 'transition_post_status'),  10, 3);
	// due to the wordpress bug below, 'transition_post_status' must have 10, 3
	// http://wordpress.org/support/topic/plugin-yet-another-related-posts-plugin-problem-addingediting-posts-after-upgrading-to-yarpp-321b1
}

?>