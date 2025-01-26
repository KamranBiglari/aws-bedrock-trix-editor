<?php

namespace KamranBiglari\AwsBedrockTrixEditor\Components;

use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Wizard;
use Filament\Notifications\Notification;
use Aws\Sdk as AWS;
use Illuminate\Support\Str;
use Closure;

class AwsBedrockTrixEditor extends RichEditor
{

    protected string $view = 'aws-bedrock-trix-editor::aws-bedrock-trix-editor';
    protected array | Closure $toolbarButtons = [
        'attachFiles',
        'blockquote',
        'bold',
        'bulletList',
        'codeBlock',
        'h2',
        'h3',
        'italic',
        'link',
        'orderedList',
        'strike',
        'undo',
        'redo',
        'awsBedrockTools'
    ];

    public $options = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->options = $this->getOptions();

        $this->registerListeners([

            'awsBedrockTrixEditor::execute' => [
                function ($component, string $statePath, string $uuid, string $action = null ,$selectedText = ''): void {

                    if ($component->isDisabled() || $statePath !== $component->getStatePath()) {
                        return;
                    }

                    $livewire = $component->getLivewire();
                    $textToSend = $component->getState();

                    $configPrompts  = optional(config('aws-bedrock-trix-editor'))['prompt-prefixes'];
                    $prompt        = collect($configPrompts)->where('prefix_key', $action)->first();

                    if(is_null($component->getState())){
                        $livewire->dispatch('update-selected-content', statePath: $statePath);
                        $this->sendNotification(__('aws-bedrock-trix-editor::aws-bedrock-trix-editor.notification.warning'), __('aws-bedrock-trix-editor::aws-bedrock-trix-editor.notification.input_empty'), 'warning');
                        return;
                    }

                    if(isset($prompt['on_selected']) && $prompt['on_selected'] == true){
                        if($selectedText == ''){
                            $livewire->dispatch('update-selected-content', statePath: $statePath);
                            $this->sendNotification(__('aws-bedrock-trix-editor::aws-bedrock-trix-editor.notification.warning'), __('aws-bedrock-trix-editor::aws-bedrock-trix-editor.notification.input_empty'), 'warning');
                            return;
                        }
                        $textToSend = $selectedText;
                    }

                    $req = $this->sendAwsBedrockRrequest($textToSend,$action);
                    $awsBedrockResponse = $req['message'];

                    if(!$req['status']){
                        $livewire->dispatch('update-selected-content', statePath: $statePath);
                        $livewire->dispatch('update-content', statePath: $statePath);
                        $this->sendNotification(__('aws-bedrock-trix-editor::aws-bedrock-trix-editor.notification.error'), $awsBedrockResponse, 'danger');
                        return;
                    }

                    if(!isset($prompt['on_selected'])){
                        $livewire->dispatch('update-content', statePath: $statePath,content: $awsBedrockResponse);
                    }else{
                        $livewire->dispatch('update-selected-content', statePath: $statePath,content: $awsBedrockResponse);
                    }

                    $this->sendNotification(__('aws-bedrock-trix-editor::aws-bedrock-trix-editor.notification.success'), null, 'success');


                },
            ],
        ]);

    }

    /**
     * Sent the Request to the AWS Bedrock API
     *
     * @param $prompt
     * @param string $action
     * @return array
     */
    function sendAwsBedrockRrequest($prompt = null,string $promptKey = 'run'): array
    {
        try{
            // Aws Bedrock Request

            $awsBedrockClient = (new AWS([
                'region' => config('aws-bedrock-trix-editor.region', config('aws.region')),
                'version' => 'latest',
            ]))->createClient('bedrock-runtime');

            #sleep(2); return ['status' => true,'message' => "<b>New</b> Test-".time()];

            $promptPrefix = $this->getPrompt($promptKey);
            if(is_null($promptPrefix)){
                return ['status' => false,'message' => __('aws-bedrock-trix-editor::aws-bedrock-trix-editor.notification.invalid_action')];
            }

            $result = $awsBedrockClient->invokeModel([
                'modelId' => config('aws-bedrock-trix-editor.model_id'),
                'contentType' => 'application/json',
                'accept'    => 'application/json',
                'body' => json_encode([
                    'messages' => $promptPrefix.$prompt,
                    ...(config('aws-bedrock-trix-editor.extras',[]))
                ])
            ]);

            $responseBody = json_decode($result['body'], true);

            $responseText = 'No response text found.';
            // dd($responseBody);
            if (isset($responseBody['content']) && is_array($responseBody['content'])) {
                foreach ($responseBody['content'] as $message) {
                    if (isset($message['type']) && $message['type'] === 'text' && isset($message['text'])) {
                        $responseText = $message['text'];  // Extract the actual text
                        break;  // Stop after the first text response
                    }
                }
            }

            return ['status' => true,'message' => $responseText];

        }catch(\Throwable $e){

            return ['status' => false,'message' => $e->getMessage()];

        }
    }

    /**
     * Get options for the Aws Bedrock Button Dropdown
     *
     * @return array
     */
    function getOptions():array
    {
        $prefixes = collect(config('aws-bedrock-trix-editor')['prompt-prefixes']);
        return $prefixes->map(function($value, $key) {
            return [
                'key'           => $value['prefix_key'],
                'label'         => __('aws-bedrock-trix-editor::aws-bedrock-trix-editor.' . $value['prefix_label']),
                'on_selected'   => isset($value['on_selected']) ? $value['on_selected'] : false
            ];
        })->all();
    }


    /**
     * Returns the prompt associated with the given key.
     *
     * @param string $promptKey The key of the prompt to retrieve.
     *
     * @return string|null The prompt associated with the given key, or null if not found.
     */
    function getPrompt(string $promptKey): ?string
    {
        $configPrompts = optional(config('aws-bedrock-trix-editor'))['prompt-prefixes'];
        if(is_null($configPrompts)){
            return null;
        }
        $prompt = collect($configPrompts)->where('prefix_key', $promptKey)->first();
        return $prompt ? $prompt['prefix'] : null;
    }



    /**
     * Sends a notification with the specified icon, color, body and type.
     *
     * @param string|null $icon
     * @param string $color
     * @param string $body
     * @param string $type
     */
    protected function sendNotification(?string $title, ?string $body, string $type = 'success'): void
    {
        if(config('aws-bedrock-trix-editor.enable_notifications') == false && $type == 'success'){
            return;
        }
        Notification::make()
            ->title($title)
            ->{$type}()
            ->body($body)
            ->send();
    }

}
