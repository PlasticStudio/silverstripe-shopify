<?php

namespace Swordfox\Shopify\Task;

ini_set('max_execution_time', 300);

use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Swordfox\Shopify\Client;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class Import
 *
 * @author Graham McLellan
 */
class ShopifyShipping extends BuildTask
{
    protected string $title = 'Shopify shipping zones/rates/countries';

    protected static string $description = 'Shopify shipping zones/rates/countries';

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

        $this->updateShipping($client);

        if (!Director::is_cli()) {
            $output->writeln('</pre>');;
        }
        
        $output->writeln('Done');
        return 0;
    }
}
