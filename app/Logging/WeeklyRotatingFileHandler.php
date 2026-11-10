<?php

namespace App\Logging;

use DateTimeImmutable;
use Monolog\Handler\RotatingFileHandler;

/**
 * Rotates the log file once per ISO-8601 week instead of once per day,
 * reusing RotatingFileHandler's pruning of old files via `maxFiles`.
 */
class WeeklyRotatingFileHandler extends RotatingFileHandler
{
    protected function getTimedFilename(): string
    {
        $fileInfo = pathinfo($this->filename);
        $timedFilename = str_replace(
            ['{filename}', '{date}'],
            [$fileInfo['filename'], $this->weekIdentifier()],
            ($fileInfo['dirname'] ?? '').'/'.$this->filenameFormat
        );

        if (isset($fileInfo['extension'])) {
            $timedFilename .= '.'.$fileInfo['extension'];
        }

        return $timedFilename;
    }

    protected function getGlobPattern(): string
    {
        $fileInfo = pathinfo($this->filename);
        $glob = str_replace(
            ['{filename}', '{date}'],
            [$fileInfo['filename'], '[0-9][0-9][0-9][0-9]-W[0-9][0-9]'],
            ($fileInfo['dirname'] ?? '').'/'.$this->filenameFormat
        );

        if (isset($fileInfo['extension'])) {
            $glob .= '.'.$fileInfo['extension'];
        }

        return $glob;
    }

    protected function getNextRotation(): DateTimeImmutable
    {
        return (new DateTimeImmutable('next monday', $this->timezone))->setTime(0, 0, 0);
    }

    private function weekIdentifier(): string
    {
        return (new DateTimeImmutable(timezone: $this->timezone))->format('o-\WW');
    }
}
