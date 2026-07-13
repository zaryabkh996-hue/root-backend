<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Amen AI Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Controls which AI provider powers Amen's responses.
    | Supported: "openai", "anthropic"
    |
    | OpenAI is active by default. To switch to Claude in the future,
    | change AMEN_AI_PROVIDER to "anthropic" in your .env file.
    |
    */

    'provider' => in_array(env('AMEN_AI_PROVIDER'), ['openai', 'anthropic']) ? env('AMEN_AI_PROVIDER') : 'openai',

    /*
    |--------------------------------------------------------------------------
    | Emotional Analysis Threshold
    |--------------------------------------------------------------------------
    |
    | Messages scoring above this threshold on emotional weight analysis
    | will use the heavier/more empathetic model variant.
    |
    */

    'emotional_threshold' => (float) env('AMEN_AI_EMOTIONAL_THRESHOLD', 0.65),

    /*
    |--------------------------------------------------------------------------
    | Knowledge Bank Confidence Threshold
    |--------------------------------------------------------------------------
    |
    | Minimum Pinecone cosine similarity score required to inject a
    | Knowledge Bank fragment into Amen's system prompt.
    |
    | ≥ 0.90 — High confidence
    | 0.78–0.89 — Moderate confidence (injected with note)
    | 0.60–0.77 — Below threshold (logged as near-miss)
    | < 0.60 — No match
    |
    | Do NOT lower below 0.75 without product team review.
    |
    */

    'kb_confidence_threshold' => (float) env('AMEN_AI_KB_CONFIDENCE_THRESHOLD', 0.78),

    /*
    |--------------------------------------------------------------------------
    | Near-Miss Threshold (for gap analysis logging)
    |--------------------------------------------------------------------------
    */

    'kb_near_miss_threshold' => (float) env('AMEN_AI_KB_NEAR_MISS_THRESHOLD', 0.60),

    /*
    |--------------------------------------------------------------------------
    | Citation Payout Amount (USD)
    |--------------------------------------------------------------------------
    |
    | Each time Amen cites a Custodian's Knowledge Bank fragment,
    | this amount is queued for monthly payout via M-Pesa / Paystack.
    |
    */

    'citation_payout_usd' => (float) env('AMEN_AI_CITATION_PAYOUT_USD', 0.35),

    /*
    |--------------------------------------------------------------------------
    | Embedding Model
    |--------------------------------------------------------------------------
    |
    | OpenAI embedding model used for both Knowledge Bank storage
    | and query-time semantic search. Must match dimensions in Pinecone.
    |
    | text-embedding-3-small → 1536 dimensions
    | text-embedding-3-large → 3072 dimensions
    |
    */

    'embedding_model' => env('AMEN_EMBEDDING_MODEL', 'text-embedding-3-small'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    */

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model'   => env('AMEN_AI_MODEL', 'gpt-4o'),
        'max_tokens' => 1024,
        'temperature' => 0.7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Anthropic (Claude) Configuration — Future Use
    |--------------------------------------------------------------------------
    |
    | Claude integration is fully coded but commented in AmenAIService.
    | To activate: set AMEN_AI_PROVIDER=anthropic in .env
    |
    */

    'anthropic' => [
        'api_key'     => env('ANTHROPIC_API_KEY'),
        'model_heavy' => 'claude-sonnet-4-20250514',
        'model_light' => 'claude-haiku-4-5-20251001',
        'max_tokens'  => 1024,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pinecone Vector Database Configuration
    |--------------------------------------------------------------------------
    |
    | Index must be created manually at https://app.pinecone.io
    | Recommended: 1536 dimensions, cosine metric, AWS us-east-1
    |
    */

    'pinecone' => [
        'api_key'     => env('PINECONE_API_KEY'),
        'index'       => env('PINECONE_INDEX', 'ourroots-knowledge-bank'),
        'environment' => env('PINECONE_ENVIRONMENT', 'us-east-1'),
        'namespace'   => 'knowledge-bank',
    ],

];
