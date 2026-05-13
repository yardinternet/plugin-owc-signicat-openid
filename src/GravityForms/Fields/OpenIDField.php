<?php

declare(strict_types=1);

namespace OWCSignicatOpenID\GravityForms\Fields;

use GFAPI;
use GFFormDisplay;
use GFFormsModel;
use GF_Field;
use OWCSignicatOpenID\ContainerManager;
use OWCSignicatOpenID\Interfaces\Services\IdentityProviderServiceInterface;
use OWCSignicatOpenID\IdentityProvider;
use OWCSignicatOpenID\Interfaces\Services\OpenIDServiceInterface;
use OWC\IdpUserData\DigiDPartnerSession;
use OWC\IdpUserData\DigiDSession;
use OWC\IdpUserData\eHerkenningPartnerSession;
use OWC\IdpUserData\eHerkenningSession;

class OpenIDField extends GF_Field
{
    protected OpenIDServiceInterface $openIDService;
    public IdentityProvider $idp;
    public string $label;
    public string $idpSlug;
	public ?string $openIdSelectedScopeValue = null;
	public ?bool $openIdIsSecondLogin = null;
    public array $selectableScopes = [];

    public function __construct($data = [])
    {
        if (isset($data['idp']) && ! is_a($data['idp'], IdentityProvider::class)) {
            unset($data['idp']);
        }

        if (empty($data['type'])) {
            $data['type'] = sprintf('owc-signicat-openid-%s', $data['idp']->getSlug());
        }

        parent::__construct($data);
    }

    public function get_form_editor_field_title()
    {
        return $this->idp->getName();
    }

    private function getLoginSlot(): string
    {
        return ($this->openIdIsSecondLogin ?? false) ? '2' : '';
    }

	public function get_field_input($form, $value = '', $entry = null)
	{
		if (isset($this->openIdSelectedScopeValue) && null !== $this->openIdSelectedScopeValue) {
			$this->idp->addIdpScope($this->openIdSelectedScopeValue);
		}

		$logoElement = sprintf(
			"<img src='%s' width='90px' height='90px' class='gform-theme__disable-reset'>",
			$this->idp->getLogoUrl(),
		);

		$userInfo = $this->openIDService->getUserInfo($this->idp, $this->getLoginSlot());

		if (($userInfo || $this->hasActiveSessionForIDP()) && ! $this->is_form_editor()) {
			$loggedInMessage = ($this->openIdIsSecondLogin ?? false)
				? 'Medeaanvrager is ingelogd'
				: 'Je bent ingelogd';

			return sprintf(
				"<div class='ginput_container ginput_container_openid'>%s<p>%s</p></div>",
				$logoElement,
				$loggedInMessage
			);
		}

		$input = $logoElement;

		if ($this->is_form_editor()) {
			// Set the field property used for the scope select in the form editor.
			$this->selectableScopes = $this->prepareScopeSelectOptions();
		}

		if (! $this->is_entry_detail() && ! $this->is_form_editor() && ! $this->hasActiveSessionForIDP()) {
			$resumeUrl = $this->getResumeUrl();
			$input = sprintf(
				"<a href='%s'>%s<div class='ginput_container_openid__text'><div>%s</div><div>%s</div></div></a>",
				esc_url($this->openIDService->getLoginUrl($this->idp, $resumeUrl, $resumeUrl, $this->idp->getIdpScopes(), $this->getLoginSlot())),
				$input,
				$this->field_title(),
				$this->field_sub_title()
			);

			$input = $this->addPossibleErrorsToInput($input);
		}

		return sprintf("<div class='ginput_container ginput_container_openid'>%s</div>", $input);
	}

    /**
     * Prepare the scope options for the select field in the form editor.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function prepareScopeSelectOptions(): array
    {
        $default = [
            'value' => '',
            'label' => __('Select a Service Index', 'owc-signicat-openid'),
        ];

        $supportedScopes = array_map(function ($scope) {
            if (strpos($scope, $this->idp->getSlug()) === false && ! $this->openIDService->isLegacyImplementation()) {
                return null; // Skip scopes that do not match the IDP's slug.
            }

            return [
                'value' => $scope,
                'label' => $scope,
            ];
        }, $this->openIDService->getScopesSupported() ?? []);

        return [$default, ...array_filter($supportedScopes)];
    }

    protected function addPossibleErrorsToInput(string $input): string
    {
        $errors = $this->openIDService->flashErrors();

        if (! count($errors)) {
            return $input;
        }

        $errorItems = $this->formatErrors($errors);

        $html = sprintf('
		<div class="alert alert-danger">
			<strong>Er zijn problemen met de inlogpoging:</strong>
			<ul>%s</ul>
		</div>', $errorItems);

        return $html . $input;
    }

    /**
     * The errors array is double nested, implode to string.
     */
    private function formatErrors(array $errors): string
    {
        return implode(
            '',
            array_map(
                fn ($errorGroup) => implode(
                    '',
                    array_map(
                        fn ($message) => sprintf('<li>%s</li>', esc_html($message)),
                        $errorGroup
                    )
                ),
                $errors
            )
        );
    }

    protected function getResumeUrl(): string
    {
        $currentPageURL = GFFormsModel::get_current_page_url(true);

		/**
		 * Reuse the current page URL, it already contains the existing token via REQUEST_URI when present.
		 * Creating a new one would overwrite GFFormsModel::$uploaded_files via set_uploaded_files() (called inside
         * process_form()) and discard previously saved file data.
		 */
        if ($this->shouldReturnCurrentPageURL()) {
            return $currentPageURL;
        }

        add_filter('gform_incomplete_submission_pre_save', function ($submissionJSON, $resumeToken, $form) {
            $submissionData = json_decode($submissionJSON);
            $submissionData->page_number = GFFormDisplay::get_current_page($this->formId);
            $submissionJSON = json_encode($submissionData);

            return $submissionJSON;
        }, 10, 3);

        $this->preserveUploadedFilesInPost();

        $resume = GFAPI::submit_form(
            $this->formId,
            [
                'gf_submitting_' . $this->formId => true,
                'saved_for_later' => true,
                'gform_save' => true,
            ]
        );

        if (is_wp_error($resume)) {
            return $currentPageURL;
        }

        $resumeToken = $resume['resume_token'] ?? null;

        if (! is_string($resumeToken) || 1 > strlen($resumeToken)) {
            return $currentPageURL;
        }

        return add_query_arg('gf_token', $resumeToken, $currentPageURL);
    }

	/**
	 * Check if a gf_token is already present in the URL, or any IDP session is active for this login slot, based on the result of this method we can
	 * reuse the current page URL as the resume URL.
	 * This avoids creating a new incomplete submission and overwriting previously saved file data in GFFormsModel::$uploaded_files.
	 *
	 * @since NEXT
	 */
	private function shouldReturnCurrentPageURL(): bool
	{
		$gfTokenPresentInURL = isset($_GET['gf_token']) && is_string($_GET['gf_token']) && strlen($_GET['gf_token']) > 0;

		return $gfTokenPresentInURL || $this->hasActiveIDPSession();
	}

	/**
	 * Preserve files uploaded on earlier pages so they survive inside the draft.
	 * set_uploaded_files() inside process_form() reads $_POST['gform_uploaded_files']; without this
	 * the read is empty and wipes the temp paths that forward navigation already stored in
	 * GFFormsModel::$uploaded_files, causing the draft to be saved with no file data.
	 *
	 * @since NEXT
	 */
	private function preserveUploadedFilesInPost(): void
	{
		$currentFiles = GFFormsModel::$uploaded_files[$this->formId] ?? [];

		if (! is_array($currentFiles) || [] === $currentFiles) {
			return;
		}

		$_POST['gform_uploaded_files'] = json_encode($currentFiles, JSON_UNESCAPED_UNICODE);
	}

    /**
     * Validates that the user (or partner, when openIdIsSecondLogin is true) has an active IDP session.
     * When multiple IDP fields are used in the same form, a field passes validation as long as any
     * supported IDP session is active for the relevant login slot.
     */
    public function validate($value, $form)
    {
        if ($this->hasActiveIDPSession()) {
            $this->failed_validation = false;

            return;
        }

        $this->failed_validation = true;
        $this->validation_message = ($this->openIdIsSecondLogin ?? false)
            ? 'De medeaanvrager is niet ingelogd'
            : 'Je bent niet ingelogd';
    }

    private function hasActiveIDPSession(): bool
    {
		$activeSessions = $this->active_idp_session_by_slot();
		$digidSession = $activeSessions['digid'] ?? false;
		$eHerkenningSession = $activeSessions['eherkenning'] ?? false;

		return $digidSession || $eHerkenningSession;
    }

	private function active_idp_session_by_slot(): array
	{
		$loginSlot = $this->getLoginSlot();

		if ($loginSlot === '2') {
			return [
				'digid' => DigiDSession::isPartnerLoggedIn() && ! is_null(DigiDPartnerSession::getUserData()),
				'eherkenning' => eHerkenningSession::isPartnerLoggedIn() && ! is_null(eHerkenningPartnerSession::getUserData()),
			];
		}

		if ($loginSlot !== '') {
			return [
				'digid' => false,
				'eherkenning' => false,
			];
		}

		return [
			'digid' => DigiDSession::isLoggedIn() && ! is_null(DigiDSession::getUserData()),
			'eherkenning' => eHerkenningSession::isLoggedIn() && ! is_null(eHerkenningSession::getUserData()),
		];
	}

	private function hasActiveSessionForIDP(): bool
	{
		$slug = $this->resolveIDP() instanceof IdentityProvider ? $this->resolveIDP()->getSlug() : '';

		if ($this->getLoginSlot() === '2') {
			return match ($slug) {
				'digid' => DigiDSession::isPartnerLoggedIn() && ! is_null(DigiDPartnerSession::getUserData()),
				'eherkenning' => eHerkenningSession::isPartnerLoggedIn() && ! is_null(eHerkenningPartnerSession::getUserData()),
				default => false,
			};
		}

		return match ($slug) {
			'digid' => DigiDSession::isLoggedIn() && ! is_null(DigiDSession::getUserData()),
			'eherkenning' => eHerkenningSession::isLoggedIn() && ! is_null(eHerkenningSession::getUserData()),
			default => false,
		};
	}

	/**
	 * Resolves the IDP from the field type (e.g. 'owc-signicat-openid-digid' → 'digid').
	 */
	private function resolveIDP(): ?IdentityProvider
	{
		if (null === $this->idp) {
			$slug = str_replace('owc-signicat-openid-', '', (string) $this->type);
			$idp_service = ContainerManager::getContainer()->get(IdentityProviderServiceInterface::class);
			$this->idp = $idp_service->get_identity_provider($slug);
		}

		return $this->idp;
	}

    public function get_value_save_entry($value, $form, $input_name, $lead_id, $lead)
    {
        if ($this->openIdIsSecondLogin ?? false) {
            return sprintf('Ingelogd medeaanvrager (%s)', $this->idp->getName());
        }

        return sprintf('Ingelogd (%s)', $this->idp->getName());
    }

    public function get_value_entry_list($value, $entry, $field_id, $columns, $form)
    {
        return $value;
    }

    public function get_value_entry_detail($value, $currency = '', $use_text = false, $format = 'html', $media = 'screen')
    {
        return $value;
    }

    public function is_value_submission_empty($formId)
    {
        return empty($this->openIDService->getUserInfo($this->idp, $this->getLoginSlot()));
    }

    public function get_value_submission($field_values, $get_from_post_global_var = true)
    {
        return $this->openIDService->getUserInfo($this->idp, $this->getLoginSlot());
    }

    public function get_form_editor_field_settings()
    {
        return [
            'label_setting',
            'label_placement_setting',
            'description_setting',
            'columns_setting', //?is dit nodig
            'conditional_logic_field_setting',
            'css_class_setting',
            'rules_setting',
            'open_id_select_scope_setting',
            'open_id_second_login_setting',
        ];
    }

    public function get_form_editor_field_icon()
    {
        return $this->idp->getLogoUrl();
    }

	/**
	 * @since 3.1.4
	 */
	protected function field_title(): string
	{
		return apply_filters('owc_signicat_openid_field_display_title', __('Login to', 'owc-signicat-openid'));
	}

	/**
	 * @since 3.1.4
	 */
	protected function field_sub_title(): string
	{
		return apply_filters('owc_signicat_openid_field_display_subtitle', get_bloginfo('name'));
	}
}
