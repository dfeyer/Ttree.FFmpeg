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

class FFmpegOutputProvider extends AbstractOutputProvider {

	protected static $EX_CODE_NO_FFMPEG = 334560;

	/**
	 * Constructor
	 *
	 * @param string $ffmpegBinary path to ffmpeg executable
	 * @param boolean $persistent  persistent functionality on/off
	 */
	public function __construct($ffmpegBinary = '/usr/local/bin/ffmpeg', $persistent = FALSE) {
		parent::__construct($ffmpegBinary, $persistent);
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

		// Get information about file from ffmpeg
		$output = array();

		exec($this->binary . ' -i ' . escapeshellarg($this->movieFile) . ' 2>&1', $output, $retVar);
		$output = join(PHP_EOL, $output);

		// ffmpeg installed
		if (!preg_match('/FFmpeg version/i', $output)) {
			throw new BinaryNotFoundException('FFmpeg is not installed on host server', self::$EX_CODE_NO_FFMPEG);
		}

		// Storing persistent opening
		if ($this->persistent == TRUE) {
			self::$persistentBuffer[get_class($this) . $this->binary . $this->movieFile] = $output;
		}

		return $output;
	}
}

?>