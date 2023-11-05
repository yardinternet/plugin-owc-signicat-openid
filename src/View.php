<?php
/**
 * View class.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare ( strict_types = 1 );

namespace OWCSignicatOpenID;

use Exception;

/**
 * View class.
 *
 * @since 0.0.1
 */
class View
{
	protected $template_directory;
	protected $data = array();

	public function __construct($template_directory = null )
	{
		$this->template_directory = $template_directory ?? OWC_SIGNICAT_OPENID_DIR_PATH . 'resources/views';
	}

	public function assign($key, $value )
	{
		$this->data[ $key ] = $value;
	}

	public function render(string $template_file ): string
	{
		$template_path = $this->template_directory . '/' . $template_file;

		if ( ! file_exists( $template_path )) {
			throw new Exception( 'Template file not found: ' . $template_path );
		}

		extract( $this->data ); // Extract data variables

		ob_start();
		require $template_path;
		$output = ob_get_clean();

		return $output;
	}
}
