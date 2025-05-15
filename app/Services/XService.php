<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class XService
{
    // No bearer token cache or method needed for mock implementation
    public function post(string $pageId, string $pageToken, string $content, ?string $imageUrl = null): array
    {
        // In a real implementation, this would use the X API with the provided parameters
        // For now, we'll simulate a successful response
        // We're keeping the parameters for API consistency even though they're not used

        // Use the parameters in a simple way to avoid IDE warnings
        $debugInfo = compact('pageId', 'pageToken', 'content', 'imageUrl');

        $postId = 'x_' . uniqid();
        $username = 'your_x_handle';

        return [
            'response' => [
                'id' => $postId,
                'text' => $content,
                'created_at' => now()->toIso8601String(),
                'debug' => $debugInfo,
            ],
            'url' => "https://x.com/{$username}/status/{$postId}",
        ];
    }

    /**
     * List available X accounts
     */
    public function listAccounts(): array
    {
        // In a real implementation, this would fetch accounts from the X API
        // For now, we'll return a mock account
        return [
            'x_account_1' => 'Your X Account',
        ];
    }

    /**
     * Fill account details for the Filament form
     */
    public function fillAccountDetails(string $accountId, $set): void
    {
        // In a real implementation, this would fetch account details from the X API
        // For now, we'll set mock values based on the account ID
        $accountName = "X Account ({$accountId})";

        $set('external_name', $accountName);
        $set('label', $accountName);
        $set('external_url', "https://x.com/{$accountId}");
        $set('external_token', 'mock_token_' . $accountId);
        $set('external_picture_url', 'https://via.placeholder.com/150?text=X');
    }

    /**
     * Get metrics for an X post
     *
     * @param string $accountId The X account ID
     * @param string $postId The X post ID
     * @param string $accountToken The account access token
     * @return array The metrics data
     */
    public function getMetrics(string $accountId, string $postId, string $accountToken): array
    {
        try {
            Log::debug('Getting X metrics', compact('accountId', 'postId', 'accountToken'));

            // For now, return mock data since we don't have a proper X API setup
            // This avoids API errors while still providing reasonable metrics
            // In a production environment, you would implement the real API call

            // Generate consistent mock data based on the post ID to avoid random fluctuations
            $postIdHash = crc32($postId);
            $reach = 50 + ($postIdHash % 250); // 50-300 range
            $likes = 5 + ($postIdHash % 25);   // 5-30 range
            $comments = 1 + ($postIdHash % 5);  // 1-6 range
            $shares = 1 + ($postIdHash % 10);   // 1-11 range

            Log::info('Using consistent mock data for X metrics', [
                'post_id' => $postId,
                'metrics' => compact('reach', 'likes', 'comments', 'shares')
            ]);

            return [
                'reach' => $reach,
                'likes' => $likes,
                'comments' => $comments,
                'shares' => $shares,
            ];
        } catch (\Throwable $e) {
            // Log the error but return empty metrics
            Log::error('Failed to get X metrics: ' . $e->getMessage(), [
                'account_id' => $accountId,
                'post_id' => $postId,
            ]);

            return [
                'reach' => 0,
                'likes' => 0,
                'comments' => 0,
                'shares' => 0,
            ];
        }
    }
}