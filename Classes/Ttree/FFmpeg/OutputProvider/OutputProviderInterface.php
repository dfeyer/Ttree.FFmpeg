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
interface OutputProviderInterface {

	/**
	 * Setting movie file path
	 *
	 * @param string $movieFile
	 */
	public function setMovieFile($movieFile);

	/**
	 * Getting parsable output
	 *
	 * @return string
	 */
	public function getOutput();

}

?>