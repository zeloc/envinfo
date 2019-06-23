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
        $output->writeln('<info>Site URL: </info><fg=blue;>' . $this->getHostUrl() . '</>');
        $output->writeln('<info>Base Path: </info><fg=blue;>' . $this->getRootPath() . '</>');
        $output->writeln('<info>Front Name: </info><fg=blue;>' . $this->envData['backend']['frontName'] . '</>');
        $output->writeln(
            '<info>Database Name:</info> <fg=blue;>'
            . $this->envData['db']['connection']['default']['dbname'] . '</>'
        );
        $output->writeln(
            '<info>Database User Name:</info> <fg=blue;>'
            . $this->envData['db']['connection']['default']['username'] . '</>'
        );
        $output->writeln(
            '<info>Database Password:</info> <fg=blue;>'
            . $this->envData['db']['connection']['default']['password'] . '</>'
        );
        $output->writeln('<info>Mage Mode:</info> <fg=blue;>' . $this->envData['MAGE_MODE'] . '</>');
        $output->writeln('<info>Current Stores:</info> <fg=blue;>' . $this->getStoreNames() . '</>');
        $output->writeln(
            '<info>Caches Enabled:</info> <fg=blue;>' . $this->getCacheList($this->getCacheState(1)) . '</>'
        );
        $output->writeln(
            '<info>Caches Disabled:</info> <fg=blue;>' . $this->getCacheList($this->getCacheState(0)) . '</>'
        );
        $output->writeln('<info>PHP Version:</info> <fg=blue;>' . phpversion() . '</>');
        $output->writeln('<info>Xdebug Status:</info> <fg=blue;>' . $this->getXdebugStatus() . '</>');
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
