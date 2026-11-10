<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envío por la WhatsApp Cloud API de Meta desde el número del colegio.
 *
 * Fuera de una conversación abierta, WhatsApp solo permite mensajes con plantilla aprobada por Meta.
 * Nunca se escribe la contraseña en los registros: si Meta rechaza el envío, se anota solo su error.
 */
class WhatsAppClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.whatsapp.token')) && filled(config('services.whatsapp.phone_number_id'));
    }

    /**
     * Envía la contraseña temporal con la plantilla configurada.
     *
     * @param  array{name: string, password: string, login_url: string, email: string, expires: string}  $data
     * @return array{sent: bool, message_id: ?string, error: ?string}
     */
    public function sendTemporaryPassword(string $phone, array $data): array
    {
        if (! $this->isConfigured()) {
            return ['sent' => false, 'message_id' => null, 'error' => 'El envío automático no está configurado.'];
        }

        $config = config('services.whatsapp');
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $config['template'],
                'language' => ['code' => $config['template_lang']],
                'components' => $this->components($config['template_kind'], $data),
            ],
        ];

        try {
            $response = Http::withToken($config['token'])
                ->acceptJson()
                ->timeout($config['timeout'])
                ->post("https://graph.facebook.com/{$config['api_version']}/{$config['phone_number_id']}/messages", $payload);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp: no se pudo conectar con Meta.', ['error' => $e->getMessage()]);

            return ['sent' => false, 'message_id' => null, 'error' => 'No se pudo conectar con WhatsApp. Revisa la conexión del servidor.'];
        }

        $messageId = $response->json('messages.0.id');
        if ($response->successful() && $messageId) {
            return ['sent' => true, 'message_id' => $messageId, 'error' => null];
        }

        $metaError = $response->json('error.error_data.details') ?: $response->json('error.message') ?: ('HTTP ' . $response->status());
        Log::warning('WhatsApp: Meta rechazó el envío.', [
            'status' => $response->status(),
            'code' => $response->json('error.code'),
            'error' => $metaError,
        ]);

        return ['sent' => false, 'message_id' => null, 'error' => 'WhatsApp rechazó el envío: ' . $metaError];
    }

    /**
     * «authentication»: plantilla de código de Meta. El cuerpo lleva el código y el botón «Copiar»
     * también lo necesita. «utility»: plantilla propia con 5 variables en este orden.
     */
    private function components(string $kind, array $data): array
    {
        if ($kind === 'utility') {
            return [[
                'type' => 'body',
                'parameters' => array_map(fn ($text) => ['type' => 'text', 'text' => $text], [
                    $data['name'], $data['password'], $data['login_url'], $data['email'], $data['expires'],
                ]),
            ]];
        }

        return [
            ['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $data['password']]]],
            ['type' => 'button', 'sub_type' => 'url', 'index' => '0', 'parameters' => [['type' => 'text', 'text' => $data['password']]]],
        ];
    }
}
