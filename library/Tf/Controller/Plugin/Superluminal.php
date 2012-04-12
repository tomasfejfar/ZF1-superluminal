<?php
class Tf_Controller_Plugin_Superluminal extends Zend_Controller_Plugin_Abstract
{
    protected $knownClasses = array();
    protected $usesNames = array();
    protected $r = array();
    const ZF_CLASS_CACHE = './cache.php';

    public function dispatchLoopShutdown()
    {
        $this->cache();
    }
/**
     * Cache declared interfaces and classes to a single file
     *
     * @param  \Zend\Mvc\MvcEvent $e
     * @return void
     */
    public function cache()
    {

        if (file_exists(self::ZF_CLASS_CACHE)) {
            $this->reflectClassCache();
            $code = file_get_contents(self::ZF_CLASS_CACHE);
        } else {
            $code = "<?php\n";
        }

        $classes = array_merge(get_declared_interfaces(), get_declared_classes());
        foreach ($classes as $class) {
            // Skip the autoloader factory and this class
            if (in_array($class, array('Zend_Loader_AutoloaderFactory', __CLASS__))) {
                continue;
            }

            // Skip any classes we already know about
            if (in_array($class, $this->knownClasses)) {
                continue;
            }

            $class = new Zend_Reflection_Class($class);

            // Skip internal classes or classes from extensions
            if ($class->isInternal()
                || $class->getExtensionName()
            ) {
                continue;
            }

            // Skip ZF2-based autoloaders
            if (in_array('Zend\Loader\SplAutoloader', $class->getInterfaceNames())) {
                continue;
            }

            $code .= $this->getCacheCode($class);
        }

        file_put_contents(self::ZF_CLASS_CACHE, $code);
    }

    /**
     * Generate code to cache from class reflection.
     *
     * This is a total mess, I know. Just wanted to flesh out the logic.
     * @todo Refactor into a class, clean up logic, DRY it up, maybe move
     *       some of this into Zend\Code
     * @param  ClassReflection $r
     * @return string
     */
    protected function getCacheCode(Zend_Reflection_Class $r)
    {
        $useString = '';
        $usesNames = array();

        $declaration = '';

        if ($r->isAbstract() && !$r->isInterface()) {
            $declaration .= 'abstract ';
        }

        if ($r->isFinal()) {
            $declaration .= 'final ';
        }

        if ($r->isInterface()) {
            $declaration .= 'interface ';
        }

        if (!$r->isInterface()) {
            $declaration .= 'class ';
        }

        $declaration .= $r->getShortName();

        if ($parent = $r->getParentClass()) {
            $parentName   = array_key_exists($parent->getName(), $usesNames)
                          ? ($usesNames[$parent->getName()] ? null : $parent->getShortName())
                          : (0
                            ? substr($parent->getName(), strlen($r->getNamespaceName()) + 1)
                            : '\\' . $parent->getName());

            $declaration .= " extends {$parentName}";
        }

        $interfaces = array_diff($r->getInterfaceNames(), $parent ? $parent->getInterfaceNames() : array());
        if (count($interfaces)) {
            foreach ($interfaces as $interface) {
                $iReflection = new Zend_Reflection_Class($interface);
                $interfaces  = array_diff($interfaces, $iReflection->getInterfaceNames());
            }
            $declaration .= $r->isInterface() ? ' extends ' : ' implements ';
            $declaration .= implode(', ', array_map(array($this, 'getDefinition'), $interfaces));
        }

        $classContents = $r->getContents(false);
        $classFileDir  = dirname($r->getFileName());
        $classContents = str_replace('__DIR__', sprintf("'%s'", $classFileDir), $classContents);

        return $useString
               . $declaration . "\n"
               . strstr($classContents, '{'); // messes up when 'implements' is on separate line
    }

    protected function getDefinition($interface) {
        $iReflection = new Zend_Reflection_Class($interface);
        return (array_key_exists($iReflection->getName(), $this->usesNames)
               ? ($this->usesNames[$iReflection->getName()] ? null : $iReflection->getShortName())
               : ((0 === strpos($iReflection->getName(), '/'))
                 ? substr($iReflection->getName(), strlen($this->r->getNamespaceName()) + 1)
                 : '\\' . $iReflection->getName()));
    }

    /**
     * Determine what classes are present in the cache
     *
     * @return void
     */
    protected function reflectClassCache()
    {
        $scanner = new FileScanner(self::ZF_CLASS_CACHE);
        $this->knownClasses = $scanner->getClassNames();
    }
}