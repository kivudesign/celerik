<?php

declare(strict_types=1);

namespace Celerik\Ui\FileManager;

use LogicException;

/**
 * Runs the elFinder connector (studio-42/elfinder) for one request.
 *
 * elFinder writes its own response (JSON or a file stream, with range support) and ends
 * the request, so call this as the last thing in the controller. elFinder checks its own
 * CSRF token on every command that changes files; send the framework's CSRF token as well
 * with FileManager::csrf().
 */
final class FileManagerConnector
{
    /**
     * @param bool $authorized Result of the application's authorization check (policy, voter,
     *                         role...). There is deliberately no default.
     */
    public static function run(FileManagerConfig $config, bool $authorized): never
    {
        if (!class_exists(\elFinderConnector::class)) {
            throw new LogicException('The file manager requires elFinder: composer require studio-42/elfinder');
        }

        if (!$authorized) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => ['errPerm']]);
            exit;
        }

        (new \elFinderConnector(new \elFinder($config->toArray())))->run();
        exit;
    }
}
