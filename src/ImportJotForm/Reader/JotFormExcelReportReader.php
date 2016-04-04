<?php

namespace ImportJotForm\Reader;

use ImportJotForm\Exception\UnableToRetrieveJotFormFile;
use Ddeboer\DataImport\Reader\ExcelReader;
use ImportJotForm\Helper\JotFormDownloadHelper;

class JotFormExcelReportReader extends ExcelReader {
	protected $jotFormUrl;
	protected $password;

	/**
	 *
	 * @param string $jotFormUrl        	
	 * @param string|null $password        	
	 * @throws UnableToRetrieveCsvFile
	 */
	public function __construct($jotFormUrl, $password = null) {
		// My Constructor
		$this->jotFormUrl = $jotFormUrl;
		$this->password = $password;
		
		$downloadHelper = new JotFormDownloadHelper ();
		
		// Download Excel-File from jotForm
		$filePath = $downloadHelper->downloadFromJotForm ( $jotFormUrl, $password );
		
		$splFile = new \SplFileObject ( $filePath );
		
		// Parent Construct
		parent::__construct ( $splFile, $rowNumber = 0 );
	}

}