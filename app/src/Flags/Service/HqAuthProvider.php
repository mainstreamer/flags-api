<?php

namespace App\Flags\Service;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\GenericResourceOwner;
use League\OAuth2\Client\Token\AccessToken;

class HqAuthProvider extends GenericProvider
{
    public function __construct(array $options = [], array $collaborators = [])
    {
        $options['urlAuthorize'] = $options['domain'].'/oauth2/authorize';
        $options['urlAccessToken'] = $options['domain'].'/oauth2/token';
        $options['urlResourceOwnerDetails'] = $options['domain'].'/oauth2/userinfo';

        parent::__construct($options, $collaborators);
    }

    protected function getScopeSeparator(): string
    {
        return ' '; // Return space instead of comma
    }

    protected function createResourceOwner(array $response, AccessToken $token): GenericResourceOwner
    {
        return new GenericResourceOwner($response, 'sub'); // Use 'sub' instead of 'id'
    }
}
