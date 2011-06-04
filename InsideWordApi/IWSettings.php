<?php
class IWOptions
{
	static private $_options_page = "insideword_options_page";
	static private $_options_group = "insideword_options_group";
	static private $_options_info_section = "insideword_info_options_section";
	static private $_options_category_section = "insideword_options_category_section";
	static private $_options_performance_section = "insideword_options_performance_section";
	static private $_options_debug_section = "insideword_options_debug_section";
	static private $_options_key = "insideword_options";
	static private $_options;
	
	static function create_options() {
		if(!IWOptions::has_SaveSettings() || IWOPtions::get_SaveSettings() == false)
		{
			self::$_options = array();
			delete_option(self::$_options_key);
			add_option(self::$_options_key, self::$_options, "", "yes");
		}
		IWOptions::default_options();
	}
	
	static function load_options() {
		self::$_options = get_option(self::$_options_key, array());
	}
	
	static function update_options() {
		update_option(self::$_options_key, self::$_options);
	}
	
	static function delete_options() {
		unregister_setting(self::$_options_group, self::$_options_key, array('IWOptions', 'options_page_parse'));
		if(!IWOptions::has_SaveSettings() || IWOPtions::get_SaveSettings() == false)
		{
			self::$_options = null;
			delete_option(self::$_options_key);
		}
	}
	
	static function default_options() {
		IWOptions::set_SynchProgress(0);
		IWOptions::set_RetryCount(1);
		if(!IWOptions::has_SaveSettings() || IWOPtions::get_SaveSettings() == false)
		{
			IWOptions::set_Logging(false);
			IWOptions::set_ErrorHandler(false);
			IWOptions::set_SaveSettings(false);
			IWOptions::set_PublishIncrement(2);
			IWOptions::set_MaxRetry(12);
			IWOptions::set_RankCacheTime(3600);
			IWOptions::set_RetryDelay(3600);
			IWOptions::set_LoginSessionTime(900);// RetryDelay should be larger than LoginSessionTime
			IWOptions::set_RescheduleDelay(86400);
			IWOptions::set_IWDomain("http://www.insideword.com");
		}
	}
	
	static private $_contactPostIdKey = "contact_post_id";
	static function set_ContactPostId($value)		{ self::$_options[self::$_contactPostIdKey] = $value; IWOptions::update_options(); }
	static function get_ContactPostId()				{ return self::$_options[self::$_contactPostIdKey]; }
	static function delete_ContactPostId()			{ unset(self::$_options[self::$_contactPostIdKey]); }
	static function has_ContactPostId()		 		{ return array_key_exists(self::$_contactPostIdKey, self::$_options); }
	
	static private $_contactUriKey = "contact_uri";
	static function set_ContactUri($value)			{ self::$_options[self::$_contactUriKey] = $value; IWOptions::update_options(); }
	static function get_ContactUri()				{ return self::$_options[self::$_contactUriKey]; }
	static function delete_ContactUri()				{ unset(self::$_options[self::$_contactUriKey]); }
	static function has_ContactUri()		 		{ return array_key_exists(self::$_contactUriKey, self::$_options); }
	
	static private $_contactUriSubFolderKey = "contact_uri_sub_folder";
	static function set_ContactUriSubFolder($value)	{ self::$_options[self::$_contactUriSubFolderKey] = $value; IWOptions::update_options(); }
	static function get_ContactUriSubFolder()		{ return self::$_options[self::$_contactUriSubFolderKey]; }
	static function delete_ContactUriSubFolder()	{ unset(self::$_options[self::$_contactUriSubFolderKey]); }
	static function has_ContactUriSubFolder()		{ return array_key_exists(self::$_contactUriSubFolderKey, self::$_options); }
	
	static private $_IWDomainKey = "iw_domain";
	static function set_IWDomain($value)			{ self::$_options[self::$_IWDomainKey] = $value; IWOptions::update_options(); }
	static function get_IWDomain()					{ return self::$_options[self::$_IWDomainKey]; }
	static function delete_IWDomain()				{ unset(self::$_options[self::$_IWDomainKey]); }
	static function has_IWDomain()			 		{ return array_key_exists(self::$_IWDomainKey, self::$_options); }
	
	static private $_IWProfileKey = "iw_profile";
	static function set_IWProfile($value)			{ self::$_options[self::$_IWProfileKey] = $value; IWOptions::update_options(); }
	static function get_IWProfile()					{ return self::$_options[self::$_IWProfileKey]; }
	static function delete_IWProfile()				{ unset(self::$_options[self::$_IWProfileKey]); }
	static function has_IWProfile()			 		{ return array_key_exists(self::$_IWProfileKey, self::$_options); }
	
	static private $_IWAccountKey = "iw_account";
	static function set_IWAccount($value)			{ self::$_options[self::$_IWAccountKey] = $value; IWOptions::update_options(); }
	static function get_IWAccount()					{ return self::$_options[self::$_IWAccountKey]; }
	static function delete_IWAccount()				{ unset(self::$_options[self::$_IWAccountKey]); }
	static function has_IWAccount()			 		{ return array_key_exists(self::$_IWAccountKey, self::$_options); }
	
	static private $_identificationKeyKey = "identification_key";
	static function set_IdentificationKey($value)	{ self::$_options[self::$_identificationKeyKey] = $value; IWOptions::update_options(); }
	static function get_IdentificationKey()			{ return self::$_options[self::$_identificationKeyKey]; }
	static function delete_IdentificationKey()		{ unset(self::$_options[self::$_identificationKeyKey]); }
	static function has_IdentificationKey() 		{ return array_key_exists(self::$_identificationKeyKey, self::$_options); }
	
	static private $_thisMonthKeyKey = "this_month_key";
	static function set_ThisMonthKey($value)		{ self::$_options[self::$_thisMonthKeyKey] = $value; IWOptions::update_options(); }
	static function get_ThisMonthKey()				{ return self::$_options[self::$_thisMonthKeyKey]; }
	static function delete_ThisMonthKey()			{ unset(self::$_options[self::$_thisMonthKeyKey]); }
	static function has_ThisMonthKey()		 		{ return array_key_exists(self::$_thisMonthKeyKey, self::$_options); }
	
	static private $_nextMonthKeyKey = "next_month_key";
	static function set_NextMonthKey($value)		{ self::$_options[self::$_nextMonthKeyKey] = $value; IWOptions::update_options(); }
	static function get_NextMonthKey()				{ return self::$_options[self::$_nextMonthKeyKey]; }
	static function delete_NextMonthKey()			{ unset(self::$_options[self::$_nextMonthKeyKey]); }
	static function has_NextMonthKey()		 		{ return array_key_exists(self::$_nextMonthKeyKey, self::$_options); }
	
	static function has_IssuedKey() 				{ return IWOptions::has_ThisMonthKey() || IWOptions::has_NextMonthKey(); }
	
	static private $_loggingKey = "logging";
	static function set_Logging($value)				{ self::$_options[self::$_loggingKey] = $value; IWOptions::update_options(); }
	static function get_Logging()					{ return self::$_options[self::$_loggingKey]; }
	static function delete_Logging()				{ unset(self::$_options[self::$_loggingKey]); }
	static function has_Logging()		 			{ return array_key_exists(self::$_loggingKey, self::$_options); }
	
	static private $_errorHandlerKey = "error_handler";
	static function set_ErrorHandler($value)		{ self::$_options[self::$_errorHandlerKey] = $value; IWOptions::update_options(); }
	static function get_ErrorHandler()				{ return self::$_options[self::$_errorHandlerKey]; }
	static function delete_ErrorHandler()			{ unset(self::$_options[self::$_errorHandlerKey]); }
	static function has_ErrorHandler()		 		{ return array_key_exists(self::$_errorHandlerKey, self::$_options); }
	
	static private $_retryCountKey = "identify_attempt";
	static function set_RetryCount($value)			{ self::$_options[self::$_retryCountKey] = $value; IWOptions::update_options(); }
	static function get_RetryCount()				{ return self::$_options[self::$_retryCountKey]; }
	static function delete_RetryCount()				{ unset(self::$_options[self::$_retryCountKey]); }
	static function has_RetryCount()		 		{ return array_key_exists(self::$_retryCountKey, self::$_options); }
	
	static private $_maxRetryKey = "max_identify_attempt";
	static function set_MaxRetry($value)			{ self::$_options[self::$_maxRetryKey] = $value; IWOptions::update_options(); }
	static function get_MaxRetry()					{ return self::$_options[self::$_maxRetryKey]; }
	static function delete_MaxRetry()				{ unset(self::$_options[self::$_maxRetryKey]); }
	static function has_MaxRetry()				 	{ return array_key_exists(self::$_maxRetryKey, self::$_options); }
	
	static private $_publishIncrementKey = "publish_increment";
	static function set_PublishIncrement($value)	{ self::$_options[self::$_publishIncrementKey] = $value; IWOptions::update_options(); }
	static function get_PublishIncrement()			{ return self::$_options[self::$_publishIncrementKey]; }
	static function delete_PublishIncrement()		{ unset(self::$_options[self::$_publishIncrementKey]); }
	static function has_PublishIncrement()		 		{ return array_key_exists(self::$_publishIncrementKey, self::$_options); }
	
	static private $_IWCategoryArrayKey = "iw_category_array";
	static function set_IWCategoryArray($value)		{ self::$_options[self::$_IWCategoryArrayKey] = $value; IWOptions::update_options(); }
	static function get_IWCategoryArray()			{ return self::$_options[self::$_IWCategoryArrayKey]; }
	static function delete_IWCategoryArray()		{ unset(self::$_options[self::$_IWCategoryArrayKey]); }
	static function has_IWCategoryArray()		 		{ return array_key_exists(self::$_IWCategoryArrayKey, self::$_options); }
	
	static private $_categoryMapKey = "category_map";
	static function set_CategoryMap($wpId, $iwId)	{ self::$_options[self::$_categoryMapKey.$wpId] = $iwId; IWOptions::update_options(); }
	static function get_CategoryMap($wpId)			{ return self::$_options[self::$_categoryMapKey.$wpId]; }
	static function delete_CategoryMap($wpId)		{ unset(self::$_options[self::$_categoryMapKey.$wpId]); }
	static function has_CategoryMap($wpId)		 	{ return array_key_exists(self::$_categoryMapKey.$wpId, self::$_options); }
	static function key_CategoryMap($wpId)		 	{ return self::$_categoryMapKey.$wpId; }
	
	static private $_synchProgressKey = "synch_progress";
	static function set_SynchProgress($value)	{ self::$_options[self::$_synchProgressKey] = $value; IWOptions::update_options(); }
	static function get_SynchProgress()			{ return self::$_options[self::$_synchProgressKey]; }
	static function delete_SynchProgress()		{ unset(self::$_options[self::$_synchProgressKey]); }
	static function has_SynchProgress()	 		{ return array_key_exists(self::$_synchProgressKey, self::$_options); }
	
	static private $_isSynchKey = "is_synching";
	static function set_IsSynching($value)		{ self::$_options[self::$_isSynchKey] = $value; IWOptions::update_options(); }
	static function get_IsSynching()			{ return self::$_options[self::$_isSynchKey]; }
	static function delete_IsSynching()			{ unset(self::$_options[self::$_isSynchKey]); }
	static function has_IsSynching()		 	{ return array_key_exists(self::$_isSynchKey, self::$_options); }
	
	static private $_minTimeOutKey = "min_timeout";
	static function set_MinTimeOut($value)		{ self::$_options[self::$_minTimeOutKey] = $value; IWOptions::update_options(); }
	static function get_MinTimeOut()			{ return self::$_options[self::$_minTimeOutKey]; }
	static function delete_MinTimeOut()			{ unset(self::$_options[self::$_minTimeOutKey]); }
	static function has_MinTimeOut()	 		{ return array_key_exists(self::$_minTimeOutKey, self::$_options); }
	
	static private $_rankCacheTimeKey = "rank_cache_time";
	static function set_RankCacheTime($value)	{ self::$_options[self::$_rankCacheTimeKey] = $value; IWOptions::update_options(); }
	static function get_RankCacheTime()			{ return self::$_options[self::$_rankCacheTimeKey]; }
	static function delete_RankCacheTime()		{ unset(self::$_options[self::$_rankCacheTimeKey]); }
	static function has_RankCacheTime()		 	{ return array_key_exists(self::$_rankCacheTimeKey, self::$_options); }
	
	static private $_statusKey = "status";
	static function set_Status($value)			{ self::$_options[self::$_statusKey] = $value; IWOptions::update_options(); }
	static function get_Status()				{ return self::$_options[self::$_statusKey]; }
	static function delete_Status()				{ unset(self::$_options[self::$_statusKey]); }
	static function has_Status()			 	{ return array_key_exists(self::$_statusKey, self::$_options); }
	
	static private $_retryDelayKey = "retry_delay";
	static function set_RetryDelay($value)			{ self::$_options[self::$_retryDelayKey] = $value; IWOptions::update_options(); }
	static function get_RetryDelay()				{ return self::$_options[self::$_retryDelayKey]; }
	static function delete_RetryDelay()				{ unset(self::$_options[self::$_retryDelayKey]); }
	static function has_RetryDelay()			 	{ return array_key_exists(self::$_retryDelayKey, self::$_options); }
	
	static private $_loginSessionTimeKey = "login_session_time";
	static function set_LoginSessionTime($value)	{ self::$_options[self::$_loginSessionTimeKey] = $value; IWOptions::update_options(); }
	static function get_LoginSessionTime()			{ return self::$_options[self::$_loginSessionTimeKey]; }
	static function delete_LoginSessionTime()		{ unset(self::$_options[self::$_loginSessionTimeKey]); }
	static function has_LoginSessionTime()			{ return array_key_exists(self::$_loginSessionTimeKey, self::$_options); }
	
	static private $_rescheduleDelayKey = "reschedule_delay";
	static function set_RescheduleDelay($value)		{ self::$_options[self::$_rescheduleDelayKey] = $value; IWOptions::update_options(); }
	static function get_RescheduleDelay()			{ return self::$_options[self::$_rescheduleDelayKey]; }
	static function delete_RescheduleDelay()		{ unset(self::$_options[self::$_rescheduleDelayKey]); }
	static function has_RescheduleDelay()			{ return array_key_exists(self::$_rescheduleDelayKey, self::$_options); }
	
	static private $_saveSettingsKey = "save_settings";
	static function set_SaveSettings($value)	{ self::$_options[self::$_saveSettingsKey] = $value; IWOptions::update_options(); }
	static function get_SaveSettings()			{ return self::$_options[self::$_saveSettingsKey]; }
	static function delete_SaveSettings()		{ unset(self::$_options[self::$_saveSettingsKey]); }
	static function has_SaveSettings()			{ return array_key_exists(self::$_saveSettingsKey, self::$_options); }
	
	static function options_menu()
	{
		add_options_page('InsideWordSyncher',
						 'InsideWordSyncher',
						 'manage_options',
						 self::$_options_page,
						 array('IWOptions', 'options_page'));
	}
	
	static function options_page_init()
	{
		IWOptions::load_options();
		register_setting( self::$_options_group, self::$_options_key, array('IWOptions', 'options_page_parse') );
		add_settings_section(self::$_options_info_section, 'Info', array('IWOptions', 'options_info_section_text'), self::$_options_page);
		
		if(IWOptions::has_IWAccount() && IWOptions::has_NextMonthKey()) {
			add_settings_field(	self::$_IWAccountKey,
								'Login to Account', 
								array('IWOptions', 'settings_anchor'), 
								self::$_options_page, 
								self::$_options_info_section,
								array(IWOptions::get_IWAccount()."/".IWOptions::get_NextMonthKey(), "Click to Login"));
		}
		
		if(IWOptions::has_IWProfile()) {
			add_settings_field( self::$_IWProfileKey,
								'InsideWord Profile', 
								array('IWOptions', 'settings_anchor'), 
								self::$_options_page, 
								self::$_options_info_section,
								array(IWOptions::get_IWProfile(), IWOptions::get_IWProfile()));
		}
		
		if(IWOptions::has_IWCategoryArray()) {
			add_settings_section(self::$_options_category_section, 'Your categories to InsideWord categories', array('IWOptions', 'options_category_section_text'), self::$_options_page);
			$IWCategoryList = IWOptions::get_IWCategoryArray();
			$args = array('hide_empty' => 0);
			$categoryList = get_categories($args);
			foreach($categoryList as $category) {
				$catId = $category->cat_ID;
				$IWId = null;
				$tagId = IWOptions::key_CategoryMap($catId);
				if( IWOptions::has_CategoryMap($catId) ) {
					$IWId = IWOptions::get_CategoryMap($catId);
				}
				
				add_settings_field($tagId,
								'From '.$category->name.' to ', 
								array('IWOptions', 'settings_dropdown'), 
								self::$_options_page, 
								self::$_options_category_section,
								array( $tagId, $IWId,$IWCategoryList, 'auto' ));
			}
		}
		
		add_settings_section(self::$_options_performance_section, 'Performance', array('IWOptions', 'options_performance_section_text'), self::$_options_page);
		
		if(IWOptions::has_MinTimeOut()) {
			add_settings_field(self::$_minTimeOutKey,
								'Minimum time out (sec)', 
								array('IWOptions', 'settings_string'), 
								self::$_options_page, 
								self::$_options_performance_section,
								array( self::$_minTimeOutKey, IWOptions::get_MinTimeOut(),
										"Minimum acceptable latency between your server and InsideWord. Increase this if you have issues connecting to InsideWord."));
		}
		
		if(IWOptions::has_RankCacheTime()) {
			add_settings_field(self::$_rankCacheTimeKey,
								'Rank cache time (sec)', 
								array('IWOptions', 'settings_string'), 
								self::$_options_page, 
								self::$_options_performance_section,
								array( self::$_rankCacheTimeKey, IWOptions::get_RankCacheTime(),
										"How long to cache your article's InsideWord rankings before fetching them again from InsideWord. Increase this to reduce server load."));
		}
		
		if(IWOptions::has_PublishIncrement()) {
			add_settings_field(self::$_publishIncrementKey,
								'Post batch rate', 
								array('IWOptions', 'settings_string'), 
								self::$_options_page, 
								self::$_options_performance_section,
								array( self::$_publishIncrementKey, IWOptions::get_PublishIncrement(),
										"How many articles should be synched up at a time. This is only used when the plugin is first activated."));
		}
		
		if(IWOptions::has_RescheduleDelay()) {
			add_settings_field(self::$_rescheduleDelayKey,
								'Scheduled download delay (sec)', 
								array('IWOptions', 'settings_string'), 
								self::$_options_page, 
								self::$_options_performance_section,
								array( self::$_rescheduleDelayKey, IWOptions::get_RescheduleDelay(), 
										"How frequently your server should fetch data from InsideWord. Increase this to reduce server load." ));
		}
		
		if(IWOptions::has_LoginSessionTime()) {
			add_settings_field(self::$_loginSessionTimeKey,
								'Login session time (sec)', 
								array('IWOptions', 'settings_string'), 
								self::$_options_page, 
								self::$_options_performance_section,
								array( self::$_loginSessionTimeKey, IWOptions::get_LoginSessionTime(),
										"How long to store the login cookie. Increasing this will improve performance but can cause issues."));
		}
		
		if(IWOptions::has_MaxRetry()) {
			add_settings_field(self::$_maxRetryKey,
								'Max retry attempts', 
								array('IWOptions', 'settings_string'), 
								self::$_options_page, 
								self::$_options_performance_section,
								array( self::$_maxRetryKey, IWOptions::get_MaxRetry(),
										"Number of tries to connect to InsideWord before aborting. Increase this if you have an unstable connection."));
		}
		
		if(IWOptions::has_RetryDelay()) {
			add_settings_field(self::$_retryDelayKey,
								'Retry delay (sec)', 
								array('IWOptions', 'settings_string'), 
								self::$_options_page, 
								self::$_options_performance_section,
								array( self::$_retryDelayKey, IWOptions::get_RetryDelay(),
										"Delay between retries."));
		}
		
		
		add_settings_section(self::$_options_debug_section, 'Debug', array('IWOptions', 'options_debug_section_text'), self::$_options_page);
		
		if(IWOptions::has_Status()) {
			add_settings_field(	self::$_statusKey,
								'Plugin status',
								array('IWOptions', 'settings_label'),
								self::$_options_page,
								self::$_options_debug_section,
								array(IWOptions::get_Status()));
		}
		
		add_settings_field(self::$_loggingKey,
							'Enable Logging', 
							array('IWOptions', 'settings_checkbox'), 
							self::$_options_page, 
							self::$_options_debug_section,
							array(self::$_loggingKey, (IWOptions::has_Logging())? IWOptions::get_Logging() : false));
		
		add_settings_field(self::$_errorHandlerKey,
							'Enable Error Handler', 
							array('IWOptions', 'settings_checkbox'), 
							self::$_options_page, 
							self::$_options_debug_section,
							array(self::$_errorHandlerKey, (IWOptions::has_ErrorHandler())? IWOptions::get_ErrorHandler() : false));
							
		add_settings_field(self::$_saveSettingsKey,
							'Save Settings', 
							array('IWOptions', 'settings_checkbox'), 
							self::$_options_page, 
							self::$_options_debug_section,
							array(self::$_saveSettingsKey, IWOptions::has_SaveSettings()? IWOptions::get_SaveSettings() : false,
									"Saves your settings when deactivating the plugin."));
	}
	
	static function options_page_parse($form_values)
	{
		IWOptions::load_options();
		if( WP_DEBUG === true ){
			error_log("parse -\n" . print_r($form_values, true));
			error_log("options -\n" . print_r(self::$_options, true));
		}
		
		if(!empty($form_values)) {
			// check boxes don't get retrieved normally if they aren't 'checked' so handle them with a special case
			self::$_options[self::$_loggingKey] = array_key_exists(self::$_loggingKey, $form_values);
			self::$_options[self::$_errorHandlerKey] = array_key_exists(self::$_errorHandlerKey, $form_values);
			self::$_options[self::$_saveSettingsKey] = array_key_exists(self::$_saveSettingsKey, $form_values);
			
			$args = array('hide_empty' => 0);
			$categoryList = get_categories($args);
			foreach($categoryList as $category) {
				$catId = $category->cat_ID;
				$catMapKey = IWOptions::key_CategoryMap( $catId );
				if( array_key_exists( $catMapKey, $form_values ) ) {
					self::$_options[$catMapKey] = (int)$form_values[$catMapKey];
				} else {
					self::$_options[$catMapKey] = null;
				}
			}
			
			if( array_key_exists( self::$_minTimeOutKey, $form_values ) ) {
				$intVal = $form_values[self::$_minTimeOutKey];
				if(is_numeric($intVal)) {
					$intVal = (int)$intVal;
					if($intVal > 0) {
						self::$_options[self::$_minTimeOutKey] = $intVal;
					}
				}
			}
			
			if( array_key_exists( self::$_publishIncrementKey, $form_values ) ) {
				$intVal = $form_values[self::$_publishIncrementKey];
				if(is_numeric($intVal)) {
					$intVal = (int)$intVal;
					if($intVal > 0) {
						self::$_options[self::$_publishIncrementKey] = (int)$intVal;
					}
				}
			}
			
			if( array_key_exists( self::$_rankCacheTimeKey, $form_values ) ) {
				$intVal = $form_values[self::$_rankCacheTimeKey];
				if(is_numeric($intVal)) {
					$intVal = (int)$intVal;
					if($intVal > 0) {
						self::$_options[self::$_rankCacheTimeKey] = (int)$intVal;
					}
				}
			}
			
			if( array_key_exists( self::$_rescheduleDelayKey, $form_values ) ) {
				$intVal = $form_values[self::$_rescheduleDelayKey];
				if(is_numeric($intVal)) {
					$intVal = (int)$intVal;
					if($intVal > 0) {
						self::$_options[self::$_rescheduleDelayKey] = $intVal;
					}
				}
			}
			
			if( array_key_exists( self::$_loginSessionTimeKey, $form_values ) ) {
				$intVal = $form_values[self::$_loginSessionTimeKey];
				if(is_numeric($intVal)) {
					$intVal = (int)$intVal;
					if($intVal > 0) {
						self::$_options[self::$_loginSessionTimeKey] = $intVal;
					}
				}
			}
			
			if( array_key_exists( self::$_maxRetryKey, $form_values ) ) {
				$intVal = $form_values[self::$_maxRetryKey];
				if(is_numeric($intVal)) {
					$intVal = (int)$intVal;
					if($intVal > 0) {
						self::$_options[self::$_maxRetryKey] = $intVal;
					}
				}
			}
			
			if( array_key_exists( self::$_retryDelayKey, $form_values ) ) {
				$intVal = $form_values[self::$_retryDelayKey];
				if(is_numeric($intVal)) {
					$intVal = (int)$intVal;
					if($intVal > 0) {
						self::$_options[self::$_retryDelayKey] = $intVal;
					}
				}
			}
			
		} else {
			// check boxes don't get retrieved normally if they aren't 'checked' so handle them with a special case
			self::$_options[self::$_loggingKey] = false;
			self::$_options[self::$_errorHandlerKey] = false;
			self::$_options[self::$_saveSettingsKey] = false;
		}
		
		return self::$_options;
	}
	
	static function options_info_section_text()
	{
		echo "<p>These settings provide general info and InsideWord account.</p>\n";
	}
	
	static function options_category_section_text()
	{
		echo "<p>The settings below allow you to match your categories to InsideWord categories. This will allow your posts to be sent to the correct categories in InsideWord.com.</p>\n";
	}
	
	static function options_performance_section_text()
	{
		echo "<p>Adjust these settings only if you need to fix an issue.</p>\n";
	}
	
	static function options_debug_section_text()
	{
		echo "<p>These are debug settings. Turning them on can slow down your server considerably.</p>\n";
	}
	
	static function options_page()
	{
		if(is_admin())
		{
			if(!current_user_can('manage_options'))
			{
				wp_die( __('You do not have sufficient permissions to access this page.') );
			}
			?>
			<div class="wrap">
				<div id="icon-options-general" class="icon32"><br /></div>
				<h2>InsideWord Syncher Settings</h2>
				<form action="options.php" method="post">
					<?php settings_fields(self::$_options_group); ?>
					<?php do_settings_sections(self::$_options_page); ?>
					<p class="submit">
						<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
					</p>
				</form>
			</div>
		<?php
		}
	}
	
	static function settings_anchor($args)
	{
		echo "<a href='".$args[0]."'>".$args[1]."</a>\n";
	}
	
	static function settings_label($args)
	{
		echo "<label>".$args[0]."</label>";
	}
	
	static function settings_dropdown($args)
	{
		$htmlSelectList = "<select id='".$args[0]."' name='".self::$_options_key."[".$args[0]."]' >\n";
		if(array_key_exists(3, $args)) {
			if($args[1] === null) {
				$htmlSelectList .= "<option selected='selected' value='-1'>".$args[3]."</option>\n";
			} else {
				$htmlSelectList .= "<option value='-1'>".$args[3]."</option>\n";
			}
		}
		if(is_array($args[2])) {
			foreach($args[2] as $item) {
				if($args[1] === $item->value) {
					$htmlSelectList .= "<option selected='selected' value='".$item->value."'>".$item->text."</option>\n";
				} else {
					$htmlSelectList .= "<option value='".$item->value."'>".$item->text."</option>\n";
				}
			}
		}
		$htmlSelectList .= "</select>";
		echo $htmlSelectList;
	}
	
	static function settings_checkbox($args) {
		if($args[1] === true) { $checked = ' checked="checked" '; }
		else { $checked = ""; }
		$comment = "\n";
		if(array_key_exists(2, $args)) {
			$comment = "<label>".$args[2]."</label>\n";
		}
		echo "<input id='".$args[0]."' name='".self::$_options_key."[".$args[0]."]' type='checkbox' ".$checked." value='true' />".$comment;
	}
	
	static function settings_string($args)
	{
		$comment = "\n";
		if(array_key_exists(2, $args)) {
			$comment = "<br /><label>".$args[2]."</label>\n";
		}
		echo "<input id='".$args[0]."' name='".self::$_options_key."[".$args[0]."]' size='40' type='text' value='".$args[1]."' />".$comment;
	}
}
?>