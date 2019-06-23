<?php

namespace Zeloc\EnvInfo\Console\Command;

use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Store\Api\StoreRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnvInfoCommand extends Command
{
    private $directoryList;
    private $scopeConfig;
    private $storeRepository;
    private $envData;
    private $cachManager;

    /**
     * EnvInfoCommand constructor.
     * @param CacheManager $cacheManager
     * @param StoreRepositoryInterface $storeRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryList $directoryList
     * @param string|null $name
     */
    public function __construct(
        CacheManager $cacheManager,
        StoreRepositoryInterface $storeRepository,
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList,
        string $name = null
    ) {
        $this->cachManager = $cacheManager;
        $this->directoryList = $directoryList;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($name);
        $this->storeRepository = $storeRepository;
        $this->envData = $this->getEnvData();
    }

    protected function configure()
    {
        $this->setName('zeloc:env:info');
        $this->setDescription('Display env info');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<question>########    Env Info    #########</question>');
        $output->writeln('');
        $output->writeln('<info>Site URL: </info><comment>' . $this->getHostUrl() . '</comment>');
        $output->writeln('<info>Base Path: </info><comment>' . $this->getRootPath() . '</comment>');
        $output->writeln('<info>Front Name: </info><comment>' . $this->envData['backend']['frontName'] . '</comment>');
        $output->writeln(
            '<info>Database Name:</info> <comment>'
            . $this->envData['db']['connection']['default']['dbname'] . '</comment>'
        );
        $output->writeln(
            '<info>Database User Name:</info> <comment>'
            . $this->envData['db']['connection']['default']['username'] . '</comment>'
        );
        $output->writeln(
            '<info>Database Password:</info> <comment>'
            . $this->envData['db']['connection']['default']['password'] . '</comment>'
        );
        $output->writeln('<info>Mage Mode:</info> <comment>' . $this->envData['MAGE_MODE'] . '</comment>');
        $output->writeln('<info>Current Stores:</info> <comment>' . $this->getStoreNames() . '</comment>');
        $output->writeln(
            '<info>Caches Enabled:</info> <comment>' . $this->getCacheList($this->getCacheState(1)) . '</comment>'
        );
        $output->writeln(
            '<info>Caches Disabled:</info> <comment>' . $this->getCacheList($this->getCacheState(0)) . '</comment>'
        );
        $output->writeln('<info>PHP Version:</info> <comment>' . phpversion() . '</comment>');
        $output->writeln('<info>Xdebug Status:</info> <comment>' . $this->getXdebugStatus() . '</comment>');
        $output->writeln('');
        $output->writeln('<question>##################################</question>');
        $output->writeln('');
    }

    private function getCacheList($cacheStatus)
    {
        $list = '';

        if ($cacheStatus && is_array($cacheStatus)) {
            foreach ($cacheStatus as $key => $value) {
                if (!$list) {
                    $list .= $key;
                } else {
                    $list .= ', ' . $key;
                }
            }
        }

        if (!$list) {
            return 'None';
        }

        return $list;
    }

    private function getXdebugStatus()
    {
        return extension_loaded('xdebug') ? 'Active' : 'Disabled ';
    }

    private function getCacheState($stateType)
    {
        $cacheState = [];

        if (!in_array($stateType, [1, 0])) {
            return $cacheState;
        }
        foreach ($this->cachManager->getStatus() as $type => $state) {
            if ($state == $stateType) {
                $cacheState[$type] = $stateType;
            }
        }

        return $cacheState;
    }

    private function getRootPath()
    {
        return $this->directoryList->getRoot();
    }

    private function getEnvData()
    {
        if (!$this->envData) {
            $envPath = $this->getRootPath() . '/app/etc/env.php';
            $this->envData = include $envPath;
        }

        return $this->envData;
    }

    private function getHostUrl()
    {
        return $this->scopeConfig->getValue('web/unsecure/base_link_url');
    }

    private function getStoreNames()
    {
        $storeInfo = $this->storeRepository->getList();
        $stores = '';
        foreach ($storeInfo as $item) {
            if (!$stores) {
                $stores .= $item->getName();
            } else {
                $stores .= ', ' . $item->getName();
            }
        }

        return $stores;
    }
}
