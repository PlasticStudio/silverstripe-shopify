<?php

namespace Swordfox\Shopify\Task;

ini_set('max_execution_time', '300');

use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\InputInterface;
use Swordfox\Shopify\Client;

/**
 * Class Import
 *
 * @author Graham McLellan
 */
class DeleteOnShopify extends BuildTask
{
    protected string $title = 'Delete shopify products';

    protected static string $description = 'Delete shopify products with DeleteOnShopify = 1';

    protected bool $enabled = true;

    public function execute(InputInterface $input, PolyOutput $output): int
    {
        if (!Director::is_cli()) {
            $output->writeln('<pre>');;
        }

        try {
            $client = new Client();
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $output->writeln($e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return 1;
        }

        $this->deleteProducts($client);

        if (!Director::is_cli()) {
            $output->writeln('</pre>');;
        }

        $output->writeln('Done');
        return 0;
    }
}
