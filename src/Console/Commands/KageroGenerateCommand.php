<?php

declare(strict_types=1);

namespace Kagero\Laravel\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\File;

#[Signature('kagero:generate')]
#[Description('Generate the root view manifest for Kagero')]
final class KageroGenerateCommand extends Command
{
    public function handle(Vite $vite): void
    {
        $manifestPath = public_path('build/manifest.json');

        if (! File::exists($manifestPath)) {
            $this->fail('Vite manifest not found. Run `npm run build` first.');
        }

        /** @var array<string, array<string, mixed>> $manifest */
        $manifest = File::json($manifestPath, JSON_THROW_ON_ERROR);

        $vite->useHotFile('')->createAssetPathsUsing(fn (string $path): string => '/'.$path);

        $rootView = [
            'version' => hash_file('xxh128', $manifestPath),
            'fonts' => $vite->fonts()->toHtml(),
            'vite' => $vite(['resources/css/app.css', 'resources/js/app.ts'])->toHtml(),
            'pages' => (object) $this->pages($vite, $manifest),
        ];

        $json = json_encode($rootView, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";

        File::ensureDirectoryExists(resource_path('js/kagero'));
        File::replace(resource_path('js/kagero/root-view.json'), $json);

        $this->components->info('Kagero root view generated at [resources/js/kagero/root-view.json].');
    }

    /**
     * Compile the Vite tags for every page entry in the manifest.
     *
     * @param  array<string, array<string, mixed>>  $manifest
     * @return array<string, string>
     */
    private function pages(Vite $vite, array $manifest): array
    {
        $pages = [];

        foreach (array_keys($manifest) as $key) {
            if (preg_match('#^resources/js/pages/(.+)\.vue$#', $key, $matches)) {
                $pages[$matches[1]] = $vite([$key])->toHtml();
            }
        }

        ksort($pages);

        return $pages;
    }
}
