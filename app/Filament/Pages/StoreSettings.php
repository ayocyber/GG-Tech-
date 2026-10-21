<?php

namespace App\Filament\Pages;

use App\Filament\NavigationGroup;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Services\ProductImageProcessor;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class StoreSettings extends Page
{
    protected string $view = 'filament.pages.store-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Settings;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Store settings');
    }

    public function getTitle(): string
    {
        return __('Store settings');
    }

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'whatsapp' => Setting::value('whatsapp'),
            'phone' => Setting::value('phone'),
            'email' => Setting::value('email'),
            'address' => Setting::value('address'),
            'opening_hours' => Setting::value('opening_hours'),
            'maps_url' => Setting::value('maps_url'),
            'google_business_url' => Setting::value('google_business_url'),
            'watermark' => [
                'opacity' => Setting::value('watermark.opacity'),
                'width_percent' => Setting::value('watermark.width_percent'),
            ],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('Contact'))
                    ->description(__('Shown across the website, in emails and on the WhatsApp button.'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('whatsapp')->label(__('WhatsApp number'))->tel()->required(),
                        TextInput::make('phone')->label(__('Phone'))->tel()->required(),
                        TextInput::make('email')->label(__('Email (receives orders)'))->email()->required(),
                        TextInput::make('address')->label(__('Shop address'))->required(),
                        TextInput::make('opening_hours.fr')->label(__('Opening hours (French)'))->required(),
                        TextInput::make('opening_hours.en')->label(__('Opening hours (English)'))->required(),
                        TextInput::make('maps_url')->label(__('Google Maps link'))->url(),
                        TextInput::make('google_business_url')->label(__('Google Business Profile'))->url(),
                    ]),
                Section::make(__('Photo watermark'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('watermark.opacity')->label(__('Opacity'))->numeric()->minValue(5)->maxValue(80)->suffix('%')->required(),
                            TextInput::make('watermark.width_percent')->label(__('Size (share of photo width)'))->numeric()->minValue(5)->maxValue(50)->suffix('%')->required(),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::store([
            'whatsapp' => $state['whatsapp'],
            'phone' => $state['phone'],
            'email' => $state['email'],
            'address' => $state['address'],
            'opening_hours' => $state['opening_hours'],
            'maps_url' => $state['maps_url'],
            'google_business_url' => $state['google_business_url'],
            'watermark.opacity' => (int) $state['watermark']['opacity'],
            'watermark.width_percent' => (int) $state['watermark']['width_percent'],
        ]);

        Notification::make()->title(__('Settings saved'))->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerate')
                ->label(__('Regenerate all photos'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(__('Applies the current watermark to every product photo. This can take a few minutes.'))
                ->action(function (ProductImageProcessor $processor) {
                    $count = 0;

                    ProductImage::query()->lazyById()->each(function (ProductImage $image) use ($processor, &$count) {
                        $processor->process($image);
                        $count++;
                    });

                    Notification::make()->title(trans_choice(':count photo regenerated|:count photos regenerated', $count, ['count' => $count]))->success()->send();
                }),
        ];
    }
}
