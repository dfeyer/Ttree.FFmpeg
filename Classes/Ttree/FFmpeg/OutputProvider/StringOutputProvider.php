<?php
namespace Ttree\FFmpeg\OutputProvider;

	/*                                                                        *
	 * This script belongs to the FLOW3 package "Ttree.FFmpeg".               *
	 *                                                                        *
	 * It is free software; you can redistribute it and/or modify it under    *
	 * the terms of the MIT license.                                          *
	 *                                                                        */

/**
 * @api
 */
class StringOutputProvider extends AbstractOutputProvider {

	protected $_output;

	/**
	 * Constructor
	 *
	 * @param string $ffmpegBinary path to ffmpeg executable
	 * @param boolean $persistent  persistent functionality on/off
	 */
	public function __construct($ffmpegBinary = 'ffmpeg', $persistent = FALSE) {
		$this->_output = '';
		parent::__construct($ffmpegBinary, $persistent);
	}

	/**
	 * Getting parsable output from ffmpeg binary
	 *
	 * @return string
	 */
	public function getOutput() {

		// Persistent opening
		if ($this->persistent == TRUE && array_key_exists(get_class($this) . $this->binary . $this->movieFile, self::$persistentBuffer)) {
			return self::$persistentBuffer[get_class($this) . $this->binary . $this->movieFile];
		}

		return $this->_output;
	}

	/**
	 * Setting parsable output
	 *
	 * @param string $output
	 */
	public function setOutput($output) {

		$this->_output = $output;

		// Storing persistent opening
		if ($this->persistent == TRUE) {
			self::$persistentBuffer[get_class($this) . $this->binary . $this->movieFile] = $output;
		}
	}
}

?>