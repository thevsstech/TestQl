<?php

namespace NovaTech\TestQL\Resolvers;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ConfigResolver
{

    /**
     * this function will try to read given config path
     * if file could not found it will throw an array
     * if file is exists it will read resolver, logging, verbose, options from it.
     *
     * @param string $configFilePath
     * @param bool|null $verbose
     * @param bool|null $logging
     * @param string|null $resolverName
     * @param string|null $file
     * @param string|null $directory
     * @param array|null $ignoreClasses
     * @param array|null $testClasses
     * @return void
     */
    public function resolve(
        string  $configFilePath,
        ?bool   &$verbose,
        ?bool   &$logging,
        ?string &$resolverName,
        ?string &$file,
        ?string &$directory,
        ?array  &$ignoreClasses,
        ?array  &$testClasses,
        ?bool   &$strict,
        ?array  &$defaults = [],
    ): void
    {
        if (!file_exists($configFilePath) || !is_readable($configFilePath)) {
            throw new \InvalidArgumentException(
                sprintf('Config file %s does not exist or not readable', $configFilePath)
            );
        }

        $configContent = file_get_contents($configFilePath);

        try {
            $config = Yaml::parse($configContent);



            if (isset($config['env'])) {
                $environments = $config['env'];


                if (!is_array($environments)) {
                    throw new \InvalidArgumentException(
                        '"env" defination in yaml must be an array'
                    );
                }

                $_ENV = [
                    ...$_ENV,
                    ...$environments
                ];

                $_SERVER = [
                    ...$_SERVER,
                    ...$environments
                ];
            }

            if (isset($config['defaults'])) {
                $headers = $config['defaults']['headers'] ?? [];
                $defaults = [
                    'headers' => $headers,
                ];
            }


            if (isset($config['verbose'])) {
                $verbose = $config['verbose'];
            }

            if (isset($config['logging'])) {
                $logging = $config['logging'];
            }

            if (isset($config['resolver'])) {
                $resolverArray = $config['resolver'];
                $resolverName = $resolverArray['name'] ?? null;


                if (!$resolverName) {
                    throw new \InvalidArgumentException(
                        'You must provide a resolver to run tests'
                    );
                }

                if (isset($resolverArray['file'])) {
                    $file = $resolverArray['file'];
                }

                if (isset($resolverArray['directory'])) {
                    $directory = $resolverArray['directory'];
                }

                if (isset($resolverArray['tests'])) {
                    $testClasses = $resolverArray['tests'];
                }

                if (isset($resolverArray['ignored'])) {
                    $ignoreClasses = $resolverArray['ignored'];
                }

                if (isset($resolverArray['strict'])) {
                    $strict = $resolverArray['strict'];
                }
            }


        } catch (ParseException $exception) {
            printf('Unable to parse the YAML string: %s', $exception->getMessage());
        }
    }


}