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


	public function __construct(array $config = []){

		parent::__construct($config);
	}

	public function create($data){

		if (is_array($data)){
			$text = $data[0];
		}else{
			$text = $data;
		}
		$this->WriteHTML($text);
		$this->Output();
	}

}