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

    private static function getNamespace(string $content)
    {
        $re = '/namespace (.*?);/m';
        $namespaceMatches = null;
        preg_match($re, $content, $namespaceMatches );
        if ($namespaceMatches && count($namespaceMatches)) {
            return $namespaceMatches[1];
        }
        return '';
    }
    public static function getResolverClassFromPath(string $file, array $ignoreClasses): ?TestCase{
        $file = realpath($file);

        if (!file_exists($file)) {
            throw new \RuntimeException(sprintf(
                'File "%s" does not exist.',
                $file
            ));
        }

        $content = file_get_contents($file);
        if(!str_contains($content, '<?php')){
            throw new \RuntimeException(
                sprintf(
                    'File "%s" is not a valid PHP file.',
                    $file
                )
            );
        }

        // get the file name of the current file without the extension
        // which is essentially the class name
        $className = trim(basename($file, '.php'));
        $namespace = static::getNamespace($content);
        $className = $namespace. '\\' . $className;


        // if file is ignored we will pass it
        if (in_array($className, $ignoreClasses)) {
            return null;
        }

        return new $className;
    }

    public function getTestCases(): array
    {
        $testCases = [];
        $files= [];

        foreach ($this->fileList as $file)
        {
            $files = [
                ...$files,
                ...glob($file)
            ];
        }

        foreach ($files as $file)
        {
            $class = static::getResolverClassFromPath(
                $file,
                $this->ignoreClasses
            );

            $testCases[] = $class;
        }

       return $testCases;
    }
}