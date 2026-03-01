<?php

declare(strict_types=1);

namespace MauticPlugin\OdradekAIBundle\Service;

/**
 * Ready-made VoC survey template definitions.
 *
 * Each template expands into a full Mautic form definition that can be passed
 * directly to MauticToolExecutor::createForm(). Field aliases follow strict
 * naming conventions so that survey_analytics can auto-detect the survey type
 * and compute the correct metric (NPS score, CSAT %, CES avg, PMF %).
 */
class SurveyTemplates
{
    // ── Template catalogue ────────────────────────────────────────────────

    /**
     * List available survey templates with metadata.
     *
     * @return array<string, array{name: string, description: string, metric: string, scale: string}>
     */
    public static function listTemplates(): array
    {
        return [
            'nps' => [
                'name'        => 'Net Promoter Score (NPS)',
                'description' => 'Measures customer loyalty: "How likely are you to recommend us?" (0-10 scale).',
                'metric'      => 'NPS = %Promoters(9-10) - %Detractors(0-6). Range: -100 to +100.',
                'scale'       => '0-10 radio',
                'benchmarks'  => ['excellent' => '>70', 'good' => '50-70', 'ok' => '0-50', 'poor' => '<0'],
            ],
            'csat' => [
                'name'        => 'Customer Satisfaction (CSAT)',
                'description' => 'Measures satisfaction with a product or interaction (1-5 scale).',
                'metric'      => 'CSAT = % of responses rating 4 or 5.',
                'scale'       => '1-5 radio (Very Unsatisfied → Very Satisfied)',
                'benchmarks'  => ['excellent' => '>90%', 'good' => '75-90%', 'average' => '50-75%', 'poor' => '<50%'],
            ],
            'ces' => [
                'name'        => 'Customer Effort Score (CES)',
                'description' => 'Measures how easy it was to complete an interaction (1-7 scale).',
                'metric'      => 'CES = Average of all responses. Higher = easier.',
                'scale'       => '1-7 radio (Strongly Disagree → Strongly Agree)',
                'benchmarks'  => ['excellent' => '>6', 'good' => '5-6', 'average' => '4-5', 'poor' => '<4'],
            ],
            'pmf' => [
                'name'        => 'Product-Market Fit (PMF)',
                'description' => 'Sean Ellis test: "How would you feel if you could no longer use this product?"',
                'metric'      => 'PMF = % who chose "Very Disappointed". >40% indicates strong product-market fit.',
                'scale'       => '4-option radio',
                'benchmarks'  => ['strong_pmf' => '>40%', 'promising' => '25-40%', 'weak' => '<25%'],
            ],
            'onboarding' => [
                'name'        => 'Onboarding Feedback',
                'description' => 'Evaluates setup experience: rating + what was confusing + improvement suggestions.',
                'metric'      => 'Average onboarding rating (1-5).',
                'scale'       => '1-5 radio + 2 textareas',
                'benchmarks'  => ['excellent' => '>4.5', 'good' => '3.5-4.5', 'needs_work' => '<3.5'],
            ],
            'churn' => [
                'name'        => 'Churn / Exit Survey',
                'description' => 'Understand why customers leave: reason checkboxes + improvement textarea.',
                'metric'      => 'Frequency distribution of churn reasons.',
                'scale'       => 'Checkbox group (7 reasons) + textarea',
                'benchmarks'  => [],
            ],
            'post_purchase' => [
                'name'        => 'Post-Purchase Feedback',
                'description' => 'Measures purchase satisfaction, product quality, and repurchase likelihood.',
                'metric'      => 'Average across 3 dimensions (satisfaction, quality, repurchase) on 1-5 scale.',
                'scale'       => '3x 1-5 radio + textarea',
                'benchmarks'  => ['excellent' => '>4.5', 'good' => '3.5-4.5', 'average' => '2.5-3.5', 'poor' => '<2.5'],
            ],
        ];
    }

    // ── Template expansion ────────────────────────────────────────────────

    /**
     * Expand a template into a full form definition ready for createForm().
     *
     * @return array{name: string, description: string, postAction: string, postActionProperty: string, fields: list<array>}
     */
    public static function getTemplate(
        string  $template,
        ?string $companyName   = null,
        ?string $productName   = null,
        ?string $customFollowUp = null,
    ): array {
        $company = $companyName ?? 'us';
        $product = $productName ?? 'our product';

        return match ($template) {
            'nps'           => self::npsTemplate($company, $product, $customFollowUp),
            'csat'          => self::csatTemplate($company, $product, $customFollowUp),
            'ces'           => self::cesTemplate($company, $product, $customFollowUp),
            'pmf'           => self::pmfTemplate($company, $product, $customFollowUp),
            'onboarding'    => self::onboardingTemplate($company, $product, $customFollowUp),
            'churn'         => self::churnTemplate($company, $product, $customFollowUp),
            'post_purchase' => self::postPurchaseTemplate($company, $product, $customFollowUp),
            default         => throw new \InvalidArgumentException("Unknown survey template: {$template}"),
        };
    }

    // ── Scoring configuration ─────────────────────────────────────────────

    /**
     * Get scoring metadata for a template type.
     */
    public static function getScoringConfig(string $template): array
    {
        return match ($template) {
            'nps' => [
                'type'        => 'nps',
                'score_field' => 'nps_score',
                'method'      => 'nps',
                'formula'     => '%Promoters(9-10) - %Detractors(0-6)',
                'range'       => '-100 to +100',
            ],
            'csat' => [
                'type'        => 'csat',
                'score_field' => 'csat_score',
                'method'      => 'top_box_percentage',
                'top_box'     => [4, 5],
                'scale_max'   => 5,
                'formula'     => '% of 4-5 ratings',
            ],
            'ces' => [
                'type'        => 'ces',
                'score_field' => 'ces_score',
                'method'      => 'average',
                'scale_max'   => 7,
                'formula'     => 'Average of all responses (1-7)',
            ],
            'pmf' => [
                'type'         => 'pmf',
                'score_field'  => 'pmf_score',
                'method'       => 'single_option_percentage',
                'target_value' => 'very_disappointed',
                'formula'      => '% who chose "Very Disappointed"',
                'benchmark'    => '>40% = strong product-market fit',
            ],
            'onboarding' => [
                'type'        => 'onboarding',
                'score_field' => 'onboarding_rating',
                'method'      => 'average',
                'scale_max'   => 5,
                'formula'     => 'Average onboarding rating (1-5)',
            ],
            'churn' => [
                'type'        => 'churn',
                'score_field' => 'churn_reason',
                'method'      => 'frequency_distribution',
                'formula'     => 'Frequency distribution of churn reasons',
            ],
            'post_purchase' => [
                'type'         => 'post_purchase',
                'score_fields' => ['purchase_satisfaction', 'product_quality', 'repurchase_likelihood'],
                'method'       => 'multi_average',
                'scale_max'    => 5,
                'formula'      => 'Average across satisfaction, quality, and repurchase likelihood',
            ],
            default => throw new \InvalidArgumentException("Unknown survey template: {$template}"),
        };
    }

    // ══════════════════════════════════════════════════════════════════════
    //  Private template builders
    // ══════════════════════════════════════════════════════════════════════

    private static function npsTemplate(string $company, string $product, ?string $followUp): array
    {
        return [
            'name'               => 'NPS Survey' . ($company !== 'us' ? " — {$company}" : ''),
            'description'        => 'Net Promoter Score survey — measures customer loyalty and likelihood to recommend.',
            'postAction'         => 'message',
            'postActionProperty' => 'Thank you for your feedback! Your response helps us improve.',
            'fields'             => [
                [
                    'label'       => "How likely are you to recommend {$company} to a friend or colleague?",
                    'type'        => 'radio',
                    'alias'       => 'nps_score',
                    'isRequired'  => true,
                    'helpMessage' => '0 = Not at all likely, 10 = Extremely likely',
                    'properties'  => ['optionlist' => ['list' => self::numericOptions(0, 10)]],
                ],
                [
                    'label'      => $followUp ?? 'What is the primary reason for your score?',
                    'type'       => 'textarea',
                    'alias'      => 'nps_followup',
                    'isRequired' => false,
                ],
                self::emailField(),
                self::submitButton(),
            ],
        ];
    }

    private static function csatTemplate(string $company, string $product, ?string $followUp): array
    {
        return [
            'name'               => 'CSAT Survey' . ($company !== 'us' ? " — {$company}" : ''),
            'description'        => 'Customer Satisfaction survey — measures satisfaction with a product or interaction.',
            'postAction'         => 'message',
            'postActionProperty' => 'Thank you for your feedback!',
            'fields'             => [
                [
                    'label'      => "How satisfied are you with {$product}?",
                    'type'       => 'radio',
                    'alias'      => 'csat_score',
                    'isRequired' => true,
                    'properties' => ['optionlist' => ['list' => [
                        ['label' => '1 — Very Unsatisfied', 'value' => '1'],
                        ['label' => '2 — Unsatisfied',      'value' => '2'],
                        ['label' => '3 — Neutral',          'value' => '3'],
                        ['label' => '4 — Satisfied',        'value' => '4'],
                        ['label' => '5 — Very Satisfied',   'value' => '5'],
                    ]]],
                ],
                [
                    'label'      => $followUp ?? 'What could we do to improve your experience?',
                    'type'       => 'textarea',
                    'alias'      => 'csat_followup',
                    'isRequired' => false,
                ],
                self::emailField(),
                self::submitButton(),
            ],
        ];
    }

    private static function cesTemplate(string $company, string $product, ?string $followUp): array
    {
        return [
            'name'               => 'CES Survey' . ($company !== 'us' ? " — {$company}" : ''),
            'description'        => 'Customer Effort Score survey — measures how easy it was to complete an interaction.',
            'postAction'         => 'message',
            'postActionProperty' => 'Thank you for your feedback!',
            'fields'             => [
                [
                    'label'       => "The company made it easy for me to resolve my issue.",
                    'type'        => 'radio',
                    'alias'       => 'ces_score',
                    'isRequired'  => true,
                    'helpMessage' => '1 = Strongly Disagree, 7 = Strongly Agree',
                    'properties'  => ['optionlist' => ['list' => [
                        ['label' => '1 — Strongly Disagree', 'value' => '1'],
                        ['label' => '2 — Disagree',          'value' => '2'],
                        ['label' => '3 — Slightly Disagree', 'value' => '3'],
                        ['label' => '4 — Neutral',           'value' => '4'],
                        ['label' => '5 — Slightly Agree',    'value' => '5'],
                        ['label' => '6 — Agree',             'value' => '6'],
                        ['label' => '7 — Strongly Agree',    'value' => '7'],
                    ]]],
                ],
                [
                    'label'      => $followUp ?? 'What would have made this easier?',
                    'type'       => 'textarea',
                    'alias'      => 'ces_followup',
                    'isRequired' => false,
                ],
                self::emailField(),
                self::submitButton(),
            ],
        ];
    }

    private static function pmfTemplate(string $company, string $product, ?string $followUp): array
    {
        return [
            'name'               => 'Product-Market Fit Survey' . ($company !== 'us' ? " — {$company}" : ''),
            'description'        => 'Sean Ellis PMF test — measures how disappointed users would be without the product.',
            'postAction'         => 'message',
            'postActionProperty' => 'Thank you for sharing your thoughts!',
            'fields'             => [
                [
                    'label'      => "How would you feel if you could no longer use {$product}?",
                    'type'       => 'radio',
                    'alias'      => 'pmf_score',
                    'isRequired' => true,
                    'properties' => ['optionlist' => ['list' => [
                        ['label' => 'Very disappointed',     'value' => 'very_disappointed'],
                        ['label' => 'Somewhat disappointed', 'value' => 'somewhat_disappointed'],
                        ['label' => 'Not disappointed',      'value' => 'not_disappointed'],
                        ['label' => 'N/A — I no longer use it', 'value' => 'na'],
                    ]]],
                ],
                [
                    'label'      => $followUp ?? "What is the main benefit you get from {$product}?",
                    'type'       => 'textarea',
                    'alias'      => 'pmf_followup',
                    'isRequired' => false,
                ],
                self::emailField(),
                self::submitButton(),
            ],
        ];
    }

    private static function onboardingTemplate(string $company, string $product, ?string $followUp): array
    {
        return [
            'name'               => 'Onboarding Feedback' . ($company !== 'us' ? " — {$company}" : ''),
            'description'        => 'Evaluates the setup and onboarding experience for new users.',
            'postAction'         => 'message',
            'postActionProperty' => 'Thank you! Your feedback helps us improve the onboarding experience.',
            'fields'             => [
                [
                    'label'       => 'How would you rate your overall setup experience?',
                    'type'        => 'radio',
                    'alias'       => 'onboarding_rating',
                    'isRequired'  => true,
                    'helpMessage' => '1 = Very Poor, 5 = Excellent',
                    'properties'  => ['optionlist' => ['list' => [
                        ['label' => '1 — Very Poor', 'value' => '1'],
                        ['label' => '2 — Poor',      'value' => '2'],
                        ['label' => '3 — Average',   'value' => '3'],
                        ['label' => '4 — Good',      'value' => '4'],
                        ['label' => '5 — Excellent',  'value' => '5'],
                    ]]],
                ],
                [
                    'label'      => $followUp ?? 'Was anything confusing or difficult during setup?',
                    'type'       => 'textarea',
                    'alias'      => 'onboarding_confusing',
                    'isRequired' => false,
                ],
                [
                    'label'      => 'What would have made the onboarding experience better?',
                    'type'       => 'textarea',
                    'alias'      => 'onboarding_improve',
                    'isRequired' => false,
                ],
                self::emailField(),
                self::submitButton(),
            ],
        ];
    }

    private static function churnTemplate(string $company, string $product, ?string $followUp): array
    {
        return [
            'name'               => 'Exit Survey' . ($company !== 'us' ? " — {$company}" : ''),
            'description'        => 'Understand why customers are leaving — churn reason analysis.',
            'postAction'         => 'message',
            'postActionProperty' => "We're sorry to see you go. Thank you for helping us improve.",
            'fields'             => [
                [
                    'label'       => 'Why are you leaving? (select all that apply)',
                    'type'        => 'checkboxgrp',
                    'alias'       => 'churn_reason',
                    'isRequired'  => true,
                    'properties'  => ['optionlist' => ['list' => [
                        ['label' => 'Too expensive',         'value' => 'too_expensive'],
                        ['label' => 'Missing features I need', 'value' => 'missing_features'],
                        ['label' => 'Switched to a competitor', 'value' => 'switched_competitor'],
                        ['label' => 'No longer need it',     'value' => 'no_longer_needed'],
                        ['label' => 'Poor customer support',  'value' => 'poor_support'],
                        ['label' => 'Too complicated to use', 'value' => 'too_complicated'],
                        ['label' => 'Other',                  'value' => 'other'],
                    ]]],
                ],
                [
                    'label'      => $followUp ?? 'What could we have done to keep you as a customer?',
                    'type'       => 'textarea',
                    'alias'      => 'churn_improve',
                    'isRequired' => false,
                ],
                self::emailField(),
                self::submitButton(),
            ],
        ];
    }

    private static function postPurchaseTemplate(string $company, string $product, ?string $followUp): array
    {
        return [
            'name'               => 'Post-Purchase Feedback' . ($company !== 'us' ? " — {$company}" : ''),
            'description'        => 'Measures purchase satisfaction, product quality, and repurchase likelihood.',
            'postAction'         => 'message',
            'postActionProperty' => 'Thank you for your purchase feedback!',
            'fields'             => [
                [
                    'label'      => 'How satisfied are you with your recent purchase?',
                    'type'       => 'radio',
                    'alias'      => 'purchase_satisfaction',
                    'isRequired' => true,
                    'properties' => ['optionlist' => ['list' => self::satisfactionOptions()]],
                ],
                [
                    'label'      => 'How would you rate the product quality?',
                    'type'       => 'radio',
                    'alias'      => 'product_quality',
                    'isRequired' => true,
                    'properties' => ['optionlist' => ['list' => self::satisfactionOptions()]],
                ],
                [
                    'label'      => "How likely are you to purchase from {$company} again?",
                    'type'       => 'radio',
                    'alias'      => 'repurchase_likelihood',
                    'isRequired' => true,
                    'properties' => ['optionlist' => ['list' => [
                        ['label' => '1 — Very Unlikely',  'value' => '1'],
                        ['label' => '2 — Unlikely',       'value' => '2'],
                        ['label' => '3 — Neutral',        'value' => '3'],
                        ['label' => '4 — Likely',         'value' => '4'],
                        ['label' => '5 — Very Likely',    'value' => '5'],
                    ]]],
                ],
                [
                    'label'      => $followUp ?? 'Anything else you would like to share about your experience?',
                    'type'       => 'textarea',
                    'alias'      => 'purchase_followup',
                    'isRequired' => false,
                ],
                self::emailField(),
                self::submitButton(),
            ],
        ];
    }

    // ── Reusable field helpers ─────────────────────────────────────────────

    /**
     * Generate numeric radio options (e.g., 0-10 for NPS).
     */
    private static function numericOptions(int $min, int $max): array
    {
        $options = [];
        for ($i = $min; $i <= $max; $i++) {
            $options[] = ['label' => (string) $i, 'value' => (string) $i];
        }
        return $options;
    }

    /**
     * Standard 1-5 satisfaction scale options.
     */
    private static function satisfactionOptions(): array
    {
        return [
            ['label' => '1 — Very Unsatisfied', 'value' => '1'],
            ['label' => '2 — Unsatisfied',      'value' => '2'],
            ['label' => '3 — Neutral',          'value' => '3'],
            ['label' => '4 — Satisfied',        'value' => '4'],
            ['label' => '5 — Very Satisfied',   'value' => '5'],
        ];
    }

    /**
     * Optional email field for contact identification.
     */
    private static function emailField(): array
    {
        return [
            'label'        => 'Email (optional)',
            'type'         => 'email',
            'alias'        => 'email',
            'isRequired'   => false,
            'mappedObject' => 'contact',
            'mappedField'  => 'email',
        ];
    }

    /**
     * Submit button.
     */
    private static function submitButton(): array
    {
        return [
            'label' => 'Submit',
            'type'  => 'button',
            'alias' => 'submit',
        ];
    }
}
