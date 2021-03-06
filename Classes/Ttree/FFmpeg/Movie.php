<?php
namespace Ttree\FFmpeg;

/*                                                                        *
 * This script belongs to the FLOW3 package "Ttree.FFmpeg".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Media\Domain\Model\Image;

/**
 * @api
 */
class Movie implements \Serializable {

	protected static $REGEX_DURATION = '/Duration: ([0-9]{2}):([0-9]{2}):([0-9]{2})(\.([0-9]+))?/';

	protected static $REGEX_FRAME_RATE = '/([0-9\.]+\sfps,\s)?([0-9\.]+)\stbr/';

	protected static $REGEX_COMMENT = '/comment\s*(:|=)\s*(.+)/i';

	protected static $REGEX_TITLE = '/title\s*(:|=)\s*(.+)/i';

	protected static $REGEX_ARTIST = '/(artist|author)\s*(:|=)\s*(.+)/i';

	protected static $REGEX_COPYRIGHT = '/copyright\s*(:|=)\s*(.+)/i';

	protected static $REGEX_GENRE = '/genre\s*(:|=)\s*(.+)/i';

	protected static $REGEX_TRACK_NUMBER = '/track\s*(:|=)\s*(.+)/i';

	protected static $REGEX_YEAR = '/year\s*(:|=)\s*(.+)/i';

	protected static $REGEX_FRAME_WH = '/Video:.+?([1-9][0-9]*)x([1-9][0-9]*)/';

	protected static $REGEX_PIXEL_FORMAT = '/Video: [^,]+, ([^,]+)/';

	protected static $REGEX_BITRATE = '/bitrate: ([0-9]+) kb\/s/';

	protected static $REGEX_VIDEO_BITRATE = '/Video:.+?([0-9]+) kb\/s/';

	protected static $REGEX_AUDIO_BITRATE = '/Audio:.+?([0-9]+) kb\/s/';

	protected static $REGEX_AUDIO_SAMPLE_RATE = '/Audio:.+?([0-9]+) Hz/';

	protected static $REGEX_VIDEO_CODEC = '/Video:\s([^,]+),/';

	protected static $REGEX_AUDIO_CODEC = '/Audio:\s([^,]+),/';

	protected static $REGEX_AUDIO_CHANNELS = '/Audio:\s[^,]+,[^,]+,([^,]+)/';

	protected static $REGEX_HAS_AUDIO = '/Stream.+Audio/';

	protected static $REGEX_HAS_VIDEO = '/Stream.+Video/';

	protected static $REGEX_ERRORS = '/.*(Error|Permission denied|could not seek to position|Invalid pixel format|Unknown encoder|could not find codec|does not contain any stream).*/i';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Resource\ResourceManager
	 */
	protected $resourceManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Utility\Environment
	 */
	protected $environment;

	/**
	 * FFmpeg binary
	 *
	 * @var string
	 */
	protected $ffmpegBinary;

	/**
	 * Output provider
	 *
	 * @var OutputProvider
	 */
	protected $provider;

	/**
	 * Movie file path
	 *
	 * @var string
	 */
	protected $movieFile;

	/**
	 * provider output
	 *
	 * @var string
	 */
	protected $output;

	/**
	 * Movie duration in seconds
	 *
	 * @var float
	 */
	protected $duration;

	/**
	 * Current frame index
	 *
	 * @var int
	 */

	protected $frameCount;

	/**
	 * Movie frame rate
	 *
	 * @var float
	 */

	protected $frameRate;

	/**
	 * Comment ID3 field
	 *
	 * @var string
	 */
	protected $comment;

	/**
	 * Title ID3 field
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Author ID3 field
	 *
	 * @var string
	 */
	protected $artist;

	/**
	 * Copyright ID3 field
	 *
	 * @var string
	 */
	protected $copyright;

	/**
	 * Genre ID3 field
	 *
	 * @var string
	 */
	protected $genre;

	/**
	 * Track ID3 field
	 *
	 * @var int
	 */
	protected $trackNumber;

	/**
	 * Year ID3 field
	 *
	 * @var int
	 */
	protected $year;

	/**
	 * Movie frame height
	 *
	 * @var int
	 */
	protected $frameHeight;

	/**
	 * Movie frame width
	 *
	 * @var int
	 */
	protected $frameWidth;

	/**
	 * Movie pixel format
	 *
	 * @var string
	 */
	protected $pixelFormat;

	/**
	 * Movie bit rate combined with audio bit rate
	 *
	 * @var int
	 */
	protected $bitRate;

	/**
	 * Movie video stream bit rate
	 *
	 * @var int
	 */
	protected $videoBitRate;

	/**
	 * Movie audio stream bit rate
	 *
	 * @var int
	 */
	protected $audioBitRate;

	/**
	 * Audio sample rate
	 *
	 * @var int
	 */
	protected $audioSampleRate;

	/**
	 * Current frame number
	 *
	 * @var int
	 */
	protected $frameNumber;

	/**
	 * Movie video cocec
	 *
	 * @var string
	 */
	protected $videoCodec;

	/**
	 * Movie audio coded
	 *
	 * @var string
	 */
	protected $audioCodec;

	/**
	 * Movie audio channels
	 *
	 * @var int
	 */
	protected $audioChannels;

	/**
	 * @var int
	 */
	protected $fileSize;

	/**
	 * @param $moviePath
	 * @param OutputProvider\OutputProviderInterface $outputProvider
	 * @param string $ffmpegBinary
	 */
	public function __construct($moviePath, OutputProvider\OutputProviderInterface $outputProvider = NULL, $ffmpegBinary = '/usr/local/bin/ffmpeg') {
		$this->movieFile = $moviePath;
		$this->frameNumber = 0;
		$this->ffmpegBinary = $ffmpegBinary;
		if ($outputProvider === NULL) {
			$outputProvider = new OutputProvider\FFmpegOutputProvider($ffmpegBinary);
		}
		$this->setProvider($outputProvider);
	}

	/**
	 * Getting current provider implementation
	 *
	 * @return OutputProvider
	 */
	public function getProvider() {
		return $this->provider;
	}

	/**
	 * Setting provider implementation
	 *
	 * @param OutputProvider\OutputProviderInterface $outputProvider
	 */
	public function setProvider(OutputProvider\OutputProviderInterface $outputProvider) {
		$this->provider = $outputProvider;
		$this->provider->setMovieFile($this->movieFile);
		if (@is_file($this->movieFile)) {
			$this->fileSize = filesize($this->movieFile);
		}
		$this->output = $this->provider->getOutput();
	}

	/**
	 * Return the comment field from the movie or audio file.
	 *
	 * @return string
	 */
	public function getComment() {
		if ($this->comment === NULL) {
			$match = array();
			preg_match(self::$REGEX_COMMENT, $this->output, $match);
			$this->comment = (array_key_exists(2, $match)) ? trim($match[2]) : '';
		}

		return $this->comment;
	}

	/**
	 * Return the title field from the movie or audio file.
	 *
	 * @return string
	 */
	public function getTitle() {
		if ($this->title === NULL) {
			$match = array();
			preg_match(self::$REGEX_TITLE, $this->output, $match);
			$this->title = (array_key_exists(2, $match)) ? trim($match[2]) : '';
		}

		return $this->title;
	}

	/**
	 * Return the author field from the movie or the artist ID3 field from an mp3 file.
	 *
	 * @return string
	 */
	public function getAuthor() {
		return $this->getArtist();
	}

	/**
	 * Return the author field from the movie or the artist ID3 field from an mp3 file; alias $movie->getArtist()
	 *
	 * @return string
	 */
	public function getArtist() {
		if ($this->artist === NULL) {
			$match = array();
			preg_match(self::$REGEX_ARTIST, $this->output, $match);
			$this->artist = (array_key_exists(3, $match)) ? trim($match[3]) : '';
		}

		return $this->artist;
	}

	/**
	 * Return the copyright field from the movie or audio file.
	 *
	 * @return string
	 */
	public function getCopyright() {
		if ($this->copyright === NULL) {
			$match = array();
			preg_match(self::$REGEX_COPYRIGHT, $this->output, $match);
			$this->copyright = (array_key_exists(2, $match)) ? trim($match[2]) : '';
		}

		return $this->copyright;
	}

	/**
	 * Return the genre ID3 field from an mp3 file.
	 *
	 * @return string
	 */
	public function getGenre() {
		if ($this->genre === NULL) {
			$match = array();
			preg_match(self::$REGEX_GENRE, $this->output, $match);
			$this->genre = (array_key_exists(2, $match)) ? trim($match[2]) : '';
		}

		return $this->genre;
	}

	/**
	 * Return the track ID3 field from an mp3 file.
	 *
	 * @return int
	 */
	public function getTrackNumber() {
		if ($this->trackNumber === NULL) {
			$match = array();
			preg_match(self::$REGEX_TRACK_NUMBER, $this->output, $match);
			$this->trackNumber = (int)((array_key_exists(2, $match)) ? $match[2] : 0);
		}

		return $this->trackNumber;
	}

	/**
	 * Return the year ID3 field from an mp3 file.
	 *
	 * @return int
	 */
	public function getYear() {
		if ($this->year === NULL) {
			$match = array();
			preg_match(self::$REGEX_YEAR, $this->output, $match);
			$this->year = (int)((array_key_exists(2, $match)) ? $match[2] : 0);
		}

		return $this->year;
	}

	/**
	 * Return the width of the movie in pixels.
	 *
	 * @return int
	 */
	public function getFrameWidth() {
		if ($this->frameWidth === NULL) {
			$this->getFrameHeight();
		}

		return $this->frameWidth;
	}

	/**
	 * Return the height of the movie in pixels.
	 *
	 * @return int
	 */
	public function getFrameHeight() {
		if ($this->frameHeight == NULL) {
			$match = array();
			preg_match(self::$REGEX_FRAME_WH, $this->output, $match);
			if (array_key_exists(1, $match) && array_key_exists(2, $match)) {
				$this->frameWidth = (int)$match[1];
				$this->frameHeight = (int)$match[2];
			} else {
				$this->frameWidth = 0;
				$this->frameHeight = 0;
			}
		}

		return $this->frameHeight;
	}

	/**
	 * Return the pixel format of the movie.
	 *
	 * @return string
	 */
	public function getPixelFormat() {
		if ($this->pixelFormat === NULL) {
			$match = array();
			preg_match(self::$REGEX_PIXEL_FORMAT, $this->output, $match);
			$this->pixelFormat = (array_key_exists(1, $match)) ? trim($match[1]) : '';
		}

		return $this->pixelFormat;
	}

	/**
	 * Return the bit rate of the movie or audio file in bits per second.
	 *
	 * @return int
	 */
	public function getBitRate() {
		if ($this->bitRate === NULL) {
			$match = array();
			preg_match(self::$REGEX_BITRATE, $this->output, $match);
			$this->bitRate = (int)((array_key_exists(1, $match)) ? ($match[1] * 1000) : 0);
		}

		return $this->bitRate;
	}

	/**
	 * Return the bit rate of the video in bits per second.
	 *
	 * NOTE: This only works for files with constant bit rate.
	 *
	 * @return int
	 */
	public function getVideoBitRate() {
		if ($this->videoBitRate === NULL) {
			$match = array();
			preg_match(self::$REGEX_VIDEO_BITRATE, $this->output, $match);
			$this->videoBitRate = (int)((array_key_exists(1, $match)) ? ($match[1] * 1000) : 0);
		}

		return $this->videoBitRate;
	}

	/**
	 * Return the audio bit rate of the media file in bits per second.
	 *
	 * @return int
	 */
	public function getAudioBitRate() {
		if ($this->audioBitRate === NULL) {
			$match = array();
			preg_match(self::$REGEX_AUDIO_BITRATE, $this->output, $match);
			$this->audioBitRate = (int)((array_key_exists(1, $match)) ? ($match[1] * 1000) : 0);
		}

		return $this->audioBitRate;
	}

	/**
	 * Return the audio sample rate of the media file in bits per second.
	 *
	 * @return int
	 */
	public function getAudioSampleRate() {
		if ($this->audioSampleRate === NULL) {
			$match = array();
			preg_match(self::$REGEX_AUDIO_SAMPLE_RATE, $this->output, $match);
			$this->audioSampleRate = (int)((array_key_exists(1, $match)) ? $match[1] : 0);
		}

		return $this->audioSampleRate;
	}

	/**
	 * Return the current frame index.
	 *
	 * @return int
	 */
	public function getFrameNumber() {
		return ($this->frameNumber == 0) ? 1 : $this->frameNumber;
	}

	/**
	 * Return the name of the video codec used to encode this movie as a string.
	 *
	 * @return string
	 */
	public function getVideoCodec() {
		if ($this->videoCodec === NULL) {
			$match = array();
			preg_match(self::$REGEX_VIDEO_CODEC, $this->output, $match);
			$this->videoCodec = (array_key_exists(1, $match)) ? trim($match[1]) : '';
		}

		return $this->videoCodec;
	}

	/**
	 * Return the name of the audio codec used to encode this movie as a string.
	 *
	 * @return string
	 */
	public function getAudioCodec() {
		if ($this->audioCodec === NULL) {
			$match = array();
			preg_match(self::$REGEX_AUDIO_CODEC, $this->output, $match);
			$this->audioCodec = (array_key_exists(1, $match)) ? trim($match[1]) : '';
		}

		return $this->audioCodec;
	}

	/**
	 * Return the number of audio channels in this movie as an integer.
	 *
	 * @return int
	 */
	public function getAudioChannels() {
		if ($this->audioChannels === NULL) {
			$match = array();
			preg_match(self::$REGEX_AUDIO_CHANNELS, $this->output, $match);
			if (array_key_exists(1, $match)) {
				switch (trim($match[1])) {
					case 'mono':
						$this->audioChannels = 1;
						break;
					case 'stereo':
						$this->audioChannels = 2;
						break;
					case '5.1':
						$this->audioChannels = 6;
						break;
					case '5:1':
						$this->audioChannels = 6;
						break;
					default:
						$this->audioChannels = (int)$match[1];
				}
			} else {
				$this->audioChannels = 0;
			}
		}

		return $this->audioChannels;
	}

	/**
	 * Return boolean value indicating whether the movie has an audio stream.
	 *
	 * @return boolean
	 */
	public function hasAudio() {
		return (boolean)preg_match(self::$REGEX_HAS_AUDIO, $this->output);
	}

	/**
	 * Return boolean value indicating whether the movie has a video stream.
	 *
	 * @return boolean
	 */
	public function hasVideo() {
		return (boolean)preg_match(self::$REGEX_HAS_VIDEO, $this->output);
	}

	/**
	 * Returns the next key frame from the movie as an Frame object. Returns false if the frame was not found.
	 *
	 * @return Frame|boolean
	 */
	public function getNextKeyFrame() {
		return $this->getFrame();
	}

	/**
	 * Returns a frame from the movie as an Frame object. Returns false if the frame was not found.
	 *
	 * @param int $framenumber
	 * @param int $height
	 * @param int $width
	 * @param int $quality
	 * @return Image
	 * @throws Exception\InvalidArgumentException
	 */
	public function getFrame($framenumber = NULL, $height = NULL, $width = NULL, $quality = NULL) {
		$framePos = ($framenumber === NULL) ? $this->frameNumber : (((int)$framenumber) - 1);

		// Frame position out of range
		if (!is_numeric($framePos) || $framePos < 0 || $framePos > $this->getFrameCount()) {
			throw new Exception\InvalidArgumentException('Invalid frame number', 1359623542);
		}

		$frameTime = round((($framePos / $this->getFrameCount()) * $this->getDuration()), 4);

		$frame = $this->getFrameAtTime($frameTime, $height, $width, $quality);

		// Increment internal frame number
		if ($framenumber === NULL) {
			++$this->frameNumber;
		}

		return $frame;
	}

	/**
	 * Return the number of frames in a movie or audio file.
	 *
	 * @return int
	 */
	public function getFrameCount() {
		if ($this->frameCount === NULL) {
			$this->frameCount = (int)($this->getDuration() * $this->getFrameRate());
		}

		return $this->frameCount;
	}

	/**
	 * Return the duration of a movie or audio file in seconds.
	 *
	 * @return float movie duration in seconds
	 */
	public function getDuration() {
		if ($this->duration === NULL) {
			$match = array();
			preg_match(self::$REGEX_DURATION, $this->output, $match);
			if (array_key_exists(1, $match) && array_key_exists(2, $match) && array_key_exists(3, $match)) {
				$hours = (int)$match[1];
				$minutes = (int)$match[2];
				$seconds = (int)$match[3];
				$fractions = (float)((array_key_exists(5, $match)) ? "0.$match[5]" : 0.0);

				$this->duration = (($hours * (3600)) + ($minutes * 60) + $seconds + $fractions);
			} else {
				$this->duration = 0.0;
			}

			return $this->duration;
		}

		return $this->duration;
	}

	/**
	 * Return the frame rate of a movie in fps.
	 *
	 * @return float
	 */
	public function getFrameRate() {
		if ($this->frameRate === NULL) {
			$match = array();
			preg_match(self::$REGEX_FRAME_RATE, $this->output, $match);
			$this->frameRate = (float)((array_key_exists(1, $match)) ? $match[1] : 0.0);
		}

		return $this->frameRate;
	}

	/**
	 * Returns a frame from the movie as an Frame object. Returns false if the frame was not found.
	 *
	 * @param float $seconds
	 * @param int $width
	 * @param int $height
	 * @param int $quality
	 * @param int $frameFilePath
	 * @param int $output
	 * @return Image
	 * @throws Exception
	 * @throws Exception\RuntimeException
	 */
	public function getFrameAtTime($seconds = NULL, $width = NULL, $height = NULL, $quality = NULL, $frameFilePath = NULL, &$output = NULL) {
		// Set frame position for frame extraction
		$frameTime = ($seconds === NULL) ? 0 : $seconds;

		// time out of range
		if (!is_numeric($frameTime) || $frameTime < 0 || $frameTime > $this->getDuration()) {
			throw(new Exception\RuntimeException('Frame time is not in range ' . $frameTime . '/' . $this->getDuration() . ' ' . $this->getFilename()));
		}

		if (is_numeric($height) && is_numeric($width)) {
			$imageSize = ' -s ' . $width . 'x' . $height;
		} else {
			$imageSize = '';
		}

		if (is_numeric($quality)) {
			$quality = ' -qscale ' . $quality;
		} else {
			$quality = '';
		}

		$deleteTmp = FALSE;
		if ($frameFilePath === NULL) {
			$frameFilePath = $this->environment->getPathToTemporaryDirectory() . uniqid('frame', TRUE) . '.jpg';
			$deleteTmp = TRUE;
		}

		$output = array();

		exec(implode(' ', array(
			$this->ffmpegBinary,
			'-i ' . escapeshellarg($this->movieFile),
			'-f image2',
			'-ss ' . $frameTime,
			'-vframes 1',
			$imageSize,
			$quality,
			escapeshellarg($frameFilePath),
			'2>&1',
		)), $output, $retVar);
		$output = join(PHP_EOL, $output);

		// Cannot write frame to the data storage
		if (!file_exists($frameFilePath)) {
			// Find error in output
			preg_match(self::$REGEX_ERRORS, $output, $errors);
			if ($errors) {
				throw new Exception\RuntimeException($errors[0], 1359623669);
			}
			// Default file not found error
			throw new Exception('TMP image not found/written ' . $frameFilePath);
		}

		// Create gdimage and delete temporary image
		$resource = $this->resourceManager->importResource($frameFilePath);
		$frame = new Image($resource);
		if ($deleteTmp && is_writable($frameFilePath)) {
			unlink($frameFilePath);
		}

		return $frame;
	}

	/**
	 * Return the path and name of the movie file or audio file.
	 *
	 * @return string
	 */
	public function getFilename() {
		return $this->movieFile;
	}

	public function __clone() {
		$this->provider = clone $this->provider;
	}

	/**
	 * @return int
	 */
	public function getFileSize() {
		return $this->fileSize;
	}

	/**
	 * @param int $fileSize
	 */
	public function setFileSize($fileSize) {
		$this->fileSize = $fileSize;
	}

	public function serialize() {
		$data = serialize(array(
			$this->ffmpegBinary,
			$this->movieFile,
			$this->output,
			$this->frameNumber,
			$this->provider
		));

		return $data;
	}

	public function unserialize($serialized) {
		list($this->ffmpegBinary,
			$this->movieFile,
			$this->output,
			$this->frameNumber,
			$this->provider
			) = unserialize($serialized);
	}
}

?>