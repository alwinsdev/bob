<?php

namespace App\Services;

use App\Models\Agent;

class FuzzyMatchService
{
    private array $agentsCache = [];

    public function __construct()
    {
        $agents = Agent::active()->get();
        foreach ($agents as $agent) {
            $this->agentsCache[] = [
                'code' => $agent->agent_code,
                'first_name' => strtolower($agent->first_name),
                'last_name' => strtolower($agent->last_name),
                'email' => strtolower($agent->email),
                'phone' => preg_replace('/[^0-9]/', '', $agent->phone),
                'full_name' => $agent->full_name,
                'group' => $agent->group_team_sales,
            ];
        }
    }

    public function match(array $imsRecord): array
    {
        $bestMatch = null;
        $highestConfidence = 0;
        $method = null;
        $scores = [];

        $targetFirst = strtolower($imsRecord['client_first_name'] ?? '');
        $targetLast = strtolower($imsRecord['client_last_name'] ?? '');
        $targetEmail = strtolower($imsRecord['client_email'] ?? '');
        $targetPhone = preg_replace('/[^0-9]/', '', $imsRecord['client_phone'] ?? '');

        foreach ($this->agentsCache as $agent) {
            // Email exact match = instantaneous 100%
            if (!empty($targetEmail) && $agent['email'] === $targetEmail) {
                return [
                    'agent_code' => $agent['code'],
                    'full_name' => $agent['full_name'],
                    'group' => $agent['group'],
                    'confidence' => 100,
                    'method' => 'exact_email',
                    'scores' => ['email' => 1.0, 'first_name' => 1.0, 'last_name' => 1.0, 'phone' => 1.0]
                ];
            }

            $firstScore = 0;
            $lastScore = 0;
            if ($targetFirst && $agent['first_name']) {
                similar_text($targetFirst, $agent['first_name'], $firstScore);
                $firstScore /= 100;
            }
            if ($targetLast && $agent['last_name']) {
                similar_text($targetLast, $agent['last_name'], $lastScore);
                $lastScore /= 100;
            }

            $phoneScore = 0;
            if ($targetPhone && $agent['phone'] && $targetPhone === $agent['phone']) {
                $phoneScore = 1.0;
            }

            $currentScores = [
                'first_name' => $firstScore,
                'last_name' => $lastScore,
                'phone' => $phoneScore,
                'email' => 0,
            ];

            // Weighted average
            $totalScore = ($firstScore * 0.35) + ($lastScore * 0.45) + ($phoneScore * 0.20);
            
            if ($totalScore > $highestConfidence) {
                $highestConfidence = $totalScore;
                $bestMatch = $agent;
                $scores = $currentScores;
                $method = 'weighted_fuzzy';
            }
        }

        if ($highestConfidence >= 0.80) { // 80% threshold
            return [
                'agent_code' => $bestMatch['code'],
                'full_name' => $bestMatch['full_name'],
                'group' => $bestMatch['group'],
                'confidence' => round($highestConfidence * 100, 2),
                'method' => $method,
                'scores' => $scores
            ];
        }

        return [
            'agent_code' => null,
            'full_name' => null,
            'group' => null,
            'confidence' => round($highestConfidence * 100, 2),
            'method' => 'failed',
            'scores' => $scores
        ];
    }
}
