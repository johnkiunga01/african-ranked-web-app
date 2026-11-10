<?php

namespace App\OAuth;

use DateTimeImmutable;
use Laravel\Passport\Bridge\AccessToken;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\CryptKeyInterface;

class EditorialAccessToken extends AccessToken
{
    private CryptKeyInterface $editorialSigningKey;

    public function setPrivateKey(#[\SensitiveParameter] CryptKeyInterface $privateKey): void
    {
        parent::setPrivateKey($privateKey);
        $this->editorialSigningKey = $privateKey;
    }

    public function toString(): string
    {
        $configuration = Configuration::forAsymmetricSigner(new Sha256, InMemory::plainText($this->editorialSigningKey->getKeyContents(), $this->editorialSigningKey->getPassPhrase() ?? ''), InMemory::plainText('unused'));

        return $configuration->builder()->issuedBy(url('/'))
            ->permittedFor($this->getClient()->getIdentifier(), url('/api/editorial/mcp'))
            ->identifiedBy($this->getIdentifier())->issuedAt(new DateTimeImmutable)
            ->canOnlyBeUsedAfter(new DateTimeImmutable)->expiresAt($this->getExpiryDateTime())
            ->relatedTo($this->getUserIdentifier() ?? $this->getClient()->getIdentifier())
            ->withClaim('scopes', $this->getScopes())
            ->getToken($configuration->signer(), $configuration->signingKey())->toString();
    }
}
