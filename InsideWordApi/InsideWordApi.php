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

require_once('InsideWordArticle.php');

class InsideWordApi {
	private $_iwHost;
	public function get_IWHost() { return $this->_iwHost; }
	public function set_IWHost($iwHost) { $this->_iwHost = preg_replace("/^https?:\/\//", "", $iwHost); }
	
	private $_cookieFile;
	public function get_CookieFile() { return $this->_cookieFile; }
	public function set_CookieFile($fileName)
	{
		$this->_cookieFile = $fileName;
		if(!file_exists($fileName)) { 
		    $fh = fopen($fileName, "w");
		    fwrite($fh, "");
		    fclose($fh); 
		}
	}

	public function domainIdentificationRequest($domainAddress)
	{
		$request = 'http://' . $this->_iwHost . '/api/domain_identification_request';
		$data = array(
		    'domainAddress' => rawurlencode($domainAddress)
		);
		
		$session = curl_init($request);
		curl_setopt( $session, CURLOPT_POST, true);
		curl_setopt( $session, CURLOPT_POSTFIELDS, $data);
		curl_setopt( $session, CURLOPT_HEADER, false);
		curl_setopt( $session, CURLOPT_RETURNTRANSFER, true);
		$response = json_decode(curl_exec($session));
		curl_close($session);
		return $response;
	}
	
	public function delayedVisitRequest($pageAddress, $secDelay)
	{
		$request = 'http://' . $this->_iwHost . '/api/delayed_visit_request';
		$data = array(
		    'pageAddress' => rawurlencode($pageAddress),
			'secDelay' => rawurlencode($secDelay)
		);
		
		$session = curl_init($request);
		curl_setopt( $session, CURLOPT_POST, true);
		curl_setopt( $session, CURLOPT_POSTFIELDS, $data);
		curl_setopt( $session, CURLOPT_HEADER, false);
		curl_setopt( $session, CURLOPT_RETURNTRANSFER, true);
		$response = json_decode(curl_exec($session));
		curl_close($session);
		return $response;
	}
	
	public function domainIdentification($domainAddress, $subFolder)
	{
		$request = 'http://' . $this->_iwHost . '/api/domain_identification';
		$data = array(
		    'domainAddress' => rawurlencode($domainAddress),
		    'subFolder' => rawurlencode($subFolder)
		);
		
		$session = curl_init($request);
		curl_setopt( $session, CURLOPT_POST, true);
		curl_setopt( $session, CURLOPT_POSTFIELDS, $data);
		curl_setopt( $session, CURLOPT_HEADER, false);
		curl_setopt( $session, CURLOPT_RETURNTRANSFER, true);
		$response = json_decode(curl_exec($session));
		curl_close($session);
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
		return $response;
	}
	
	public function login($issuedKey)
	{
		$request = 'http://' . $this->_iwHost . '/api/login';
		$data = array(
		    'issuedKey' => rawurlencode($issuedKey)
		);
		
		$session = curl_init($request);
		curl_setopt( $session, CURLOPT_POST, true);
		curl_setopt( $session, CURLOPT_POSTFIELDS, $data);
		curl_setopt( $session, CURLOPT_HEADER, false);
		curl_setopt( $session, CURLOPT_RETURNTRANSFER, true);
		
		// Set the COOKIE files 
		curl_setopt($session, CURLOPT_COOKIEFILE, $this->get_CookieFile()); 
		curl_setopt($session, CURLOPT_COOKIEJAR, $this->get_CookieFile());
		
		$response = json_decode(curl_exec($session));
		curl_close($session);
		return ($response->StatusCode == 0);
	}
	
	public function issued_key_request($issuedKey)
	{
		$request = 'http://' . $this->_iwHost . '/api/issued_key_request/'. $issuedKey;
		$session = curl_init($request);
		curl_setopt( $session, CURLOPT_HEADER, false);
		curl_setopt( $session, CURLOPT_RETURNTRANSFER, true);
		$response = json_decode(curl_exec($session));
		curl_close($session);
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
		return $response;
	}
	
	public function publish_article($insideWordArticleOjbect)
	{
		$request = 'http://' . $this->_iwHost . '/api/publish_article';
		$data = array(
		    'id' => rawurlencode($insideWordArticleOjbect->get_InsideWordId()),
			'altId' => rawurlencode($insideWordArticleOjbect->get_AltId()),
			'headline' => rawurlencode($insideWordArticleOjbect->get_Title()),
			'blurb' => rawurlencode($insideWordArticleOjbect->get_Blurb()),
			'text' => rawurlencode($insideWordArticleOjbect->get_Text()),
			'categoryId' => rawurlencode($insideWordArticleOjbect->get_CategoryId()),
			'isPublished' => rawurlencode($insideWordArticleOjbect->get_IsPublished()),
			'editDate' => rawurlencode($insideWordArticleOjbect->get_EditDate()),
			'createDate' => rawurlencode($insideWordArticleOjbect->get_CreateDate())
		);
		$session = curl_init($request);
		curl_setopt( $session, CURLOPT_POST, true);
		curl_setopt( $session, CURLOPT_POSTFIELDS, $data);
		curl_setopt( $session, CURLOPT_HEADER, false);
		curl_setopt( $session, CURLOPT_RETURNTRANSFER, true);
		
		// Set the COOKIE files 
		curl_setopt($session, CURLOPT_COOKIEFILE, $this->get_CookieFile());
		curl_setopt($session, CURLOPT_COOKIEJAR, $this->get_CookieFile());
		$response = json_decode(curl_exec($session));
		curl_close($session);
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
	
	public function article_rank($id)
	{
		$request = 'http://' . $this->_iwHost . '/api/article_rank/'. $id;
		$session = curl_init($request);
		curl_setopt( $session, CURLOPT_HEADER, false);
		curl_setopt( $session, CURLOPT_RETURNTRANSFER, true);
		$response = json_decode(curl_exec($session));
		curl_close($session);
		return $response;
	}
}
?>