<?php

namespace OWCSignicatOpenID;

use JsonSerializable;

class IdentityProvider implements JsonSerializable
{
	protected string $slug;
	protected string $name;
	protected array $mapping;
	protected string $scope;
	protected array $idpScopes = array();

	protected string $userDataClass;

	public function __construct(array $data )
	{
		$class_vars = get_class_vars( static::class );

		$data = wp_parse_args( $data, $class_vars );
		$data = wp_array_slice_assoc( $data, array_keys( $class_vars ) );

		foreach ($data as $key => $value) {
			$this->$key = $value;
		}

		$this->scope = sprintf( 'idp_scoping:%s', $this->slug );
	}

	public function jsonSerialize()
	{
		return array(
			'slug' => $this->slug,
			'name' => $this->name,
		);
	}

	public function getSlug(): string
	{
		return $this->slug;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getScope(): string
	{
		return $this->scope;
	}

	public function setScope(string $scope ): self
	{
		$this->scope = $scope;

		return $this;
	}

	public function getIdpScopes(): array
	{
		return $this->idpScopes;
	}

	/**
	 * Add a single IDP scope if it doesn't already exist.
	 */
	public function addIdpScope(string $idpScope ): self
	{
		if (in_array( $idpScope, $this->idpScopes, true )) {
			return $this;
		}

		$this->idpScopes[] = $idpScope;

		return $this;
	}

	/**
	 * Add multiple IDP scopes, ignoring any that already exist.
	 */
	public function addIdpScopes(array $idpScopes ): self
	{
		foreach ($idpScopes as $idpScope) {
			if (in_array( $idpScope, $this->idpScopes, true )) {
				continue;
			}

			$this->idpScopes[] = $idpScope;
		}

		return $this;
	}

	public function getLogoUrl(): string
	{
		return OWC_SIGNICAT_OPENID_PLUGIN_URL . sprintf( 'resources/img/logo-%s.svg', $this->getSlug() );
	}

	public function getMapping(): array
	{
		return $this->mapping;
	}

	public function getUserDataClass(): string
	{
		return $this->userDataClass;
	}
}
