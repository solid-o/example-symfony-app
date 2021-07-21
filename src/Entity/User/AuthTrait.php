<?php

declare(strict_types=1);

namespace App\Entity\User;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

use function base_convert;
use function mb_strlen;
use function mt_rand;
use function random_int;
use function sha1;

/**
 * Trait containing fields and methods for user authentication.
 */
trait AuthTrait
{
    /** @ORM\Column(type="string") */
    private string $password;

    /** @ORM\Column(type="string", length=100) */
    private string $salt;

    /** @ORM\Column(type="string") */
    private string $encoder;

    /** @ORM\Column(type="datetimetz_immutable", nullable=true) */
    private ?DateTimeImmutable $passwordExpiresAt = null;
    private ?string $plainPassword = null;

    /**
     * Set the ENCODED password.
     * Please DO NOT USE this method directly! Use {@see updatePassword} instead.
     *
     * This is public since it is used by PasswordUpdater, but should not be used directly.
     * If you want to change the user's password, please set the new password via
     * {@see updatePassword} method instead
     *
     * @internal
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * Get the encoded password.
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Get the password salt.
     */
    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * @see PasswordHasherAwareInterface::getPasswordHasherName()
     */
    public function getPasswordHasherName(): string
    {
        return self::class;
    }

    /**
     * Sets the password (in plaintext).
     * Will be encoded and stored at next flush operation.
     */
    public function updatePassword(?string $plainPassword): self
    {
        if (empty($plainPassword)) {
            return $this;
        }

        $this->renewSalt();
        $this->plainPassword = $plainPassword;
        $this->passwordExpiresAt = null;

        return $this;
    }

    /**
     * Whether a password update has been requested.
     */
    public function shouldEncodePassword(): bool
    {
        return $this->getPlainPassword() !== false;
    }

    /**
     * Get the plain password if an update has been requested,
     * false otherwise.
     */
    public function getPlainPassword(): bool | string
    {
        return $this->plainPassword ?? false;
    }

    /**
     * Remove any sensitive information (ex: plaintext password) from the object.
     */
    public function eraseCredentials(): void
    {
        unset($this->plainPassword);
    }

    public function isCredentialsNonExpired(): bool
    {
        if ($this->passwordExpiresAt === null) {
            return true;
        }

        return new DateTimeImmutable() < $this->passwordExpiresAt;
    }

    /**
     * Generates a random password, sets the password as expired and returns it.
     * Should be used as one-time password, when creating users or in emergency
     * cases, when the user is unable to recover its password by email.
     */
    public function generateAndResetPassword(): string
    {
        $randomPassword = self::randomPassword();
        $this->updatePassword($randomPassword);

        // Expires immediately. This password has to be changed onto next login.
        $this->passwordExpiresAt = new DateTimeImmutable();

        return $randomPassword;
    }

    /**
     * Generates a random password.
     */
    public static function randomPassword(int $length = 8): string
    {
        // 'O' is missing as it is too similar to 0 for users
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ!$%&/\\';

        $str = '';
        $max = mb_strlen($keyspace) - 1;

        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }

        return $str;
    }

    /**
     * Generates a new salt and set the password field to null.
     * A new password MUST be set using the {@see updatePassword} method.
     */
    private function renewSalt(): self
    {
        $this->salt = base_convert(sha1((string) mt_rand()), 16, 36);
        $this->encoder = 'sha512';
        unset($this->password);

        return $this;
    }
}
