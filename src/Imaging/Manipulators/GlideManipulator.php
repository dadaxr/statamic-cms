<?php

namespace Statamic\Imaging\Manipulators;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use League\Glide\Server;
use League\Glide\ServerFactory;
use Statamic\Facades\Asset;
use Statamic\Imaging\Manipulators\Glide\ImageGenerator;

class GlideManipulator extends Manipulator
{
    private ImageGenerator $generator;

    public function __construct(private array $config = [])
    {
        //
    }

    public function getAvailableParams(): array
    {
        return [
            'bg',
            'blur',
            'border',
            'bri',
            'con',
            'crop',
            'dpr',
            'filt',
            'fit',
            'flip',
            'fm',
            'gam',
            'h',
            'mark',
            'markalpha',
            'markfit',
            'markh',
            'markpad',
            'markpos',
            'markw',
            'markx',
            'marky',
            'or',
            'p',
            'pixel',
            'q',
            'sharp',
            'w',
        ];
    }

    public function getUrl(): string
    {
        $prefix = str($this->config['url'])->start('/')->finish('/');

        return $prefix.$this->generate();
    }

    public function getDataUrl(): string
    {
        // generate the image

        // read the image

        // construct the data url

        // return it

        return 'data:...';
    }

    public function getAttributes(): array
    {
        // generate the image

        // use the image :: attributes to get from that generated image

        // return the attributes. dont include url.
    }

    public function getGenerator(): ImageGenerator
    {
        return $this->generator ?? new ImageGenerator($this->getServer());
    }

    public function setGenerator(ImageGenerator $generator): self
    {
        $this->generator = $generator;

        return $this;
    }

    public function getServer(): Server
    {
        $server = ServerFactory::create([
            'source' => base_path(), // This gets overridden on the fly by the image generator
            'cache' => $this->getCacheDisk()->getDriver(),
            'driver' => $this->config['library'] ?? 'gd',
            'watermarks' => public_path(),
        ]);

        // Todo: Glide::generateHashUsing() etc should be translated to Image::driver('glide')->generateHashUsing()
        // where it gets set on this instance rather than a singleton.
        $server->setCachePathCallable($this->getCachePathCallable());

        return $server;
    }

    private function generate(): string
    {
        return match ($this->getSourceType()) {
            'path' => $this->getGenerator()->generateByPath($this->source, $this->params),
            'url' => $this->getGenerator()->generateByUrl($this->source, $this->params),
            'asset' => $this->getGenerator()->generateByAsset($this->source, $this->params),
            'id' => $this->getGenerator()->generateByAsset(Asset::findOrFail($this->source), $this->params),
        };
    }

    public function getCacheDisk(): Filesystem
    {
        if (! $cache = $this->config['cache'] ?? null) {
            throw new \Exception('Glide cache is not defined.');
        }

        try {
            return Storage::disk($cache);
        } catch (\InvalidArgumentException $e) {
            return Storage::build([
                'driver' => 'local',
                'root' => $cache,
                'visibility' => 'public',
            ]);
        }
    }

    private function getCachePathCallable()
    {
        $hashCallable = $this->getHashCallable();

        return function ($path, $params) use ($hashCallable) {
            /* @var $this \League\Glide\Server */

            $sourcePath = $this->getSourcePath($path);

            if ($this->sourcePathPrefix) {
                $sourcePath = substr($sourcePath, strlen($this->sourcePathPrefix) + 1);
            }

            $params = $this->getAllParams($params);
            unset($params['s'], $params['p']);
            ksort($params);

            $ext = $params['fm'] ?? pathinfo($path, PATHINFO_EXTENSION);
            $ext = $ext === 'pjpg' ? 'jpg' : $ext;
            $ext = $ext ? ".$ext" : '';

            return vsprintf('%s/%s/%s/%s%s', [
                $this->cachePathPrefix,
                $sourcePath,
                $hashCallable($sourcePath, $params),
                pathinfo($path, PATHINFO_FILENAME),
                $ext,
            ]);
        };
    }

    private function getHashCallable()
    {
        return $this->customHashCallable ?? function (string $source, array $params) {
            return md5($source.'?'.http_build_query($params));
        };
    }
}