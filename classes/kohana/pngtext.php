<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * PNGText
 *
 * @package    PNGText Kohana Module
 * @author     John Heathco <jheathco@gmail.com>
 */
class Kohana_PNGText
{
	protected $_text;
	protected $_styles;
	protected $_font_file;
	protected $_lines;
	protected $_max_width;
	protected $_img;
	protected $_line_widths;

	public function __construct($text, $styles = array())
	{
		// Load text and CSS styles
		$this->_text   = $text;
		$this->_styles = $styles;

		if (empty($this->_styles['font-family']))
		{
			throw new Exception('No font family specified');
		}

		if (empty($this->_styles['font-size']))
		{
			// Default font size
			$this->_styles['font-size'] = 12;
		}
		else
		{
			// Convert from Ypx to int
			$this->_styles['font-size'] = intval($this->_styles['font-size']);
		}

		if (empty($this->_styles['line-height']) OR $this->_styles['line-height'] == 'normal')
		{
			// Default line-height
			$this->_styles['line-height'] = $this->_styles['font-size'] * 1.4;
		}
		else
		{
			// Convert form Ypx to int
			$this->_styles['line-height'] = intval($this->_styles['line-height']);
		}

		if (empty($this->_styles['color']))
		{
			// Use pure black
			$this->_styles['color'] = array(0, 0, 0, 0);
		}
		else
		{
			// Scan rgb(r, g, b)
			$this->_styles['color'] = sscanf($this->_styles['color'], 'rgb(%d, %d, %d)');
			array_push($this->_styles['color'], 0);
		}

		if (empty($this->_styles['width']))
		{
			// Some arbitrary huge width we won't need to worry about
			$this->_styles['width'] = 1000000;
		}
		else
		{
			// Restrain to given width
			$this->_styles['width'] = intval($this->_styles['width']);
		}

		if (empty($this->_styles['text-align']))
		{
			// Default to left align
			$this->_styles['text-align'] = 'left';
		}

		$this->_font_file = MODPATH.'pngtext/fonts/'.Kohana::config('pngtext.fonts.'.$styles['font-family']);

		if ( ! is_file($this->_font_file))
		{
			throw new Exception('Cannot find font file for \''.$styles['font-family'].'\'');
		}

		$this->_process();
		$this->render();
	}

	/**
	 * Processes the text into lines that fit the given width
	 */
	public function _process()
	{
		// Convert <br> to newlines
		$text = preg_replace('/<br>/is', "\n", $this->_text);

		// Convert HTML entities
		$text = html_entity_decode($text);

		$lines = explode("\n", $text);

		// Max width of all text lines
		$this->_max_width = 0;

		$this->_line_widths = array();

		for ($i = 0; $i < count($lines); $i++)
		{
			// See if the line width is greater than our allowed

			while (($width = $this->_width($lines[$i])) > $this->_styles['width'])
			{
				// Trim any whitespace around line
				$lines[$i] = trim($lines[$i]);

				$words = explode(' ', $lines[$i]);

				if (count($words) == 1)
				{
					// Can't split up a single-word line
					break;
				}

				// Trailing word gets bumped to next line and we try again
				$trailing = array_pop($words);

				$lines[$i] = implode(' ', $words);

				if (isset($lines[$i + 1]))
				{
					// Trailing word becomes prefix to next line
					$lines[$i + 1] = $trailing.' '.$lines[$i + 1];
				}
				else
				{
					// Add a completely new line
					$lines[$i + 1] = $trailing;
				}
			}

			// Check if we have a new max width
			$this->_max_width = max($width + 1, $this->_max_width);

			$this->_line_widths[$i] = $width;
		}

		$this->_lines = $lines;
	}

	/**
	 * Renders the image
	 */
	public function render()
	{
		$this->_img = imagecreatetruecolor($this->_max_width, count($this->_lines) * $this->_styles['line-height']);

		imagesavealpha($this->_img, TRUE);
		imagefill($this->_img, 0, 0, $this->_color(0, 0, 0, 127));

		// Divide initial y coordinate so line-height balances equally at top and bottom
		$y = $this->_styles['font-size'] + (int) ($this->_styles['line-height'] - $this->_styles['font-size']) / 2;

		list($r, $g, $b, $alpha) = $this->_styles['color'];

		for ($i = 0; $i < count($this->_lines); $i++)
		{
			switch ($this->_styles['text-align'])
			{
				case 'center':
					$x = (int) ($this->_max_width - $this->_line_widths[$i]) / 2;
					break;
				case 'right':
					$x = $this->_max_width - $this->_line_widths[$i];
					break;
				default:
					$x = 0;
					break;
			}

			// Draw this line of text and go to next line
			imagettftext($this->_img, $this->_styles['font-size'], 0, $x, $y, $this->_color($r, $g, $b, $alpha), $this->_font_file, $this->_lines[$i]);
			$y += $this->_styles['line-height'];
		}
	}

	/**
	 * Draws the image, returning pixel data
	 *
	 * @return string  pixel data
	 */
	public function draw()
	{
		if ( ! $this->_img)
		{
			$this->render();
		}

		return imagepng($this->_img);
	}

	/**
	 * Allocates color with our image
	 *
	 * @param  int  r
	 * @param  int  g
	 * @param  int  b
	 * @param  int  alpha
	 * @return int  color
	 */
	protected function _color($r, $g, $b, $alpha)
	{
		return imagecolorallocatealpha($this->_img, $r, $g, $b, $alpha);
	}

	/**
	 * Determines the width of the given string in font
	 *
	 * @param  string  text
	 * @return int     width
	 */
	protected function _width($text)
	{
		$box = imagettfbbox($this->_styles['font-size'], 0, $this->_font_file, $text);
		return $box[2];
	}

	/**
	 * Stacks another image below and merges the result
	 *
	 * @param  PNGText  image to stack below
	 */
	public function stack(PNGText $stack)
	{
		$width  = imagesx($this->_img);
		$height = imagesy($this->_img);

		$new = imagecreatetruecolor($width, $height * 2);

		imagesavealpha($new, TRUE);

		imagefill($new, 0, 0, $this->_color(0, 0, 0, 127));

		imagecopy($new, $this->_img, 0, 0, 0, 0, $width, $height);
		imagecopy($new, $stack->_img, 0, $height, 0, 0, $width, $height);

		$this->_img = $new;
	}
}