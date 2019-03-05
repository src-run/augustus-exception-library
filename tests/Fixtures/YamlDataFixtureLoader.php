<?php

/*
 * This file is part of the `src-run/augustus-exception-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Exception\Tests\Fixtures;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class YamlDataFixtureLoader
{
    /**
     * @var string
     */
    private $target;

    /**
     * @var string
     */
    private $search;

    /**
     * @param string $target
     */
    public function __construct(string $target, string $search = null)
    {
        $this->target = $target;
        $this->search = $search ?? realpath(__DIR__);
    }

    /**
     * @param string|null $context
     *
     * @return \Generator
     */
    public function load(string $context = null): \Generator
    {
        $data = $this->readData();
        $cont = $context ? [$context] : self::parseContexts($data);

        foreach ($cont as $c) {
            if (!array_key_exists($c, $data) || !array_key_exists('data', $data[$c])) {
                throw new \InvalidArgumentException(sprintf(
                    'Failed to retrieve fixture data context: "%s".', $c
                ));
            }

            yield from $data[$c]['data'];
        }
    }

    /**
     * @return array
     */
    private function readData(): array
    {
        try {
            return Yaml::parse(file_get_contents($this->resolveFilePath()));
        } catch (ParseException $exception) {
            throw new \RuntimeException(sprintf(
                'Failed to parse YAML from resolved file target "%s": %s.', $this->resolveFilePath(), $exception->getMessage()
            ));
        }
    }

    /**
     * @return string
     */
    private function resolveFilePath(): string
    {
        return str_replace(['SR\Exception', '\\'], [$this->search, DIRECTORY_SEPARATOR], $this->target).'.yaml';
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private static function parseContexts(array $data): array
    {
        return array_keys($data);
    }
}
