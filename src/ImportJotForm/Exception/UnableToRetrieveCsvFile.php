<?php

namespace ImportJotForm\Exception;

class UnableToRetrieveCsvFile extends \Exception {

	public function __construct($message) {
		parent::__construct ( $message );
	}

}