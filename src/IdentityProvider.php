<?php

namespace OWCSignicatOpenID;

use JsonSerializable;

class IdentityProvider implements JsonSerializable
{
	protected string $slug;
	protected string $name;
	protected array $mapping;

	protected string $userDataClass;

	public function __construct(array $data )
	{
		$class_vars = get_class_vars( static::class );

		$data = wp_parse_args( $data, $class_vars );
		$data = wp_array_slice_assoc( $data, array_keys( $class_vars ) );

		foreach ($data as $key => $value) {
			$this->$key = $value;
		}
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
		return sprintf( 'idp_scoping:%s', $this->slug );
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
