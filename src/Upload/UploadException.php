<?php

declare(strict_types=1);

namespace Celerik\Ui\Upload;

use InvalidArgumentException;

/**
 * A rejected upload. The message is safe to show to the user.
 */
final class UploadException extends InvalidArgumentException
{
}
