<?php

namespace ImportJotForm\Reader;

use Ddeboer\DataImport\Reader\CsvReader;
use Zend\Http\Client;
use Zend\Http\Request;
use ImportJotForm\Exception\UnableToRetrieveCsvFile;

class JotFormCsvReportReader extends CsvReader {
	protected $jotFormId;
	protected $password;
	protected $jotFormBase = 'https://eu.jotform.com/csv';

	/**
	 *
	 * @param string $jotFormReportId        	
	 * @param string|null $password        	
	 * @throws UnableToRetrieveCsvFile
	 */
	public function __construct($jotFormReportId, $password = null) {
		// My Constructor
		$this->jotFormId = $jotFormReportId;
		$this->password = $password;
		
		// Download CSV-File from jotForm
		$splFile = $this->downloadCsvFromJotForm ( $jotFormReportId );
		
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

	public function __destruct() {
		// Remove temporary file
		$fname = $this->file->getPath () . DIRECTORY_SEPARATOR . $this->file->getFilename ();
		$this->file = null;
		unlink ( $fname );
	}

	/**
	 *
	 * @param string $jotFormReportId        	
	 * @throws UnableToRetrieveCsvFile
	 * @return \SplFileObject
	 */
	protected function downloadCsvFromJotForm($jotFormReportId) {
		$client = new Client ();
		
		$client->setUri ( $this->jotFormBase . '/' . $jotFormReportId );
		$client->setOptions ( array( 
				'maxredirects' => 2,
				'timeout' => 30 
		) );
		
		// Set Certification Path when https is used - does not work (yet)
		if (strpos ( $this->jotFormBase, 'https:' ) === 0) {
			$client->setOptions ( array( 
					// 'sslcapath' => '/etc/ssl/certs',
					// 'ssltransport' => 'tls',
					'adapter' => 'Zend\Http\Client\Adapter\Curl',
					'curloptions' => array( 
							CURLOPT_FOLLOWLOCATION => TRUE,
							CURLOPT_SSL_VERIFYPEER => FALSE 
					) 
			) );
		}
		
		// will use temp file
		$client->setStream ();
		
		// Password, if set
		if (! empty ( $this->password )) {
			$client->setMethod ( Request::METHOD_POST );
			$client->setParameterPost ( array( 
					'passKey' => $this->password 
			) );
		}
		
		$response = $client->send ();
		
		if ($response->getStatusCode () != 200) {
			throw new UnableToRetrieveCsvFile ( 'Wront StatusCode: ' . $response->getStatusCode () . ' (StatusCode=200 expected)' );
		}
		// Copy StreamInput
		$tmpName = tempnam ( '/tmp', 'jotFormReport_' );
		copy ( $response->getStreamName (), $tmpName );
		
		// Add a Newline to the end of the file (because SplFileObject needs a newline)
		$splFile = new \SplFileObject ( $tmpName, 'a+' );
		$splFile->fwrite ( PHP_EOL );
		
		return $splFile;
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
				if (empty ( $headerName )) {
					$headerName = 'UNKNOWN';
				}
				$headers [$key] = $headerName . $incrementedHeaders [$headerName] ++;
			}
		}
		
		return $headers;
	}

}