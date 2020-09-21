<?php

namespace Awesome\Cache\Console;

use Awesome\Cache\Model\Cache;
use Awesome\Console\Model\Cli\Input\InputDefinition;
use Awesome\Console\Model\Cli\Output;

class Clean extends \Awesome\Console\Model\Cli\AbstractCommand
{
    /**
     * @var Cache $cache
     */
    private $cache;

    /**
     * Cache Clean constructor.
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public static function configure($definition)
    {
        return parent::configure($definition)
            ->setDescription('Clear application cache')
            ->addArgument('types', InputDefinition::ARGUMENT_ARRAY, 'Cache types to be cleared');
    }

    /**
     * Clear application cache.
     * @inheritDoc
     */
    public function execute($input, $output)
    {
        $definedTypes = $this->cache->getTypes();
        $types = $input->getArgument('types') ?: $definedTypes;

        foreach ($types as $type) {
            if (in_array($type, $definedTypes, true)) {
                $this->cache->invalidate($type);
                $output->writeln('Cache cleared: ' . $type);
            } else {
                $output->writeln('Provided cache type was not recognized.');
                $output->writeln();
                $output->writeln('Allowed types:');
                $output->writeln($output->colourText(implode(', ', $definedTypes)), 2);

                throw new \InvalidArgumentException('Invalid cache type is provided');
            }
        }
    }
}
