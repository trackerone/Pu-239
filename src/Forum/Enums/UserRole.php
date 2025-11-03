<?php

declare(strict_types=1);

namespace Pu239\Forum\Enums;

enum UserRole: string
{
    case User = 'user';
    case Moderator = 'moderator';
    case Admin = 'admin';
}
