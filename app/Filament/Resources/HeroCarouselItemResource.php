<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HeroCarouselItemResource\Pages;
use App\Models\HeroCarouselItem;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class HeroCarouselItemResource extends Resource
{
    protected static ?string $model = HeroCarouselItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Homepage';

    protected static ?string $navigationLabel = 'Hero carousel';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Hero item')->schema([
                Grid::make(12)->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(8),
                    TextInput::make('sort_order')
                        ->label('Sort order')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->columnSpan(2),
                    Toggle::make('is_published')
                        ->label('Published')
                        ->default(true)
                        ->columnSpan(2),
                    TextInput::make('digital_object_slug')
                        ->label('Digital object slug')
                        ->required()
                        ->maxLength(255)
                        ->rules(fn (?HeroCarouselItem $record) => [
                            Rule::unique('hero_carousel_items', 'digital_object_slug')->ignore($record?->id),
                        ])
                        ->columnSpan(12),
                    CuratorPicker::make('cover_media_id')
                        ->label('Cover media')
                        ->required()
                        ->columnSpan(12),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('Order')->sortable(),
                TextColumn::make('title')->label('Title')->searchable()->sortable(),
                TextColumn::make('digital_object_slug')->label('Digital object')->searchable(),
                IconColumn::make('cover_media_id')->label('Media')->boolean()->trueIcon('heroicon-o-photo')->falseIcon('heroicon-o-x-mark'),
                ToggleColumn::make('is_published')->label('Published'),
                TextColumn::make('updated_at')->label('Updated')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHeroCarouselItems::route('/'),
            'create' => Pages\CreateHeroCarouselItem::route('/create'),
            'edit' => Pages\EditHeroCarouselItem::route('/{record}/edit'),
        ];
    }
}
