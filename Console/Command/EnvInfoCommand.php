<?php

namespace Zeloc\EnvInfo\Console\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Filesystem\DirectoryList;

class EnvInfoCommand extends Command
{
    private $directoryList;
    private $scopeConfig;
    private $storeRepository;
    private $envData;

    public function __construct(
        StoreRepositoryInterface $storeRepository,
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList,
        string $name = null
    ){
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
        $output->writeln('<info>Database Name:</info> <comment>' . $this->envData['db']['connection']['default']['dbname'] . '</comment>');
        $output->writeln('<info>Database User Name:</info> <comment>' . $this->envData['db']['connection']['default']['username'] . '</comment>');
        $output->writeln('<info>Database Password:</info> <comment>' . $this->envData['db']['connection']['default']['password'] . '</comment>');
        $output->writeln('<info>Mage Mode:</info> <comment>' . $this->envData['MAGE_MODE'] . '</comment>');
        $output->writeln('<info>Current Stores:</info> <comment>' . $this->getStoreNames() . '</comment>');
        $output->writeln('');
        $output->writeln('<question>##################################</question>');
        $output->writeln('');
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
