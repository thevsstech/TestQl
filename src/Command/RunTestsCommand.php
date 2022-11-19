<?php

namespace NovaTech\TestQL\Command;

use NovaTech\TestQL\Interfaces\TestCaseResolverInterface;
use NovaTech\TestQL\Resolvers\ArrayTestResolver;
use NovaTech\TestQL\Resolvers\DirectoryResolver;
use NovaTech\TestQL\Resolvers\FileListResolver;
use NovaTech\TestQL\Resolvers\FileResolver;
use NovaTech\TestQL\TestQl;
use NovaTech\Tests\Cases\TestApiResolvesDashboard;
use NovaTech\Tests\Cases\TestAsteriks;
use NovaTech\Tests\Cases\TestAuthenticationResolver;
use NovaTech\Tests\Cases\TestClassUsesPersistentAuth;
use NovaTech\Tests\Cases\TestDependency;
use NovaTech\Tests\Cases\TestDirectives;
use NovaTech\Tests\Cases\TestLocalhost;
use NovaTech\Tests\Cases\TestResponseFailing;
use NovaTech\Tests\Cases\TestSimpleRequest;
use NovaTech\Tests\Cases\TestSimpleResponse;
use NovaTech\Tests\Cases\TestSimpleResponseWithStatusCode;
use NovaTech\Tests\Cases\TestWithNoDependency;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

#[AsCommand('run:test')]
class RunTestsCommand extends Command
{

    protected function configure()
    {
        $this->addOption('ignoreClasses', 'i', InputOption::VALUE_OPTIONAL, 'Classes to ignore');
        $this->addOption('directory', null, InputOption::VALUE_OPTIONAL, 'director path for directory resolver');
        $this->addOption('file', null, InputOption::VALUE_OPTIONAL, 'file path for file resolver');
        $this->addOption('resolver', 'r', InputOption::VALUE_OPTIONAL, 'test resolver');
        $this->addOption('groups', 'g', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Groups to run');
        $this->addOption('logging', 'l', InputOption::VALUE_OPTIONAL, 'Should we save logs');
        $this->addOption('config-file', 'c', InputOption::VALUE_OPTIONAL, 'File path the parse configs');
        $this->addOption('tests', 't', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'The test classes to run');

    }

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
    private function getConfigContent(
        string  $configFilePath,
        ?bool   &$verbose,
        ?bool   &$logging,
        ?string &$resolverName,
        ?string &$file,
        ?string &$directory,
        ?array  &$ignoreClasses,
        ?array  &$testClasses,
    )
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
            }


        } catch (ParseException $exception) {
            printf('Unable to parse the YAML string: %s', $exception->getMessage());
        }
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $style = new SymfonyStyle($input, $output);
        $verbose = $input->getOption('verbose');
        $logging = $input->getOption('logging') ?: false;
        $groups = $input->getOption('groups') ?? [];
        $resolverName = $input->getOption('resolver') ?? null;
        $file = $input->getOption('file') ?? null;
        $directory = $input->getOption('directory') ?? null;
        $ignoreClasses = $input->getOption('ignoreClasses') ?? [];
        $configFilePath = $input->getOption('config-file') ?? null;
        $testClasses = $input->getOption('tests') ?? null;

        if (!$configFilePath && !$resolverName) {
            throw new \InvalidArgumentException(
                'You must provide a config-file or resolver to run tests'
            );
        }

        if ($configFilePath) {
            $configFilePath = realpath($configFilePath);
            $this->getConfigContent($configFilePath,
                $verbose,
                $logging,
                $resolverName,
                $file,
                $directory,
                $ignoreClasses,
                $testClasses
            );
        }

        $resolvers = [
            'directory' => $directory ? fn() : TestCaseResolverInterface => new DirectoryResolver(
                $directory, $ignoreClasses
            ) : fn() => throw new \InvalidArgumentException(
                'Please provide a directory to scan through the test classes'
            ),
            'file' => $file ? fn() : TestCaseResolverInterface => new FileResolver(
                $file,
                $ignoreClasses
            ) : fn() => throw new \InvalidArgumentException(
                'Please provide a file to resolve'
            ),
            'list' => $testClasses ? fn() : TestCaseResolverInterface => new FileListResolver(
                $testClasses,
                $ignoreClasses
            ) : fn() => throw new \InvalidArgumentException(
                'Please provide a test classes to run'
            )

        ];


        $resolver = $resolvers[$resolverName] ?? null;

        if (!$resolver) {
            throw new \InvalidArgumentException(
                sprintf('Invalid resolver "%s" provided, available resolvers are %s, %s, list', $resolverName, 'directory', 'file')
            );
        }


        $resolver = $resolver();

        $testql = new TestQl(
            $resolver, $verbose, $logging, $style
        );

        $count = count($resolver->getTestCases());
        if ($verbose) {
            $style->info(sprintf('Running  %d tests resolved with %s', $count, get_class($resolver)));

        }

        $progressBar = new ProgressBar($output, $count,);
        $progressBar->setMessage('');
        $format = <<<TEXT
%current%/%max% [%bar%] %percent:3s%%
🏁  %estimated:-21s% %memory:21s%
--> %message%\n
TEXT;
        $progressBar->setFormat($format);


        $progressBar->setBarCharacter('<fg=green>⚬</>');
        $progressBar->setEmptyBarCharacter("<fg=gray>⚬</>");
        $progressBar->setProgressCharacter("<fg=green>➤</>");

        $progressBar->start($count);

        $failed = [];
        $success = [];

        foreach ($testql->runTests($groups) as $index => $output) {
            $progressBar->setMessage(sprintf('Running test %s instance', $output['test']));

            if ($output['status'] === false) {
                $failed[] = $output;
                // $style->writeln(sprintf('Test %s failed with "%s" message', $output['test'], $output['message'] ?? ''));
            } else {
                $success[] = $output;
            }

            $progressBar->advance(1);

        }


        foreach ($failed as $item) {
            $style->writeln(
                sprintf(
                    'Test %s failed with <fg=;whitebg=#eb3734>"%s"</> message',
                    $item['test'],
                    $item['message'] ?? ''
                )
            );

            if ($verbose) {
                $style->writeln($item['stacktrace'] ?? '');
            }
        }

        $style->writeln(sprintf('Tests finished with <fg=#eb3734>%d</> fails, and <fg=green>%d</> success', count($failed), count($success)));


        $progressBar->finish();


        return count($failed) ? Command::FAILURE : Command::SUCCESS;
    }
}