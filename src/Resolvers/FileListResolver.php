<?php

namespace NovaTech\TestQL\Resolvers;

use NovaTech\TestQL\Interfaces\TestCaseResolverInterface;
use NovaTech\TestQL\TestCase;


class FileListResolver implements  TestCaseResolverInterface
{
    public function __construct(
        public array $fileList =[],
        public array $ignoreClasses = [],
        private bool $strict = false
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

    /**
     * this function will try to resolve from given path
     * if 'strict' is true it will throw exception either class is not found
     * or its not a TestCase, if strict is false just ignore it
     *
     * @param string $file
     * @param array $ignoreClasses
     * @param bool $strict
     * @return TestCase|null
     */
    public static function getResolverClassFromPath(string $file, array $ignoreClasses, bool $strict = false): ?TestCase{
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



        if (!class_exists($className)) {

            if ($strict) {
                throw new \RuntimeException(
                    sprintf(
                        'Class "%s" does not exist.',
                        $className
                    )
                );
            }else{
                return null;
            }

        }


        // if file is ignored we will pass it
        if (in_array($className, $ignoreClasses)) {
            return null;
        }

        try {
            $object = new $className;
            print_r($object);
            if (!$object instanceof TestCase) {
                if ($strict) {
                    throw new \RuntimeException(
                        sprintf(
                            'Class "%s" is not a valid test case.',
                            $className
                        )
                    );
                }else{
                    return null;
                }
            }else{
                return $object;
            }
        }catch (\Exception $e){
            return null;
        }

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
                $this->ignoreClasses,
                $this->strict
            );

            if ($class) {
                $testCases[] = $class;
            }
        }

       return $testCases;
    }
}