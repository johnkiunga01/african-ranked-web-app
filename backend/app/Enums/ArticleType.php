<?php

namespace App\Enums;

enum ArticleType: string
{
    case Ranking = 'ranking';
    case Guide = 'guide';
    case Sponsored = 'sponsored';
    case Affiliate = 'affiliate';

    public function requiresDisclosure(): bool
    {
        return in_array($this, [self::Sponsored, self::Affiliate], true);
    }
}
