<?php
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

if( !class_exists( 'WP_Http' ) )
{
	  require_once( ABSPATH . WPINC. '/class-http.php' );
}

require_once('InsideWordArticle.php');

class InsideWordPressApi {
	
	private $_iwHost;
	public function get_IWHost() { return $this->_iwHost; }
	public function set_IWHost($iwHost) { $this->_iwHost = preg_replace("/^https?:\/\//", "", $iwHost); }
	
	private $_cookieFile;
	public function get_Cookie() { return get_transient("insideWordPressApi_Cookie"); }
	public function set_Cookie($value, $cacheTime)
	{
		delete_transient("insideWordPressApi_Cookie");
		set_transient("insideWordPressApi_Cookie", $value, $cacheTime);
	}
	
	private $_minTimeOut;
	public function get_MinTimeOut()       { return $this->_minTimeOut; }
	public function set_MinTimeOut($value) { $this->_minTimeOut = $value; }
	
	public function delete_Cookie()
	{
		delete_transient("insideWordPressApi_Cookie");
	}

	public function domainIdentificationRequest($domainAddress)
	{
		$request = 'http://' . $this->_iwHost . '/api/domain_identification_request';
		$body = array(
		    'domainAddress' => rawurlencode($domainAddress)
		);
		$message = array (
			'body' => $body,
			'timeout' => $this->_minTimeOut+7
		);
		$response = wp_remote_post($request, $message);
		
		if(!is_wp_error( $response ))
		{
			$response = wp_remote_retrieve_body( $response );
			$response = json_decode( $response );
		}
		
		return $response;
	}
	
	public function domainIdentification($domainAddress, $subFolder)
	{
		$request = 'http://' . $this->_iwHost . '/api/domain_identification';
		
		$body = array(
		    'domainAddress' => rawurlencode($domainAddress),
		    'subFolder' => rawurlencode($subFolder)
		);
		$message = array (
			'body' => $body,
			'timeout' => (2*$this->_minTimeOut)+9
		);
		$response = wp_remote_post($request, $message);
		
		if(!is_wp_error( $response ))
		{
			$response = wp_remote_retrieve_body( $response );
			$response = json_decode( $response );
			if($response->StatusCode == 0)
			{
				$issuedKeys = preg_split("/,/", $response->Content);
				$response->ThisMonth = $issuedKeys[0];
				$response->NextMonth = $issuedKeys[1];
				$response->Content = '';
			}
			else 
			{
				$response->ThisMonth = '';
				$response->NextMonth = '';
			}
		}
		
		return $response;
	}
	
	public function login($issuedKey, $issuedKey2, $cacheTime)
	{
		$cookieValue = $this->get_Cookie();
		if( !empty($cookieValue) ) {
			$response->StatusCode = InsideWordPressApi::login_success;
			$response->StatusMessage = "";
			$response->Content = "";
		} else { 
			
			$request = 'http://' . $this->_iwHost . '/api/login';
			
			$body = array(
				'issuedKey' => rawurlencode($issuedKey),
				'issuedKey2' => rawurlencode($issuedKey2)
			);
			$message = array (
				'body' => $body,
				'timeout' => $this->_minTimeOut+4
			);
			
			$response = wp_remote_post($request, $message);
			if(is_wp_error( $response )) {
				$response->StatusCode = InsideWordPressApi::login_failed;
				$response->StatusMessage = "timed out";
				$response->Content = "";
			} else {
				$body = wp_remote_retrieve_body( $response );
				$body = json_decode( $body );
				
				if($body->StatusCode == InsideWordPressApi::login_success) {
					$this->set_Cookie($response["cookies"], $cacheTime);
				}
				$response = $body;
				if($response->StatusCode == InsideWordPressApi::login_new_key)
				{
					$issuedKeys = preg_split("/,/", $response->Content);
					$response->ThisMonth = $issuedKeys[0];
					$response->NextMonth = $issuedKeys[1];
					$response->Content = '';
				}
			}
		}
		
		return $response;
	}
	
	public function publish_article($insideWordArticleOjbect)
	{
		$request = 'http://' . $this->_iwHost . '/api/publish_article';
		$body = array(
		    'id' => rawurlencode($insideWordArticleOjbect->get_InsideWordId()),
			'altId' => rawurlencode($insideWordArticleOjbect->get_AltId()),
			'title' => rawurlencode($insideWordArticleOjbect->get_Title()),
			'blurb' => rawurlencode($insideWordArticleOjbect->get_Blurb()),
			'text' => rawurlencode($insideWordArticleOjbect->get_Text()),
			'categoryId' => rawurlencode($insideWordArticleOjbect->get_CategoryId()),
			'alternateCategoryId' => rawurlencode($insideWordArticleOjbect->get_AlternateCategoryId()),
			'isPublished' => rawurlencode($insideWordArticleOjbect->get_IsPublished()),
			'createDate' => rawurlencode($insideWordArticleOjbect->get_CreateDate())
		);
		$message = array (
			'body' => $body,
			'cookies' => $this->get_Cookie(),
			'timeout' => $this->_minTimeOut+13
		);
		$response = wp_remote_post($request, $message);
		if(!is_wp_error( $response ))
		{
			$response = wp_remote_retrieve_body( $response );
			$response = json_decode( $response );
		}
		
		return $response;
	}
	
	public function edit_article($insideWordArticleObject)
	{
		if($insideWordArticleObject->getIWId() == null &&
		   $insideWordArticleObject->getAltId() == null)
		{
			$response->StatusCode = 1;
			$response->StatusMessage = "insideWordArticleObject must have IWId or AltId set, otherwise we will not know which article to edit and InsideWord will assume you are creating a new one.";
		}
		else
		{
			$response = $this->publish_article($insideWordArticleOjbect);
		}
		
		return $response;
	}
	
	public function article_rank($articleId)
	{
		$request = 'http://' . $this->_iwHost . '/api/article_rank/'. $articleId;
		$message = array (
			'timeout' => $this->_minTimeOut+2
		);
		$response = wp_remote_get($request, $message);
		if(!is_wp_error( $response ))
		{
			$response = wp_remote_retrieve_body( $response );
			$response = json_decode( $response );
		}
		
		return $response;
	}
	
	public function account_sync($email, $categoryMapList)
	{
		$request = 'http://' . $this->_iwHost . '/api/account_sync';
		$body = array();
		$index = 0;
		$body['memberData.Email'] = rawurlencode($email); 
		foreach($categoryMapList as $categoryMap)
		{
			$body['memberData.MemberToIWMap['.$index.'].AlternateTitle'] = rawurlencode($categoryMap[0]); 
			$body['memberData.MemberToIWMap['.$index.'].AlternateId'] = rawurlencode($categoryMap[1]);
			$body['memberData.MemberToIWMap['.$index.'].MapId'] = rawurlencode($categoryMap[2]);
			$index++;
		}
		$message = array (
			'body' => $body,
			'cookies' => $this->get_Cookie(),
			'timeout' => $this->_minTimeOut+7
		);
		$response = wp_remote_post($request, $message);
		
		if(!is_wp_error( $response ))
		{
			$response = wp_remote_retrieve_body( $response );
			$response = json_decode( $response );
		}
		
		return $response;
	}
	
	public function ping()
	{
		$request = 'http://' . $this->_iwHost . '/api/ping/';
		$message = array (
			'timeout' => $this->_minTimeOut
		);
		$response = wp_remote_get($request, $message);
		return !is_wp_error( $response );
	}
	
	public function delayed_visit_request($pageAddress, $secDelay)
	{
		$request = 'http://' . $this->_iwHost . '/api/delayed_visit_request';
		
		$body = array(
		    'pageAddress' => rawurlencode($pageAddress),
			'secDelay' => rawurlencode($secDelay)
		);
		$message = array (
			'body' => $body,
			'blocking' => false
		);
		wp_remote_post($request, $message);
	}
	
	public function change_article_state($insideWordId, $state)
	{
		$request = 'http://' . $this->_iwHost . '/api/change_article_state';
		
		$body = array(
			'articleId' => $insideWordId,
			'state' => $state
		);
		$message = array (
			'body' => $body,
			'cookies' => $this->get_Cookie(),
			'blocking' => false
			
		);
		wp_remote_post($request, $message);
	}
	
	public function log($text)
	{	
		$request = 'http://' . $this->_iwHost . '/api/log_info';
		$body = array(
		    'text' => rawurlencode($text)
		);
		$message = array (
			'body' => $body,
			'blocking' => false
		);
		wp_remote_post($request, $message);
	}
	
	const login_success = 0;
	const login_failed = 1;
	const login_new_key = 2;
	const login_banned = 3;
}
?>