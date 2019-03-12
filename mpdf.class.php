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


class MPDF extends \Mpdf\Mpdf
{
	
	private $default_config = array(
		'mode' => 'utf-8',
		'orientation' => 'P',
		'margin_left' => '25',
		'margin_right' => '15',
		'margin_top' => '15',
		'font_family' => 'helvetica',
		'font_size' => '8',
		'font_size_sitename' => '14',
		'font_size_page_url' => '8',
		'font_size_page_number' => '8',
		'show_logo' => true,
		'show_sitename' => false,
		'show_page_url' => true,
		'show_page_number' => true,
		'error_reporting' => true
	);
	
	private $config = array();

	/**
	 * MPDF constructor.
	 *
	 * @param array $config
	 * @throws \Mpdf\MpdfException
	 */
	public function __construct(array $config = array())
	{
		// Set default config
		$this->config = $this->default_config;

		// Read prefs (if any)
		$prefs = e107::pref('mpdf');

		// If prefs available, merge/overwrite default config
		if (!empty($prefs)){
			$this->config = array_merge($this->config, $prefs);
		}

		// If there are custom prefs, merge/overwrite config
		if (!empty($config)){
			$this->config = array_merge($this->config, $config);
		}

		// Apply config to parent class
		parent::__construct($this->config);
	}

	/**
	 * Create and display the pdf on screen
	 *
	 * @param $html     HTML to convert
	 * @param $author   Author of the HTML content
	 * @param $title    Title, also used as filename (after filname cleaning)
	 * @param $subject  Subject, category in case of news
	 * @param $keywords Keywords
	 * @param $url      URL used
	 * @throws \Mpdf\MpdfException
	 */
	public function create($html, $author = '', $title = '', $subject = '', $keywords = '', $url = '')
	{

		try{
			$this->_preparePdf($html, $author, $title, $subject, $keywords, $url);
		}catch(\Mpdf\MpdfException $ex){
			echo e107::getMessage()->addError('MPDF error: ' .$ex->getMessage())->reset();
			exit;
		}

		if (!empty($title)){
			$filename = e107::getFile()->cleanFileName($title);
		}else{
			$filename = 'e107pdf.pdf';
		}
		if (pathinfo($filename, PATHINFO_EXTENSION) !== 'pdf'){
			$filename .= '.pdf';
		}

		try{
			$this->Output($filename, Mpdf\Output\Destination::INLINE);
		}catch(\Mpdf\MpdfException $ex){
			echo e107::getMessage()->addError('MPDF error: ' .$ex->getMessage())->reset();
			exit;
		}
	}


	/**
	 * Create and save a pdf on the disk
	 *
	 * @param $html     HTML to convert
	 * @param $author   Author of the HTML content
	 * @param $title    Title, also used as filename (after filname cleaning)
	 * @param $subject  Subject, category in case of news
	 * @param $keywords Keywords
	 * @param $url      URL used
	 * @param $filename Filename to use for saving. If empty, title will be used (if not empty)
	 * @throws \Mpdf\MpdfException
	 *
	 * @return string The (cleaned) filename or an empty string on error
	 */
	public function save($html, $author = '', $title = '', $subject = '', $keywords = '', $url = '', $filename = '')
	{

		try{
			$this->_preparePdf($html, $author, $title, $subject, $keywords, $url);
		}catch(\Mpdf\MpdfException $ex){
			echo e107::getMessage()->addError('MPDF error: ' .$ex->getMessage())->reset();
			exit;
		}

		if (!empty($title)){
			$filename = e107::getFile()->cleanFileName($title);
		}else{
			$filename = 'e107pdf.pdf';
		}
		if (pathinfo($filename, PATHINFO_EXTENSION) !== 'pdf'){
			$filename .= '.pdf';
		}

		try{
			$this->Output($filename, Mpdf\Output\Destination::FILE);
		}catch(\Mpdf\MpdfException $ex){
			echo e107::getMessage()->addError('MPDF error: ' .$ex->getMessage())->reset();
			exit;
		}

		return file_exists($filename) ? $filename : '';
	}

	/**
	 * Prepare the pdf
	 *
	 * @param $html     HTML to convert
	 * @param $author   Author of the HTML content
	 * @param $title    Title, also used as filename (after filname cleaning)
	 * @param $subject  Subject, category in case of news
	 * @param $keywords Keywords
	 * @param $url      URL used
	 * @throws \Mpdf\MpdfException
	 *
	 * @return string The (cleaned) filename or an empty string on error
	 */
	private function _preparePdf($html, $author = '', $title = '', $subject = '', $keywords = '', $url = '')
	{
		$tp = e107::getParser();

		$creator = SITENAME;

		//define logo and source pageurl (before the parser!)
		if(is_readable(THEME.'images/logopdf.png'))
		{
			$logo = THEME.'images/logopdf.png';
		}
		else
		{
			$logo = e_IMAGE.'logo.png';
		}
		define('PDFLOGO', $logo);					//define logo to add in header
		if (substr($url, -1) == '?')
		{
			$url = substr($url, 0, -1);
		}
		define('PDFPAGEURL', $url);				//define page url to add in header

		//parse the data
		$title = $this->_toPDFTitle($title);		//replace some in the title

		$html = $tp->toHTML($html, TRUE, 'BODY');
		$creator = $tp->toHTML($creator, TRUE, 'BODY');
		$author = $tp->toHTML($author, TRUE, 'BODY');
		$title = $tp->toHTML($title, TRUE, 'BODY');
		$subject = $tp->toHTML($subject, TRUE, 'BODY');
		$keywords = $tp->toHTML($keywords, TRUE, 'BODY');
		$url = $tp->toHTML($url, TRUE, 'BODY');

		//set some variables
		$this->SetMargins(
			$this->config['margin_left'],
			$this->config['margin_right'],
			$this->config['margin_top']);

		$this->SetAutoPageBreak(true,25);			// Force new page break at 25mm from bottom

		$this->DefOrientation=(varset($this->config['orientation'], 'P') == 'L' ? 'L' : 'P'); 	// Page orientation - P=portrait, L=landscape
		
		$this->AddPage();							//start page

		$this->SetFont(
			$this->config['font_family'],
			'',
			$this->config['font_size']);				//set font

		//write html
		$this->WriteHTML($html, Mpdf\HTMLParserMode::HTML_BODY);
		//name of creator
		$this->SetCreator($creator);
		//name of author
		$this->SetAuthor($author);
		//title
		$this->SetTitle($title);
		//subject
		$this->SetSubject($subject);
		//space/comma separated
		$this->SetKeywords($keywords);

	}

	/**
	 *	Convert e107-encoded text to title text
	 *
	 *	@param string $text
	 *
	 *	@return string with various characters replaced with '-'
	 */
	private function _toPDFTitle($text)
	{
		$search = array('&#39;', '&#039;', '&#036;', '&quot;', ":", "*", "?", '"', '<', '>', '|');
		$replace = array("'", "'", '$', '"', '-', '-', '-', '-', '-', '-', '-');
		$text = str_replace($search, $replace, $text);
		return $text;
	}

	/**
	 *	Add e107-specific header to each page.
	 *	Uses various prefs set in the admin page.
	 *	Overrides the tcpdf default header function
	 */
	function Header($content = '')
	{
		$topMargin = $this->config['margin_top'];
		if($this->config['show_logo'])
		{
			$this->SetFont($this->config['font_family'],'',$this->config['font_size']);
			$this->Image(PDFLOGO, $this->GetX(), $topMargin);
			$imgx = $this->getImageRBX();
			$imgy = $this->getImageRBY();			// Coordinates of bottom right of logo

			$a=$this->GetStringWidth(SITENAME);
			$b=$this->GetStringWidth(PDFPAGEURL);
			$c = max($a, $b) + $this->rMargin;
			if(($imgx + $c) > $pageWidth)			// See if room for other text to right of logo
			{	// No room - move to underneath
				$this->SetX($this->lMargin);
				$this->SetY($imgy+2);
			}
			else
			{
				$m = 0;
				if($this->config['show_sitename'])
				{
					$m = 5;
				}
				if($this->config['show_page_url'])
				{
					$m += 5;
				}
				if($this->config['show_page_number'])
				{
					$m += 5;
				}
				$this->SetX($imgx);						// May not be needed
				$newY = max($topMargin, $imgy - $m);
				$this->SetY($newY);						//Room to right of logo - calculate space to line up bottom of text with bottom of logo
			}
		}
		else
		{
			$this->SetY($topMargin);
		}

		// Now print text - 'cursor' positioned in correct start position
		$cellwidth	= $pageWidth - $this->GetX()-$this->rMargin;
		$align		= 'R';
//		echo "imgx: {$imgx}   imgy: {$imgy}   cellwidth: {$cellwidth}   m: {$m} <br />";
		if($this->config['show_sitename'])
		{
			$this->SetFont($this->config['font_family'],'B',$this->config['font_size_sitename']);
			$this->Cell($cellwidth,5,SITENAME,0,1,$align);
		}
		if($this->config['show_page_url'])
		{
			$this->SetFont($this->config['font_family'],'I',$this->config['font_size_page_url']);
			$this->Cell($cellwidth,5,PDFPAGEURL,0,1,$align,'',PDFPAGEURL);
		}
		if($this->config['show_page_number'])
		{
			$this->SetFont($this->config['font_family'],'I',$this->config['font_size_page_number']);
			//$this->Cell($cellwidth,5,PDF_LAN_19.' '.$this->PageNo().'/{nb}',0,1,$align);		// {nb} is an alias for the total number of pages
			$this->Cell($cellwidth,5,PDF_LAN_19.' '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(),0,1,$align);
		}

		$this->SetFont($this->config['font_family'],'',$this->config['font_size']);

		// Following cloned from tcpdf header function
		$this->SetY((2.835 / $this->getScaleFactor()) + max($imgy, $this->GetY()));		// 2.835 is number of pixels per mm
		if ($this->getRTL())
		{
			$this->SetX($ormargins['right']);
		}
		else
		{
			$this->SetX($ormargins['left']);
		}
		$this->Cell(0, 0, '', 'T', 0, 'C');			// This puts a line between header and text

		$this->SetTopMargin($this->GetY()+2);		// FIXME: Bodge to force main body text to start below header
	}


}