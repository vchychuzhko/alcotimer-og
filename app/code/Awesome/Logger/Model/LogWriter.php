<?php

namespace Awesome\Logger\Model;

class LogWriter
{
    private const EXCEPTION_LOG_FILE = 'var/log/exception.log';
    private const CURRENT_TIMEZONE = 'Europe/Kiev';
    private const TIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * Write all Errors, Warnings and Exceptions to log file
     * @param string $string
     * @return self
     */
    public function write($string)
    {
        $content = (string) @file_get_contents(BP . '/' . self::EXCEPTION_LOG_FILE);
        file_put_contents(
            BP . '/' . self::EXCEPTION_LOG_FILE,
            ($content ? "$content\n" : '') . $this->getCurrentTime() . ' - ' . $string
        );

        return $this;
    }

    /**
     * Prepare current time and offset as a string.
     * @return string
     */
    private function getCurrentTime()
    {
        try {
            $time = new \DateTime('UTC');
            $time = $time->format(self::TIME_FORMAT) . $this->getOffset($time);
        } catch (\Exception $e) {
            $time = gmdate(self::TIME_FORMAT, time()) . ' UTC';
        }

        return $time;
    }

    /**
     * Get difference (offset) between current and UTC time.
     * @param \DateTime $utcTime
     * @return string
     */
    private function getOffset($utcTime)
    {
        $currentTimeZone = timezone_open(self::CURRENT_TIMEZONE);
        $offsetInSecs =  $currentTimeZone->getOffset($utcTime);
        $offset = gmdate('H:i', abs($offsetInSecs));

        return ' UTC' . ($offsetInSecs > 0 ? '+' : '-') . $offset;
    }
}
