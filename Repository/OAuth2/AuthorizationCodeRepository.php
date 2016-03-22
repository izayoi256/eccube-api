<?php

namespace Plugin\EccubeApi\Repository\OAuth2;

use Doctrine\ORM\EntityRepository;
use Plugin\EccubeApi\Entity\OAuth2\AuthorizationCode;
use OAuth2\Storage\AuthorizationCodeInterface;
use OAuth2\OpenID\Storage\AuthorizationCodeInterface as OpenIDAuthorizationCodeInterface;

/**
 * AuthorizationCodeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @link http://bshaffer.github.io/oauth2-server-php-docs/cookbook/doctrine2/
 */
class AuthorizationCodeRepository extends EntityRepository implements AuthorizationCodeInterface, OpenIDAuthorizationCodeInterface
{
    public function getAuthorizationCode($code)
    {
        $authCode = $this->findOneBy(array('code' => $code));
        if ($authCode && $authCode->getExpires()->getTimestamp() >= time()) {
            $authCode = $authCode->toArray();
            if (is_object($authCode['client'])) {
                $authCode['client_id'] = $authCode['client']->getClientIdentifier();
            }
            if (is_object($authCode['user'])) {
                $authCode['user_id'] = $authCode['user']->getId();
            }
            $authCode['expires'] = $authCode['expires']->getTimestamp();
        }
        return $authCode;
    }

    public function setAuthorizationCode($code, $clientIdentifier, $userEmail, $redirectUri, $expires, $scope = null, $id_token = null)
    {
        $client = $this->_em->getRepository('Plugin\EccubeApi\Entity\OAuth2\Client')
            ->findOneBy(
                array('client_identifier' => $clientIdentifier)
            );
        $user = $this->_em->getRepository('Plugin\EccubeApi\Entity\OAuth2\User')
            ->findOneBy(
                array('email' => $userEmail)
            );
        $AuthorizationCode = $this->_em->getRepository('Plugin\EccubeApi\Entity\OAuth2\AuthorizationCode')
            ->findOneBy(
                array('code' => $code)
            );

        $now = new \DateTime();
        if ($AuthorizationCode) {
            $AuthorizationCode->setPropertiesFromArray(
                array(
                    'code' => $code,
                    'client' => $client,
                    'user' => $user,
                    'redirect_uri' => $redirectUri,
                    'expires' => $now->setTimestamp($expires),
                    'scope' => $scope,
                    'id_token' => $id_token,
                )
            );
        } else {
            $AuthorizationCode = new \Plugin\EccubeApi\Entity\OAuth2\AuthorizationCode();
            $AuthorizationCode->setPropertiesFromArray(
                array(
                    'code' => $code,
                    'client' => $client,
                    'user' => $user,
                    'redirect_uri' => $redirectUri,
                    'expires' => $now->setTimestamp($expires),
                    'scope' => $scope,
                    'id_token' => $id_token,
                )
            );
            $this->_em->persist($AuthorizationCode);
        }

        $this->_em->flush($AuthorizationCode);
    }

    public function expireAuthorizationCode($code)
    {
        $authCode = $this->findOneBy(array('code' => $code));
        if ($authCode->getExpires()->getTimestamp() <= time()) {
            $this->_em->remove($authCode);
            $this->_em->flush();
        }
    }
}
