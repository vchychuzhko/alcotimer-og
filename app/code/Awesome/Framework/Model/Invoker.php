<?php
declare(strict_types=1);

namespace Awesome\Framework\Model;

use Awesome\Framework\Model\SingletonInterface;

final class Invoker implements \Awesome\Framework\Model\SingletonInterface
{
    /**
     * @var array $instances
     */
    private static $instances = [];

    /**
     * Invoker constructor.
     */
    private function __construct() {}

    /**
     * Create requested class instance.
     * Non-object and extra parameters can be passed as an array.
     * Regardless of SingletonInterface new instance will be created.
     * @param string $id
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */
    public function create(string $id, array $parameters = [])
    {
        $id = ltrim($id, '\\');

        $reflectionClass = new \ReflectionClass($id);
        $arguments = [];

        if ($constructor = $reflectionClass->getConstructor()) {
            foreach ($constructor->getParameters() as $parameter) {
                $parameterName = $parameter->getName();

                if (isset($parameters[$parameterName])) {
                    $arguments[] = $parameters[$parameterName];
                } elseif ($class = $parameter->getClass()) {
                    $arguments[] = $this->get($class->getName());
                } elseif ($parameter->isOptional()) {
                    $arguments[] = $parameter->getDefaultValue();
                } else {
                    throw new \Exception(
                        sprintf('Parameter "%s" was not provided for "%s" constructor', $parameterName, $id)
                    );
                }
            }
        }

        return new $id(...$arguments);
    }

    /**
     * Get requested class instance.
     * Non-object and extra parameters can be passed as an array.
     * @param string $id
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */
    public function get(string $id, array $parameters = [])
    {
        $id = ltrim($id, '\\');

        if (isset(self::$instances[$id])) {
            $object = self::$instances[$id];
        } else {
            $object = $this->create($id, $parameters);

            if ($object instanceof SingletonInterface) {
                self::$instances[$id] = $object;
            }
        }

        return $object;
    }

    /**
     * Add singletone to registry.
     * Overriding is not allowed and will throw an exception by default.
     * @param SingletonInterface $object
     * @param bool $graceful
     * @return $this
     * @throws \Exception
     */
    public function register($object, $graceful = false)
    {
        $id = get_class($object);

        if (!($object instanceof SingletonInterface)) {
            throw new \Exception(sprintf('Object "%s" must be an instance of "%s" to be registered', $id, SingletonInterface::class));
        }

        if (isset(self::$instances[$id])) {
            if (!$graceful) {
                throw new \Exception(sprintf('Instance of "%s" is already set', $id));
            }
        } else {
            self::$instances[$id] = $object;
        }

        return $this;
    }

    /**
     * Get DIContainer instance.
     * @return $this
     */
    public static function getInstance(): self
    {
        if (!isset(self::$instances[self::class])) {
            self::$instances[self::class] = new self();
        }

        return self::$instances[self::class];
    }
}
