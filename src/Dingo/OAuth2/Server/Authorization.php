<?php namespace Dingo\OAuth2\Server;

use Dingo\OAuth2\ScopeValidator;
use Dingo\OAuth2\Storage\Adapter;
use Dingo\OAuth2\Grant\GrantInterface;
use Symfony\Component\HttpFoundation\Request;

class Authorization {

	protected $storage;

	protected $request;

	protected $scopeValidator;

	protected $grants = [];

	public function __construct(Adapter $storage, Request $request = null)
	{
		$this->storage = $storage;
		$this->request = $request ?: Request::createFromGlobals();
	}

	public function registerGrant(GrantInterface $grant)
	{
		$key = $grant->getGrantIdentifier();

		// Get an instance of the scope validator and set it on the grant so
		// that scopes can be validated when authorizing a request.
		$scopeValidator = $this->getScopeValidator();

		$grant->setScopeValidator($scopeValidator);

		// Grants will often need to interact with the request instance and
		// the storage adapter so we'll set these on the grant.
		$grant->setRequest($this->request) and $grant->setStorage($this->storage);

		$this->grants[$key] = $grant;

		return $this;
	}

	public function createToken()
	{
		if ( ! $this->request->isMethod('post'))
		{
			throw new \Exception('invalid_request');
		}

		if ( ! $grant = $this->request->request->get('grant_type'))
		{
			throw new \Exception('invalid_request');
		}

		if ( ! isset($this->grants[$grant]))
		{
			throw new \Exception('unsupported_grant_type');
		}

		return $this->grants[$grant]->execute();
	}

	public function getStorage()
	{
		return $this->storage;
	}

	public function getRequest()
	{
		return $this->request;
	}

	public function getScopeValidator()
	{
		if ( ! isset($this->scopeValidator))
		{
			$this->scopeValidator = new ScopeValidator($this->request, $this->storage->get('scope'));
		}

		return $this->scopeValidator;
	}

	public function setScopeValidator(ScopeValidator $scopeValidator)
	{
		$this->scopeValidator = $scopeValidator;

		return $this;
	}


}