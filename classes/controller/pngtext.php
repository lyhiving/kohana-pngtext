<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * PNGText
 *
 * @package    PNGText Kohana Module
 * @author     John Heathco <jheathco@gmail.com>
 */
class Controller_PNGText extends Controller
{
	/**
	 * Processes incoming text
	 */
	public function action_index()
	{
		$this->request->headers['Content-type'] = 'image/png';

		// Grab text and styles
		$text   = arr::get($_GET, 'text');
		$styles = $_GET;

		$hover = FALSE;

		try
		{
			// Create image
			$img = new PNGText($text, $styles);

			foreach ($styles as $key => $value)
			{
				if (substr($key, 0, 6) == 'hover-')
				{
					// Grab hover associated styles and override existing styles
					$hover = TRUE;
					$styles[substr($key, 6)] = $value;
				}
			}

			if ($hover)
			{
				// Create new hover image and stack it
				$hover = new PNGText(arr::get($_GET, 'text'), $_GET);
				$img->stack($hover);
			}

			echo $img->draw();
		}
		catch (Exception $e)
		{
			if (Kohana::config('pngtext.debug'))
			{
				// Dump error message in an image form
				$img = imagecreatetruecolor(strlen($e->getMessage()) * 6, 16);

				imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
				imagestring($img, 2, 0, 0, $e->getMessage(), imagecolorallocate($img, 0, 0, 0));

				echo imagepng($img);
			}
		}
	}

	/**
	 * Dump PNGText javascript to browser
	 */
	public function action_js()
	{
		$this->request->headers['Content-type'] = 'text/javascript';

		echo file_get_contents(MODPATH.'pngtext/js/pngtext.js');
	}
}