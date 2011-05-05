<?php
/*
Plugin Name: InsideWordSyncher
Plugin URI: http://wordpress.org/extend/plugins/insidewordsyncher/
Description: Allows you to share all your blog posts with InsideWord. Note: the plugin may take several minutes after activation to sync all your posts.
Version: 0.4.4.367
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

if(!function_exists('iw_log')){
	function iw_log( $message ) {
		if( WP_DEBUG === true ){
			error_log($message);
		} else if(InsideWordSyncher::get_EnableErrorLogging()) {
			try {
				$iwDomain = InsideWordSyncher::get_IWDomain();
				if(!empty($iwDomain))
				{
					$api = new InsideWordPressApi;
					$api->set_IWHost($iwDomain);
					$domain = get_bloginfo('url');
					$api->log($domain . " - ". $message);
				}
			} catch(Exception $ignoredException) {
				// just ignore the exception
			}
		}
	}
}

class InsideWordSyncher
{
	//=============================================
	// Hook functions
	//=============================================
	function install()
	{
		InsideWordSyncher::set_EnableErrorLogging(false);
		InsideWordSyncher::set_IWDomain("http://www.insideword.com");
		InsideWordSyncher::set_PublishIncrement(2);
	
		if(!InsideWordSyncher::insideWord_server_setup())
		{
			// get error message before cleaning up
			$errorMsg = InsideWordSyncher::get_ErrorMsgList();
			InsideWordSyncher::cleanup_Plugin();
			wp_die("Failed to install plugin encountered following error: " . $errorMsg,
				   "Installation Error",
				   array (
						'back_link' => true
					));
		}
	}

	function remove()
	{
		InsideWordSyncher::cleanup_Plugin();
	}
	
	function options_page()
	{
		if(is_admin())
		{
			if(!current_user_can('manage_options'))
			{
				wp_die( __('You do not have sufficient permissions to access this page.') );
			}
			
			if(array_key_exists('insideWordSyncher_Clear', $_REQUEST) && $_REQUEST['insideWordSyncher_Clear'])
			{
				InsideWordSyncher::delete_ErrorMsgList();
			}
			
			if(InsideWordSyncher::get_IsSynching())
			{
				$status = "synched ". InsideWordSyncher::get_SynchProgress() . " posts so far.";
			}
			else if(InsideWordSyncher::has_IdentificationKey())
			{
				$status = "fetching issued keys from InsideWord.";
			}
			else if(InsideWordSyncher::get_IdentifyAttempt() > InsideWordSyncher::get_MaxIdentifyAttempt())
			{
				$status = "The plugin failed to identify itself with the InsideWord server after ".InsideWordSyncher::get_MaxIdentifyAttempt(). " attempts. Contact us at support@insideword.com or visit our site to resolve the issue.";
			}
			else
			{
				$status = "done synching";
			}
			
			$errorMessage = "";
			$iwErrList = InsideWordSyncher::get_ErrorMsgList();
			if(!empty($iwErrList))
			{
				iw_log("Installation error list: " . print_r( $iwErrList, true ));
				$errorMessage = '<tr valign="top">
									<th><label>Error Messages</label></th>
									<td><ol>';
				if(is_array($iwErrList))
				{
					foreach($iwErrList as $errorString)
					{
						$errorMessage .= '<li>'.$errorString.'</li>';
					}
				}
				else
				{
					$errorMessage .= '<li>'.$iwErrList.'</li>';
				}
				$errorMessage .= '</li><input name="insideWordSyncher_Clear" type="submit" class="button-secondary" value="Clear list" /></td></tr>';
			}
			
			echo '<div class="wrap">
					<div id="icon-options-general" class="icon32">
						<br />
					</div>
					<h2>InsideWordSyncher Settings</h2>
					<form method="post">
						<table class="form-table">
							<tr valign="top">
								<th><label>InsideWord Server</label></th>
								<td><a href="'.InsideWordSyncher::get_IWDomain().'">' . InsideWordSyncher::get_IWDomain() . '</a><td>
							</tr>
							<tr valign="top">
								<th><label>Your Profile</label></th>
								<td><a href="'.InsideWordSyncher::get_IWProfile().'">' . InsideWordSyncher::get_IWProfile() . '</a><td>
							</tr>
							<tr valign="top">
								<th><label>Default Category</label></th>
								<td>'.InsideWordSyncher::get_DefaultCategoryId() . '<td>
							</tr>
							<tr valign="top">
								<th><label>Status</label></th><td>'.$status.'<td>
							</tr>
							'.$errorMessage.'
						</table>
					</form>
				</div>';
		}
	}
	
	static function insert_identification()
	{
		global $post;
		$contactPostId = InsideWordSyncher::get_ContactPostId();
		if ( (empty($contactPostId) && is_home()) || (!is_home() && $post->ID == $contactPostId) )
		{
			$api = new InsideWordPressApi;
			$api->set_IWHost(InsideWordSyncher::get_IWDomain());
			$domain = site_url();
			
			iw_log("inserting identification");
			$nonceKey = InsideWordSyncher::get_IdentificationKey();
			if ($nonceKey)
			{
				echo '<input type="hidden" id="' . $nonceKey . '" />';
				$identifyAttempt = InsideWordSyncher::get_IdentifyAttempt();
				if($identifyAttempt > InsideWordSyncher::get_MaxIdentifyAttempt())
				{
					// abort the identification
					InsideWordSyncher::delete_IdentificationKey();
					iw_log("plugin failed after ". InsideWordSyncher::get_MaxIdentifyAttempt() ." attempts");
					InsideWordSyncher::set_EnableErrorLogging(false);
				}
				else if (!InsideWordSyncher::acquire_SynchLock(40))
				{
					iw_log("insert_identification currently locked");
				}
				else
				{
					// give this about a minute to resolve
					iw_log("beginning identification request");
					set_time_limit ( 40 );
					InsideWordSyncher::set_IdentifyAttempt($identifyAttempt+1);
					$subFolder = InsideWordSyncher::get_ContactUriSubFolder();
					$identificationResponse = $api->domainIdentification($domain, $subFolder);
					iw_log(print_r( $identificationResponse, true ));
					if(is_wp_error($identificationResponse))
					{
						InsideWordSyncher::add_ErrorMsgList("Failed to perform domain identification: ".$identificationResponse->get_error_message());
					}
					else if($identificationResponse->StatusCode != 0)
					{
						//throw some error message
						InsideWordSyncher::add_ErrorMsgList($identificationResponse->StatusMessage);
					}
					else
					{
						InsideWordSyncher::set_IsSynching(true);
						InsideWordSyncher::set_ThisMonthKey( $identificationResponse->ThisMonth );
						InsideWordSyncher::set_NextMonthKey( $identificationResponse->NextMonth );
						InsideWordSyncher::delete_IdentificationKey();
						InsideWordSyncher::get_InsideWordProfile($api);
						InsideWordSyncher::set_IdentifyAttempt(1);
						iw_log("successfully got curent and next month's issued keys");
					}
					InsideWordSyncher::release_SynchLock();
					InsideWordSyncher::visit_request($api, 5);
					iw_log("ending identification request");
					// set the time back to normal
					set_time_limit ( 30 );
				}
			}
		}
	}
	
	static function synch_posts()
	{
		global $post;
		$contactPostId = InsideWordSyncher::get_ContactPostId();
		if ( (empty($contactPostId) && is_home()) || (!is_home() && $post->ID == $contactPostId) )
		{
			iw_log("entered synch_posts");
			if (!InsideWordSyncher::acquire_SynchLock(60))
			{
				iw_log("synch_posts currently locked.");
			}
			else
			{
				$api = new InsideWordPressApi;
				$api->set_IWHost(InsideWordSyncher::get_IWDomain());
				if(InsideWordSyncher::login($api))
				{
					$start = InsideWordSyncher::get_SynchProgress();
					$end = $start + InsideWordSyncher::get_PublishIncrement();
					$args = array(
						"offset" => $start,
						"numberposts" => InsideWordSyncher::get_PublishIncrement(),
						'order' => 'ASC'
					);
					
					$postList = get_posts($args);
					if(empty($postList))
					{
						InsideWordSyncher::set_IsSynching(false);
						iw_log("Synched all posts");
						InsideWordSyncher::set_EnableErrorLogging(false);
					}
					else
					{
						// give this thing a minute to resolve
						set_time_limit ( 50 );
						foreach($postList as $aPost)
						{
							InsideWordSyncher::publish_post($aPost, $api);
						}
						// set the time back to normal
						set_time_limit ( 30 );
						InsideWordSyncher::set_SynchProgress($end);
						InsideWordSyncher::visit_request($api, 5);
						iw_log("finished batch");
					}
				}
				InsideWordSyncher::release_SynchLock();
			}
		}
	}
	
	static function content_footer($content)
	{
		global $post;
		return $content . InsideWordSyncher::get_InsideWordFooter($post->ID); 
	}
	
	static function transition_post_status($newstatus, $oldstatus, $post)
	{
		$api = new InsideWordPressApi;
		$api->set_IWHost(InsideWordSyncher::get_IWDomain());
		$insideWordId = InsideWordSyncher::get_InsideWordId($post->ID);
		iw_log("post status change - " .$oldstatus . " to " . $newstatus . ":\n". print_r( $post, true ));
		switch($newstatus)
		{
			case 'publish':
				if(InsideWordSyncher::login($api))
				{
					iw_log("Trying to publish");
					InsideWordSyncher::publish_post($post, $api);
				}
				break;
			
			case 'pending':
			case 'draft':
			case 'future':
			case 'private':
				if(!empty($insideWordId) && InsideWordSyncher::login($api))
				{
					iw_log("Trying to change state");
					InsideWordSyncher::change_post_state($insideWordId, $api->state_draft(), $api);
				}
				break;
				
			case 'trash':
				if(!empty($insideWordId) && InsideWordSyncher::login($api))
				{
					iw_log("Trying to delete");
					InsideWordSyncher::change_post_state($insideWordId, $api->state_delete(), $api);
				}
				break;
				
			// leave these blank for now
			case 'auto-draft':
				break;
				
			case 'inherit':
				break;
		}
	}
	
	//=============================================
	// Utility functions
	//=============================================
	
	static function set_ContactPostId($value) {	       delete_option("insideWordSyncher_ContactPostId");
											return add_option("insideWordSyncher_ContactPostId", $value, "", "yes"); }
	static function get_ContactPostId()       { return get_option("insideWordSyncher_ContactPostId",""); }
	static function delete_ContactPostId() { return delete_option("insideWordSyncher_ContactPostId"); }
	
	static function set_ContactUri($value) {	       delete_option("insideWordSyncher_ContactUri");
											return add_option("insideWordSyncher_ContactUri", $value, "", "yes"); }
	static function get_ContactUri()       { return get_option("insideWordSyncher_ContactUri",""); }
	static function delete_ContactUri() { return delete_option("insideWordSyncher_ContactUri"); }
	
	static function set_ContactUriSubFolder($value) {	       delete_option("insideWordSyncher_ContactUriSubFolder");
											return add_option("insideWordSyncher_ContactUriSubFolder", $value, "", "yes"); }
	static function get_ContactUriSubFolder()       { return get_option("insideWordSyncher_ContactUriSubFolder",""); }
	static function delete_ContactUriSubFolder() { return delete_option("insideWordSyncher_ContactUriSubFolder"); }
	
	static function set_IWDomain($value) {	       delete_option("insideWordSyncher_IWDomain");
											return add_option("insideWordSyncher_IWDomain", $value, "", "yes"); }
	static function get_IWDomain()       { return get_option("insideWordSyncher_IWDomain",""); }
	static function delete_IWDomain() { return delete_option("insideWordSyncher_IWDomain"); }
	
	static function set_IWProfile($value) {	       delete_option("insideWordSyncher_IWProfile");
											return add_option("insideWordSyncher_IWProfile", $value, "", "yes"); }
	static function get_IWProfile()       { return get_option("insideWordSyncher_IWProfile",""); }
	static function delete_IWProfile() { return delete_option("insideWordSyncher_IWProfile"); }
	
	static function set_IdentificationKey($value) {		   delete_option("insideWordSyncher_IdentificationKey");
													return add_option("insideWordSyncher_IdentificationKey", $value, null, 'yes'); }
	static function get_IdentificationKey()       { return get_option("insideWordSyncher_IdentificationKey"); }
	static function delete_IdentificationKey() { return delete_option("insideWordSyncher_IdentificationKey"); }
	static function has_IdentificationKey()
	{
		$idKey = get_option("insideWordSyncher_IdentificationKey");
		return !empty($idKey);
	}
	
	static function set_ThisMonthKey($value) {		   delete_option("insideWordSyncher_ThisMonthKey");
												return add_option("insideWordSyncher_ThisMonthKey", $value, null, 'no'); }
	static function get_ThisMonthKey()       { return get_option("insideWordSyncher_ThisMonthKey"); }
	static function delete_ThisMonthKey() { return delete_option("insideWordSyncher_ThisMonthKey"); }
	
	static function set_NextMonthKey($value) { 		   delete_option("insideWordSyncher_NextMonthKey");
												return add_option("insideWordSyncher_NextMonthKey", $value, null, 'no'); }
	static function get_NextMonthKey()       { return get_option("insideWordSyncher_NextMonthKey"); }
	static function delete_NextMonthKey() { return delete_option("insideWordSyncher_NextMonthKey"); }
	
	static function has_IssuedKey()
	{
		$currentKey = InsideWordSyncher::get_ThisMonthKey();
		$returnValue = !empty($currentKey);
		if(!$returnValue)
		{
			$nextKey = InsideWordSyncher::get_NextMonthKey();
			$returnValue = !empty($nextKey);
		}
		return $returnValue;
	}
	
	static function add_ErrorMsgList($value)
	{
		$list = get_option("insideWordSyncher_ErrorMsg", "");
		if(!empty($list)) {
			$list .= ",";
		}
		$list .= $value;
		delete_option("insideWordSyncher_ErrorMsg");
		return add_option("insideWordSyncher_ErrorMsg", $list, "", "no");
	}
	static function get_ErrorMsgList()
	{
		$returnValue = get_option("insideWordSyncher_ErrorMsg","");
		if(!empty($returnValue)) {
			preg_split("/,/", $returnValue);
		}
		return $returnValue;
	}
	static function delete_ErrorMsgList()	{ return delete_option("insideWordSyncher_ErrorMsg"); }
	
	static function set_EnableErrorLogging($value) { 		   delete_option("insideWordSyncher_ErrorLogging");
												return add_option("insideWordSyncher_ErrorLogging", $value, null, 'no'); }
	static function get_EnableErrorLogging()       { return get_option("insideWordSyncher_ErrorLogging"); }
	static function delete_EnableErrorLogging() { return delete_option("insideWordSyncher_ErrorLogging"); }
	
	static function set_IdentifyAttempt($value) { 		   delete_option("insideWordSyncher_IdentifyAttempt");
												return add_option("insideWordSyncher_IdentifyAttempt", $value, null, 'no'); }
	static function get_IdentifyAttempt()       { return get_option("insideWordSyncher_IdentifyAttempt"); }
	static function delete_IdentifyAttempt() { return delete_option("insideWordSyncher_IdentifyAttempt"); }
	
	static function set_MaxIdentifyAttempt($value) { 		   delete_option("insideWordSyncher_MaxIdentifyAttempt");
												return add_option("insideWordSyncher_MaxIdentifyAttempt", $value, null, 'no'); }
	static function get_MaxIdentifyAttempt()       { return get_option("insideWordSyncher_MaxIdentifyAttempt"); }
	static function delete_MaxIdentifyAttempt() { return delete_option("insideWordSyncher_MaxIdentifyAttempt"); }
	
	static function set_PublishIncrement($value) {  delete_option("insideWordSyncher_PublishIncrement");
													return add_option("insideWordSyncher_PublishIncrement", $value, "", "no"); }
	static function get_PublishIncrement()       { return get_option("insideWordSyncher_PublishIncrement",""); }
	static function delete_PublishIncrement() { return delete_option("insideWordSyncher_PublishIncrement"); }
	
	static function set_DefaultCategoryId($value)	{  delete_option("insideWordSyncher_DefaultCategoryId");
													return add_option("insideWordSyncher_DefaultCategoryId", $value, "", "no"); }
	static function get_DefaultCategoryId()		{ return get_option("insideWordSyncher_DefaultCategoryId",""); }
	static function delete_DefaultCategoryId()	{ return delete_option("insideWordSyncher_DefaultCategoryId"); }
	
	static function set_SynchProgress($value) { delete_option("insideWordSyncher_SynchProgress");
												return add_option("insideWordSyncher_SynchProgress", $value, "", "no"); }
	static function get_SynchProgress()       { return get_option("insideWordSyncher_SynchProgress",""); }
	static function delete_SynchProgress() { return delete_option("insideWordSyncher_SynchProgress"); }
	
	static function set_IsSynching($value) {	delete_option("insideWordSyncher_IsSynching");
												return add_option("insideWordSyncher_IsSynching", $value, "", "no"); }
	static function get_IsSynching()       { return get_option("insideWordSyncher_IsSynching",""); }
	static function delete_IsSynching() { return delete_option("insideWordSyncher_IsSynching"); }
	
	static function set_InsideWordId($postId, $value)	{ return add_post_meta($postId, "insideWord_ID", $value, true); }
	static function get_InsideWordId($postId)			{ return get_post_meta($postId, "insideWord_ID", true); }
	static function delete_InsideWordId($postId)		{ return delete_post_meta($postId, "insideWord_ID"); }
	static function has_InsideWordId($postId)
	{
		$iwId = get_post_meta($postId, "insideWord_ID", true);
		return !empty($iwId);
	}
	
	static function acquire_SynchLock($timeOut)
	{
		// Unfortunately there are no mutexes in wordpress and static variables do not last past
		// the session so we will have to make do with transients, which are by no means
		// valid mutexes. Hence this function is NOT a real lock.
		$gotLock = false;
		$lock = get_transient("iW");// pick some string, but keep it small to increase speed
		if(empty($lock))
		{
			set_transient("iW", 1, $timeOut);
			$gotLock = true;
		}
		
		return $gotLock;
	}
	
	static function release_SynchLock()
	{
		delete_transient("iW");
	}
	
	static function insideWord_server_setup()
	{
		InsideWordSyncher::set_EnableErrorLogging(false);
		InsideWordSyncher::set_SynchProgress(0);
		InsideWordSyncher::set_IdentifyAttempt(1);
		InsideWordSyncher::set_MaxIdentifyAttempt(3);
		
		// use the smallest post as the point of contact
		$smallestPostId = InsideWordSyncher::find_smallest_old_post();
		iw_log("contact post is ". $smallestPostId ." at ". get_permalink($smallestPostId));
		if(!empty($smallestPostId))
		{
			$permaLink = get_permalink($smallestPostId);
			$subFolder = InsideWordSyncher::get_sub_folder(site_url(), $permaLink);
			iw_log("subFolder = ". $subFolder);
			InsideWordSyncher::set_ContactPostId($smallestPostId);
			InsideWordSyncher::set_ContactUri($permaLink);
			InsideWordSyncher::set_ContactUriSubfolder($subFolder);
		}
		
		$api = new InsideWordPressApi;
		$api->set_IWHost(InsideWordSyncher::get_IWDomain());
		return InsideWordSyncher::static_data_request($api) &&
			   InsideWordSyncher::identification_request($api) &&
			   InsideWordSyncher::visit_request($api, 5);
	}
	
	static function static_data_request($insideWordApi)
	{
		$returnValue = false;
		$response = $insideWordApi->default_category_id();
		if (is_wp_error( $response ))
		{
			InsideWordSyncher::add_ErrorMsgList("Failed to get static data from InsideWord: ".$response->get_error_message());
		}
		else if($response->StatusCode != 0)
		{
			//throw some error message
			InsideWordSyncher::add_ErrorMsgList($response->StatusMessage);
		}
		else
		{
			$returnValue = true;
			InsideWordSyncher::set_DefaultCategoryId($response->Content);
		}
		return $returnValue;
	}
	
	static function identification_request($insideWordApi)
	{
		$returnValue = false;
		$domain = site_url();
		$nonceKeyResponse = $insideWordApi->domainIdentificationRequest($domain);

		if (is_wp_error( $nonceKeyResponse ))
		{
			InsideWordSyncher::add_ErrorMsgList("Failed to fetch identification key from InsideWord: ".$nonceKeyResponse->get_error_message());
		}
		else if($nonceKeyResponse->StatusCode != 0)
		{
			//throw some error message
			InsideWordSyncher::add_ErrorMsgList($nonceKeyResponse->StatusMessage);
		}
		else
		{
			InsideWordSyncher::set_IdentificationKey($nonceKeyResponse->Content);
			iw_log("successfully got identification key");
			$returnValue = true;
		}
		return $returnValue;
	}
	
	static function visit_request($insideWordApi, $sec)
	{
		$returnValue = false;
		$url = InsideWordSyncher::get_ContactUri();
		$response = $insideWordApi->delayedVisitRequest($url, $sec);
		iw_log("delayedVisitRequest response:\n". print_r( $response, true ));
		if( is_wp_error($response) )
		{
			InsideWordSyncher::add_ErrorMsgList("Failed to initiate a delayed visit request with InsideWord: ".$response->get_error_message());
		}
		else if($response->StatusCode != 0)
		{
			//throw some error message
			InsideWordSyncher::add_ErrorMsgList($response->StatusMessage);
		}
		else
		{
			$returnValue = true;
		}
		return $returnValue;
	}
	
	static function cleanup_Plugin()
	{
		$api = new InsideWordPressApi;
		$api->delete_Cookie();
		InsideWordSyncher::delete_IWDomain();
		InsideWordSyncher::delete_IWProfile();
		InsideWordSyncher::delete_IdentificationKey();
		InsideWordSyncher::delete_ThisMonthKey();
		InsideWordSyncher::delete_NextMonthKey();
		InsideWordSyncher::delete_ErrorMsgList();
		InsideWordSyncher::delete_PublishIncrement();
		InsideWordSyncher::delete_DefaultCategoryId();
		InsideWordSyncher::delete_SynchProgress();
		InsideWordSyncher::delete_IsSynching();
		InsideWordSyncher::delete_EnableErrorLogging();
		InsideWordSyncher::delete_IdentifyAttempt();
		InsideWordSyncher::delete_MaxIdentifyAttempt();
		InsideWordSyncher::delete_ContactPostId();
		InsideWordSyncher::delete_ContactUri();
		InsideWordSyncher::delete_ContactUriSubFolder();
		InsideWordSyncher::release_SynchLock();
		InsideWordSyncher::clear_IWIDs();
	}
	
	static function clear_IWIDs()
	{
		$postList = get_posts();
		iw_log("Clearing all posts of IW ids.");
		foreach($postList as $aPost)
		{
			if(InsideWordSyncher::has_InsideWordId($aPost->ID))
			{
				iw_log("Clearing post ". $aPost->ID);
				InsideWordSyncher::delete_InsideWordId($aPost->ID);
				iw_log("Done clearing post ". $aPost->ID);
			}
		}
		iw_log("Done Clearing all posts");
	}
	
	static function login($insideWordApi)
	{
		//check with this month's key
		$loginResult = $insideWordApi->login(InsideWordSyncher::get_ThisMonthKey());
		if(!$loginResult)
		{
			iw_log("Login key for current month failed. Checking to see if we switched to next month's");
			// the login failed, check with next month's key
			$loginResult = $insideWordApi->login(InsideWordSyncher::get_NextMonthKey());
			if($loginResult)
			{
				iw_log("Next month's key worked, so set this key as the current month's and get the new key for next month.");
				// The login worked, but we need to update the keys.
				$response = $insideWordApi->issued_key_request();
				if (is_wp_error( $response ))
				{
					InsideWordSyncher::add_ErrorMsgList("Failed to Login with InsideWord: ".$response->get_error_message());
				}
				else if($response->StatusCode != 0)
				{
					//throw some error message
					InsideWordSyncher::add_ErrorMsgList($response->StatusMessage);
				}
				else
				{
					iw_log("successfully updated the keys.");
					InsideWordSyncher::set_ThisMonthKey( $response->ThisMonth );
					InsideWordSyncher::set_NextMonthKey( $response->NextMonth );
				}
			}
			else
			{
				iw_log("Neither key worked. We are completely out of date. Do a total resynch.");
				// The login failed. We need to perform a full validation
				InsideWordSyncher::insideWord_server_setup();
			}
		}
		
		return $loginResult;
	}
	
	static function publish_post($post, $insideWordApi)
	{
		if($post->post_status=="publish" && empty($post->post_password) && !empty($post->post_content))
		{
			$iwArticle = new InsideWordArticle;
			$iwArticle->set_AltId($post->guid);
			$iwArticle->set_Title($post->post_title);
			$iwArticle->set_Blurb($post->post_excerpt);
			
			// wordpress doesn't insert the html paragraph tags in the blogs
			// so we have to do it manually
			$preRegexContent = $post->post_content;
			iw_log("================================================\nPreRegex Post Content:\n".print_r( $preRegexContent, true ));
			
			$validNestedPTagList = "a|img|strong|em|del|span";
			$tag = '<(' . $validNestedPTagList . ')(>|\s[^>]*>)';
			$wordPressPMatch = "%^\s*((" . $tag . "|[^<\s]).*)$%m";
	
			$pStyle = "class='wordpress'";
			$wordPressPReplace = "<p " . $pStyle . ">$1</p>\n";
			$content = 	preg_replace  ( $wordPressPMatch,
									    $wordPressPReplace,
									    $preRegexContent );
	
			$iwArticle->set_Text($content);
			$iwArticle->set_IsPublished('true');
			$iwArticle->set_CreateDate($post->post_date_gmt);
			$iwArticle->set_CategoryId(InsideWordSyncher::get_DefaultCategoryId());
			
			$response = $insideWordApi->publish_article($iwArticle);
			iw_log("publish_post response:\n" . print_r( $response, true ));
			if( is_wp_error($response) )
			{
				InsideWordSyncher::add_ErrorMsgList("Failed to publish post ".$post->ID.": ".$response->get_error_message());
			}
			else if($response->StatusCode != 0)
			{
				//throw some error message
				InsideWordSyncher::add_ErrorMsgList($response->StatusMessage);
			}
			else
			{
				InsideWordSyncher::set_InsideWordId($post->ID, $response->Content);
			}
		}
	}
	
	static function get_InsideWordRank($postId)
	{
		$key = $postId . "iwrKey";//keep it small to increase speed
		$rank = get_transient($key);
		if(empty($rank))
		{
			$api = new InsideWordPressApi;
			$api->set_IWHost(InsideWordSyncher::get_IWDomain());
			$response = $api->article_rank(InsideWordSyncher::get_InsideWordId($postId));
			iw_log("get_InsideWordRank response:\n" . print_r( $response, true ));
			if( is_wp_error($response) )
			{
				InsideWordSyncher::add_ErrorMsgList("Failed to fetch rank from InsideWord: ".$response->get_error_message());
			}
			else if($response->StatusCode != 0)
			{
				//throw some error message
				InsideWordSyncher::add_ErrorMsgList($response->StatusMessage);
			}
			else
			{
				$rank = (int)$response->Content;
			}
			set_transient($key, $rank, 3600);
		}
		return $rank;
	}
	
	static function get_InsideWordFooter($postId)
	{
		$key = $postId . "iwFooter";//keep it small to increase speed
		$footer = get_transient($key);
		if(empty($footer))
		{
			$rank = InsideWordSyncher::get_InsideWordRank($postId);
			if(is_wp_error( $rank ) || empty($rank))
			{
				$footer = '';
			}
			else
			{
				$temp = strval($rank);
				$suffix = InsideWordSyncher::number_suffix($rank);
				$rankString = $temp . $suffix. ' Rank';
				$iwDomain = InsideWordSyncher::get_IWDomain();
				$footer = '	<div style="clear:both"></div>
							<div style="height:21px;">
								<img src="' . $iwDomain . '/favicon.ico" alt="iw" style="float:left;margin-top:4px;" /> 
								<a href="' . $iwDomain . '" style="text-decoration: none;color: #004276;font-size:11px;">
										&nbsp;'. $rankString . '
								</a>
							</div>';
			}
			set_transient($key, $footer, 3600);
		}
		return $footer;
	}
	
	static function change_post_state($insideWordId, $state, $insideWordApi)
	{
		$returnValue = false;
		$response = $insideWordApi->change_article_state($insideWordId, $state);
		iw_log("change_post_state response:\n" . print_r( $response, true ));
		if( is_wp_error($response) )
		{
			InsideWordSyncher::add_ErrorMsgList("Failed to change post state of post with InsideWord Id ".$insideWordId.": ".$response->get_error_message());
		}
		else if($response->StatusCode != 0)
		{
			//throw some error message
			InsideWordSyncher::add_ErrorMsgList($response->StatusMessage);
		}
		else
		{
			$returnValue = true;
		}
		return $returnValue;
	}
	
	static function number_suffix($number)
	{
		$returnValue = "th";
		switch($number%10)
		{
			case 1:
				if($number != 11)
				{
					$returnValue = "st";
				}
			break;
			case 2:
				if($number != 12)
				{
					$returnValue = "nd";
				}
			break;
			case 3:
				if($number != 13)
				{
					$returnValue = "rd";
				}
			break;
		}
		return $returnValue;
	}
	
	static function get_InsideWordProfile($insideWordApi)
	{
		if(InsideWordSyncher::login($insideWordApi))
		{
			$response = $insideWordApi->profile_link();
			iw_log("profile_link response:\n" . print_r( $response, true ));
			if( is_wp_error($response) )
			{
				InsideWordSyncher::add_ErrorMsgList("Failed to fetch profile link with InsideWord: ".$response->get_error_message());
			}
			else if($response->StatusCode != 0)
			{
				//throw some error message
				InsideWordSyncher::add_ErrorMsgList($response->StatusMessage);
			}
			else
			{
				InsideWordSyncher::set_IWProfile($response->Content);
			}
		}
	}
	
	static function error_reporting($errno, $errstr, $errfile, $errline)
	{	
		if (!(error_reporting() & $errno)) {
			// This error code is not included in error_reporting
			return;
		}
	
		try {
			$api = new InsideWordPressApi;
			$api->set_IWHost(InsideWordSyncher::get_IWDomain());
			$domain = site_url();
			switch($errno) {
				case E_NOTICE:
				case E_STRICT:
				case E_DEPRECATED:
					// do nothing
					break;
				default:
					// ignore errors that are not for us
					$matchResult = preg_match("iwsyncher|InsideWordApi",$errfile);
					if($matchResult != false && $matchResult > 0) {
						$api->log($domain . " - [#" . $errno . "][message " . $errstr . "][line " . $errline ."][file " . $errfile . "]");
					}
					break;
			}
		} catch(Exception $ignoredException) {
		
		}
		
		return false;
	}
	
	static function find_smallest_old_post()
	{
		$smallestLength = -1;
		$smallestId = "";
		$args = array(
			"offset" => 0,
			"numberposts" => 10,
			'order' => 'ASC'
		);
				
		$postList = get_posts($args);
		if(!empty($postList) && is_array($postList))
		{
			foreach($postList as $aPost)
			{
				$contentLength = strlen($aPost->post_content);
				if($smallestLength == -1 || $smallestLength > $contentLength)
				{
					$smallestLength = $contentLength;
					$smallestId = $aPost->ID;
				}
			}
		}
		
		return $smallestId;
	}
	
	static function get_sub_folder($root, $fullpath)
	{
		$subFolder = split($root, $fullpath);
		$subFolder = $subFolder[1];
		if(strpos($subFolder, "/") === 0)
		{
			$subFolder = substr($subFolder, 1);
		}
		return $subFolder;
	}
}

$_insideWordSyncher = new InsideWordSyncher;
register_activation_hook(__FILE__, array($_insideWordSyncher, 'install'));
register_deactivation_hook(__FILE__, array($_insideWordSyncher, 'remove'));

if(InsideWordSyncher::get_EnableErrorLogging())
{
	$old_error_handler = set_error_handler(array('InsideWordSyncher', 'error_reporting'));
}
else
{
	iw_log("deactivating the error handler");
	restore_error_handler();
}

add_action('admin_menu', 'options_menu');
function options_menu()
{
	add_options_page('InsideWordSyncher',
					 'InsideWordSyncher',
					 'manage_options',
					 'InsideWordSyncher',
					 array('InsideWordSyncher', 'options_page'));
}

if(InsideWordSyncher::has_IdentificationKey())
{
	add_action('wp_head', array('InsideWordSyncher','insert_identification'));
}

if(InsideWordSyncher::get_IsSynching())
{
	add_action('shutdown', array('InsideWordSyncher','synch_posts'));
}

if(InsideWordSyncher::has_IssuedKey() &&
   !InsideWordSyncher::get_IsSynching())
{
	// don't start the normal hooks until we're done synching.
	add_action('the_content', array('InsideWordSyncher', 'content_footer'));
	
	add_action('transition_post_status', array('InsideWordSyncher', 'transition_post_status'),  10, 3);
	// due to the wordpress bug below, 'transition_post_status' must have 10, 3
	// http://wordpress.org/support/topic/plugin-yet-another-related-posts-plugin-problem-addingediting-posts-after-upgrading-to-yarpp-321b1
}
?>