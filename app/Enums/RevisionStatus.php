<?php

namespace App\Enums;

enum RevisionStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Published = 'published';
}
