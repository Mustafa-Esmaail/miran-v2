<?php

namespace App\Filament\Admin\Resources\ApiClients\Schemas;

use App\Models\ApiClient;
use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Symfony\Component\HttpFoundation\IpUtils;

class ApiClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.basics'))
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')
                        ->label(__('admin.api_clients.fields.name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('rate_limit_per_minute')
                        ->label(__('admin.api_clients.fields.rate_limit_per_minute'))
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(1000)
                        ->default(60)
                        ->required(),
                    CheckboxList::make('abilities')
                        ->label(__('admin.api_clients.fields.abilities'))
                        ->options(collect(ApiClient::ABILITIES)
                            ->mapWithKeys(fn (string $ability): array => [$ability => __('admin.api_clients.abilities.'.$ability)])
                            ->all())
                        ->required(),
                    DateTimePicker::make('expires_at')
                        ->label(__('admin.api_clients.fields.expires_at'))
                        ->helperText(__('admin.api_clients.helpers.expires_at'))
                        ->after('now'),
                    TagsInput::make('allowed_ips')
                        ->label(__('admin.api_clients.fields.allowed_ips'))
                        ->helperText(__('admin.api_clients.helpers.allowed_ips'))
                        ->placeholder('203.0.113.10')
                        ->nestedRecursiveRules([
                            fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if (! is_string($value) || ! self::isValidIpOrCidr($value)) {
                                    $fail(__('admin.api_clients.validation.invalid_ip'));
                                }
                            },
                        ])
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    private static function isValidIpOrCidr(string $value): bool
    {
        [$ip, $mask] = array_pad(explode('/', $value, 2), 2, null);

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return $mask === null || (ctype_digit($mask) && IpUtils::checkIp($ip, $value));
    }
}
