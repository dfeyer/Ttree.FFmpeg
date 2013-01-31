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
use Ttree\FFmpeg\Exception\BinaryNotFoundException;
use Ttree\FFmpeg\Exception\InvalidArgumentException;

class FFprobeOutputProvider extends AbstractOutputProvider {

	protected static $EX_CODE_NO_FFPROBE = 334563;

	/**
	 * Constructor
	 *
	 * @param string $ffprobeBinary path to ffprobe executable
	 * @param boolean $persistent   persistent functionality on/off
	 */
	public function __construct($ffprobeBinary = 'ffprobe', $persistent = FALSE) {
		parent::__construct($ffprobeBinary, $persistent);
	}

	/**
	 * @return string
	 * @throws \Ttree\FFmpeg\Exception\BinaryNotFoundException
	 * @throws \Ttree\FFmpeg\Exception\InvalidArgumentException
	 */
	public function getOutput() {
		// Persistent opening
		if ($this->persistent == TRUE && array_key_exists(get_class($this) . $this->binary . $this->movieFile, self::$persistentBuffer)) {
			return self::$persistentBuffer[get_class($this) . $this->binary . $this->movieFile];
		}

		// File doesn't exist
		if (!file_exists($this->movieFile)) {
			throw new InvalidArgumentException('Movie file not found', self::$EX_CODE_FILE_NOT_FOUND);
		}

		// Get information about file from ffprobe
		$output = array();

		exec($this->binary . ' ' . escapeshellarg($this->movieFile) . ' 2>&1', $output, $retVar);
		$output = join(PHP_EOL, $output);

		// ffprobe installed
		if (!preg_match('/FFprobe version/i', $output)) {
			throw new BinaryNotFoundException('FFprobe is not installed on host server', self::$EX_CODE_NO_FFPROBE);
		}

		// Storing persistent opening
		if ($this->persistent == TRUE) {
			self::$persistentBuffer[get_class($this) . $this->binary . $this->movieFile] = $output;
		}

		return $output;
	}
}

?>