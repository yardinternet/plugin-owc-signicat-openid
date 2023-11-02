<?php
/**
 * GravityForms eHerkenningLoginField.
 *
 * @package OWC_Signicat_OpenID
 * @author  Yard | Digital Agency
 * @since   0.0.1
 */

declare ( strict_types = 1 );

namespace OWCSignicatOpenID\GravityForms\Fields;

/**
 * Exit when accessed directly.
 */
if ( ! defined( 'ABSPATH' )) {
	exit;
}

use Aura\Session\Segment;
use OWCSignicatOpenID\DigiD;
use OWCSignicatOpenID\Foundation\Plugin;

use function OWCSignicatOpenID\Foundation\Helpers\config;
use function OWCSignicatOpenID\Foundation\Helpers\resolve;
use function OWCSignicatOpenID\Foundation\Helpers\view;

/**
 * GravityForms eHerkenningLoginField class.
 *
 * @since 0.0.1
 */
class eHerkenningLoginField extends AbstractField
{
	/** @var DigiD */
	protected DigiD $digid;

	protected Segment $session;

	/**
	 * @param object $field
	 * @param array  $value
	 */
	public function __construct(object $field, array $value, Segment $session )
	{
		parent::__construct( $field, $value );
		$this->session = $session;
	}

	/**
	 * Render the input.
	 *
	 * @return string
	 */
	public function render(): string
	{
		if ($this->is_admin || ! \rgar( $this->getInput(), 'isHidden' )) {
			if ( ! is_admin()) {
				$loggedIn = ! empty( $this->session->get( 'userInfo' ) );

				if ($loggedIn) {
					return view( 'blocks/eherkenning-logged-in.php' );
				}

				$this->session->set( 'resume_link', $this->getResumeLink() );

				if (defined( 'WP_DEBUG' ) && WP_DEBUG) {
					resolve( 'teams' )->info(
						'Set resume_link',
						array(
							'user_agent'               => $_SERVER['HTTP_USER_AGENT'] ?? '',
							'resume_link_from_session' => $this->session->get( 'resume_link' ),
							'resume_link'              => $this->getResumeLink(),
						)
					);
				}
			}

			return "{$this->getSpanField()}
                        {$this->getLabelField()}
                        {$this->getInputField()}
                    </span>";
		}

		return '';
	}

	/**
	 * Get the resume link.
	 *
	 * @return string
	 */
	protected function getResumeLink(): string
	{
		// If form is not yet created.
		if (1 > $this->field->formId) {
			return '';
		}

		if (\is_admin()) {
			return '';
		}

		add_filter(
			'gform_incomplete_submission_pre_save',
			function ($submission_json, $resume_token, $form ) {
				$submissionData              = \json_decode( $submission_json );
				$submissionData->page_number = \GFFormDisplay::get_current_page( $this->field->formId );
				$submission_json             = \json_encode( $submissionData );
				return $submission_json;
			},
			10,
			3
		);

		$resume = \GFAPI::submit_form(
			$this->field->formId,
			array(
				'gf_submitting_' . $this->field->formId => true,
				'saved_for_later'                       => true,
				'gform_save'                            => true,
			)
		);

		$resumeToken = $resume['resume_token'] ?? null;
		return sprintf( '%s?gf_token=%s', \get_permalink(), $resumeToken );
	}

	/**
	 * Get the structured label of the field.
	 *
	 * @return string
	 */
	protected function getLabelField(): string
	{
		return "";
	}

	/**
	 * Get the structured input.
	 */
	protected function getInputField(): string
	{
		$pathLogin = get_option( 'signicat_openid_path_login_settings' );

		return view(
			'blocks/eherkenning.php',
			array(
				'error'    => $this->session->getFlash( 'error' ),
				'logo'     => Plugin::getInstance()->resourceUrl( 'logo-eherkenning.svg', 'img' ),
				'link'     => is_admin() ? '' : get_site_url( null, $pathLogin ),
				'title'    => $this->getFieldTitle(),
				'subtitle' => $this->getFieldSubTitle(),
			)
		);
	}

	/**
	 * Get the input field display title.
	 *
	 * @return string
	 */
	protected function getFieldTitle(): string
	{
		return apply_filters( 'owc_gravityforms_digid_field_display_title', __( 'Login to', config( 'core.text_domain' ) ) );
	}

	/**
	 * Get the input field display subtitle.
	 *
	 * @return string
	 */
	protected function getFieldSubTitle(): string
	{
		return apply_filters( 'owc_gravityforms_digid_field_display_subtitle', get_bloginfo( 'name' ) );
	}
}
