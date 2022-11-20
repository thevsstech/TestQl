<?php

namespace NovaTech\TestQL\Service;

use Twig\Environment;

class StubService
{
    private array $stubs = [];
    private Environment $twig;

    public function __construct()
    {
        $this->initialize();
    }

    public function initialize(): void
    {
        $stubs = ['test_stub.twig'];
        $stubDir = dirname(__FILE__, 2) . '/stubs';

        foreach ($stubs as $stub) {
            $stubPath = implode(DIRECTORY_SEPARATOR, [
                $stubDir,
                $stub
            ]);

            if (!file_exists($stubPath)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Stub file "%s" does not exist.',
                        $stubPath
                    )
                );
            }

            $this->stubs[$stub] = file_get_contents($stubPath);

            $loader = new \Twig\Loader\ArrayLoader($this->stubs);
            $twig = new \Twig\Environment($loader);
            $this->twig = $twig;
        }
    }

    public function renderTestStub(
        string $name,
        string $url,
        string $method = 'GET',
        ?array $json = null,
        array $headers = [],
        array $dependencies = [],
        array $groups = [],
        ?string $namespace = null
    )
    {

        $isAuthenticated = isset($headers['Authorization']) && $headers['Authorization'] !== '';
        $implements = [];

        if (count($dependencies)) {
            $implements[] = 'TestDependsOnInterface';
        }

        if (count($groups)) {
            $implements[] = 'GroupedTestInterface';
        }

        $implements = implode(', ', $implements);
        $headers= var_export($headers, true);
        $json = var_export($json, true);



        return $this->twig->render(
            'test_stub.twig',
            [
                'authenticated' => $isAuthenticated,
                'name' => $name,
                'url' => $url,
                'method' => $method,
                'json' => $json,
                'headers' => $headers,
                'implements' => $implements,
                'dependencies' => $dependencies,
                'groups' => implode(',', $groups),
                'namespace' => $namespace
            ]
        );
    }


}