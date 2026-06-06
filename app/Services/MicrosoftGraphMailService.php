<?php

namespace App\Services;

use App\Models\EmailSetting;
use GuzzleHttp\Client;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;

class MicrosoftGraphMailService
{
    private ?EmailSetting $settings;

    public function __construct(?EmailSetting $settings = null)
    {
        $this->settings = $settings ?? EmailSetting::current();
    }

    public function isConfigured(): bool
    {
        if ($this->settings === null) {
            return false;
        }

        return filled($this->settings->tenant_id)
            && filled($this->settings->client_id)
            && filled($this->settings->client_secret)
            && filled($this->settings->mail);
    }

    /**
     * @param  string|array<int, string>  $recipients
     */
    public function send(Mailable $mailable, string|array $recipients): void
    {
        if (! $this->isConfigured()) {
            Log::error('MicrosoftGraphMailService: email settings incomplete — no mail sent. Configure /secure/email-settings', [
                'recipients' => $recipients,
            ]);

            return;
        }

        $recipients = $this->normalizeRecipients($recipients);

        if ($recipients === []) {
            return;
        }

        $accessToken = $this->getAccessToken();

        if (! $accessToken) {
            Log::error('MicrosoftGraphMailService: failed to obtain Graph access token.');

            return;
        }

        $built = $mailable->build();
        $html = $built->render();
        $subject = $built->subject ?? config('app.name');
        $senderMailbox = $this->settings->mail;

        $client = new Client([
            'base_uri' => 'https://graph.microsoft.com/v1.0/',
        ]);

        $message = [
            'subject' => $subject,
            'body' => [
                'contentType' => 'HTML',
                'content' => $html,
            ],
            'toRecipients' => array_map(
                fn (string $address): array => [
                    'emailAddress' => ['address' => $address],
                ],
                $recipients,
            ),
        ];

        $from = $this->buildFromRecipient();
        $fromAddress = $from['emailAddress']['address'] ?? $senderMailbox;
        $fromName = $from['emailAddress']['name'] ?? null;

        if (strcasecmp($fromAddress, $senderMailbox) === 0 && $fromName) {
            $message['from'] = [
                'emailAddress' => [
                    'address' => $senderMailbox,
                    'name' => $fromName,
                ],
            ];
        }

        $senderPath = rawurlencode($senderMailbox);

        try {
            $response = $client->post("users/{$senderPath}/sendMail", [
                'headers' => [
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'message' => $message,
                    'saveToSentItems' => true,
                ],
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() >= 400) {
                Log::error('MicrosoftGraphMailService: sendMail failed', [
                    'status' => $response->getStatusCode(),
                    'body' => (string) $response->getBody(),
                    'recipients' => $recipients,
                    'subject' => $subject,
                ]);

                throw new \RuntimeException('Graph sendMail failed: '.$response->getStatusCode().' '.(string) $response->getBody());
            }

            Log::info('MicrosoftGraphMailService: mail sent', [
                'recipients' => $recipients,
                'subject' => $subject,
            ]);
        } catch (\Throwable $e) {
            Log::error('MicrosoftGraphMailService: '.$e->getMessage(), [
                'recipients' => $recipients,
                'subject' => $subject,
            ]);

            throw $e;
        }
    }

    private function getAccessToken(): ?string
    {
        $tenantId = $this->settings->tenant_id;
        $clientId = $this->settings->client_id;
        $clientSecret = $this->settings->client_secret;

        try {
            $client = new Client();
            $response = $client->post(
                "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
                [
                    'form_params' => [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'scope' => 'https://graph.microsoft.com/.default',
                        'grant_type' => 'client_credentials',
                    ],
                ],
            );

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['access_token'] ?? null;
        } catch (\Throwable $e) {
            Log::error('MicrosoftGraphMailService token: '.$e->getMessage());

            return null;
        }
    }

    /**
     * @return array{emailAddress: array{address: string, name?: string}}
     */
    private function buildFromRecipient(): array
    {
        $mailbox = $this->settings->mail;
        $from = trim((string) $this->settings->from_address);

        if (preg_match('/^(.+?)\s*<([^>]+)>$/', $from, $matches)) {
            return [
                'emailAddress' => [
                    'name' => trim($matches[1], " \t\"'"),
                    'address' => trim($matches[2]),
                ],
            ];
        }

        if ($from !== '' && filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return [
                'emailAddress' => [
                    'address' => $from,
                ],
            ];
        }

        if ($from !== '') {
            return [
                'emailAddress' => [
                    'name' => $from,
                    'address' => $mailbox,
                ],
            ];
        }

        return [
            'emailAddress' => [
                'address' => $mailbox,
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function normalizeRecipients(string|array $recipients): array
    {
        $list = is_array($recipients) ? $recipients : [$recipients];

        return array_values(array_filter(array_map(
            fn (mixed $email): ?string => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
            $list,
        )));
    }
}
