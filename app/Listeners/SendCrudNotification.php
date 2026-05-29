<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Notifications\CrudNotification;

class SendCrudNotification
{
    /**
     * Handle the UserCreated event.
     */
    public function handleCreated(UserCreated $event): void
    {
        // Notify the created user
        $event->user->notify(
            new CrudNotification(
                'created',
                'User',
                [
                    'name' => $event->user->name,
                    'email' => $event->user->email,
                ]
            )
        );

        // Notify admin/performer if different from the created user
        if ($event->performedBy && $event->performedBy->id !== $event->user->id) {
            $event->performedBy->notify(
                new CrudNotification(
                    'created',
                    'User',
                    [
                        'created_user' => $event->user->name,
                        'created_email' => $event->user->email,
                    ]
                )
            );
        }
    }

    /**
     * Handle the UserUpdated event.
     */
    public function handleUpdated(UserUpdated $event): void
    {
        // Notify the updated user
        $event->user->notify(
            new CrudNotification(
                'updated',
                'User',
                [
                    'changed_fields' => array_keys($event->changes),
                    'old_values' => $event->changes,
                ]
            )
        );

        // Notify admin/performer if different from the updated user
        if ($event->performedBy && $event->performedBy->id !== $event->user->id) {
            $event->performedBy->notify(
                new CrudNotification(
                    'updated',
                    'User',
                    [
                        'updated_user' => $event->user->name,
                        'changed_fields' => array_keys($event->changes),
                    ]
                )
            );
        }
    }

    /**
     * Handle the UserDeleted event.
     */
    public function handleDeleted(UserDeleted $event): void
    {
        // Notify admin/performer about the deletion
        if ($event->performedBy) {
            $event->performedBy->notify(
                new CrudNotification(
                    'deleted',
                    'User',
                    [
                        'deleted_user' => $event->user->name,
                        'deleted_email' => $event->user->email,
                        'user_id' => $event->user->id,
                    ]
                )
            );
        }
    }
}
