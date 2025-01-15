<?php

namespace App\Resources\TaskResource\Pages;

use App\Enums\RecurringType;
use App\Models\Shift;
use App\Resources\TaskResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label(__('Delete')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        if ($this->data['recurring']['type'] === RecurringType::ONETIME->value) {
            if (empty($this->data['soldier_type']) || (empty($this->data['soldier_id']) && $this->data['soldier_type'] != 'me')) {
                return;
            }
            $soldierId = $this->data['soldier_type'] === 'me' ? auth()->user()->userable_id : $this->data['soldier_id'];
            Shift::where('task_id', $this->data['id'])->update(['soldier_id' => $soldierId]);
        }
    }
}
