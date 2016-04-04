<?php

namespace ImportJotForm\Helper;

use Zend\Http\Client;
use Zend\Http\Request;
use ImportJotForm\Exception\UnableToRetrieveJotFormFile;

class JotFormDownloadHelper {
	protected $cleanupDownloadsWithLivecycle;
	protected $downloads;

	/**
	 *
	 * @param string $jotFormUrl        	
	 * @param string|null $password        	
	 * @throws UnableToRetrieveJotFormFile
	 */
	public function __construct($cleanupDownloadsWithLivecycle = true) {
		$this->downloads = [];
		$this->cleanupDownloadsWithLivecycle = $cleanupDownloadsWithLivecycle;
	}

	public function __destruct() {
		// Remove temporary file
		if ($this->cleanupDownloadsWithLivecycle === true) {
			foreach ( $this->downloads as $dl ) {
				unlink ( $dl );
			}
		}
	}

	/**
	 *
	 * @param string $jotFormUrl        	
	 * @throws UnableToRetrieveJotFormFile
	 * @return $localFilePath
	 */
	public function downloadFromJotForm($jotFormUrl, $password) {
		$client = new Client ();
		
		$client->setUri ( $jotFormUrl );
		$client->setOptions ( array( 
				'maxredirects' => 2,
				'timeout' => 30 
		) );
		
		// Set Certification Path when https is used - does not work (yet)
		if (strpos ( $jotFormUrl, 'https:' ) === 0) {
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
		if (! empty ( $password )) {
			$client->setMethod ( Request::METHOD_POST );
			$client->setParameterPost ( array( 
					'passKey' => $password 
			) );
		}
		
		$response = $client->send ();
		
		if ($response->getStatusCode () != 200) {
			throw new UnableToRetrieveJotFormFile ( 'Wront StatusCode: ' . $response->getStatusCode () . ' (StatusCode=200 expected)' );
		}
		// Copy StreamInput
		$tmpName = tempnam ( '/tmp', 'jotFormReport_' );
		copy ( $response->getStreamName (), $tmpName );
		
		// Add to delete late
		$this->downloads [] = $tmpName;
		
		return $tmpName;
	}

	public function fileNameToSplFile($filename) {
		// Add a Newline to the end of the file (because SplFileObject needs a newline)
		$splFile = new \SplFileObject ( $filename, 'a+' );
		$splFile->fwrite ( PHP_EOL );
		
		return $splFile;
	}

}
