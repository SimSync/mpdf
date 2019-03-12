<?php
	/*
	 * e107 website system
	 *
	 * Copyright (C) 2008-2019 e107 Inc (e107.org)
	 * Released under the terms and conditions of the
	 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
	 *
	 * Plugin - PDF Generator
	 *
	 * $URL$
	 * $Id$
	 */

	/**
	 *	e107 mpdf generation plugin
	 *
	 *	@package	e107_plugins
	 *	@subpackage	mpdf
	 *	@version 	$Id$;
	 */
	require_once '../../class2.php';
	if (!defined('e107_INIT')) { exit; }

	if(!e107::isInstalled('mpdf') || !e_QUERY)
	{
		header('Location: '.e_BASE.'index.php');
	}

	$qs = explode('.', e_QUERY,2);
	$source = $qs[0];
	$parms = varset($qs[1],'');

//include_lan(e_PLUGIN.'pdf/languages/'.e_LANGUAGE.'_admin_pdf.php');

	// require the autoloader for the vendor files
	require_once 'vendor/autoload.php';

	require_once(e_PLUGIN.'mpdf/mpdf.class.php');	//require the mpdf class
	$pdf = new mpdf();

	if(strpos($source,'plugin:') !== FALSE)
	{
		$plugin = substr($source,7);
		if(file_exists(e_PLUGIN.$plugin.'/e_emailprint.php'))
		{
			include_once(e_PLUGIN.$plugin.'/e_emailprint.php');
			if (method_exists('print_item_pdf'))
			{
				$text = call_user_func(array($plugin.'_emailprint', 'print_item_pdf'), $parms);
				$pdf->create($text);
			}
			else
			{
				echo 'PDF generation not supported in this section';
			}
		}
		else
		{
			echo 'file missing: '.e_PLUGIN.$plugin.'/e_emailprint.php';
			exit;
		}
	}
	else
	{
		if($source == 'news')
		{
			$con = new convert;
			$sql->select('news', '*', 'news_id='.intval($parms));
			$row = $sql->fetch();
			$news_body = $tp->toHTML($row['news_body'], TRUE);
			$news_extended = $tp->toHTML($row['news_extended'], TRUE);
			if ($row['news_author'] == 0)
			{
				$user_name = 'e107';
				$category_name = 'e107 welcome message';
			}
			else
			{
				$user_name = $sql->retrieve('user', 'user_name', 'user_id='.intval($row['news_author']));
				$category_name = $sql->retrieve('news_category', 'category_name', 'category_id='.intval($row['news_category']));;
			}
			$row['news_datestamp'] = $con->convert_date($row['news_datestamp'], "long");

			$row['news_title'] = $tp->toHTML($row['news_title'], TRUE, 'parse_sc');

			//remove existing links from news title
			$search = array();
			$replace = array();
			$search[0] = "/\<a href=\"(.*?)\">(.*?)<\/a>/si";
			$replace[0] = '\\2';
			$search[1] = "/\<a href='(.*?)'>(.*?)<\/a>/si";
			$replace[1] = '\\2';
			$search[2] = "/\<a href='(.*?)'>(.*?)<\/a>/si";
			$replace[2] = '\\2';
			$search[3] = "/\<a href=&quot;(.*?)&quot;>(.*?)<\/a>/si";
			$replace[3] = '\\2';
			$search[4] = "/\<a href=&#39;(.*?)&#39;>(.*?)<\/a>/si";
			$replace[4] = '\\2';
			$row['news_title'] = preg_replace($search, $replace, $row['news_title']);

			$text = sprintf("<b>%s</b><br/>%s<br/>%s, %s<br/><br/>%s%s<br/>",
				$row['news_title'],
				$row['category_name'],
				$user_name, $row['news_datestamp'],
				$news_body,
				!empty($news_extended) ? '<br/>'.$news_extended : '');

			//if ($row['news_extended'] != ""){ $text .= "<br /><br />".$row['news_extended']; }
			if ($row['news_source'] != ""){ $text .= "<br /><br />".$row['news_source']; }
			if ($row['news_url'] != ""){ $text .= "<br />".$row['news_url']; }

			$text = trim(str_replace(array('[html]', '[/html]', "\r", "\n", "\t"), '', $text));

			$text		= $text;					//define text
			//$creator	= SITENAME;					//define creator
			$author		= $user_name;				//define author
			$title		= $row['news_title'];		//define title
			$subject	= $category_name;			//define subject
			$keywords	= '';						//define keywords

			//define url and logo to use in the header of the pdf file
			$url = e107::getUrl()->create('news/view/item', $row, array('full' => 1));

			//always return an array with the following data:
			//$text = array($text, $creator, $author, $title, $subject, $keywords, $url);
			$pdf->create($text, $author, $title, $subject, $keywords, $url);
		}
	}

