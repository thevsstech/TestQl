<?php

namespace NovaTech\TestQL\Resolvers;

use NovaTech\TestQL\Interfaces\TestCaseResolverInterface;

class FileResolver implements TestCaseResolverInterface
{
    public function __construct(public string $file )
    {
    }

    private function checkFile()
    {
        if (!is_dir($this->file)) {
            throw new \InvalidArgumentException(sprintf('File "%s" does not exist.', $this->file));
        }
    }


    /**
     * @return array|\NovaTech\TestQL\TestCase[]
     */
    public function getTestCases(): array
    {
       $this->checkFile();

       $return = require_once $this->file;

        if (!$return instanceof TestCaseResolverInterface) {
            throw new \InvalidArgumentException(sprintf('File "%s" must return a resolver.', $this->file));
        }

        return $return->getTestCases();
    }
}