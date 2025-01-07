<?php

namespace App\Filament\Notifications;

use App\Filament\Notifications\Concerns\HasCommonKey;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class MyNotification extends Notification
{
    use HasCommonKey;

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'commonKey' => $this->getCommonKey(),
            'actions' => array_map(fn (Action|ActionGroup $action): array => $action->toArray(), $this->getActions()),
            'body' => $this->getBody(),
            'color' => $this->getColor(),
            'duration' => $this->getDuration(),
            'icon' => $this->getIcon(),
            'iconColor' => $this->getIconColor(),
            'status' => $this->getStatus(),
            'title' => $this->getTitle(),
            'view' => $this->getView(),
            'viewData' => $this->getViewData(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): static
    {
        $static = static::make($data['id'] ?? Str::random());

        if (
            ($static::class !== self::class) &&
            (get_called_class() === self::class)
        ) {
            return $static::fromArray($data);
        }

        $static->actions(
            array_map(
                fn (array $action): Action|ActionGroup => match (array_key_exists('actions', $action)) {
                    true => ActionGroup::fromArray($action),
                    false => Action::fromArray($action),
                },
                $data['actions'] ?? [],
            ),
        );

        $view = $data['view'] ?? null;

        if (filled($view) && ($static->getView() !== $view) && $static->isViewSafe($view)) {
            $static->view($data['view']);
        }
        $static->viewData($data['viewData'] ?? []);
        $static->commonKey($data['commonKey'] ?? null);
        $static->body($data['body'] ?? null);
        $static->color($data['color'] ?? null);
        $static->duration($data['duration'] ?? $static->getDuration());
        $static->status($data['status'] ?? $static->getStatus());
        $static->icon($data['icon'] ?? $static->getIcon());
        $static->iconColor($data['iconColor'] ?? $static->getIconColor());
        $static->title($data['title'] ?? null);

        return $static;
    }
}
