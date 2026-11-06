<?php

namespace App\Filament\Pages;

use App\Models\TeamOverviewTemplate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Edits the BBCode of the ATC-TD team overview forum post. Admin-only: superusers are excluded.
 *
 * @property-read Schema $form
 */
class TeamOverviewTemplatePage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'ATD Team Overview';

    protected static string|\UnitEnum|null $navigationGroup = 'System & Administration';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'ATD Team Overview Template';

    protected static ?string $slug = 'atd-team-overview-template';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->isAdmin() === true;
    }

    public function mount(): void
    {
        $this->form->fill(['content' => TeamOverviewTemplate::currentContent()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('content')
                    ->label('BBCode')
                    ->helperText('Posted to the forum daily at 06:00. The placeholders '.implode(', ', TeamOverviewTemplate::PLACEHOLDERS).' are replaced with the generated lists and must stay inside their [CODE] blocks.')
                    ->required()
                    ->rows(40)
                    ->extraInputAttributes(['class' => 'font-mono'])
                    ->rules([
                        fn () => function (string $attribute, mixed $value, \Closure $fail) {
                            $missing = array_filter(TeamOverviewTemplate::PLACEHOLDERS, fn ($placeholder) => ! str_contains((string) $value, $placeholder));

                            if ($missing) {
                                $fail('The template is missing: '.implode(', ', $missing));
                            }
                        },
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Save')
                            ->submit('save'),
                        Action::make('reset')
                            ->label('Reset to default')
                            ->color('gray')
                            ->requiresConfirmation()
                            ->action(fn () => $this->form->fill(['content' => TeamOverviewTemplate::defaultContent()])),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $content = $this->form->getState()['content'];

        $template = TeamOverviewTemplate::query()->first() ?? new TeamOverviewTemplate;
        $template->content = $content;
        $template->save();

        Notification::make()->title('Template saved')->success()->send();
    }
}
