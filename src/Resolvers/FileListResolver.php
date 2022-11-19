<?php

namespace NovaTech\TestQL\Resolvers;

use NovaTech\TestQL\Interfaces\TestCaseResolverInterface;
use NovaTech\TestQL\TestCase;

class FileListResolver implements  TestCaseResolverInterface
{
    public function __construct(
        public array $fileList =[],
        public array $ignoreClasses = []
    )
    {

    }
    public static function getResolverClassFromPath(string $path, array $ignoreClasses): ?TestCase{
        $content = file_get_contents($path);
        if(!str_contains('<?php', $content)){
            return null;
        }
        require_once $file;

        // get the file name of the current file without the extension
        // which is essentially the class name
        $class = basename($file, '.php');

        // if file is ignored we will pass it
        if (in_array($class, $ignoreClasses)) {
            return null;
        }

        return new $class;
    }

    public function getTestCases(): array
    {
        $this->checkDirectory();
        $testCases = [];


        foreach ($this->fileList as $file)
        {
            if (!file_exists($file)) {
                throw new \RuntimeException(sprintf(
                    'File "%s" does not exist.',
                    $file
                ));
            }

            $class = static::getResolverClassFromPath(
                $file,
                $this->ignoreClasses
            );

            if (class_exists($class) && is_a($class, TestCase::class))
            {
                $obj = new $class;
                $testCases[] = $obj;
            }
        }

       return $testCases;
    }
}