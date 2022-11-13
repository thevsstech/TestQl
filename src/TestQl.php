<?php

namespace NovaTech\TestQL;

use NovaTech\TestQL\Entities\AuthenticationCapsule;
use NovaTech\TestQL\Interfaces\AuthenticationResolverInterface;
use NovaTech\TestQL\Interfaces\GroupedTestInterface;
use NovaTech\TestQL\Interfaces\IgnorePersistentAuthenticationInterface;
use NovaTech\TestQL\Interfaces\PersistentAuthenticationInterface;
use NovaTech\TestQL\Interfaces\TestCaseResolverInterface;
use NovaTech\TestQL\Interfaces\TestDependsOnInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestQl
{
    private array $tests = [];
    private ?SymfonyStyle $style;
    private array $dependencies = [];
    private array $responses = [];


    /**
     * @param TestCase[] $tests
     * @param bool $verbose
     */
    public function __construct(
        public readonly TestCaseResolverInterface $resolver,
        public readonly bool                      $verbose = false,
        public readonly bool                      $logging = false,

    )
    {
        $this->tests = $this->resolver->getTestCases();
        $this->checkTests();
        $this->getDependencies();
    }

    /**
     * @return SymfonyStyle|null
     */
    public function getStyle(): ?SymfonyStyle
    {
        return $this->style;
    }

    /**
     * @param SymfonyStyle|null $style
     */
    public function setStyle(?SymfonyStyle $style)
    {
        $this->style = $style;
        return $this;
    }

    private function checkTests(): void
    {
        foreach ($this->tests as $test) {
            if (!$test instanceof TestCase) {
                throw new \UnexpectedValueException(sprintf('
                %s must be an instance of %s', is_object($test) ? get_class($test) : gettype($test), TestCase::class));
            }
        }
    }


    /**
     * gets dependencies
     *
     * @return void
     */
    private function getDependencies(): void
    {
        $dependencies = [];

        foreach ($this->tests as $test) {
            if ($test instanceof TestDependsOnInterface) {
                $dependsOn = $test->dependsOn();

                foreach ($dependsOn as $dependency) {
                    $dependencies[$dependency][] = get_class($test);
                }
            }
        }

        $this->dependencies = $dependencies;
    }

    /**
     * Sorts tests based on dependencies
     *
     * @return array
     */
    public function getSortedTests(array $dependencies): array
    {
        $sortedTests = [];


        foreach ($dependencies as $class => $dependency) {
            $sortedTests = [
                ...$sortedTests,
                ...$dependency,
                $class
            ];
        }

        $tests = array_unique([...$sortedTests], SORT_REGULAR);

        $classNames =  array_map('get_class', $this->tests);

        foreach ($classNames as $class){
            if (!in_array($class, $tests, true)) {
                $tests[] = $class;
            }
        }
        return array_reverse($tests);
    }

    public function getGroupFilteredTests(array $tests, array $groups) : array
    {
        $newTests = [];

        $testsPrepared = $this->convertTests($tests);

        foreach ($tests as $test) {
            $instance = $testsPrepared[$test];
            if ($instance instanceof GroupedTestInterface) {
                $testGroups = $instance->getGroups();

                foreach ($testGroups as $group){
                    if(in_array($group, $groups)){
                        $newTests[] = $test;
                    }
                }
            }
        }

        return $newTests;
    }

    public function convertTests(array $tests) : array
    {
        $testsPrepared = [];

        foreach($this->tests as $test) {
            $testsPrepared[get_class($test)] = $test;
        }

        return array_map(fn($test) => $testsPrepared[$test], $tests);
    }


    
    public function runTests(mixed $payload = null, array $groups = []): array|\Generator
    {
        $outputs = [];
        $stacktrace = [];
        $tests = $this->getSortedTests($this->dependencies);
        $persistentAuthentication = null;

        if (count($groups)) {
            $tests = $this->getGroupFilteredTests($tests, $groups );

        }



        $tests = $this->convertTests($tests);
        foreach ($tests as $test) {
            $className =get_class($test);
            try {
                $localAuthentication = $test instanceof AuthenticatedTestCase ? $persistentAuthentication : new AuthenticationCapsule(
                    'none',
                    ''
                );

                if ($test instanceof AuthenticationResolverInterface) {

                    // lets try the authentication information from authenticate method
                    // this method can return an AuthenticationCapsule or an array of AuthenticationCapsules
                    // if this method didn't return anything we will just create a not authenticated capsule
                    // in any case this class inherits IgnorePersistentAuthenticationInterface interface, we won't pass
                    // persistent authentication information
                    $localAuthentication = $test->authenticate(
                       $test instanceof IgnorePersistentAuthenticationInterface ? null : $persistentAuthentication
                    );



                    // persist authentication information to used in other test
                    // this will make sure we will pass authentication to other tests
                    if ($test instanceof PersistentAuthenticationInterface) {
                        $persistentAuthentication = $localAuthentication;
                    }
                }



                if ($test instanceof TestDependsOnInterface) {
                    $dependencies = $test->dependsOn();



                    foreach ($dependencies as $dependency) {
                        if (!isset($outputs[$dependency])) {
                            throw new \RuntimeException(
                                sprintf(
                                    'Test "%s" depends on test "%s" which is not present in the test suite',
                                    get_class($test),
                                    $dependency
                                ));
                        }

                        $dependencyResponse = $outputs[$dependency] ?? null;

                        if($dependencyResponse['status'] === false){
                            throw new \RuntimeException(
                                sprintf(
                                    'Test "%s" depends on test "%s" which is failed',
                                    get_class($test),
                                    $dependency
                                ));
                        }
                    }
                }



                if (!is_array($localAuthentication)) {
                    $localAuthentication = [$localAuthentication];
                }


                foreach($localAuthentication as $index => $auth){
                    if ($test instanceof TestCase) {
                        $test->setPreviousResponses($this->responses);
                    }

                    if ($test instanceof AuthenticatedTestCase) {
                        $test->setAuthentication($auth);
                    }

                    $payload = $test->test($payload);

                    if ($test instanceof TestCase) {
                        $this->responses[$className][$auth->identifier ?? $index] = $test->getResponse();
                    }
                }



                $output = [
                    'test' => $className,
                    'status' => true,
                ];

                yield  $output;



            } catch (\Exception $e) {
                if ($this->verbose) {
                    $stacktrace[$className] = $e->getTraceAsString();
                }
                $output = [
                    'test' => $className,
                    'status' => false,
                    'message' => $e->getMessage(),
                    'stacktrace' => $e->getTraceAsString()
                ];
                yield $output;
            }

            $outputs[$className] = $output;

        }



        return $outputs;
    }

}