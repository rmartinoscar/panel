<?php

namespace App\Extensions\Captcha\Providers;

use App\Filament\Components\Forms\Fields\TurnstileCaptcha;
use Exception;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;

class TurnstileProvider extends CaptchaProvider
{
    public function getId(): string
    {
        return 'turnstile';
    }

    public function getComponent(): Component
    {
        return TurnstileCaptcha::make('turnstile');
    }

    /**
     * @return Component[]
     */
    public function getSettingsForm(): array
    {
        return array_merge(parent::getSettingsForm(), [
            Toggle::make('CAPTCHA_TURNSTILE_VERIFY_DOMAIN')
                ->label(trans('admin/setting.captcha.verify.domain'))
                ->columnSpan(2)
                ->inline(false)
                ->onIcon('tabler-check')
                ->offIcon('tabler-x')
                ->onColor('success')
                ->offColor('danger')
                ->default(env('CAPTCHA_TURNSTILE_VERIFY_DOMAIN', true)),
            Toggle::make('CAPTCHA_TURNSTILE_VERIFY_IP')
                ->label(trans('admin/setting.captcha.verify.ip'))
                ->columnSpan(2)
                ->inline(false)
                ->onIcon('tabler-check')
                ->offIcon('tabler-x')
                ->onColor('success')
                ->offColor('danger')
                ->default(env('CAPTCHA_TURNSTILE_VERIFY_IP', true)),
            Toggle::make('CAPTCHA_TURNSTILE_VERIFY_IDEMPOTENCY')
                ->label(trans('admin/setting.captcha.verify.idempotency'))
                ->columnSpan(2)
                ->inline(false)
                ->onIcon('tabler-check')
                ->offIcon('tabler-x')
                ->onColor('success')
                ->offColor('danger')
                ->default(env('CAPTCHA_TURNSTILE_VERIFY_IDEMPOTENCY', true)),
            Placeholder::make('info')
                ->label(trans('admin/setting.captcha.info_label'))
                ->columnSpan(2)
                ->content(new HtmlString(trans('admin/setting.captcha.info'))),

        ]);
    }

    public function getIcon(): string
    {
        return 'tabler-brand-cloudflare';
    }

    public static function register(Application $app): self
    {
        return new self($app);
    }

    /**
     * @return array<string, string|bool>
     */
    public function validateResponse(?string $captchaResponse = null): array
    {
        $captchaResponse ??= request()->get('cf-turnstile-response');

        $secret = env('CAPTCHA_TURNSTILE_SECRET_KEY');

        if (!$secret) {
            throw new Exception('Turnstile secret key is not defined.');
        }

        $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

        $data = [
            'secret' => $secret,
            'response' => $captchaResponse,
        ];

        if (env('CAPTCHA_TURNSTILE_VERIFY_IP', true)) {
            $data['remoteip'] = request()->ip();
        }

        if (env('CAPTCHA_TURNSTILE_VERIFY_IDEMPOTENCY', true)) {
            $data['idempotency_key'] = str()->uuid();
        }

        $firstOutcome = Http::asJson()
            ->timeout(config('panel.guzzle.timeout'))
            ->connectTimeout(config('panel.guzzle.connect_timeout'))
            ->post($url, $data)
            ->json();

        if (env('CAPTCHA_TURNSTILE_VERIFY_IDEMPOTENCY', true)) {
            $subsequentOutcome = Http::asJson()
                ->timeout(config('panel.guzzle.timeout'))
                ->connectTimeout(config('panel.guzzle.connect_timeout'))
                ->post($url, $data)
                ->json();

            if ($firstOutcome['success'] && $subsequentOutcome['success']) {
                return $subsequentOutcome;
            }

            return [
                'success' => false,
                'message' => 'Unknown error occurred, please try again',
            ];
        }

        return $firstOutcome;
    }

    public function verifyDomain(string $hostname, ?string $requestUrl = null): bool
    {
        if (!env('CAPTCHA_TURNSTILE_VERIFY_DOMAIN', true)) {
            return true;
        }

        $requestUrl ??= request()->url();
        $requestUrl = parse_url($requestUrl);

        return $hostname === array_get($requestUrl, 'host');
    }
}
