<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A landing page cannot be published as a new version; the message is translated and shown to the admin.
 */
class LandingVersionException extends RuntimeException {}
