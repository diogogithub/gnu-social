<?php

declare(strict_types = 1);

namespace App\Entity;

use App\Core\Entity;
use DateTimeInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;

class ResetPasswordRequest extends Entity implements ResetPasswordRequestInterface
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private int $id;
    private int $user_id;
    private ?string $selector;
    private ?string $token;
    private DateTimeInterface $expires;
    private DateTimeInterface $created;

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setSelector(?string $selector): self
    {
        $this->selector = $selector;
        return $this;
    }

    public function getSelector(): ?string
    {
        return $this->selector;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setExpires(DateTimeInterface $expires): self
    {
        $this->expires = $expires;
        return $this;
    }

    public function getExpires(): DateTimeInterface
    {
        return $this->expires;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    // @codeCoverageIgnoreEnd
    // }}} Autocode

    // {{{ Interface
    // @codeCoverageIgnoreStart
    public function __construct(object $user, DateTimeInterface $expiresAt, string $selector, string $hashedToken)
    {
        $this->user_id  = $user->getId();
        $this->expires  = $expiresAt;
        $this->selector = $selector;
        $this->token    = $hashedToken;
    }

    public function getUser(): object
    {
        return LocalUser::getByPK($this->user_id);
    }

    public function getRequestedAt(): DateTimeInterface
    {
        return $this->created;
    }

    public function isExpired(): bool
    {
        return $this->expires->getTimestamp() <= time();
    }

    public function getExpiresAt(): DateTimeInterface
    {
        return $this->expires;
    }

    public function getHashedToken(): string
    {
        return $this->token;
    }
    // @codeCoverageIgnoreEnd
    // }}}

    public static function schemaDef(): array
    {
        return [
            'name'        => 'reset_password_request',
            'description' => 'Represents a request made by a user to change their passowrd',
            'fields'      => [
                'id'       => ['type' => 'serial', 'not null' => true],
                'user_id'  => ['type' => 'int', 'foreign key' => true, 'target' => 'LocalUser.id', 'multiplicity' => 'many to many', 'not null' => true, 'description' => 'foreign key to local_user table'],
                'selector' => ['type' => 'char', 'length' => 20],
                'token'    => ['type' => 'char', 'length' => 100],
                'expires'  => ['type' => 'datetime', 'not null' => true],
                'created'  => ['type' => 'datetime', 'not null' => true, 'default' => 'CURRENT_TIMESTAMP', 'description' => 'date this record was created'],
            ],
            'primary key' => ['id'],
        ];
    }
}
