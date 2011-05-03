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

class InsideWordArticle
{	
	private $_IWId;
	public function get_InsideWordId() { return $this->_IWId; }
	public function set_InsideWordId($IWId) { $this->_IWId = $IWId; }
	
	private $_altId;
	public function get_AltId() { return $this->_altId; }
	public function set_AltId($altId) { $this->_altId = $altId; }

	private $_title;
	public function get_Title() { return $this->_title; }
	public function set_Title($title) { $this->_title = $title; }
	
	private $_blurb;
	public function get_Blurb() { return $this->_blurb; }
	public function set_Blurb($blurb) { $this->_blurb = $blurb; }
	
	private $_text;
	public function get_Text() { return $this->_text; }
	public function set_Text($text) { $this->_text = $text; }
	
	private $_categoryId;
	public function get_CategoryId() { return $this->_categoryId; }
	public function set_CategoryId($categoryId) { $this->_categoryId = $categoryId; }
	
	private $_isPublished;
	public function get_IsPublished() { return $this->_isPublished; }
	public function set_IsPublished($isPublished) { $this->_isPublished = $isPublished; }
	
	private $_createDate;
	public function get_CreateDate() { return $this->_createDate; }
	public function set_CreateDate($createDate) { $this->_createDate = $createDate; }
}
?>