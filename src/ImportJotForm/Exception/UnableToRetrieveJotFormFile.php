<?php

namespace ImportJotForm\Exception;

class UnableToRetrieveJotFormFile extends \Exception {

	public function __construct($message) {
		parent::__construct ( $message );
	}

}