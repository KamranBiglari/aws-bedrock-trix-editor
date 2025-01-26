<?php

return [

    /*
     * Set the model_id to use for the AWS BEDROCK API.
     */
    'model_id' => env('TRIX_AWS_BEDROCK_MODEL_ID', 'anthropic.claude-3-haiku-20240307-v1:0'),

    /*
     * Set the region to use for the AWS BEDROCK API.
     */
    'region' => env('TRIX_AWS_BEDROCK_REGION', 'us'),

    /*
     * Set the extras to use for the AWS BEDROCK API.
     */

    'extras' => [
        'max_tokens' => 1000, // The maximum number of tokens to generate in the completion.
        /* 
            The maximum number of tokens to generate in the completion.
            This value can be between 1 and 4096 tokens.
            The default value is 60.
            https://platform.openai.com/docs/api-reference/completions/create#completions/create-max_tokens
        */
        'temperature' => 0,
        'anthropic_version' => 'bedrock-2023-05-31'
    ],

    /*
        If set to true, notifications will be enabled for successfull aws bedrock api calls.
    */
    'enable_notifications'  => true,

    /*
    |
    | Prompt labels and propmpts that are currently listed on the dropdown menu
    |
    | for example: if the Text area content is "Write a poem about Space" and
    | when you click run, as default that content will be appended with the following and send to AWS Bedrock
    | Complete the following text and return back with the same HTML : Write a poem about Space
    |
    |
    */

    'prompt-prefixes'   =>  [
        [
            'prefix_key'    => 'run',
            'prefix_label'  => 'prompt_prefixes.run',
            'prefix'        =>  'Complete the following and return the same HTML format:',
        ],
        [
            'prefix_key'    => 'run_on_selected_text',
            'prefix_label'  => 'prompt_prefixes.run_on_selected_text',
            'prefix'        => 'Complete the following and return the same HTML format:',
            'on_selected'  => true
        ],
        [
            'prefix_key'    => 'check_grammar',
            'prefix_label'  => 'prompt_prefixes.check_grammar',
            'prefix'        => 'Check only the grammar and return the same HTML format:',
        ],
        [
            'prefix_key'    => 'fix_grammar_on_selected_text',
            'prefix_label'  => 'prompt_prefixes.check_grammar_on_selected_text',
            'prefix'        => 'Fix the grammar and spelling issues and return the same HTML format without changes:',
            'on_selected'  => true
        ]
    ]


];
