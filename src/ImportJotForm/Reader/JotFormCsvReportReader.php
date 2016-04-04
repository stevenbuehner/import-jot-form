<?php

namespace ImportJotForm\Reader;

use Ddeboer\DataImport\Reader\CsvReader;
use ImportJotForm\Exception\UnableToRetrieveJotFormFile;
use ImportJotForm\Helper\JotFormDownloadHelper;

class JotFormCsvReportReader extends CsvReader {
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
		
		// Download CSV-File from jotForm
		$filePath = $downloadHelper->downloadFromJotForm ( $jotFormUrl, $password );
		$splFile = $downloadHelper->fileNameToSplFile ( $filePath );
		
		// Parent Construct
		parent::__construct ( $splFile, $delimiter = ',', $enclosure = '"', $escape = "\n" );
		$this->file->seek ( 0 );
		
		// jotForm Specific CSV-Options
		$this->setHeaderRowNumber ( 0, CsvReader::DUPLICATE_HEADERS_INCREMENT );
		$this->file->setFlags ( \SplFileObject::READ_CSV | \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY );
		
		// Sometimes the CSV-Entry contains a "," at the end and sometimes not
		// Stupid jotForm
		$this->setStrict ( false );
	}

	/**
	 * Overriding default function
	 * Bugfix
	 *
	 * @param array $headers        	
	 *
	 * @return array
	 */
	protected function incrementHeaders(array $headers) {
		$incrementedHeaders = array();
		
		// Get all headlines that are duplicate or more
		foreach ( array_count_values ( $headers ) as $header => $count ) {
			if ($count > 1) {
				$incrementedHeaders [$header] = 0;
			}
		}
		
		// Replace the headers with the new header name but keep the position ($key) in the array
		foreach ( $headers as $key => $headerName ) {
			if (isset ( $incrementedHeaders [$headerName] )) {
				$prefix = empty ( $headerName ) ? 'UNKNOWN' : '';
				$headers [$key] = $prefix . $headerName . $incrementedHeaders [$headerName] ++;
			}
		}
		
		return $headers;
	}

}