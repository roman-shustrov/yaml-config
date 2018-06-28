<?php

namespace YamlConfig\ClassCodeGenerator;

use YamlConfig\StructureCodeGenerator\ConfigStructureInfoInterface;
use YamlConfig\StructureCodeGenerator\StructureInfoList;
use YamlConfig\StructureCodeGenerator\UseStructure;
use YamlConfig\YamlFileToTree;

/** Список информации о классах */
class ClassInfoList extends StructureInfoList
{


    /** @var string пространство имён интерфейса узла конфига */
    protected $configInterfaceNamespace;

    /** @var string пространство имён интерфейса узла конфига */
    protected $configInterfaceListNamespace;

    /** @var YamlFileToTree относительный путь расположения yaml-файл с настройками для интерфейса */
    protected $yamlFileToTreeInterface;

    /** @var YamlFileToTree относительный путь расположения yaml-файл с настройками для интерфейса(список) */
    protected $yamlFileToTreeInterfaceList;

    /**
     * @return YamlFileToTree|null
     */
    public function getYamlFileToTreeInterfaceList(): ?YamlFileToTree
    {
        return $this->yamlFileToTreeInterfaceList;
    }

    /**
     * @param YamlFileToTree|null $yamlFileToTreeInterfaceList
     * @return $this
     */
    public function setYamlFileToTreeInterfaceList(YamlFileToTree $yamlFileToTreeInterfaceList = null)
    {
        $this->yamlFileToTreeInterfaceList = $yamlFileToTreeInterfaceList;
        return $this;
    }

    /**
     * @return YamlFileToTree|null
     */
    public function getYamlFileToTreeInterface(): ?YamlFileToTree
    {
        return $this->yamlFileToTreeInterface;
    }

    /**
     * @param YamlFileToTree|null $yamlFileToTreeInterface
     * @return $this
     */
    public function setYamlFileToTreeInterface(YamlFileToTree $yamlFileToTreeInterface = null)
    {
        $this->yamlFileToTreeInterface = $yamlFileToTreeInterface;
        return $this;
    }


    /**
     * @return ConfigStructureInfoInterface информация о структуре конфига
     */
    protected function createConfigStructureInfo()
    {
        return new ConfigClassInfo();
    }

    /**
     * @return UseStructure подключаемая структура
     */
    protected function createUseStructure()
    {
        return new UseStructure();
    }


    /**
     * @return string пространство имён интерфейса узла конфига
     */
    protected function getConfigInterfaceNamespace()
    {
        return $this->configInterfaceNamespace;
    }

    /**
     * @param string $configInterfaceNamespace пространство имён интерфейса узла конфига
     * @return $this
     */
    public function setConfigInterfaceNamespace($configInterfaceNamespace)
    {
        $this->configInterfaceNamespace = $configInterfaceNamespace;
        return $this;
    }

    /**
     * @return string пространство имён интерфейса узла конфига
     */
    protected function getConfigInterfaceListNamespace()
    {
        return $this->configInterfaceListNamespace;
    }

    /**
     * @param string $configInterfaceListNamespace пространство имён интерфейса узла конфига
     * @return $this
     */
    public function setConfigInterfaceListNamespace($configInterfaceListNamespace)
    {
        $this->configInterfaceListNamespace = $configInterfaceListNamespace;
        return $this;
    }

    /**
     * @param ConfigClassInfo $configStructureInfo
     * @param array $structureNode узел структуры для $configStructureInfo
     * @param array $path путь в виде масива к узлу
     */
    protected function addedModifiersStructure($configStructureInfo, array $structureNode, array $path)
    {
        $this->fillInterface($configStructureInfo, $structureNode, $path);
        $this->fillInterfaceList($configStructureInfo, $structureNode, $path);
        parent::addedModifiersStructure($configStructureInfo, $structureNode, $path);
    }

    /**
     * @param ConfigClassInfo $configClassInfo
     * @param array $structureNode узел структуры для $configStructureInfo
     * @param array $path путь в виде масива к узлу
     */
    protected function fillInterfaceList(ConfigClassInfo $configClassInfo, array $structureNode, array $path)
    {
        if(
            $this->getYamlFileToTreeInterfaceList()
        ) {
            foreach($this->getInterfaceListNameByPath($path) as $interfaceName) {
                $interfaceFullName = $this->getConfigInterfaceListNamespace() . '\\' . $interfaceName;
                $interfaceAlias = $this->generateInterfaceAlias($interfaceName);
                $useStructure = $this
                    ->createUseStructure()
                    ->setStructureFullName($interfaceFullName)
                    ->setAlias($interfaceAlias);
                $configClassInfo->addUseClasses($useStructure);
                $configClassInfo->addImplements($interfaceAlias);
            }
        }
    }

    /**
     * Масив имен интерфейсов
     * @param string[] $path
     * @return string[]
     */
    protected function getInterfaceListNameByPath(array $path)
    {
        $out = [];
        foreach($this->getYamlFileToTreeInterfaceList()->getYamlConfigTree() as $interfaceName => $interfaceConfig){
            if($this->inArrayXpath($path,$interfaceConfig['xpath'])){
                $out[] = $this->fixStructureName($interfaceName);
            }
        }
        return $out;
    }

    /**
     * @param array $path
     * @param array $xpathList
     * @return bool
     */
    protected function inArrayXpath(array $path, array $xpathList)
    {
        foreach($xpathList as $xpath){
            $domNodeList = $this->getYamlFileToTree()->getDOMNodeListByXpath($xpath);
            if($domNodeList == false){
                return false;
            }
            foreach($domNodeList as $domElement){
                $pathDomElement = explode('/',ltrim($domElement->getNodePath(),'/'));
                if($this->getNodePath($pathDomElement) == $this->getNodePath($path)){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param ConfigClassInfo $configClassInfo
     * @param array $structureNode узел структуры для $configStructureInfo
     * @param array $path путь в виде масива к узлу
     */
    protected function fillInterface(ConfigClassInfo $configClassInfo, array $structureNode, array $path)
    {
        if(
            $this->getYamlFileToTreeInterface()
            &&
            $this->iStructureNode($structureNode)
            &&
            $this->getInterfaceStructureNodeByNodePath($this->getNodePath($path)) !== null
        ){
            $interfaceNamespace = $this->getNamespaceByPath($this->getConfigInterfaceNamespace(), $path);
            $interfaceFullName = $interfaceNamespace. '\\'. $configClassInfo->getName();
            $interfaceAlias = $this->generateInterfaceAlias($configClassInfo->getName());

            $useStructure = $this
                ->createUseStructure()
                ->setStructureFullName($interfaceFullName)
                ->setAlias($interfaceAlias);

            $configClassInfo->addUseClasses($useStructure);
            $configClassInfo->addImplements($interfaceAlias);
        }
    }

    /**
     * @param array $nodePath список частей пути к узлу
     * @return array дерево конфигурации интерфейсов
     */
    protected function getInterfaceStructureNodeByNodePath(array $nodePath)
    {
        $out = $this->getYamlFileToTreeInterface()->getYamlConfigTree();
        if(empty($out)){
            return null;
        }
        foreach ($nodePath as $currentNodeName){
            if(isset( $out[$currentNodeName])) {
                $out = $out[$currentNodeName];
            }else{
                return null;
            }
        }
        return $out;
    }

    /**
     * @param string $interfaceName название интерфейса
     * @return string название псевдонима интерфейса
     */
    protected function generateInterfaceAlias(string $interfaceName):string
    {
        return $interfaceName.'Interface';
    }

}