<?php
declare(strict_types=1);

namespace Awesome\Framework\Model\FileManager;

class PhpFileManager extends \Awesome\Framework\Model\FileManager
{
    /**
     * Include and parsePHP array file.
     * @param string $path
     * @param bool $graceful
     * @return array
     * @throws \RuntimeException
     */
    public function readArrayFile(string $path, bool $graceful = false): array
    {
        if (!is_file($path) && !$graceful) {
            throw new \RuntimeException(
                sprintf('Provided path "%s" does not exist or is not a file and cannot be included', $path)
            );
        }
        $array = include $path;

        if (!is_array($array)) {
            throw new \RuntimeException(
                sprintf('Provided path "%s" does not contain valid PHP array', $path)
            );
        }

        return $array;
    }

    /**
     * Generate PHP array file.
     * According to short array syntax.
     * @param string $path
     * @param array $data
     * @param string $annotation
     * @return bool
     * @throws \RuntimeException
     */
    public function createArrayFile(string $path, array $data, string $annotation = ''): bool
    {
        $content = '<?php' . ($annotation ? ' /** ' . $annotation . ' */' : '') . "\n"
            . 'return ' . array_export($data, true) . ';' . "\n";

        return $this->createFile($path, $content, true);
    }

    /**
     * Check if requested PHP object have corresponding file.
     * @param string $objectName
     * @return bool
     */
    public function objectFileExists(string $objectName): bool
    {
        $path = APP_DIR . '/' . str_replace('\\', '/', ltrim($objectName, '\\')) . '.php';

        return is_file($path);
    }
}
