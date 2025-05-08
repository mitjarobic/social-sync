<?php

namespace App\Services;

class XService
{
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
}