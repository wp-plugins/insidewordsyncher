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
	public function set_Cookie($value)
	{
		delete_transient("insideWordPressApi_Cookie");
		set_transient("insideWordPressApi_Cookie", $value, 900);
	}
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
			'timeout' => 10
		);
		$response = wp_remote_post($request, $message);
		
		if(!is_wp_error( $response ))
		{
			$response = wp_remote_retrieve_body( $response );
			$response = json_decode( $response );
		}
		
		return $response;
	}
	
	public function delayedVisitRequest($pageAddress, $secDelay)
	{
		$request = 'http://' . $this->_iwHost . '/api/delayed_visit_request';
		
		$body = array(
		    'pageAddress' => rawurlencode($pageAddress),
			'secDelay' => rawurlencode($secDelay)
		);
		$message = array (
			'body' => $body,
			'timeout' => 7
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
			'timeout' => 50
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
	
	public function login($issuedKey)
	{
		$cookieValue = $this->get_Cookie();
		$loggedIn = !empty($cookieValue);
		if(!$loggedIn)
		{
			$request = 'http://' . $this->_iwHost . '/api/login';
			
			$body = array(
				'issuedKey' => rawurlencode($issuedKey)
			);
			$message = array (
				'body' => $body,
				'timeout' => 7
			);
			$response = wp_remote_post($request, $message);
			if(!is_wp_error( $response ))
			{
				$body = wp_remote_retrieve_body( $response );
				$body = json_decode( $body );
				$loggedIn = ($body->StatusCode == 0);
				
				if($loggedIn)
				{
					$this->set_Cookie($response["cookies"]);
				}
			}
			else
			{
				$loggedIn = false;
			}
		}
		
		return $loggedIn;
	}
	
	public function issued_key_request()
	{
		$request = 'http://' . $this->_iwHost . '/api/issued_key_request';
		
		$message = array (
			'cookies' => $this->get_Cookie(),
			'timeout' => 7
		);
		$response = wp_remote_get($request, $message);
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
			'isPublished' => rawurlencode($insideWordArticleOjbect->get_IsPublished()),
			'createDate' => rawurlencode($insideWordArticleOjbect->get_CreateDate())
		);
		$message = array (
			'body' => $body,
			'cookies' => $this->get_Cookie(),
			'timeout' => 30
		);
		$response = wp_remote_post($request, $message);
		if(!is_wp_error( $response ))
		{
			$response = wp_remote_retrieve_body( $response );
			$response = json_decode( $response );
		}
		
		return $response;
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
			'timeout' => 7
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
	
	public function default_category_id()
	{
		$request = 'http://' . $this->_iwHost . '/api/default_category_id/';
		$message = array (
			'timeout' => 7
		);
		$response = wp_remote_get($request, $message);
		if(!is_wp_error( $response ))
		{
			$response = wp_remote_retrieve_body( $response );
			$response = json_decode( $response );
		}
		
		return $response;
	}
	
	public function article_rank($articleId)
	{
		$request = 'http://' . $this->_iwHost . '/api/article_rank/'. $articleId;
		$response = wp_remote_get($request);
		if(!is_wp_error( $response ))
		{
			$response = wp_remote_retrieve_body( $response );
			$response = json_decode( $response );
		}
		
		return $response;
	}
	
	public function log($text)
	{	
		$request = 'http://' . $this->_iwHost . '/api/log_info';
		$body = array(
		    'text' => rawurlencode($text)
		);
		$message = array (
			'body' => $body
		);
		$response = wp_remote_post($request, $message);
		if(!is_wp_error( $response ))
		{
			$response = wp_remote_retrieve_body( $response );
			$response = json_decode( $response );
		}
		
		return $response;
	}
	
	public function profile_link()
	{
		$request = 'http://' . $this->_iwHost . '/api/profile_link/';
		$message = array (
			'cookies' => $this->get_Cookie(),
			'timeout' => 7
		);
		$response = wp_remote_get($request, $message);
		if(!is_wp_error( $response ))
		{
			$response = wp_remote_retrieve_body( $response );
			$response = json_decode( $response );
		}
		
		return $response;
	}
	
	public function state_delete()  { return -3; }
	public function state_hidden()  { return -2; }
	public function state_flagged() { return -1; }
	public function state_draft()   { return 0; }
	public function state_publish() { return 1; }
}
?>