<?php

namespace FR3D\LdapBundle\Security\User;

use FR3D\LdapBundle\Ldap\LdapManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Provides users from Ldap.
 */
class LdapUserProvider implements UserProviderInterface
{
    /** @var LdapManagerInterface */
    protected $ldapManager;

    /** @var LoggerInterface|null */
    protected $logger;

    public function __construct(LdapManagerInterface $ldapManager, LoggerInterface $logger = null)
    {
        $this->ldapManager = $ldapManager;
        $this->logger = $logger;
    }

    public function loadUserByUsername($username)
    {
        $user = $this->ldapManager->findUserByUsername($username);

        if (empty($user)) {
            $this->logInfo('User {username} {result} on LDAP', [
                'action' => 'loadUserByUsername',
                'username' => $username,
                'result' => 'not found',
            ]);
            $ex = new UsernameNotFoundException(sprintf('User "%s" not found', $username));
            $ex->setUsername($username);

            throw $ex;
        }

        $this->logInfo('User {username} {result} on LDAP', [
            'action' => 'loadUserByUsername',
            'username' => $username,
            'result' => 'found',
        ]);

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return true;
    }

    /**
     * Log a message into the logger if this exists.
     */
    private function logInfo(string $message, array $context = []): void
    {
        if (!$this->logger) {
            return;
        }

        $this->logger->info($message, $context);
    }
}
