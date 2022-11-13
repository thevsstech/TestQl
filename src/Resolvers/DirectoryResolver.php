<?php

namespace NovaTech\TestQL\Resolvers;

use NovaTech\TestQL\Interfaces\TestCaseResolverInterface;
use NovaTech\TestQL\TestCase;

class DirectoryResolver implements  TestCaseResolverInterface
{
    public function __construct(
        public string $directory = '',
        public array $ignoreClasses = []
    )
    {

    }

    private function checkDirectory()
    {
        if (!is_dir($this->directory)) {
            throw new \InvalidArgumentException(sprintf('Directory "%s" does not exist.', $this->directory));
        }
    }

    public function getTestCases(): array
    {
        $this->checkDirectory();
        $testCases = [];
        $glob = glob(implode(DIRECTORY_SEPARATOR, [$this->directory, '*.php']));

        foreach ($glob as $file)
        {
            $content = file_get_contents($file);
            if(!str_contains('<?php', $content)){
                continue;
            }
            require_once $file;

            // get the file name of the current file without the extension
            // which is essentially the class name
            $class = basename($file, '.php');

            // if file is ignored we will pass it
            if (in_array($class, $this->ignoreClasses)) {
                continue;
            }

            if (class_exists($class) && is_a($class, TestCase::class))
            {
                $obj = new $class;
                $testCases[] = $obj;
            }
        }

       return $testCases;
    }
}