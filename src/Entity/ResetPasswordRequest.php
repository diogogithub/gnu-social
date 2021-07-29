<?php

namespace App\Entity;

use App\Core\Entity;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;

class ResetPasswordRequest extends Entity implements ResetPasswordRequestInterface
{
    // {{{ Autocode
    // @codeCoverageIgnoreStart
    private string $nickname;
    private \DateTimeInterface $created;

    public function setNickname(string $nickname): self
    {
        $this->nickname = $nickname;
        return $this;
    }

    public function getNickname(): string
    {
        return $this->nickname;
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

    public function __construct(object $user, \DateTimeInterface $expiresAt, string $selector, string $hashedToken)
    {
        $this->user_id  = $user->getId();
        $this->expires  = $expiresAt;
        $this->selector = $selector;
        $this->token    = $hashedToken;
    }

    public function getUser(): object
    {
        return LocalUser::getWithPK($this->user_id);
    }

    public function getRequestedAt(): \DateTimeInterface
    {
        return $this->created;
    }

    public function isExpired(): bool
    {
        return $this->expires->getTimestamp() <= time();
    }

    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expires;
    }

    public function getHashedToken(): string
    {
        return $this->token;
    }

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
