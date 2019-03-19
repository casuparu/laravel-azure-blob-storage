<?php

namespace Casuparu\LaravelAzureBlobStorage;

use Storage;
use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use function sprintf;
use function strpos;

/**
 * Class AzureBlobStorageExtendedAdapter
 *
 * Service provider for Azure blob storage
 * 
 * @package   laravel-azure-blob-storage
 * @author    Caspar Mølholt Kjellberg <mail@caspark.com>
 * @copyright 2019 Caspar Mølholt Kjellberg
 * @link      https://github.com/casuparu/laravel-azure-blob-storage
 */
class AzureBlobStorageServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('azure', function ($app, $config) {
            if ($config['local'] == true) {
                $endpoint = 'UseDevelopmentStorage=true';
            } else {
                $endpoint = sprintf(
                    'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s;',
                    $config['name'],
                    $config['key']
                );

                /*
                 * If an endpoint url is specified in the environment, use that
                 */
                if ($config['url'] != '') {
                    $endpoint .= 'BlobEndpoint=' . (($config['https'] == true) ? 'https' : 'http') . '://' 
                        . $config['url'] . ':' . $config['port'] . '/' . $config['name'] . ';';
                }
                
                /*
                 * When sig= is found in the key, we assume that we must build a ConnectionString from the SAS principle.
                 */
                if (strpos($config['key'],'sig=') !== false)
                {
                    $endpoint = sprintf(
                        'BlobEndpoint=https://%s.blob.core.windows.net;SharedAccessSignature=%s',
                        $config['name'],
                        $config['key']
                    );
                }

            }

            $client = BlobRestProxy::createBlobService($endpoint);
            return new Filesystem(new AzureBlobStorageExtendedAdapter($client, $config['container'], $config['prefix'], $config['key'], $config['url']));
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
