<?php

namespace Swordfox\Shopify\Task;

ini_set('max_execution_time', '300');

use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Swordfox\Shopify\Client;

/**
 * Class Import
 *
 * @author Graham McLellan
 */
class Import extends BuildTask
{
    protected string $title = 'Import shopify products';

    protected static string $description = 'Import shopify products from the configured store';

    protected bool $enabled = true;

    public $api_limit;
    public $cron_interval;

    public function getOptions(): array
    {
        return [
            new InputOption('productsonly', null, InputOption::VALUE_NONE, 'Import products only'),
            new InputOption('productsall', null, InputOption::VALUE_NONE, 'Import all products'),
            new InputOption('productsingle', null, InputOption::VALUE_REQUIRED, 'Import a single product by ID'),
            new InputOption('collectionsonly', null, InputOption::VALUE_NONE, 'Import collections only'),
        ];
    }

    public function execute(InputInterface $input, PolyOutput $output): int
    {
        try {
            $client = new Client();
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $output->writeln($e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            return 1;
        }

        $productsonly = $input->getOption('productsonly');
        $productsall = $input->getOption('productsall');
        $productssingle = $input->getOption('productsingle');
        $collectionsonly = $input->getOption('collectionsonly');

        // prevent conflicting options
        $optionsUsed = array_filter([
            $productsonly,
            $productsall,
            $productssingle !== null,
            $collectionsonly
        ]);

        if (count($optionsUsed) > 1) {
            $output->writeln('<error>Only one mode can be used at a time.</error>');
            return 1;
        }

        if (!Director::is_cli()) {
            $output->writeln('<pre>');
        }

        if ($productsonly) {
            $this->importProducts($client);

        } elseif ($collectionsonly) {
            $this->importCollections($client, 'custom_collections');
            $this->importCollections($client, 'smart_collections');

        } elseif ($productsall) {
            $this->importProductsAll($client);

        } elseif ($productssingle !== null) {
            $this->importProductsSingle($client, $productssingle);

        } else {
            $this->importCollections($client, 'custom_collections', $client->cron_interval);
            $this->importCollections($client, 'smart_collections', $client->cron_interval);
            $this->importProducts($client);
        }

        if (!Director::is_cli()) {
            $output->writeln('</pre>');
        }
        
        $output->writeln('Done');
        return 0;
    }

    /**
     * Loop the given data map and possible sub maps
     *
     * @param array $map
     * @param $object
     * @param $data
     */
    public static function loop_map($map, &$object, $data)
    {
        $skip = ['created_at', 'updated_at'];

        foreach ($map as $from => $to) {

            if (in_array($from, $skip, true)) {
                continue;
            }
            
            if (!isset($data->{$from})) {
                continue;
            }

            $value = $data->{$from};

            // handle nested objects/arrays safely
            if (is_object($value) || is_array($value)) {
                $value = json_encode($value);
            }

            $object->$to = DBField::create_field('Varchar', (string)$value);
        }
    }
}
