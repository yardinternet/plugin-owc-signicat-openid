<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\Services;

use Exception;
use OWCSignicatOpenID\Interfaces\Services\ViewServiceInterface;

class ViewService extends Service implements ViewServiceInterface
{
	protected string $viewPath = OWC_SIGNICAT_OPENID_DIR_PATH . 'resources/views/';

	public function render(string $template, array $data = array() ): string
	{
		$template_file = $this->viewPath . $template . '.php';

		if ( ! file_exists( $template_file )) {
			throw new Exception( 'Template file not found: ' . esc_html( $template_file ) );
		}

		extract( $data );

		ob_start();
		require $template_file;
		$output = ob_get_clean();

		return $output;
	}
}
