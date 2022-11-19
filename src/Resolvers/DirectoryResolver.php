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
        $fileList = glob(implode(DIRECTORY_SEPARATOR, [$this->directory, '*.php']));

        return (new FileListResolver(
            $fileList,
            $this->ignoreClasses
        ))->getTestCases();
    }
}