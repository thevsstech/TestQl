<?php

namespace NovaTech\TestQL\Resolvers;

use NovaTech\TestQL\Interfaces\TestCaseResolverInterface;

class FileResolver implements TestCaseResolverInterface
{
    public function __construct(public string $file )
    {
    }

    public function getFilePath()
    {
        
    }

    private function getFile()
    {
        return implode(
            DIRECTORY_SEPARATOR,
            [
                getcwd(),
                $this->file,
            ]
        );
    }

    private function checkFile()
    {
        if (!file_exists($this->getFile())) {
            throw new \InvalidArgumentException(sprintf('File "%s" does not exist.', $this->getFile()));
        }
    }


    /**
     * @return array|\NovaTech\TestQL\TestCase[]
     */
    public function getTestCases(): array
    {
       $this->checkFile();

       $return = require_once $this->getFile();

        if (!$return instanceof TestCaseResolverInterface) {
            throw new \InvalidArgumentException(sprintf('File "%s" must return a resolver.', $this->getFile()));
        }

        return $return->getTestCases();
    }
}