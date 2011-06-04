<?php
require_once('InsideWordPressApi.php');
require_once('IWSettings.php');

class IWUtility
{
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
	
	static function status( $message ) {
		IWOptions::set_Status($message);
		IWUtility::log( $message );
	}
	
	static function log( $message ) {
		IWOptions::load_options();
		if(IWOptions::has_Logging() === true && 
			IWOptions::get_Logging() === true) {
			if( WP_DEBUG === true ){
				error_log($message);
			}
			
			try {
				if(IWOptions::has_IWDomain()) {
					$api = IWUtility::get_IWApi();
					$api->log(site_url() ." - ". $message);
				}
			} catch(Exception $ignoredException) {
				// just ignore the exception
			}
		}
	}
	
	static function activate_error_handler()
	{	
		$old_error_handler = set_error_handler(array('IWUtility', 'error_reporting'));
	}
	
	static function deactivate_error_handler()
	{
		restore_error_handler();
	}
	
	static function error_reporting($errno, $errstr, $errfile, $errline)
	{	
		if (!(error_reporting() & $errno)) {
			// This error code is not included in error_reporting
			return;
		}
	
		try {
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
						IWUtility::log("[#" . $errno . "][message " . $errstr . "][line " . $errline ."][file " . $errfile . "]");
					}
					break;
			}
		} catch(Exception $ignoredException) {
		
		}
		
		return false;
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
	
	static function set_InsideWordId($postId, $value)	{ return add_post_meta($postId, "insideWord_ID", $value, true); }
	static function get_InsideWordId($postId)			{ return get_post_meta($postId, "insideWord_ID", true); }
	static function delete_InsideWordId($postId)		{ return delete_post_meta($postId, "insideWord_ID"); }
	static function has_InsideWordId($postId)
	{
		$iwId = get_post_meta($postId, "insideWord_ID", true);
		return !empty($iwId);
	}
	
	static function clear_IWIDsAndFooters()
	{
		$postList = get_posts();
		IWUtility::log("Clearing all posts of IW ids and Footers.");
		foreach($postList as $aPost)
		{
			if(IWUtility::has_InsideWordId($aPost->ID))
			{
				IWUtility::delete_InsideWordId($aPost->ID);
			}
			
			if(IWUtility::has_footer($aPost->ID))
			{
				IWUtility::delete_footer($aPost->ID);
			}
		}
		IWUtility::log("Done Clearing all posts");
	}
	
	static function set_footer($postId, $rank)
	{
		if(IWUtility::has_footer($postId) == false)
		{
			if(is_wp_error( $rank ) || empty($rank))
			{
				$footer = '';
			}
			else
			{
				IWOptions::load_options();
				$temp = strval($rank);
				$suffix = IWUtility::number_suffix($rank);
				$rankString = $temp . $suffix. ' Rank';
				$iwDomain = IWOptions::get_IWDomain();
				$footer = '	<div style="clear:both"></div>
							<div style="height:21px;">
								<img src="' . $iwDomain . '/favicon.ico" alt="iw" style="float:left;margin-top:4px;" /> 
								<a href="' . $iwDomain . '" style="text-decoration: none;color: #004276;font-size:11px;">
										&nbsp;'. $rankString . '
								</a>
							</div>';
			}
			delete_transient("iwFooter".$postId);
			set_transient("iwFooter".$postId, $footer, IWOptions::get_RankCacheTime());
		}
	}
	static function get_footer($postId)
	{
		return get_transient("iwFooter".$postId);
	}
	static function delete_footer($postId)
	{
		delete_transient("iwFooter".$postId);
	}
	
	static function has_footer($postId)
	{
		return !(get_transient("iwFooter".$postId) === false);
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
	
	static function get_IWApi()
	{
		IWOptions::load_options();
		$api = new InsideWordPressApi;
		$api->set_IWHost(IWOptions::get_IWDomain());
		if(IWOptions::has_MinTimeOut()) {
			$api->set_MinTimeOut(IWOptions::get_MinTimeOut());
		} else {
			$api->set_MinTimeOut(5);
		}
		return $api;
	}
	
	static function calculate_maximum_latency($latencyCap)
	{
		IWOptions::load_options();
		$latencyArray = array();
		$api = new InsideWordPressApi;
		$api->set_IWHost(IWOptions::get_IWDomain());
		$api->set_MinTimeOut($latencyCap);
		
		// collect some samples, time permitting
		$elapsedTime = 0;
		for($totalTime = 0; $totalTime < $latencyCap; $totalTime += $elapsedTime) {
			list($usec, $sec) = explode(' ', microtime());
			$start = (float) $sec + (float) $usec;
			$pingResult = $api->ping();
			list($usec, $sec) = explode(' ', microtime());
			$end = (float) $sec + (float) $usec;
			$elapsedTime = $end - $start;
			if($pingResult) {
				$latencyArray[] = $elapsedTime;
			}
		}
		
		if(count($latencyArray) < 3) {
			// this is too unreliable to make an estimate so return a false
			$result = false;
		} else {
			$avg = array_sum($latencyArray) / count($latencyArray);
			$stDv = IWUtility::standard_deviation($latencyArray, true);
			$result = ceil($avg+6*$stDv); //use six sigma guarantee
			IWUtility::log("array - ".print_r($latencyArray, true)."\navg - ".$avg."\nstdv - ".$stDv."\nresult - ".$result);
		}
		
		return $result;
	}
	
	
	static function standard_deviation($aValues, $bSample = false)
	{
		$n = count($aValues);
		$fMean = array_sum($aValues) / $n;
		$fVariance = 0.0;
		foreach ($aValues as $i) {
			$dif = ($i - $fMean);
			$fVariance += $dif*$dif;
		}
		$fVariance /= ( $bSample ? $n - 1 : $n );
		return (float) sqrt($fVariance);
	}
	
	static private $regexInit = false;
	static private $wordPressPMatch;
	static private $wordPressPReplace;
	static private $wordPressCaptionMatch;
	static private $wordPressCaptionReplace;
	
	static private function init_regex_strings()
	{
		if(self::$regexInit === false)
		{
			$validNestedPTagList = "a|img|strong|em|del|span";
			$tag = "<(" . $validNestedPTagList . ")(>|\s[^>]*>)";
			self::$wordPressPMatch = "%^\s*((" . $tag . "|[^<\s]).*)$%m";
			self::$wordPressPReplace = "<p class='wordpress'>$1</p>\n";
			
			$captionInternal = "(?:[^\]]*)";
			$captionAlign = "(?:".$captionInternal."align=\"([^\"]*)\"".$captionInternal.")+";
			$captionWidth = "(?:".$captionInternal."width=\"([^\"]*)\"".$captionInternal.")+";
			$captionCaption = "(?:".$captionInternal."caption=\"([^\"]*)\"".$captionInternal.")+";
			//self::$wordPressCaptionMatch = "\[caption ".$captionInternal.$captionTagList.$captionInternal."\](?P<picture>[^\[])*\[/caption\]";
			//self::$wordPressCaptionReplace = "<div class='wp-caption (?P=align)' style='width: (?P=width)px'>(?P=picture)<p>(?P=caption)</p></div>";
			self::$wordPressCaptionMatch = "%\[caption ".$captionAlign.$captionWidth.$captionCaption."\]([^\[]*)\[/caption\]%";
			self::$wordPressCaptionReplace = "<div class='wp-caption $1' style='width: $2px'>$4<p>$3</p></div>";
			self::$regexInit = true;
		}
	}
	
	static function publish_post($post, $insideWordApi)
	{
		if($post->post_status=="publish" && empty($post->post_password) && !empty($post->post_content)) {
			// Do this check after the above "if" to avoid doing a needless DB fetch.
			$catIdList = wp_get_post_categories( $post->ID );

			if(!empty($catIdList)) {

				$iwArticle = new InsideWordArticle;
				$iwArticle->set_AltId($post->guid);
				$iwArticle->set_Title($post->post_title);
				$iwArticle->set_Blurb($post->post_excerpt);
				
				// wordpress doesn't insert the html paragraph tags in the blogs
				// so we have to do it manually
				$preRegexContent = $post->post_content;
				IWUtility::log("\n================================================\nPreRegex Post Content:\n".print_r( $preRegexContent, true ));
				
				IWUtility::init_regex_strings();
				
				$content = 	preg_replace  ( self::$wordPressCaptionMatch,
											self::$wordPressCaptionReplace,
											$preRegexContent );
				
				$content = 	preg_replace  ( self::$wordPressPMatch,
											self::$wordPressPReplace,
											$content );
		
				$iwArticle->set_Text($content);
				$iwArticle->set_IsPublished('true');
				$iwArticle->set_CreateDate($post->post_date_gmt);
				
				$catId = $catIdList[0];
				$iwArticle->set_AlternateCategoryId($catId);
				if(IWOptions::has_CategoryMap($catId) === true) {
					$iwCatId = IWOptions::get_CategoryMap($catId);
					if($iwCatId != -1) {
						$iwArticle->set_CategoryId($iwCatId);
					}
				}
				
				$response = $insideWordApi->publish_article($iwArticle);
				IWUtility::log("publish_post -\n" . print_r( $response, true ));
				if( !empty($response) && !is_wp_error($response) && $response->StatusCode === 0 )
				{
					IWUtility::set_InsideWordId($post->ID, $response->Content);
				}
			}
		}
	}
	
	/* Leaves the plugin activated but places it in a stable inert state where it does pretty much nothing.
	 */
	static function disable_plugin()
	{
		IWOptions::delete_IdentificationKey();
		IWOptions::delete_ThisMonthKey();
		IWOptions::delete_NextMonthKey();
	}
	
	// Because WordProcess doesn't have a good way of unscheduling events I have to create this silly wrapper
	static function schedule_event($delay, $action_hook, $args)
	{
		$key = "iwEvent".$action_hook;
		$eventArgList = get_transient($key);
		if(empty($eventArtList))
		{
			$eventArgList = array();
		}
		$eventArgList[] = $args;
		delete_transient($key);
		$transientTime = max(86400, $delay+1);
		set_transient($key, $eventArgList, $transientTime);
		wp_schedule_single_event(time()+$delay, $action_hook, $args);
	}
	
	static function unschedule_event($action_hook)
	{
		$key = "iwEvent".$action_hook;
		$eventArgList = get_transient($key);
		if(!empty($eventArtList))
		{
			delete_transient($key);
			foreach($eventArgList as $args)
			{
				wp_clear_scheduled_hook($action_hook, $args);
			}
		}
	}
}
?>