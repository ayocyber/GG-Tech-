<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use App\Enums\TonerType;
use App\Models\Brand;
use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)
                    ->columnSpan(2)
                    ->schema([
                        Section::make(__('Product details'))
                            ->schema([
                                Tabs::make(__('Languages'))->tabs([
                                    Tab::make('Français')->schema([
                                        TextInput::make('name.fr')
                                            ->label(__('Name (French)'))
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                                                if ($operation === 'create' && blank($get('slug'))) {
                                                    $set('slug', Str::slug($state));
                                                }
                                            }),
                                        Textarea::make('short_description.fr')->label(__('Summary (French)'))->rows(2)->maxLength(300),
                                        Textarea::make('description.fr')->label(__('Description (French)'))->rows(6),
                                    ]),
                                    Tab::make('English')->schema([
                                        TextInput::make('name.en')->label(__('Name (English)'))->maxLength(255)
                                            ->helperText(__('Leave empty to reuse the French name.')),
                                        Textarea::make('short_description.en')->label(__('Summary (English)'))->rows(2)->maxLength(300),
                                        Textarea::make('description.en')->label(__('Description (English)'))->rows(6),
                                    ]),
                                ]),
                                Grid::make(2)->schema([
                                    TextInput::make('model')->label(__('Model / reference'))->maxLength(255),
                                    TextInput::make('slug')
                                        ->label(__('URL (slug)'))
                                        ->required()
                                        ->maxLength(255)
                                        ->unique(ignoreRecord: true)
                                        ->alphaDash(),
                                ]),
                                Textarea::make('compatibility')
                                    ->label(__('Compatible printers'))
                                    ->rows(2)
                                    ->helperText(__('For toners and cartridges.')),
                            ]),

                        Section::make(__('Options and prices'))
                            ->description(__('Each option has its own price.'))
                            ->schema([
                                Repeater::make('variants')
                                    ->hiddenLabel()
                                    ->relationship()
                                    ->orderColumn('position')
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->addActionLabel(__('Add an option'))
                                    ->itemLabel(fn (array $state): ?string => $state['label'][app()->getLocale()] ?? $state['label']['fr'] ?? null)
                                    ->collapsible()
                                    ->columns(6)
                                    ->schema([
                                        TextInput::make('label.fr')->label(__('Option (French)'))->placeholder(__('e.g. 12U, Original'))->columnSpan(2),
                                        TextInput::make('label.en')->label(__('Option (English)'))->columnSpan(2),
                                        TextInput::make('sku')->label(__('Item code'))->columnSpan(2),
                                        TextInput::make('price_xof')->label(__('Price (FCFA)'))->numeric()->minValue(0)->suffix('FCFA')->columnSpan(3),
                                        Toggle::make('in_stock')->label(__('In stock'))->default(true)->inline(false)->columnSpan(3),
                                    ]),
                            ]),

                        Section::make(__('Photos'))
                            ->visible(fn () => config('store.photo_uploads'))
                            ->description(__('Photos are cropped square and get the GG-TECH watermark automatically. The first photo is the cover: drag the photos to change their order. Use “Add several photos” at the top of the page to upload many at once.'))
                            ->schema([
                                Repeater::make('images')
                                    ->hiddenLabel()
                                    ->relationship()
                                    ->orderColumn('position')
                                    ->defaultItems(0)
                                    ->addActionLabel(__('Add a photo'))
                                    ->grid(3)
                                    ->schema([
                                        FileUpload::make('original_path')
                                            ->hiddenLabel()
                                            ->image()
                                            ->disk(config('store.disks.originals'))
                                            ->directory('products/originals/uploads')
                                            ->visibility('private')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->maxSize(8192)
                                            ->required(),
                                    ]),
                            ]),
                    ]),

                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([
                        Section::make(__('Publishing'))->schema([
                            Select::make('status')
                                ->label(__('Status'))
                                ->options(ProductStatus::class)
                                ->default(ProductStatus::Draft)
                                ->required(),
                            Toggle::make('needs_review')
                                ->label(__('Needs review'))
                                ->helperText(__('Catalogue details to confirm (model, price, specifications).')),
                        ]),
                        Section::make(__('Organisation'))->schema([
                            Select::make('toner_type')
                                ->label(__('Toner type'))
                                ->options(TonerType::class)
                                ->placeholder(__('Not a toner'))
                                ->helperText(__('Moves the product to the matching toner category and shows the badge in the shop.'))
                                ->live()
                                ->afterStateUpdated(function (Set $set, TonerType|string|null $state) {
                                    $type = is_string($state) ? TonerType::tryFrom($state) : $state;

                                    if ($type) {
                                        $set('category_id', Category::query()->where('slug', $type->categorySlug())->value('id'));
                                    }
                                }),
                            Select::make('category_id')
                                ->label(__('Category'))
                                ->relationship('category', 'slug')
                                ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->translate('name'))
                                ->required()
                                ->preload(),
                            Select::make('brand_id')
                                ->label(__('Brand'))
                                ->relationship('brand', 'name')
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    TextInput::make('name')->label(__('Name'))->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                                    TextInput::make('slug')->required()->unique(Brand::class),
                                ]),
                        ]),
                        Section::make(__('Selling'))->schema([
                            Toggle::make('price_on_request')
                                ->label(__('Price on request'))
                                ->helperText(__('Shows “Ask for the price on WhatsApp” instead of a price.')),
                            Toggle::make('is_featured')
                                ->label(__('Premium selection'))
                                ->helperText(__('Featured on the homepage.')),
                            Toggle::make('is_heavy')
                                ->label(__('Heavy item'))
                                ->helperText(__('Delivery fee confirmed by the team.')),
                        ]),
                    ]),
            ]);
    }
}
