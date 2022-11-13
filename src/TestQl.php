<?php

namespace NovaTech\TestQL;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\Exception\CircularReferenceException;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
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
     * @psalm-param array<class-string<DependentFixtureInterface>, int> $sequences
     * @psalm-param iterable<class-string<FixtureInterface>>|null       $classes
     *
     * @psalm-return array<class-string<FixtureInterface>>
     */
    private function getUnsequencedClasses(array $sequences, ?iterable $classes = null): array
    {
        $unsequencedClasses = [];

        if ($classes === null) {
            $classes = array_keys($sequences);
        }

        foreach ($classes as $class) {
            if ($sequences[$class] !== -1) {
                continue;
            }

            $unsequencedClasses[] = $class;
        }

        return $unsequencedClasses;
    }

    /**
     * Orders fixtures by dependencies
     *
     * @return void
     */
    private function orderFixturesByDependencies($allTests)
    {
        /** @psalm-var array<class-string<DependentFixtureInterface>, int> */
        $sequenceForClasses = [];



        // First we determine which classes has dependencies and which don't
        foreach ($allTests as $test) {
            $testClass = get_class($test);


            if ($test instanceof TestDependsOnInterface) {
                $dependenciesClasses = $test->dependsOn();

                if (in_array($testClass, $dependenciesClasses)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Class "%s" can\'t have itself as a dependency',
                        $testClass
                    ));
                }

                // We mark this class as unsequenced
                $sequenceForClasses[$testClass] = -1;
            } else {
                // This class has no dependencies, so we assign 0
                $sequenceForClasses[$testClass] = 0;
            }
        }

        // Now we order fixtures by sequence
        $sequence  = 1;
        $lastCount = -1;

        $tests = $this->convertTests($allTests);
        while (($count = count($unsequencedClasses = $this->getUnsequencedClasses($sequenceForClasses))) > 0 && $count !== $lastCount) {
            foreach ($unsequencedClasses as $key => $class) {
                $fixture                 = $tests[$class];
                $dependencies            = $fixture->dependsOn();
                $unsequencedDependencies = $this->getUnsequencedClasses($sequenceForClasses, $dependencies);

                if (count($unsequencedDependencies) !== 0) {
                    continue;
                }

                $sequenceForClasses[$class] = $sequence++;
            }

            $lastCount = $count;
        }

        $orderedFixtures = [];

        // If there're fixtures unsequenced left and they couldn't be sequenced,
        // it means we have a circular reference
        if ($count > 0) {
            $msg  = 'Classes "%s" have produced a CircularReferenceException. ';
            $msg .= 'An example of this problem would be the following: Class C has class B as its dependency. ';
            $msg .= 'Then, class B has class A has its dependency. Finally, class A has class C as its dependency. ';
            $msg .= 'This case would produce a CircularReferenceException.';

            throw new \Exception(sprintf($msg, implode(',', $unsequencedClasses)));
        } else {
            // We order the classes by sequence
            asort($sequenceForClasses);

            foreach ($sequenceForClasses as $class => $sequence) {
                // If fixtures were ordered
                $orderedFixtures[] = $tests[$class];
            }
        }

        return $orderedFixtures;
    }



    public function getGroupFilteredTests(array $tests, array $groups) : array
    {
        $newTests = [];

        if (empty($groups)) {
            return $tests;
        }

        $testsPrepared = $this->convertTests($tests);

        foreach ($tests as $test => $instance) {
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

        return $testsPrepared;
    }



    /**
     * @throws \Exception
     */
    public function runTests( array $groups = []): array|\Generator
    {
        $payload = [];
        $outputs = [];
        $stacktrace = [];
        $tests = $this->orderFixturesByDependencies(
            $this->getGroupFilteredTests($this->tests, $groups)
        );
        $persistentAuthentication = null;



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