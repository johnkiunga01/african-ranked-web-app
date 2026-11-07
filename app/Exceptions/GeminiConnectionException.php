<?php

namespace App\Exceptions;

use RuntimeException;

// Only locally authored, sanitized provider diagnostics belong in this type.
class GeminiConnectionException extends RuntimeException {}
