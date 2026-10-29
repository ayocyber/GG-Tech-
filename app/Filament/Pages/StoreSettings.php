<?php

namespace App\Filament\Pages;

use App\Enums\Shop;
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
            'google_business_url' => Setting::value('google_business_url'),
            'shops' => Setting::value('shops'),
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
                        TextInput::make('google_business_url')->label(__('Google Business Profile'))->url(),
                    ]),
                ...array_map(fn (Shop $shop) => $this->shopSection($shop), Shop::cases()),
                Section::make(__('Photo watermark'))
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('watermark.opacity')->label(__('Opacity'))->numeric()->minValue(5)->maxValue(80)->suffix('%')->required(),
                            TextInput::make('watermark.width_percent')->label(__('Size (share of photo width)'))->numeric()->minValue(5)->maxValue(50)->suffix('%')->required(),
                        ]),
                    ]),
            ]);
    }

    private function shopSection(Shop $shop): Section
    {
        $isHeadOffice = $shop === Shop::HeadOffice;
        $path = "shops.{$shop->value}";

        return Section::make($shop->getLabel())
            ->description($isHeadOffice
                ? __('Shown first on the website.')
                : __('Hidden from the website until it has an address.'))
            ->columns(2)
            ->schema([
                TextInput::make("{$path}.name.fr")->label(__('Name (French)'))->placeholder(__('e.g. Amoutivé'))->maxLength(80),
                TextInput::make("{$path}.name.en")->label(__('Name (English)'))->maxLength(80)
                    ->helperText(__('Leave empty to reuse the French name.')),
                TextInput::make("{$path}.address")->label(__('Address'))->required($isHeadOffice)->maxLength(255)->columnSpanFull(),
                TextInput::make("{$path}.maps_url")->label(__('Google Maps link'))->url()->columnSpanFull()
                    ->helperText(__('Paste the “Share” link from Google Maps. Without it, directions search for the address.')),
                TextInput::make("{$path}.opening_hours.fr")->label(__('Opening hours (French)'))->required($isHeadOffice),
                TextInput::make("{$path}.opening_hours.en")->label(__('Opening hours (English)'))->required($isHeadOffice),
                TextInput::make("{$path}.phone")->label(__('Phone'))->tel()
                    ->helperText(__('Leave empty to use the main phone number.')),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::store([
            'whatsapp' => $state['whatsapp'],
            'phone' => $state['phone'],
            'email' => $state['email'],
            'google_business_url' => $state['google_business_url'],
            'shops' => $state['shops'],
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
                ->visible(fn () => config('store.photo_uploads'))
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
