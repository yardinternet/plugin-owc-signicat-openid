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

/**
 * View class.
 *
 * @since 0.0.1
 */
class View
{
	/**
	 * Path to template directory.
	 *
	 * @var string
	 */
	protected string $template_directory = '';

	/**
	 * Variables given to templates.
	 *
	 * @var array
	 */
	protected array $vars = array();

	/**
	 * Associative array of variables that will be accessible from the template.
	 *
	 * @var array
	 */
	protected array $bindings = array();

	public function __construct( $template_directory = null )
	{
		$this->template_directory = OWC_SIGNICAT_OPENID_DIR_PATH . 'resources/views/';

		// Check here whether this directory really exists
		if (null !== $template_directory) {
			$this->template_directory = $template_directory;
		}
	}

	public function exists(string $template_file = '' ): bool
	{
		return is_file( $this->template_directory . $template_file );
	}

	/**
	 * Render the view.
	 */
	public function render(string $template_file = '', array $vars = array() ): string
	{
		if ( ! is_file( $this->template_directory . $template_file )) {
			return '';
		}

		$this->bindAll( $vars );
		ob_start();
		include $this->template_directory . $template_file;
		$data = trim( ob_get_clean() );
		return $this->parseTemplate( $data, $this->bindings );
	}

	/**
	 * Search and replace {{ variables }}.
	 *
	 * @param string $template
	 * @param array  $bindings
	 * @return string
	 */
	protected function parseTemplate(string $template, array $bindings = array() ): string
	{
		return preg_replace_callback(
			'#{{\s?(.*?)\s?}}#',
			function ( $match ) use ( $bindings ) {
				$match[1] = trim( $match[1], '' );
				return $bindings[ $match[1] ] ?? '';
			},
			$template
		);
	}

	/**
	 * Bind a single variable that will be accessible when the view is rendered.
	 *
	 * @param string $parameter
	 * @param [type] $value
	 * @return void
	 */
	public function bind( string $parameter, $value )
	{
		$this->bindings[ $parameter ] = $value;
	}

	/**
	 * Bind multiple parameters at once.
	 *
	 * @see View:bind()
	 * @param array $bindings
	 */
	public function bindAll( array $bindings )
	{
		foreach ($bindings as $parameter => $value) {
			$this->bind( $parameter, $value );
		}
	}

	public function __set($name, $value )
	{
		$this->vars[ $name ] = $value;
	}

	public function __get($name )
	{
		return $this->vars[ $name ];
	}
}
