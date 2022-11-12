<?php

namespace NovaTech\TestQL\Command;

use NovaTech\TestQL\Resolvers\ArrayTestResolver;
use NovaTech\TestQL\TestQl;
use NovaTech\Tests\Cases\TestAsteriks;
use NovaTech\Tests\Cases\TestClassUsesPersistentAuth;
use NovaTech\Tests\Cases\TestDependency;
use NovaTech\Tests\Cases\TestDirectives;
use NovaTech\Tests\Cases\TestLocalhost;
use NovaTech\Tests\Cases\TestPersistenAuth;
use NovaTech\Tests\Cases\TestResponseFailing;
use NovaTech\Tests\Cases\TestSimpleRequest;
use NovaTech\Tests\Cases\TestSimpleResponse;
use NovaTech\Tests\Cases\TestSimpleResponseWithStatusCode;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('run:test')]
class RunTestsCommand extends Command
{

    protected function configure()
    {
       $this->addOption('exit-on-fail', 'f', InputOption::VALUE_OPTIONAL, 'Tests will stop running when even one test fails');
        $this->addOption('logging', 'l', InputOption::VALUE_OPTIONAL, 'Should we save logs');
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $style = new SymfonyStyle($input, $output);
        $verbose = $input->getOption('verbose');
        $logging = $input->getOption('logging') ?: false;

        $resolver = new ArrayTestResolver([
           new TestSimpleResponseWithStatusCode(),
           new TestSimpleResponse(),
           new TestSimpleRequest(),
           new TestDependency(),
            new TestLocalhost(),
            new TestResponseFailing(),
            new TestPersistenAuth(),
            new TestClassUsesPersistentAuth(),
            new TestAsteriks(),
            new TestDirectives()
        ]);
        $testql = new TestQl(
            $resolver, $verbose, $logging
        );
        $count = count($resolver->getTestCases());
        $style->info(sprintf('Running  %d tests resolved with %s', $count, get_class($resolver)));

        $progressBar = new ProgressBar($output, $count, );
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

        foreach ($testql->runTests() as $index =>  $output) {
            $progressBar->setMessage(sprintf('Running test %s instance', $output['test']));

            if ($output['status'] === false) {
                $failed[] = $output;
               // $style->writeln(sprintf('Test %s failed with "%s" message', $output['test'], $output['message'] ?? ''));
            }else{
                $success[] = $output;
            }

            $progressBar->advance(1);

        }




            foreach ($failed as $item){
                $style->writeln(
                    sprintf(
                        'Test %s failed with <fg=;whitebg=#eb3734>"%s"</> message',
                        $item['test'],
                        $item['message']?? ''
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