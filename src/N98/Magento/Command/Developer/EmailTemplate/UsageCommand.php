<?php

namespace N98\Magento\Command\Developer\EmailTemplate;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

class UsageCommand extends AbstractMagentoCommand
{
    /**
     * @var array
     */
    protected $infos;

    protected function configure()
    {
        $this
            ->setName('dev:email-template:usage')
            ->setDescription('Display database transactional email template usage')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);
        $this->initMagento();
        $this->findEmailTemplates();

        if (!empty($this->infos)) {
            $this->getHelper('table')
                ->setHeaders(array('id', 'Name', 'Scope', 'Scope Id', 'Path'))
                ->renderByFormat($output, $this->infos, $input->getOption('format'));
        } else {
            $output->writeln("No transactional email templates stored in the database.");
        }
    }

    protected function findEmailTemplates()
    {
        $templates = \Mage::getModel('adminhtml/email_template')->getCollection();

        foreach ($templates as $template){

            /**
             * Some modules overload the template class so that the method getSystemConfigPathsWhereUsedCurrently
             * is not available, this is a workaround for that
             */
            if (!method_exists($template, 'getSystemConfigPathsWhereUsedCurrently')){
                $instance = new \Mage_Adminhtml_Model_Email_Template();
                $template = $instance->load($template->getId());
            }

            $configPaths = $template->getSystemConfigPathsWhereUsedCurrently();

            if (count($configPaths)){
                foreach ($configPaths as $configPath) {
                    $this->infos[] = array(
                        'id'            => $this->sanitizeEmailProperty($template->getId()),
                        'Template Code' => $this->sanitizeEmailProperty($template->getTemplateCode()),
                        'Scope'         => $this->sanitizeEmailProperty($configPath['scope']),
                        'Scope Id'      => $this->sanitizeEmailProperty($configPath['scope_id']),
                        'Path'          => $this->sanitizeEmailProperty($configPath['path']),
                    );
                }
            } else {
                $this->infos[] = array(
                    'id'            => $this->sanitizeEmailProperty($template->getId()),
                    'Template Code' => $this->sanitizeEmailProperty($template->getTemplateCode()),
                    'Scope'         => 'Unused',
                    'Scope Id'      => 'Unused',
                    'Path'          => 'Unused',
                );
            }
        }
    }

    /**
     * @param string $input Module property to be sanitized
     *
     * @return string
     */
    private function sanitizeEmailProperty($input)
    {
        return trim($input);
    }
}
