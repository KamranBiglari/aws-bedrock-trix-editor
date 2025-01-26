<?php

 namespace KamranBiglari\AwsBedrockTrixEditor;


use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Filament\Support\Assets\Asset;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Js;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\AlpineComponent;

class AwsBedrockTrixEditorServiceProvider extends PackageServiceProvider
{

    public static string $name = 'aws-bedrock-trix-editor';

    public function configurePackage(Package $package): void
    {
        $package
        ->name(static::$name)
        ->hasViews()
        ->hasConfigFile()
        ->hasTranslations();
    }

    public function boot()
    {
        parent::boot();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName(),
        );
    }

    protected function getAssets(): array
    {
        return [
            AlpineComponent::make('aws-bedrock-trix-editor', __DIR__ . '/../dist/components/aws-bedrock-trix-editor.js'),
        ];
    }

    protected function getAssetPackageName(): string
    {
        return 'kamranbiglari/aws-bedrock-trix-editor';
    }


}
